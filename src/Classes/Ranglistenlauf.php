<?php

declare(strict_types=1);

namespace Schachbulle\ContaoTopwertungszahlenBundle\Classes;

use Schachbulle\ContaoTopwertungszahlenBundle\Helper\Bilder;
use Schachbulle\ContaoWertungsportalBundle\Helper\Ranglisten;

/**
 * Erzeugt die zwanzig Ranglisten des Deutschen Schachbundes.
 *
 * Nachfolger von `src/Resources/public/Rangliste.php`. Die alte Datei war ein
 * unmittelbar aufgerufenes Skript, das sich Contao über
 * `system/initialize.php` selbst hochfuhr — die Datei gibt es in Contao 5
 * nicht mehr. Die Logik liegt deshalb jetzt in einer gewöhnlichen Klasse, die
 * von einer Backend-Route (@see \Schachbulle\ContaoTopwertungszahlenBundle\Controller\RanglistenlaufController)
 * und von einem Console-Command (@see \Schachbulle\ContaoTopwertungszahlenBundle\Command\RanglistenlaufCommand)
 * benutzt wird.
 *
 * Die Beschaffung der Spielerdaten kommt vollständig aus dem
 * contao-wertungsportal-bundle (`Helper\Ranglisten`, ab dessen Version
 * 1.34.0). Weggefallen sind damit: der SOAP-Aufbau zu DeWIS, `bestOfFederation()`,
 * der Einzelabruf `tournamentCardForId()` je Spieler (zweimal: einmal für die
 * Nation, einmal für den Landesverband), die Namenssuche `searchByName()` für
 * die Elo-Zuordnung und die Berechnung der geteilten Platzziffern.
 *
 * Ein Lauf ist in zwanzig Schritte zerlegt — einen je Liste. Auf der
 * Kommandozeile laufen sie in einem Rutsch durch (@see lauf()); die
 * Importmaske ruft sie einzeln auf und zeigt dazwischen den Fortschritt an
 * (@see schrittLaufen()). Die Zwischenergebnisse liegen solange als
 * Bruchstücke unter SCHRITTORDNER und werden am Ende zu den beiden
 * Ausgabedateien zusammengesetzt (@see dateienSchreiben()).
 */
class Ranglistenlauf
{
	/**
	 * Wie viele Plätze je Liste beschafft und in die Auswertungstabelle
	 * geschrieben werden. Die Zahl stammt aus dem alten Skript und bestimmt,
	 * wie tief der Verlauf später auswertbar ist.
	 */
	public const TOP = 50;

	/**
	 * Wie viele Plätze in die Ausgabedateien wandern. Die Anzeige auf der
	 * Webseite zeigt die besten Zehn; bei geteilten Platzziffern können das
	 * mehr als zehn Spieler sein.
	 */
	public const AUSGABE = 10;

	/**
	 * Ablageort der fertigen Dateien, relativ zum Projektverzeichnis.
	 *
	 * Bis Version 1.6.3 lagen sie in `web/bundles/contaotopwertungszahlen/`.
	 * Das ist ein Symlink in das vendor-Verzeichnis — die Dateien waren nach
	 * jedem `composer install` weg. Unterhalb von `files/` überstehen sie
	 * Aktualisierungen und laufen in die Sicherung mit.
	 */
	public const AUSGABEORDNER = 'files/topwertungszahlen';

	/**
	 * Ablageort der Bruchstücke zwischen den Schritten, relativ zum
	 * Projektverzeichnis.
	 *
	 * Bewußt unterhalb von `var/` und nicht unter `files/`: Ein halbfertiger
	 * Stand darf nicht über das Web erreichbar sein. Die Bruchstücke werden
	 * beim ersten Schritt eines Laufs und nach dem Schreiben der Dateien
	 * gelöscht.
	 */
	public const SCHRITTORDNER = 'var/topwertungszahlen';

	/**
	 * Absoluter Pfad zum Projektverzeichnis der Contao-Installation.
	 *
	 * @var string
	 */
	private $projektverzeichnis;

	/**
	 * Rückmeldung über den Fortschritt, oder null für einen stillen Lauf.
	 *
	 * @var callable|null
	 */
	private $melder;

	/**
	 * Zwischenspeicher für die Zuordnung PKZ zu Datensatz-ID in
	 * tl_topwertungszahlen. Innerhalb eines Laufs taucht derselbe Spieler in
	 * bis zu zwanzig Listen auf; ohne diesen Speicher würde jede Liste die
	 * Zuordnung neu ermitteln.
	 *
	 * @var array<string,int>
	 */
	private $spielerCache = [];

	/**
	 * Nimmt das Projektverzeichnis entgegen.
	 *
	 * Der Pfad kommt vom Container (`%kernel.project_dir%`) und nicht aus
	 * `TL_ROOT` oder `$_SERVER['DOCUMENT_ROOT']`: Die erste Konstante ist in
	 * Contao 5 nicht mehr gesetzt, die zweite zeigt bei der üblichen
	 * Contao-Verzeichnisstruktur ins Web-Verzeichnis und nicht auf die
	 * Installation.
	 *
	 * @param string $projektverzeichnis Absoluter Pfad ohne abschließenden Trenner
	 */
	public function __construct(string $projektverzeichnis)
	{
		$this->projektverzeichnis = rtrim($projektverzeichnis, '/\\');
	}

