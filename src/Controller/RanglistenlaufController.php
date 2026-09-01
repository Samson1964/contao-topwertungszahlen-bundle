<?php

declare(strict_types=1);

namespace Schachbulle\ContaoTopwertungszahlenBundle\Controller;

use Contao\CoreBundle\Framework\ContaoFramework;
use Schachbulle\ContaoTopwertungszahlenBundle\Classes\Ranglistenlauf;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Backend-Route, die einen Ranglistenlauf schrittweise ausführt.
 *
 * Tritt an die Stelle des früheren Direktaufrufs von
 * `bundles/contaotopwertungszahlen/Rangliste.php`. Zwei Gründe für den
 * Wechsel: Das Skript fuhr Contao über `system/initialize.php` selbst hoch —
 * die Datei gibt es in Contao 5 nicht mehr —, und es lag im Web-Verzeichnis
 * ohne jede Zugangsprüfung. Die Route liegt unterhalb von `/contao` und ist
 * damit der Anmeldung im Backend unterworfen.
 *
 * Aufgerufen wird sie von der Importmaske
 * (@see \Schachbulle\ContaoTopwertungszahlenBundle\Classes\Import) — einmal je
 * Schritt, damit dort ein Fortschritt zu sehen ist. Die Antwort ist JSON.
 *
 * Der Parameter `schritt`:
 *
 *   0 … 19     eine einzelne Liste
 *   'dateien'  die Bruchstücke zu den Ausgabedateien zusammensetzen
 *   fehlt      alles auf einmal, für den Aufruf von Hand
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
	 * Führt den angeforderten Schritt aus.
	 *
	 * @param Request $request Ausgewertet wird allein der Parameter `schritt`
	 *
	 * @return JsonResponse Bei Erfolg mit `ok: true` und einer Meldung, sonst
	 *                      mit `ok: false`, der Fehlermeldung und Statuscode
	 *                      500. Die Importmaske zeigt die Meldung in beiden
	 *                      Fällen an — bei einem Fehlschlag ist sie das
	 *                      Nützlichste, was sie hat
	 */
	public function __invoke(Request $request): JsonResponse
	{
		$this->framework->initialize();

		$schritt = $request->query->get('schritt');

		try {
			$lauf = new Ranglistenlauf($this->projektverzeichnis);

			if (null === $schritt || '' === $schritt) {
				return new JsonResponse($this->allesAufEinmal($lauf));
			}

			if ('dateien' === $schritt) {
				$ergebnis = $lauf->dateienSchreiben();

				return new JsonResponse([
					'ok'      => true,
					'meldung' => $ergebnis['meldung'],
					'dateien' => $ergebnis['dateien'],
					'fehlend' => $ergebnis['fehlend'],
				]);
			}

			// Nur Ziffern zulassen: Alles andere ist ein Aufrufefehler und
			// soll nicht als Schritt 0 durchgehen
			if (!ctype_digit((string) $schritt)) {
				return new JsonResponse(
					['ok' => false, 'meldung' => 'Unbekannter Schritt: '.$schritt],
					Response::HTTP_BAD_REQUEST
				);
			}

			$ergebnis = $lauf->schrittLaufen((int) $schritt);

			return new JsonResponse([
				'ok'         => true,
				'meldung'    => $ergebnis['meldung'],
				'name'       => $ergebnis['name'],
				'liste'      => $ergebnis['liste'],
				'beschafft'  => $ergebnis['beschafft'],
				'ausgabe'    => $ergebnis['ausgabe'],
				'quelle'     => $ergebnis['quelle'],
				'neu'        => $ergebnis['neu'],
				'zugeordnet' => $ergebnis['zugeordnet'],
			]);
		} catch (\OutOfRangeException $e) {
			return new JsonResponse(
				['ok' => false, 'meldung' => $e->getMessage()],
				Response::HTTP_BAD_REQUEST
			);
		} catch (\Throwable $e) {
			return new JsonResponse(
				['ok' => false, 'meldung' => $e->getMessage()],
				Response::HTTP_INTERNAL_SERVER_ERROR
			);
		}
	}

	/**
	 * Führt einen vollständigen Lauf aus und sammelt die Meldungen ein.
	 *
	 * Dieser Weg ist für den Aufruf der Route von Hand gedacht. Die
	 * Importmaske benutzt ihn nicht — sie geht Schritt für Schritt vor, damit
	 * sie einen Fortschritt anzeigen kann.
	 *
	 * @param Ranglistenlauf $lauf Der vorbereitete Lauf
	 *
	 * @return array{ok:bool,meldung:string,meldungen:array<int,string>,dateien:array<string,string>}
	 */
	private function allesAufEinmal(Ranglistenlauf $lauf): array
	{
		$meldungen = [];

		$bericht = $lauf->lauf(
			static function (string $meldung) use (&$meldungen): void {
				$meldungen[] = $meldung;
			}
		);

		return [
			'ok'        => true,
			'meldung'   => 'Lauf beendet.',
			'meldungen' => $meldungen,
			'dateien'   => $bericht['dateien'],
		];
	}
}
