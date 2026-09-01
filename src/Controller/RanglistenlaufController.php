<?php

declare(strict_types=1);

namespace Schachbulle\ContaoTopwertungszahlenBundle\Controller;

use Contao\CoreBundle\Framework\ContaoFramework;
use Schachbulle\ContaoTopwertungszahlenBundle\Classes\Ranglistenlauf;
use Symfony\Component\HttpFoundation\Response;

/**
 * Backend-Route, die einen Ranglistenlauf auslöst.
 *
 * Tritt an die Stelle des früheren Direktaufrufs von
 * `bundles/contaotopwertungszahlen/Rangliste.php`. Zwei Gründe für den
 * Wechsel: Das Skript fuhr Contao über `system/initialize.php` selbst hoch —
 * die Datei gibt es in Contao 5 nicht mehr —, und es lag im Web-Verzeichnis
 * ohne jede Zugangsprüfung. Die Route liegt unterhalb von `/contao` und ist
 * damit der Anmeldung im Backend unterworfen.
 *
 * Aufgerufen wird sie von der Importmaske
 * (@see \Schachbulle\ContaoTopwertungszahlenBundle\Classes\Import) per Ajax.
 * Die Antwort ist ein HTML-Bruchstück, das dort angehängt wird.
 */
class RanglistenlaufController
{
	/**
	 * @var ContaoFramework
	 */
	private $framework;

	/**
	 * @var string
	 */
	private $projektverzeichnis;

	/**
	 * @param ContaoFramework $framework          Wird gebraucht, um die
	 *                                            Contao-Klassen (Database,
	 *                                            FilesModel) benutzen zu können
	 * @param string          $projektverzeichnis Absoluter Pfad zur Installation,
	 *                                            aus %kernel.project_dir%
	 */
	public function __construct(ContaoFramework $framework, string $projektverzeichnis)
	{
		$this->framework = $framework;
		$this->projektverzeichnis = $projektverzeichnis;
	}

	/**
	 * Führt den Lauf aus und gibt die Meldungen als HTML zurück.
	 *
	 * Der Lauf dauert seit der Umstellung auf das Wertungsportal wenige
	 * Sekunden. Deshalb wird die Antwort am Stück gesendet und nicht mehr
	 * — wie früher nötig — in zwanzig Einzelaufrufe zerlegt.
	 *
	 * @return Response HTML-Bruchstück mit einer Zeile je Liste. Bei einem
	 *                  Fehler steht die Meldung im Text und der Statuscode ist
	 *                  500, damit der Aufrufer den Fehlschlag bemerkt
	 */
	public function __invoke(): Response
	{
		$this->framework->initialize();

		$zeilen = [];

		try {
			$lauf = new Ranglistenlauf($this->projektverzeichnis);

			$bericht = $lauf->lauf(
				static function (string $meldung) use (&$zeilen): void {
					$zeilen[] = htmlspecialchars($meldung, ENT_QUOTES, 'UTF-8');
				}
			);
		} catch (\Throwable $e) {
			$zeilen[] = '<b style="color:#c33">Fehler: '.htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8').'</b>';

			return new Response(implode("<br>\n", $zeilen), Response::HTTP_INTERNAL_SERVER_ERROR);
		}

		foreach ($bericht['dateien'] as $art => $pfad) {
			$zeilen[] = 'Datei geschrieben: <b>'.htmlspecialchars($pfad, ENT_QUOTES, 'UTF-8').'</b>';
		}

		return new Response(implode("<br>\n", $zeilen));
	}
}