	/**
	 * Führt einen vollständigen Lauf aus: zwanzig Listen, zwei Dateien.
	 *
	 * Die Reihenfolge ist dieselbe wie früher — erst die zehn DWZ-Listen,
	 * dann die zehn Elo-Listen —, damit die Meldungen auf dem Bildschirm
	 * vergleichbar bleiben.
	 *
	 * Seiteneffekte: schreibt in tl_topwertungszahlen (neue Spieler) und
	 * tl_topwertungszahlen_ratings (Platzierungen des Tages) und legt die
	 * beiden Ausgabedateien an. Ein zweiter Lauf am selben Tag ändert die
	 * bereits geschriebenen Platzierungen nicht — so verhielt sich das alte
	 * Skript auch.
	 *
	 * @param callable|null $melder Wird für jede fertige Liste mit einer
	 *                              Meldung als einzigem Parameter aufgerufen.
	 *                              null unterdrückt jede Ausgabe
	 *
	 * @return array{listen:array,dateien:array<string,string>,dauer:float,quelle:array<string,string>,neu:int,zugeordnet:int}
	 *               Bericht über den Lauf. `neu` zählt die angelegten Spieler,
	 *               `zugeordnet` die über den Namen nachgetragenen PKZ
	 */
	public function lauf(?callable $melder = null): array
	{
		$this->melder = $melder;

		$startzeit = microtime(true);
		$neu = 0;
		$zugeordnet = 0;

		$bericht = [
			'listen'     => [],
			'dateien'    => [],
			'dauer'      => 0.0,
			'quelle'     => [],
			'neu'        => 0,
			'zugeordnet' => 0,
		];

		foreach (self::schritte() as $nummer => $schritt) {
			$ergebnis = $this->schrittLaufen($nummer);

			$bericht['listen'][$schritt['liste']] = $ergebnis['beschafft'];
			$bericht['quelle'][$schritt['liste']] = $ergebnis['quelle'];
			$neu += $ergebnis['neu'];
			$zugeordnet += $ergebnis['zugeordnet'];

			$this->melden($ergebnis['meldung']);
		}

		$abschluss = $this->dateienSchreiben();

		$bericht['dateien'] = $abschluss['dateien'];
		$bericht['neu'] = $neu;
		$bericht['zugeordnet'] = $zugeordnet;
		$bericht['dauer'] = round(microtime(true) - $startzeit, 3);

		$this->melden(sprintf(
			'Fertig in %s Sekunden. %d Spieler neu angelegt, %d über den Namen zugeordnet.',
			str_replace('.', ',', (string) $bericht['dauer']),
			$neu,
			$zugeordnet
		));

		return $bericht;
	}

	/**
	 * Gibt die zwanzig Schritte eines Laufs in ihrer festen Reihenfolge zurück.
	 *
	 * Erst die zehn DWZ-Listen, dann die zehn Elo-Listen — dieselbe Folge wie
	 * seit jeher, damit die Meldungen auf dem Bildschirm vergleichbar bleiben.
	 *
	 * Die Reihenfolge muß stabil sein: Die Importmaske spricht die Schritte
	 * über ihre Nummer an, und die Bruchstücke im Zwischenverzeichnis werden
	 * in dieser Folge zusammengesetzt.
	 *
	 * @return array<int,array{art:string,schluessel:string,liste:string,name:string}>
	 *               Nullbasiert durchnumeriert. `liste` ist der Schlüssel in
	 *               der Auswertungstabelle ('dwz_u20w'), `name` die Beschriftung
	 *               für die Anzeige ('DWZ – U20 weiblich')
	 */
	public static function schritte(): array
	{
		$schritte = [];

		foreach (['dwz' => 'DWZ', 'elo' => 'Elo'] as $art => $beschriftung) {
			foreach (Ranglisten::listentypen() as $schluessel => $typ) {
				$schritte[] = [
					'art'        => $art,
					'schluessel' => (string) $schluessel,
					'liste'      => $art.'_'.$schluessel,
					'name'       => $beschriftung.' – '.($typ['name'] ?? $schluessel),
				];
			}
		}

		return $schritte;
	}

