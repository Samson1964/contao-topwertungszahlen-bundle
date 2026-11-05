<?php

declare(strict_types=1);

namespace Schachbulle\ContaoTopwertungszahlenBundle\Classes;

/**
 * Importmaske für die Ranglisten.
 *
 * Wird als globale Operation `importRating` aus dem Backend-Modul aufgerufen
 * (siehe `Resources/contao/config/config.php`) und arbeitet den
 * Ranglistenlauf über die Backend-Route schrittweise ab — eine Liste je
 * Aufruf, dazwischen wächst der Fortschrittsbalken.
 *
 * Bis Version 1.6.3 baute diese Klasse eine Verschachtelung aus zwanzig
 * ineinandergelegten Ajax-Aufrufen, deren Antworten roh untereinander
 * gehängt wurden. Die Schrittfolge ist geblieben, weil ein Lauf bei kaltem
 * Zwischenspeicher des Wertungsportals durchaus eine Minute dauern kann und
 * ein einziger Aufruf so lange nichts anzuzeigen hätte. Neu ist, daß die
 * Schritte aus einer Liste kommen statt aus zwanzig verschachtelten
 * Rückrufen, und daß der Fortschritt zu sehen ist.
 */
class Import
{
	/**
	 * Baut die Importmaske und startet den Lauf.
	 *
	 * Der Lauf beginnt sofort beim Aufruf der Maske — so verhielt sich die
	 * Vorgängerfassung auch, und ein zusätzlicher Klick brächte nichts, weil
	 * die Maske keinen anderen Zweck hat.
	 *
	 * @return string HTML der Maske samt Steuerskript. Die Schrittliste wird
	 *                als JSON eingebettet, damit das Skript weiß, was es
	 *                abzuarbeiten hat und wie die Schritte heißen
	 */
	public function run(): string
	{
		$url = \Contao\System::getContainer()->get('router')->generate('topwertungszahlen_ranglistenlauf');

		// Zurücklink auf die Übersicht: derselbe Aufruf ohne den Schlüssel
		// der globalen Operation
		$backlink = str_replace(['&key=importRating', '&key=importPhotos'], '', \Contao\Environment::get('request'));

		// Zwei Betriebsarten an derselben Maske: der vollständige Lauf und
		// das bloße Auffrischen der Fotos. Sie unterscheiden sich allein in
		// der Liste der Schritte, die das Steuerskript abarbeitet
		$nurFotos = 'importPhotos' === \Contao\Input::get('key');

		if ($nurFotos) {
			$schritte = [['nr' => 'fotos', 'name' => 'Fotos in den Ausgabedateien auffrischen']];
		} else {
			// Die zwanzig Listen, dahinter das Zusammensetzen der Dateien als
			// einundzwanzigster Schritt
			$schritte = [];

			foreach (Ranglistenlauf::schritte() as $nummer => $schritt) {
				$schritte[] = ['nr' => (string) $nummer, 'name' => $schritt['name']];
			}

			$schritte[] = ['nr' => 'dateien', 'name' => 'Ausgabedateien schreiben'];
		}

		$content = $this->maske($backlink, \count($schritte));
		$content .= '<script>'."\n";
		$content .= 'var twUrl = '.json_encode($url).';'."\n";
		$content .= 'var twSchritte = '.json_encode($schritte, JSON_UNESCAPED_UNICODE).';'."\n";
		$content .= 'var twSpielerbilanz = '.($nurFotos ? 'false' : 'true').';'."\n";
		$content .= $this->skript();
		$content .= '</script>'."\n";

		return $content;
	}

