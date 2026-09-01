<?php

declare(strict_types=1);

namespace Schachbulle\ContaoTopwertungszahlenBundle\Classes;

/**
 * Importmaske für die Ranglisten.
 *
 * Wird als globale Operation `importRating` aus dem Backend-Modul aufgerufen
 * (siehe `Resources/contao/config/config.php`) und stößt den Ranglistenlauf
 * über die Backend-Route an.
 *
 * Bis Version 1.6.3 baute diese Klasse eine Verschachtelung aus zwanzig
 * ineinandergelegten Ajax-Aufrufen — einen je Liste. Das war nötig, weil jede
 * einzelne Liste über DeWIS Minuten brauchte und ein Aufruf am Stück in die
 * Zeitschranke des Servers gelaufen wäre. Seit die Daten aus dem
 * Wertungsportal kommen, ist der ganze Lauf in Sekunden erledigt und es
 * genügt ein einziger Aufruf. Damit entfällt auch die Einbindung von jQuery.
 */
class Import extends \Backend
{
	/**
	 * Baut die Importmaske und startet den Lauf.
	 *
	 * Der Lauf beginnt sofort beim Aufruf der Maske — so verhielt sich die
	 * Vorgängerfassung auch, und ein zusätzlicher Klick brächte nichts, weil
	 * die Maske keinen anderen Zweck hat.
	 *
	 * @return string HTML der Maske samt Startskript. Die Meldungen des Laufs
	 *                trägt das Skript nach, sobald die Route geantwortet hat
	 */
	public function run(): string
	{
		$url = \System::getContainer()->get('router')->generate('topwertungszahlen_ranglistenlauf');

		// Zurücklink auf die Übersicht: derselbe Aufruf ohne den Schlüssel
		// der globalen Operation
		$backlink = str_replace('&key=importRating', '', \Environment::get('request'));

		$content = '<div id="rating_import" style="margin:10px;"></div>'."\n";
		$content .= '<div id="rating_import_status" style="margin:10px;">Der Lauf wurde gestartet, bitte warten …</div>'."\n";
		$content .= '<div style="margin:10px;"><a href="'.$backlink.'">Zurück</a></div>'."\n";

		$content .= '<script>'."\n";
		$content .= 'fetch('.json_encode($url).', {credentials: "same-origin", cache: "no-store"})'."\n";
		$content .= '  .then(function(antwort) {'."\n";
		// Der Text wird in jedem Fall angezeigt — auch bei Statuscode 500.
		// Dort steht die Fehlermeldung des Laufs, und die ist nützlicher als
		// ein pauschales "fehlgeschlagen"
		$content .= '    return antwort.text().then(function(text) {'."\n";
		$content .= '      document.getElementById("rating_import").innerHTML = text;'."\n";
		$content .= '      document.getElementById("rating_import_status").innerHTML = antwort.ok'."\n";
		$content .= '        ? "<b>Fertig</b>"'."\n";
		$content .= '        : "<b style=\'color:#c33\'>Abgebrochen (HTTP " + antwort.status + ")</b>";'."\n";
		$content .= '    });'."\n";
		$content .= '  })'."\n";
		$content .= '  .catch(function(fehler) {'."\n";
		$content .= '    document.getElementById("rating_import_status").innerHTML ='."\n";
		$content .= '      "<b style=\'color:#c33\'>Die Route war nicht erreichbar: </b>" + fehler;'."\n";
		$content .= '  });'."\n";
		$content .= '</script>'."\n";

		return $content;
	}
}