	/**
	 * Führt einen einzelnen Schritt aus: eine Liste beschaffen, speichern,
	 * das Bruchstück für die Ausgabedatei ablegen.
	 *
	 * Ein Lauf ist in zwanzig Schritte zerlegt, damit die Importmaske einen
	 * Fortschritt anzeigen kann. Der Weg über gestreamte Ausgabe wäre
	 * eleganter, aber unzuverlässig: Zwischenspeicher in PHP, im Webserver und
	 * im Vorschaltserver halten die Teilausgabe zurück, und die Anzeige stünde
	 * wieder bis zum Schluß still.
	 *
	 * Beim ersten Schritt wird das Zwischenverzeichnis geleert, damit
	 * Bruchstücke eines abgebrochenen Laufs nicht in die neuen Dateien geraten.
	 *
	 * @param int $nummer Nullbasierte Schrittnummer aus schritte()
	 *
	 * @return array{liste:string,name:string,beschafft:int,ausgabe:int,quelle:string,neu:int,zugeordnet:int,meldung:string}
	 *               Was der Schritt bewirkt hat. `beschafft` ist die Anzahl der
	 *               gelieferten Plätze, `ausgabe` die davon in die Datei
	 *               übernommenen
	 *
	 * @throws \OutOfRangeException wenn es den Schritt nicht gibt
	 */
	public function schrittLaufen(int $nummer): array
	{
		$schritte = self::schritte();

		if (!isset($schritte[$nummer])) {
			throw new \OutOfRangeException('Schritt '.$nummer.' gibt es nicht.');
		}

		if (0 === $nummer) {
			$this->zwischenverzeichnisLeeren();
		}

		$schritt = $schritte[$nummer];
		$typen = Ranglisten::listentypen();
		$params = $typen[$schritt['schluessel']]['params'];

		$params['limit'] = self::TOP;

		// Die Nationsprüfung liegt vollständig im Wertungsportal-Bundle: Es
		// fragt ab 1.35.0 zuerst die FIDE-Föderation, dann die Nation der
		// Mitgliederdatei, und behält nur, wovon beides unbekannt ist. Die
		// Reihenfolge ist wesentlich — Georg Meier ist laut Mitgliederdatei
		// Deutscher, spielt aber für Uruguay und gehört deshalb in keine
		// deutsche Liste.
		//
		// Bis 1.7.3 prüfte diese Klasse selbst nach, weil das Bundle damals
		// nur die Mitgliederdatei heranzog und ein leerer Wert dort als
		// deutsch galt. Die Nachprüfung ist mit 1.7.4 entfallen; sie wäre
		// nicht nur doppelt, sondern auch strenger gewesen als die Staffel
		// und hätte Spieler mit deutscher Föderation verworfen, deren
		// Mitgliederdatei etwas anderes sagt.
		$ergebnis = 'dwz' === $schritt['art']
			? Ranglisten::dwz($params)
			: Ranglisten::elo($params);

		$zeilen = $ergebnis['liste'] ?? [];
		$quelle = (string) ($ergebnis['quelle'] ?? '');

		$zaehler = ['neu' => 0, 'zugeordnet' => 0];
		$ausgabe = $this->listeSpeichern($schritt['art'], $schritt['liste'], $zeilen, date('Ymd'), $zaehler);

		$this->bruchstueckSchreiben($schritt['liste'], $ausgabe);

		$meldung = sprintf(
			'%s: %d Plätze, %d in der Ausgabe (Quelle: %s)',
			$schritt['liste'],
			\count($zeilen),
			\count($ausgabe),
			$quelle ?: 'unbekannt'
		);

		return [
			'liste'      => $schritt['liste'],
			'name'       => $schritt['name'],
			'beschafft'  => \count($zeilen),
			'ausgabe'    => \count($ausgabe),
			'quelle'     => $quelle,
			'neu'        => $zaehler['neu'],
			'zugeordnet' => $zaehler['zugeordnet'],
			'meldung'    => $meldung,
		];
	}

	/**
	 * Setzt die Bruchstücke aller Schritte zu den beiden Ausgabedateien
	 * zusammen und räumt das Zwischenverzeichnis auf.
	 *
	 * Fehlt das Bruchstück eines Schritts — weil er nie lief oder abbrach —,
	 * steht die betreffende Liste in der Datei mit einem leeren Feld. So bleibt
	 * der Aufbau der Datei verläßlich, und dem Leser fällt auf, was fehlt.
	 *
	 * @return array{dateien:array<string,string>,fehlend:array<int,string>,meldung:string}
	 *               `dateien` nennt die geschriebenen Pfade, `fehlend` die
	 *               Listen ohne Bruchstück
	 */
	public function dateienSchreiben(): array
	{
		$daten = ['dwz' => [], 'elo' => []];
		$fehlend = [];

		foreach (self::schritte() as $schritt) {
			$bruchstueck = $this->bruchstueckLesen($schritt['liste']);

			if (null === $bruchstueck) {
				$fehlend[] = $schritt['liste'];
				$bruchstueck = [];
			}

			$daten[$schritt['art']][$schritt['liste']] = $bruchstueck;
		}

		$dateien = [];

		foreach ($daten as $art => $inhalt) {
			$dateien[$art] = $this->dateiSchreiben($art.'.json', $inhalt);
		}

		$this->zwischenverzeichnisLeeren();

		$meldung = 'Dateien geschrieben: '.implode(', ', $dateien);

		if ([] !== $fehlend) {
			$meldung .= ' – ohne Daten geblieben: '.implode(', ', $fehlend);
		}

		return ['dateien' => $dateien, 'fehlend' => $fehlend, 'meldung' => $meldung];
	}

