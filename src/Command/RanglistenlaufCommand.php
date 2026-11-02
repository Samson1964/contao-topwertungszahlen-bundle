<?php

declare(strict_types=1);

namespace Schachbulle\ContaoTopwertungszahlenBundle\Command;

use Contao\CoreBundle\Framework\ContaoFramework;
use Schachbulle\ContaoTopwertungszahlenBundle\Classes\Ranglistenlauf;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Erzeugt die Ranglisten von der Kommandozeile.
 *
 * Damit läßt sich der nächtliche Lauf über den Cron des Servers anstoßen,
 * ohne den Umweg über einen Browser-Aufruf:
 *
 *     vendor/bin/contao-console topwertungszahlen:rangliste
 *
 * Früher gab es dafür nur den Aufruf von `Rangliste.php` ohne Parameter, der
 * alle zwanzig Listen nacheinander abarbeitete und `set_time_limit(0)`
 * setzen mußte.
 *
 * Der Befehlsname wird NICHT über die Eigenschaft $defaultName gesetzt,
 * sondern über das Service-Tag in `services.yml`: Symfony 7 kennt die
 * Eigenschaft nicht mehr, und das Attribut #[AsCommand] setzt PHP 8 voraus.
 * Über das Tag läuft dieselbe Datei unter Contao 4.13 und 5.
 */
class RanglistenlaufCommand extends Command
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
	 *                                            Contao-Klassen benutzen zu können
	 * @param string          $projektverzeichnis Absoluter Pfad zur Installation,
	 *                                            aus %kernel.project_dir%
	 */
	public function __construct(ContaoFramework $framework, string $projektverzeichnis)
	{
		$this->framework = $framework;
		$this->projektverzeichnis = $projektverzeichnis;

		parent::__construct();
	}

	/**
	 * Beschreibt den Befehl für die Übersicht von `contao-console list`.
	 */
	protected function configure(): void
	{
		$this
			->setDescription('Erzeugt die zwanzig Ranglisten (DWZ und Elo) aus den Daten des Wertungsportals.')
			->addOption(
				'nur-fotos',
				null,
				InputOption::VALUE_NONE,
				'Frischt allein die Fotos in den Ausgabedateien auf, ohne die Wertungszahlen neu zu laden'
			)
			->setHelp(
				'Schreibt die Platzierungen nach tl_topwertungszahlen_ratings und die '
				.'Ausgabedateien nach '.Ranglistenlauf::AUSGABEORDNER.'. Ein zweiter Lauf am '
				.'selben Tag läßt bereits festgehaltene Platzierungen unverändert.'
				."\n\n"
				.'Mit --nur-fotos wird nichts beschafft und nichts in die Auswertungstabelle '
				.'geschrieben: Die vorhandenen Ausgabedateien werden gelesen, zu jedem Eintrag '
				.'das jüngste Foto nachgeschlagen und die Dateien zurückgeschrieben.'
			)
		;
	}

	/**
	 * Führt den Lauf aus und gibt die Meldungen auf der Konsole aus.
	 *
	 * @param InputInterface  $input  Wird nicht ausgewertet, der Befehl kennt
	 *                                keine Parameter
	 * @param OutputInterface $output Ziel der Fortschrittsmeldungen
	 *
	 * @return int 0 bei Erfolg, 1 wenn der Lauf mit einer Ausnahme abbrach
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$stil = new SymfonyStyle($input, $output);
		$nurFotos = (bool) $input->getOption('nur-fotos');

		$stil->title($nurFotos
			? 'Topwertungszahlen: Fotos auffrischen'
			: 'Topwertungszahlen: Ranglisten erzeugen');

		$this->framework->initialize();

		if ($nurFotos) {
			try {
				$ergebnis = (new Ranglistenlauf($this->projektverzeichnis))->fotosAktualisieren();
			} catch (\Throwable $e) {
				$stil->error($e->getMessage());

				return 1;
			}

			$stil->success($ergebnis['meldung']);

			return 0;
		}

		try {
			$lauf = new Ranglistenlauf($this->projektverzeichnis);

			$bericht = $lauf->lauf(
				static function (string $meldung) use ($output): void {
					$output->writeln($meldung);
				}
			);
		} catch (\Throwable $e) {
			$stil->error($e->getMessage());

			return 1;
		}

		$stil->success('Geschrieben: '.implode(', ', $bericht['dateien']));

		return 0;
	}
}