	/**
	 * Liefert das unveränderliche Gerüst der Maske.
	 *
	 * Die Farben stehen als Stilangaben in den Elementen und nicht in einer
	 * eigenen CSS-Datei: Es sind fünf Regeln, und eine zusätzliche Datei
	 * müßte über `assets:install` mitwandern.
	 *
	 * @param string $backlink Ziel des Zurücklinks
	 * @param int    $anzahl   Anzahl der Schritte, die abzuarbeiten sind
	 *
	 * @return string HTML ohne Skript
	 */
	private function maske(string $backlink, int $anzahl): string
	{
		$html = '<div style="margin:10px;">'."\n";
		$html .= '<p id="tw_stand" style="margin-bottom:6px;font-weight:bold;">Der Lauf wird vorbereitet …</p>'."\n";
		$html .= '<div style="height:18px;border:1px solid #a3a3a3;background:#f7f7f7;border-radius:3px;overflow:hidden;">'."\n";
		$html .= '<div id="tw_balken" style="height:100%;width:0;background:#3f7ba7;transition:width .2s;"></div>'."\n";
		$html .= '</div>'."\n";
		$html .= '<p id="tw_zaehler" style="margin-top:6px;color:#666;">0 von '.$anzahl.' Schritten</p>'."\n";
		$html .= '<div id="tw_protokoll" style="margin-top:12px;font-family:monospace;font-size:12px;line-height:1.6;"></div>'."\n";
		$html .= '<p style="margin-top:12px;"><a href="'.$backlink.'">Zurück zur Übersicht</a></p>'."\n";
		$html .= '</div>'."\n";

		return $html;
	}

	/**
	 * Liefert das Steuerskript, das die Schritte nacheinander abruft.
	 *
	 * Nacheinander und nicht gleichzeitig: Die Schritte schreiben in dieselben
	 * Tabellen, und beim ersten wird das Zwischenverzeichnis geleert. Ein
	 * paralleler Abruf brächte durcheinandergeratene Bruchstücke.
	 *
	 * Bricht ein Schritt ab, hält das Skript an. Weiterzulaufen brächte nur
	 * eine unvollständige Ausgabedatei, und die Fehlermeldung ginge in den
	 * folgenden Zeilen unter.
	 *
	 * @return string JavaScript ohne umschließendes script-Element
	 */
	private function skript(): string
	{
		return <<<'JS'
(function() {
	var stand = document.getElementById('tw_stand');
	var balken = document.getElementById('tw_balken');
	var zaehler = document.getElementById('tw_zaehler');
	var protokoll = document.getElementById('tw_protokoll');
	var beginn = Date.now();
	var neu = 0;
	var zugeordnet = 0;

	function zeile(text, farbe) {
		var p = document.createElement('div');
		p.textContent = text;
		if (farbe) { p.style.color = farbe; p.style.fontWeight = 'bold'; }
		protokoll.appendChild(p);
	}

	function sekunden() {
		return ((Date.now() - beginn) / 1000).toFixed(1).replace('.', ',');
	}

	function abbrechen(text) {
		stand.textContent = 'Abgebrochen';
		stand.style.color = '#c33';
		balken.style.background = '#c33';
		zeile(text, '#c33');
	}

	function schritt(i) {
		if (i >= twSchritte.length) {
			stand.textContent = 'Fertig in ' + sekunden() + ' Sekunden';
			stand.style.color = '#2a8';
			// Nur beim vollständigen Lauf: Die Auffrischung der Fotos legt
			// keine Spieler an, dort stünde immer eine Null-Meldung
			if (twSpielerbilanz) {
				zeile(neu + ' Spieler neu angelegt, ' + zugeordnet + ' über den Namen zugeordnet.');
			}
			return;
		}

		stand.textContent = 'Schritt ' + (i + 1) + ' von ' + twSchritte.length + ': ' + twSchritte[i].name;
		zaehler.textContent = i + ' von ' + twSchritte.length + ' Schritten · ' + sekunden() + ' Sekunden';

		fetch(twUrl + '?schritt=' + encodeURIComponent(twSchritte[i].nr), {
			credentials: 'same-origin',
			cache: 'no-store'
		}).then(function(antwort) {
			return antwort.json().then(function(daten) {
				return {status: antwort.status, daten: daten};
			});
		}).then(function(ergebnis) {
			if (!ergebnis.daten || !ergebnis.daten.ok) {
				var text = ergebnis.daten && ergebnis.daten.meldung
					? ergebnis.daten.meldung
					: 'Unerwartete Antwort (HTTP ' + ergebnis.status + ')';
				abbrechen(twSchritte[i].name + ': ' + text);
				return;
			}

			neu += ergebnis.daten.neu || 0;
			zugeordnet += ergebnis.daten.zugeordnet || 0;

			zeile(ergebnis.daten.meldung);
			balken.style.width = Math.round(100 * (i + 1) / twSchritte.length) + '%';

			schritt(i + 1);
		}).catch(function(fehler) {
			abbrechen(twSchritte[i].name + ': ' + fehler);
		});
	}

	schritt(0);
})();

JS;
	}
}