	/**
	 * Frischt allein die Fotos in den beiden Ausgabedateien auf.
	 *
	 * Gedacht für den häufigsten Pflegefall: Zu einem Spieler der Top-10 wird
	 * ein Foto nachgetragen, und das soll auf der Webseite erscheinen, ohne
	 * daß die Ranglisten neu beschafft werden. Ein vollständiger Lauf wäre
	 * dafür nicht nur unnötig aufwendig, er würde die Platzierungen des Tages
	 * auch gar nicht mehr anfassen — die stehen bereits in der
	 * Auswertungstabelle und bleiben dort unverändert.
	 *
	 * Gearbeitet wird auf den fertigen Dateien: Jede Zeile trägt die PKZ, über
	 * sie wird der Spieler gefunden und sein jüngstes Foto nachgeschlagen.
	 * Weder das Wertungsportal noch die Auswertungstabelle werden dabei
	 * angefaßt, und es entsteht kein neuer Spielerdatensatz.
	 *
	 * Maßgeblich ist das heutige Datum und nicht das Datum der Liste: Gesucht
	 * ist das aktuellste Foto, denn genau deshalb wird aufgefrischt.
	 *
	 * @return array{dateien:array<string,string>,geprueft:int,geaendert:int,fehlend:array<int,string>,meldung:string}
	 *               `geprueft` zählt alle durchgesehenen Zeilen, `geaendert`
	 *               die mit neuem Foto. `fehlend` nennt Dateien, die es noch
	 *               nicht gibt — dann muß erst ein vollständiger Lauf laufen
	 */
	public function fotosAktualisieren(): array
	{
		$listendatum = date('Ymd');
		$dateien = [];
		$fehlend = [];
		$geprueft = 0;
		$geaendert = 0;

		// Ein Spieler steht in mehreren Listen; sein Foto wird trotzdem nur
		// einmal nachgeschlagen
		$fotos = [];

		foreach (['dwz', 'elo'] as $art) {
			$pfad = $this->projektverzeichnis.'/'.self::AUSGABEORDNER.'/'.$art.'.json';

			if (!is_file($pfad)) {
				$fehlend[] = $art.'.json';

				continue;
			}

			$daten = json_decode((string) file_get_contents($pfad), true);

			if (!\is_array($daten)) {
				$fehlend[] = $art.'.json';

				continue;
			}

			foreach ($daten as $liste => $zeilen) {
				if (!\is_array($zeilen)) {
					continue;
				}

				foreach ($zeilen as $i => $zeile) {
					++$geprueft;

					$pkz = $this->pkzAusZeile($zeile);

					if ('' === $pkz) {
						continue;
					}

					if (!isset($fotos[$pkz])) {
						$id = $this->spielerIdZuPkz($pkz);
						$fotos[$pkz] = $id > 0
							? $this->spielerfoto($id, $listendatum)
							: ['pfad' => '', 'quelle' => ''];
					}

					$foto = $fotos[$pkz];

					if ((string) ($zeile['foto'] ?? '') === $foto['pfad'] && (string) ($zeile['quelle'] ?? '') === $foto['quelle']) {
						continue;
					}

					$daten[$liste][$i]['foto'] = $foto['pfad'];
					$daten[$liste][$i]['quelle'] = $foto['quelle'];
					++$geaendert;
				}
			}

			$dateien[$art] = $this->dateiSchreiben($art.'.json', $daten);
		}

		$meldung = sprintf('%d Einträge durchgesehen, %d Fotos geändert.', $geprueft, $geaendert);

		if ([] !== $dateien) {
			$meldung .= ' Geschrieben: '.implode(', ', $dateien).'.';
		}

		if ([] !== $fehlend) {
			$meldung .= ' Nicht vorhanden: '.implode(', ', $fehlend)
				.' – dafür muß erst ein vollständiger Lauf laufen.';
		}

		return [
			'dateien'  => $dateien,
			'geprueft' => $geprueft,
			'geaendert' => $geaendert,
			'fehlend'  => $fehlend,
			'meldung'  => $meldung,
		];
	}

	/**
	 * Holt die PKZ aus einer Zeile einer Ausgabedatei.
	 *
	 * Seit 1.8.0 steht sie als eigenes Feld darin. Ältere Dateien kennen das
	 * Feld nicht; dort wird sie aus dem Spielerlink zurückgewonnen
	 * (`spieler/10285765.html`). Ohne diesen Rückfall könnte die erste
	 * Auffrischung nach der Aktualisierung nichts ausrichten, weil die
	 * vorhandenen Dateien noch aus der Zeit davor stammen.
	 *
	 * @param array $zeile Eine Zeile aus dwz.json oder elo.json
	 *
	 * @return string Die PKZ, oder ein leerer String bei Elo-Zeilen ohne
	 *                zugeordnete Person und bei Links anderer Form
	 */
	private function pkzAusZeile(array $zeile): string
	{
		$pkz = trim((string) ($zeile['pkz'] ?? ''));

		if ('' !== $pkz) {
			return $pkz;
		}

		if (preg_match('#(?:^|/)spieler/(\d+)\.html$#', (string) ($zeile['link'] ?? ''), $treffer)) {
			return $treffer[1];
		}

		return '';
	}

	/**
	 * Sucht einen Spieler allein über seine PKZ.
	 *
	 * Anders als spielerId() legt diese Methode **nichts** an und schreibt
	 * nichts nach. Beim Auffrischen der Fotos darf kein Spielerdatensatz
	 * entstehen: Die Ranglisten sind da, es geht nur um das Bild.
	 *
	 * @param string $pkz Personenkennziffer aus dem Wertungsportal
	 *
	 * @return int Datensatz-ID in tl_topwertungszahlen, oder 0 wenn der
	 *             Spieler dort nicht geführt wird
	 */
	private function spielerIdZuPkz(string $pkz): int
	{
		$treffer = \Database::getInstance()
			->prepare('SELECT id FROM tl_topwertungszahlen WHERE pkz=?')
			->limit(1)
			->execute($pkz)
		;

		return $treffer->numRows ? (int) $treffer->id : 0;
	}

	/**
	 * Schreibt eine einzelne Liste in die Auswertungstabelle und baut ihre
	 * Ausgabezeilen.
	 *
	 * @param string               $art         'dwz' oder 'elo' — bestimmt, welche
	 *                                          Wertungszahl und welcher Link
	 *                                          verwendet werden
	 * @param string               $liste       Listenschlüssel, etwa 'dwz_u20w'
	 * @param array<int,array>     $zeilen      Zeilen aus Ranglisten::dwz()/elo()
	 * @param string               $listendatum Datum als JJJJMMTT
	 * @param array                $zaehler     Wird für die Schlüssel `neu` und
	 *                                          `zugeordnet` fortgeschrieben
	 *
	 * @return array<int,array> Ausgabezeilen für die JSON-Datei, höchstens die
	 *                          besten AUSGABE Plätze. Leer, wenn die Liste
	 *                          nichts hergab
	 */
	private function listeSpeichern(string $art, string $liste, array $zeilen, string $listendatum, array &$zaehler): array
	{
		$ausgabe = [];

		foreach ($zeilen as $zeile) {
			$id = $this->spielerId($zeile, $zaehler);

			// Ohne Personenbezug kein Datensatz: Bei den Elo-Listen kann eine
			// Zeile zu einem Spieler gehören, den die Mitgliederdatei nicht
			// kennt. Die Zeile bleibt trotzdem in der Ausgabe — nur eben ohne
			// Verlauf und ohne Foto
			if ($id > 0) {
				$this->platzierungSpeichern($art, $id, $liste, $listendatum, $zeile);
			}

			if ((int) $zeile['platz'] > self::AUSGABE) {
				continue;
			}

			$foto = $id > 0 ? $this->spielerfoto($id, $listendatum) : ['pfad' => '', 'quelle' => ''];

			$ausgabe[] = [
				'rang'     => (int) $zeile['platz'],
				// Die PKZ steht seit 1.8.0 mit in der Datei. Sie wird für die
				// Anzeige nicht gebraucht, macht aber das Auffrischen der
				// Fotos möglich, ohne die Wertungszahlen neu zu beschaffen
				// (@see fotosAktualisieren()). Bei Elo-Zeilen ohne zugeordnete
				// Person bleibt sie leer
				'pkz'      => (string) $zeile['pkz'],
				'vorname'  => (string) $zeile['vorname'],
				'nachname' => (string) $zeile['nachname'],
				'verband'  => (string) $zeile['verbandskuerzel'],
				'link'     => 'dwz' === $art
					? 'spieler/'.$zeile['pkz'].'.html'
					: 'https://ratings.fide.com/profile/'.$zeile['fide_id'],
				'rating'   => 'dwz' === $art ? (int) $zeile['dwz'] : (int) $zeile['elo'],
				'title'    => $this->fideTitel((string) $zeile['fide_titel'], (string) $zeile['fide_titel_w']),
				'foto'     => $foto['pfad'],
				'quelle'   => $foto['quelle'],
			];
		}

		return $ausgabe;
	}

