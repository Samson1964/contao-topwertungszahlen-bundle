<?php

declare(strict_types=1);

namespace Schachbulle\ContaoTopwertungszahlenBundle\Classes;

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
	 * Föderation, für die die Ranglisten gelten. Geprüft wird gegen die
	 * FIDE-Föderation des Spielers — so hielt es auch die alte DeWIS-Fassung
	 * (`member->fideNation`).
	 */
	public const NATION = 'GER';

	/**
	 * Faktor, um den die DWZ-Listen überzogen angefordert werden.
	 *
	 * Die Nationsprüfung nimmt hinterher Zeilen weg. Ohne Überzug bliebe eine
	 * Liste, aus der zehn Ausländer fielen, um zehn Plätze zu kurz. Das
	 * Doppelte reicht mit Abstand: In der Spitze des deutschen Bestands sind
	 * Ausländer die Ausnahme.
	 */
	public const UEBERZUG = 2;

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

		if ('dwz' === $schritt['art']) {
			// Überzogen anfordern, weil die Nationsprüfung unten noch Zeilen
			// wegnimmt und die Liste sonst zu kurz ausfiele
			$params['limit'] = self::TOP * self::UEBERZUG;
			$ergebnis = Ranglisten::dwz($params);
			$geliefert = $ergebnis['liste'] ?? [];

			$pruefung = $this->nurDeutsche($geliefert);
			$zeilen = \array_slice($this->platzziffern($pruefung['zeilen'], 'dwz'), 0, self::TOP);
		} else {
			// Die Elo-Listen führt die FIDE-Tabelle an, und dort ist die
			// Föderation immer gefüllt — das Wertungsportal filtert sie
			// bereits richtig. Kein Nachfiltern nötig
			$params['limit'] = self::TOP;
			$ergebnis = Ranglisten::elo($params);
			$zeilen = $ergebnis['liste'] ?? [];
			$pruefung = ['verworfen' => 0, 'ohneNation' => 0, 'hinweis' => ''];
		}

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

		if ($pruefung['verworfen'] > 0) {
			$meldung .= sprintf(' – %d Nichtdeutsche verworfen', $pruefung['verworfen']);
		}

		if ($pruefung['ohneNation'] > 0) {
			$meldung .= sprintf(', %d ohne Föderation (bleiben drin)', $pruefung['ohneNation']);
		}

		if ('' !== $pruefung['hinweis']) {
			$meldung .= ' – '.$pruefung['hinweis'];
		}

		return [
			'liste'      => $schritt['liste'],
			'name'       => $schritt['name'],
			'beschafft'  => \count($zeilen),
			'ausgabe'    => \count($ausgabe),
			'quelle'     => $quelle,
			'verworfen'  => $pruefung['verworfen'],
			'ohneNation' => $pruefung['ohneNation'],
			'neu'        => $zaehler['neu'],
			'zugeordnet' => $zaehler['zugeordnet'],
			'meldung'    => $meldung,
		];
	}

	/**
	 * Wirft aus einer DWZ-Liste die Spieler heraus, die nachweislich nicht für
	 * Deutschland spielen.
	 *
	 * **Warum das hier nötig ist.** `Ranglisten::dwz()` filtert gegen
	 * `tl_wertungsportal_persons.nation`, und dort zählt ein LEERER Wert als
	 * deutsch. Für eine Verbandsrangliste ist das richtig — das Feld stammt
	 * allein aus der Vereinsmitglieder-CSV und ist für einen großen Teil des
	 * Bestands leer; wer Leerwerte ausschlösse, verlöre die halbe Liste. Für
	 * die Spitzenwertungszahlen des DSB ist es falsch: Dort standen dadurch
	 * Ausländer in der Top-10.
	 *
	 * Geprüft wird deshalb die **FIDE-Föderation**, genau wie es die alte
	 * DeWIS-Fassung tat (`member->fideNation == 'GER'`). Sie steht in
	 * `tl_wertungsportal_persons.fideNation`; ist sie dort leer, wird sie über
	 * die FIDE-ID aus `tl_wertungsportal_elo.country` nachgeschlagen. Auf
	 * genau diesen Weg verweist auch der Quelltext des Wertungsportals.
	 *
	 * Verworfen wird nur, wer eine **bekannte andere** Föderation trägt. Wer
	 * gar keine hat, bleibt drin — sonst verschwänden alle Spieler ohne
	 * FIDE-Eintrag, und das sind in den unteren Altersklassen die meisten. Wie
	 * viele das sind, steht in der Rückgabe und damit in der Meldung des
	 * Schritts.
	 *
	 * @param array<int,array> $zeilen Zeilen aus Ranglisten::dwz()
	 *
	 * @return array{zeilen:array<int,array>,verworfen:int,ohneNation:int,hinweis:string}
	 *               `hinweis` ist gesetzt, wenn die Prüfung nicht stattfinden
	 *               konnte — dann bleiben alle Zeilen stehen, und die Meldung
	 *               sagt es
	 */
	private function nurDeutsche(array $zeilen): array
	{
		if ([] === $zeilen) {
			return ['zeilen' => [], 'verworfen' => 0, 'ohneNation' => 0, 'hinweis' => ''];
		}

		try {
			$foederationen = $this->foederationen($zeilen);
		} catch (\Throwable $e) {
			// Lieber die vollständige Liste mit sichtbarem Hinweis als eine
			// stillschweigend ungeprüfte
			return [
				'zeilen'     => $zeilen,
				'verworfen'  => 0,
				'ohneNation' => 0,
				'hinweis'    => 'Nationsprüfung nicht möglich: '.$e->getMessage(),
			];
		}

		$behalten = [];
		$verworfen = 0;
		$ohneNation = 0;

		foreach ($zeilen as $zeile) {
			$pkz = (string) ($zeile['pkz'] ?? '');
			$foederation = strtoupper(trim((string) ($foederationen[$pkz] ?? '')));

			// Die Nation aus der Mitgliederdatei zählt mit, wenn sie gefüllt
			// ist: Sie ist der zweite Beleg und im Zweifel der neuere
			$nation = strtoupper(trim((string) ($zeile['nation'] ?? '')));

			if ('' !== $foederation && self::NATION !== $foederation) {
				++$verworfen;

				continue;
			}

			if ('' !== $nation && '-' !== $nation && self::NATION !== $nation) {
				++$verworfen;

				continue;
			}

			if ('' === $foederation) {
				++$ohneNation;
			}

			$behalten[] = $zeile;
		}

		return [
			'zeilen'     => $behalten,
			'verworfen'  => $verworfen,
			'ohneNation' => $ohneNation,
			'hinweis'    => '',
		];
	}

	/**
	 * Schlägt die FIDE-Föderation der Spieler einer Liste nach.
	 *
	 * Zwei Abfragen für die ganze Liste statt einer je Spieler: Die alte
	 * DeWIS-Fassung rief für jeden Spieler einzeln `tournamentCardForId()`
	 * auf, nur um an diesen einen Wert zu kommen — das war der Grund für die
	 * Laufzeiten von Minuten.
	 *
	 * @param array<int,array> $zeilen Zeilen aus Ranglisten::dwz()
	 *
	 * @return array<string,string> PKZ => Föderation ('GER', 'ISR', …). Nicht
	 *                              gefundene Spieler fehlen im Ergebnis
	 *
	 * @throws \Throwable wenn die Spiegeltabellen des Wertungsportals fehlen
	 */
	private function foederationen(array $zeilen): array
	{
		$pkzListe = [];
		$fideZuPkz = [];

		foreach ($zeilen as $zeile) {
			$pkz = (string) ($zeile['pkz'] ?? '');

			if ('' === $pkz) {
				continue;
			}

			$pkzListe[$pkz] = true;

			if ((int) ($zeile['fide_id'] ?? 0) > 0) {
				$fideZuPkz[(int) $zeile['fide_id']] = $pkz;
			}
		}

		if ([] === $pkzListe) {
			return [];
		}

		$db = \Database::getInstance();
		$ergebnis = [];

		// 1. Föderation aus der Mitgliederdatei
		$pkzWerte = array_keys($pkzListe);
		$platzhalter = implode(',', array_fill(0, \count($pkzWerte), '?'));

		$objPerson = $db
			->prepare("SELECT nuLigaPersonId, fideNation FROM tl_wertungsportal_persons WHERE nuLigaPersonId IN ($platzhalter)")
			->execute(...$pkzWerte)
		;

		while ($objPerson->next()) {
			$wert = trim((string) $objPerson->fideNation);

			if ('' !== $wert) {
				$ergebnis[(string) $objPerson->nuLigaPersonId] = $wert;
			}
		}

		// 2. Für die noch offenen Spieler die Föderation der FIDE-Tabelle
		$offen = [];

		foreach ($fideZuPkz as $fideId => $pkz) {
			if (!isset($ergebnis[$pkz])) {
				$offen[] = $fideId;
			}
		}

		if ([] !== $offen) {
			$platzhalter = implode(',', array_fill(0, \count($offen), '?'));

			$objElo = $db
				->prepare("SELECT fideid, country FROM tl_wertungsportal_elo WHERE fideid IN ($platzhalter)")
				->execute(...$offen)
			;

			while ($objElo->next()) {
				$wert = trim((string) $objElo->country);
				$pkz = $fideZuPkz[(int) $objElo->fideid] ?? '';

				if ('' !== $wert && '' !== $pkz) {
					$ergebnis[$pkz] = $wert;
				}
			}
		}

		return $ergebnis;
	}

	/**
	 * Vergibt die geteilten Platzziffern neu.
	 *
	 * Nach dem Herausnehmen der Nichtdeutschen stimmen die Ziffern des
	 * Wertungsportals nicht mehr — es hätte sonst Lücken (1, 2, 4, 7). Bei
	 * gleicher Wertungszahl teilen sich die Spieler den Platz, und der nächste
	 * überspringt die verbrauchten Ziffern: 1, 2, 2, 4.
	 *
	 * @param array<int,array> $zeilen Zeilen in ihrer sortierten Reihenfolge
	 * @param string           $art    'dwz' oder 'elo' — bestimmt, welches Feld
	 *                                 die Wertungszahl trägt
	 *
	 * @return array<int,array> Dieselben Zeilen mit neuen Werten in `platz`
	 *                          und `rang`
	 */
	private function platzziffern(array $zeilen, string $art): array
	{
		$feld = 'dwz' === $art ? 'dwz' : 'elo';
		$platz = 0;
		$letzteWertung = null;
		$nummer = 0;

		foreach ($zeilen as $i => $zeile) {
			++$nummer;
			$wertung = (int) ($zeile[$feld] ?? 0);

			if ($wertung !== $letzteWertung) {
				$platz = $nummer;
				$letzteWertung = $wertung;
			}

			$zeilen[$i]['platz'] = $platz;
			$zeilen[$i]['rang'] = $platz;
		}

		return $zeilen;
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

		$objFile = \FilesModel::findByPk($foto->singleSRC);

		// Der Verweis kann ins Leere gehen, wenn die Datei gelöscht wurde,
		// ohne den Fotodatensatz mitzunehmen
		if (null === $objFile) {
			return ['pfad' => '', 'quelle' => ''];
		}

		return ['pfad' => (string) $objFile->path, 'quelle' => (string) $foto->source];
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