	/**
	 * Trägt eine Platzierung in tl_topwertungszahlen_ratings ein.
	 *
	 * Ein vorhandener Eintrag für dieselbe Person, dieselbe Liste und dasselbe
	 * Datum wird NICHT überschrieben. Das war im alten Skript schon so und ist
	 * Absicht: Ein zweiter Lauf am selben Tag soll die einmal festgehaltene
	 * Rangliste nicht verändern.
	 *
	 * @param string $art         'dwz' oder 'elo'
	 * @param int    $id          Datensatz-ID in tl_topwertungszahlen
	 * @param string $liste       Listenschlüssel, etwa 'dwz_u20w'
	 * @param string $listendatum Datum als JJJJMMTT
	 * @param array  $zeile       Zeile aus Ranglisten::dwz()/elo()
	 */
	private function platzierungSpeichern(string $art, int $id, string $liste, string $listendatum, array $zeile): void
	{
		$vorhanden = \Database::getInstance()
			->prepare('SELECT id FROM tl_topwertungszahlen_ratings WHERE pid=? AND type=? AND date=?')
			->limit(1)
			->execute($id, $liste, $listendatum)
		;

		if ($vorhanden->numRows) {
			return;
		}

		// Die Spalte `association` faßt ein einziges Zeichen: Sie führt die
		// erste Stelle der Vereinskennziffer, aus der sich der Landesverband
		// ergibt. Das Kürzel selbst („BER") steht in der Ausgabedatei
		$set = [
			'pid'          => $id,
			'tstamp'       => time(),
			'type'         => $liste,
			'date'         => $listendatum,
			'rank'         => (int) $zeile['platz'],
			'rating'       => 'dwz' === $art ? (int) $zeile['dwz'] : (int) $zeile['elo'],
			// `rating_info` bleibt leer. Das Feld ist im Backend frei
			// beschriftbar („Info"); der Wertungsindex gehörte dort nicht
			// hinein, auch wenn er jetzt vorläge
			'rating_info'  => '',
			'rating_id'    => 'dwz' === $art ? '' : (string) $zeile['fide_id'],
			'fide_title'   => (string) $zeile['fide_titel'],
			'fide_title_w' => (string) $zeile['fide_titel_w'],
			'association'  => substr((string) $zeile['vkz'], 0, 1),
			'published'    => 1,
		];

		\Database::getInstance()
			->prepare('INSERT INTO tl_topwertungszahlen_ratings %s')
			->set($set)
			->execute()
		;
	}

	/**
	 * Ermittelt die Datensatz-ID eines Spielers in tl_topwertungszahlen und
	 * legt ihn an, wenn er dort noch fehlt.
	 *
	 * Hier liegt der heikelste Punkt der Umstellung. Die Tabelle führt ihre
	 * Spieler seit jeher über `dewis_id` — die Personenkennziffer der
	 * abgeschalteten DeWIS-Schnittstelle. Das Wertungsportal liefert die PKZ
	 * (`nuLigaPersonId`). Ob das dieselbe Nummer ist, hängt vom Bestand ab und
	 * läßt sich nur an der laufenden Installation feststellen; dafür gibt es
	 * `tools/pruefe-spielerkennung.php`.
	 *
	 * Damit der erste Lauf in JEDEM Fall unfallfrei bleibt, wird in drei
	 * Stufen gesucht:
	 *
	 *  1. über das neue Feld `pkz` — greift ab dem zweiten Lauf immer,
	 *  2. über `dewis_id` — greift, wenn beide Nummernkreise identisch sind,
	 *  3. über Nachname, Vorname und, wenn beidseits bekannt, das Geburtsjahr.
	 *
	 * Wird ein Spieler über Stufe 2 oder 3 gefunden, wird seine PKZ
	 * nachgetragen; der nächste Lauf findet ihn dann sofort. Erst wenn alle
	 * drei Stufen leer ausgehen, entsteht ein neuer Datensatz. So reißt weder
	 * der Ranglistenverlauf ab, noch verliert ein Spieler sein Foto.
	 *
	 * Bei Namensgleichheit (Stufe 3 liefert mehr als einen Treffer) wird
	 * bewußt NICHT geraten, sondern ein neuer Datensatz angelegt. Ein falsch
	 * zugeordnetes Foto ist schwerer zu bemerken als ein doppelter Spieler.
	 *
	 * @param array $zeile   Zeile aus Ranglisten::dwz()/elo()
	 * @param array $zaehler Wird für die Schlüssel `neu` und `zugeordnet` fortgeschrieben
	 *
	 * @return int Datensatz-ID, oder 0 wenn die Zeile keine PKZ trägt (bei
	 *             Elo-Zeilen ohne zugeordnete Person möglich)
	 */
	private function spielerId(array $zeile, array &$zaehler): int
	{
		$pkz = trim((string) ($zeile['pkz'] ?? ''));

		if ('' === $pkz) {
			return 0;
		}

		if (isset($this->spielerCache[$pkz])) {
			return $this->spielerCache[$pkz];
		}

		$db = \Database::getInstance();

		// Stufe 1: über die PKZ
		$treffer = $db->prepare('SELECT id FROM tl_topwertungszahlen WHERE pkz=?')
			->limit(1)
			->execute($pkz)
		;

		if ($treffer->numRows) {
			return $this->spielerCache[$pkz] = (int) $treffer->id;
		}

		// Stufe 2: über die alte DeWIS-Kennung, falls es dieselbe Nummer ist
		if (ctype_digit($pkz)) {
			$treffer = $db->prepare('SELECT id FROM tl_topwertungszahlen WHERE dewis_id=?')
				->limit(1)
				->execute($pkz)
			;

			if ($treffer->numRows) {
				$this->pkzNachtragen((int) $treffer->id, $pkz);
				++$zaehler['zugeordnet'];

				return $this->spielerCache[$pkz] = (int) $treffer->id;
			}
		}

		// Stufe 3: über den Namen
		$id = $this->spielerUeberNamen($zeile);

		if ($id > 0) {
			$this->pkzNachtragen($id, $pkz);
			++$zaehler['zugeordnet'];

			return $this->spielerCache[$pkz] = $id;
		}

		// Neuer Spieler
		$objInsert = $db->prepare('INSERT INTO tl_topwertungszahlen %s')
			->set([
				'tstamp'     => time(),
				'vorname'    => (string) $zeile['vorname'],
				'nachname'   => (string) $zeile['nachname'],
				'titel'      => substr(trim((string) $zeile['titel']), 0, 3),
				'geschlecht' => $this->geschlecht((string) $zeile['geschlecht']),
				'dewis_id'   => ctype_digit($pkz) ? (int) $pkz : 0,
				'pkz'        => $pkz,
				'published'  => 1,
			])
			->execute()
		;

		++$zaehler['neu'];

		return $this->spielerCache[$pkz] = (int) $objInsert->insertId;
	}

	/**
	 * Sucht einen vorhandenen Spieler über seinen Namen.
	 *
	 * Der Vergleich läuft über eine vereinfachte Namensform (Kleinbuchstaben,
	 * Umlaute aufgelöst, alles andere entfernt), weil in den Altbeständen
	 * „Müller" und „Mueller" nebeneinander stehen. Deshalb kann er nicht in
	 * SQL stattfinden; geladen werden nur die Sätze mit passendem
	 * Anfangsbuchstaben des Nachnamens.
	 *
	 * @param array $zeile Zeile aus Ranglisten::dwz()/elo()
	 *
	 * @return int Datensatz-ID bei genau einem Treffer, sonst 0 — auch bei
	 *             mehreren Treffern, siehe spielerId()
	 */
	private function spielerUeberNamen(array $zeile): int
	{
		$nachname = $this->namensform((string) $zeile['nachname']);
		$vorname = $this->namensform((string) $zeile['vorname']);

		if ('' === $nachname) {
			return 0;
		}

		$jahr = (int) ($zeile['geburtsjahr'] ?? 0);

		// Nur Sätze ohne PKZ kommen in Frage: Ein Satz mit PKZ gehört bereits
		// einer anderen Person, sonst hätte Stufe 1 ihn gefunden
		$kandidaten = \Database::getInstance()
			->prepare("SELECT id, vorname, nachname, geburtstag FROM tl_topwertungszahlen WHERE pkz='' AND nachname LIKE ?")
			->execute(mb_substr((string) $zeile['nachname'], 0, 1).'%')
		;

		$gefunden = [];

		while ($kandidaten->next()) {
			if ($this->namensform((string) $kandidaten->nachname) !== $nachname) {
				continue;
			}

			if ($this->namensform((string) $kandidaten->vorname) !== $vorname) {
				continue;
			}

			// Das Geburtsjahr entscheidet nur mit, wenn es beidseits bekannt
			// ist. Bei den automatisch angelegten Sätzen ist es nie gefüllt
			$kandidatenjahr = $this->geburtsjahr((string) $kandidaten->geburtstag);

			if ($jahr > 0 && $kandidatenjahr > 0 && $jahr !== $kandidatenjahr) {
				continue;
			}

			$gefunden[] = (int) $kandidaten->id;
		}

		return 1 === \count($gefunden) ? $gefunden[0] : 0;
	}

	/**
	 * Trägt die PKZ in einen vorhandenen Spielersatz nach.
	 *
	 * @param int    $id  Datensatz-ID in tl_topwertungszahlen
	 * @param string $pkz Personenkennziffer aus dem Wertungsportal
	 */
	private function pkzNachtragen(int $id, string $pkz): void
	{
		\Database::getInstance()
			->prepare('UPDATE tl_topwertungszahlen SET pkz=?, tstamp=? WHERE id=?')
			->execute($pkz, time(), $id)
		;
	}

	/**
	 * Ermittelt zu einem Spieler das jüngste Foto und dessen Quellenangabe.
	 *
	 * Gesucht wird das neueste Foto, dessen Datum nicht nach dem Listendatum
	 * liegt — so zeigt eine alte Rangliste das Bild, das damals gültig war.
	 *
	 * Die Quellenangabe kommt aus der Spalte `source` der Fototabelle und
	 * nicht aus den Metadaten der Datei: Der Versuch, sie aus der
	 * Bildunterschrift zu lesen, stand seit jeher als toter Code in der
	 * Vorgängerdatei und funktionierte nicht, weil dort die Sprache „en"
	 * eingestellt ist.
	 *
	 * @param int    $id          Datensatz-ID in tl_topwertungszahlen
	 * @param string $listendatum Datum als JJJJMMTT
	 *
	 * @return array{pfad:string,quelle:string} Beide Werte sind leer, wenn es
	 *                                          kein Foto gibt oder die Datei
	 *                                          in der Dateiverwaltung fehlt
	 */
	private function spielerfoto(int $id, string $listendatum): array
	{
		$foto = \Database::getInstance()
			->prepare('SELECT singleSRC, source FROM tl_topwertungszahlen_photos WHERE pid=? AND date<=? ORDER BY date DESC')
			->limit(1)
			->execute($id, $listendatum)
		;

		if (!$foto->numRows) {
			return ['pfad' => '', 'quelle' => ''];
		}

		// Der Verweis kann auf zwei Arten ins Leere gehen: Der Eintrag in der
		// Dateiverwaltung fehlt, oder er ist da und die Datei nicht. Beides
		// behandelt der Helfer, und beides muß hier dasselbe bedeuten — ein
		// Pfad in der Ausgabedatei, hinter dem nichts liegt, erzeugt auf der
		// Webseite ein kaputtes Bild
		$pfad = Bilder::pfad($foto->singleSRC);

		if ('' === $pfad) {
			return ['pfad' => '', 'quelle' => ''];
		}

		return ['pfad' => $pfad, 'quelle' => (string) $foto->source];
	}

	/**
	 * Legt das Bruchstück eines Schritts im Zwischenverzeichnis ab.
	 *
	 * @param string           $liste   Listenschlüssel, etwa 'dwz_u20w'
	 * @param array<int,array> $zeilen  Ausgabezeilen dieser Liste
	 *
	 * @throws \RuntimeException wenn sich das Bruchstück nicht schreiben läßt.
	 *                           Der Schritt gilt dann als gescheitert — sonst
	 *                           liefe der Lauf durch und die Liste fehlte am
	 *                           Ende stillschweigend in der Datei
	 */
	private function bruchstueckSchreiben(string $liste, array $zeilen): void
	{
		$ordner = $this->projektverzeichnis.'/'.self::SCHRITTORDNER;

		if (!is_dir($ordner) && !mkdir($ordner, 0775, true) && !is_dir($ordner)) {
			throw new \RuntimeException('Das Verzeichnis '.$ordner.' ließ sich nicht anlegen.');
		}

		$inhalt = json_encode($zeilen, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

		if (false === $inhalt || false === file_put_contents($ordner.'/'.$liste.'.json', $inhalt)) {
			throw new \RuntimeException('Das Bruchstück '.$liste.' ließ sich nicht schreiben.');
		}
	}

	/**
	 * Liest das Bruchstück eines Schritts zurück.
	 *
	 * @param string $liste Listenschlüssel, etwa 'dwz_u20w'
	 *
	 * @return array<int,array>|null Die Ausgabezeilen, oder null wenn es kein
	 *                               Bruchstück gibt oder es unlesbar ist. Der
	 *                               Aufrufer behandelt beides gleich: Die Liste
	 *                               bleibt in der Ausgabedatei leer
	 */
	private function bruchstueckLesen(string $liste): ?array
	{
		$pfad = $this->projektverzeichnis.'/'.self::SCHRITTORDNER.'/'.$liste.'.json';

		if (!is_file($pfad)) {
			return null;
		}

		$inhalt = file_get_contents($pfad);

		if (false === $inhalt) {
			return null;
		}

		$daten = json_decode($inhalt, true);

		return \is_array($daten) ? $daten : null;
	}

	/**
	 * Löscht alle Bruchstücke im Zwischenverzeichnis.
	 *
	 * Wird vor dem ersten Schritt eines Laufs und nach dem Schreiben der
	 * Ausgabedateien aufgerufen. Ohne das erste Leeren geriete das Bruchstück
	 * eines abgebrochenen Laufs in die nächsten Dateien.
	 *
	 * Gelöscht wird nur, was dem Namensmuster der Bruchstücke entspricht —
	 * fremde Dateien im selben Verzeichnis bleiben unangetastet.
	 */
	private function zwischenverzeichnisLeeren(): void
	{
		$ordner = $this->projektverzeichnis.'/'.self::SCHRITTORDNER;

		if (!is_dir($ordner)) {
			return;
		}

		foreach (self::schritte() as $schritt) {
			$pfad = $ordner.'/'.$schritt['liste'].'.json';

			if (is_file($pfad)) {
				@unlink($pfad);
			}
		}
	}

	/**
	 * Schreibt eine Ausgabedatei und legt ihr Verzeichnis an, falls nötig.
	 *
	 * Geschrieben wird zuerst in eine Datei daneben und erst danach an die
	 * endgültige Stelle umbenannt. Sonst könnte ein Seitenaufruf, der genau
	 * währenddessen die Datei liest, einen halben Datenbestand bekommen —
	 * `rename()` ist auf demselben Dateisystem unteilbar.
	 *
	 * @param string $dateiname Name ohne Pfad, etwa 'dwz.json'
	 * @param array  $daten     Listen als Schlüssel, Ausgabezeilen als Wert
	 *
	 * @return string Pfad der geschriebenen Datei, relativ zum
	 *                Projektverzeichnis
	 *
	 * @throws \RuntimeException wenn das Verzeichnis nicht angelegt oder die
	 *                           Datei nicht geschrieben werden kann
	 */
	private function dateiSchreiben(string $dateiname, array $daten): string
	{
		$ordner = $this->projektverzeichnis.'/'.self::AUSGABEORDNER;

		if (!is_dir($ordner) && !mkdir($ordner, 0775, true) && !is_dir($ordner)) {
			throw new \RuntimeException('Das Verzeichnis '.$ordner.' ließ sich nicht anlegen.');
		}

		$ziel = $ordner.'/'.$dateiname;
		$zwischen = $ziel.'.tmp';

		$inhalt = json_encode($daten, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

		if (false === $inhalt || false === file_put_contents($zwischen, $inhalt)) {
			throw new \RuntimeException('Die Datei '.$ziel.' ließ sich nicht schreiben.');
		}

		if (!rename($zwischen, $ziel)) {
			@unlink($zwischen);

			throw new \RuntimeException('Die Datei '.$ziel.' ließ sich nicht ersetzen.');
		}

		return self::AUSGABEORDNER.'/'.$dateiname;
	}

	/**
	 * Gibt den höherwertigen der beiden FIDE-Titel zurück.
	 *
	 * Die FIDE führt den offenen und den Frauentitel getrennt. Eine
	 * Großmeisterin trägt beides — angezeigt werden soll der höhere. Die
	 * Rangfolge entspricht der des alten Skripts.
	 *
	 * @param string $titel1 Offener Titel, etwa 'IM'
	 * @param string $titel2 Frauentitel, etwa 'WGM'
	 *
	 * @return string Der höherwertige Titel, oder ein leerer String, wenn
	 *                beide leer oder unbekannt sind
	 */
	private function fideTitel(string $titel1, string $titel2 = ''): string
	{
		static $rang = [
			'WCM' => 1,
			'WFM' => 2,
			'CM'  => 3,
			'WIM' => 4,
			'FM'  => 5,
			'WGM' => 6,
			'IM'  => 7,
			'GM'  => 8,
		];

		$maxwert = 0;
		$maxtitel = '';

		foreach ([$titel1, $titel2] as $titel) {
			$wert = $rang[$titel] ?? 0;

			if ($wert > $maxwert) {
				$maxwert = $wert;
				$maxtitel = $titel;
			}
		}

		return $maxtitel;
	}

	/**
	 * Übersetzt das Geschlecht des Wertungsportals in die Schreibweise dieser
	 * Tabelle.
	 *
	 * Das Wertungsportal schreibt MALE/FEMALE/DIVERSE, tl_topwertungszahlen
	 * kennt male/female/other. Ein unbekannter Wert wird zu einem leeren
	 * String, damit im Backend die Leerauswahl greift.
	 *
	 * @param string $wert Wert aus dem Wertungsportal
	 *
	 * @return string 'male', 'female', 'other' oder ''
	 */
	private function geschlecht(string $wert): string
	{
		switch (strtoupper($wert)) {
			case 'MALE':
				return 'male';

			case 'FEMALE':
				return 'female';

			case 'DIVERSE':
				return 'other';

			default:
				return '';
		}
	}

	/**
	 * Ermittelt das Geburtsjahr aus dem Feld `geburtstag`.
	 *
	 * Contao legt Datumsfelder als Unix-Zeitstempel in einem varchar ab. Das
	 * Feld ist nicht verpflichtend und in den automatisch angelegten Sätzen
	 * nie gefüllt.
	 *
	 * @param string $wert Rohwert aus tl_topwertungszahlen.geburtstag
	 *
	 * @return int Jahreszahl, oder 0 wenn nicht bestimmbar
	 */
	private function geburtsjahr(string $wert): int
	{
		$wert = trim($wert);

		if ('' === $wert || '0' === $wert || !is_numeric($wert)) {
			return 0;
		}

		$jahr = (int) date('Y', (int) $wert);

		return $jahr > 1900 && $jahr < 2100 ? $jahr : 0;
	}

	/**
	 * Formt einen Namen für den Vergleich um.
	 *
	 * Groß- und Kleinschreibung, Umlaute, Bindestriche und Leerzeichen dürfen
	 * die Zuordnung nicht verhindern: In den Altbeständen steht „Müller"
	 * neben „Mueller" und „von Herman" neben „von-Herman".
	 *
	 * @param string $name Nachname oder Vorname, roh aus der Datenbank
	 *
	 * @return string Vergleichsform aus ausschließlich Kleinbuchstaben a–z,
	 *                leer wenn nichts Verwertbares übrig bleibt
	 */
	private function namensform(string $name): string
	{
		static $ersetzungen = [
			'ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss',
			'á' => 'a', 'à' => 'a', 'â' => 'a', 'å' => 'a', 'ã' => 'a',
			'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
			'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
			'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ø' => 'o',
			'ú' => 'u', 'ù' => 'u', 'û' => 'u',
			'ç' => 'c', 'ñ' => 'n', 'š' => 's', 'ž' => 'z', 'č' => 'c', 'ř' => 'r',
		];

		return (string) preg_replace('/[^a-z]/', '', strtr(mb_strtolower(trim($name)), $ersetzungen));
	}

	/**
	 * Reicht eine Fortschrittsmeldung an den Aufrufer weiter.
	 *
	 * Ohne gesetzten Melder passiert nichts — so läuft derselbe Code still im
	 * Cron und gesprächig im Backend.
	 *
	 * @param string $meldung Einzeilige Meldung ohne Zeilenumbruch
	 */
	private function melden(string $meldung): void
	{
		if (null !== $this->melder) {
			($this->melder)($meldung);
		}
	}
}
