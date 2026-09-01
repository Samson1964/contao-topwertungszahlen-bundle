<?php

declare(strict_types=1);

namespace Schachbulle\ContaoTopwertungszahlenBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

/**
 * Schreibt die alten Geschlechtskürzel auf die Werte um, die das DCA kennt.
 *
 * Bis Version 1.6.3 übernahm der Ranglistenlauf das Geschlecht so, wie DeWIS
 * es lieferte — als einzelnen Buchstaben. Das Auswahlfeld in
 * `tl_topwertungszahlen` kennt aber `male`, `female` und `other`. Ein
 * Datensatz mit `m` oder `f` traf deshalb keine der Möglichkeiten: Das Feld
 * stand im Backend leer, und beim Speichern wäre der Wert stillschweigend
 * verschwunden.
 *
 * Die Umschreibung läuft einmalig beim `contao:migrate` und danach nie wieder,
 * weil `shouldRun()` dann nichts mehr findet. Neue Datensätze sind nicht
 * betroffen: `Classes\Ranglistenlauf::geschlecht()` setzt die richtigen Werte
 * schon beim Anlegen.
 */
class GeschlechtMigration extends AbstractMigration
{
	/**
	 * Alter Wert (kleingeschrieben) zu neuem Wert.
	 *
	 * `w` steht mit drin, weil die deutschsprachigen Bestände „w" für weiblich
	 * führen und nicht das englische „f"; `d` für divers taucht in den neueren
	 * Mitgliederdaten auf.
	 */
	private const ZUORDNUNG = [
		'm' => 'male',
		'f' => 'female',
		'w' => 'female',
		'd' => 'other',
	];

	/**
	 * @var Connection
	 */
	private $connection;

	/**
	 * @param Connection $connection Datenbankverbindung aus dem Container
	 */
	public function __construct(Connection $connection)
	{
		$this->connection = $connection;
	}

	/**
	 * Nennt die Umschreibung beim Namen, damit im Ergebnis von
	 * `contao:migrate` zu sehen ist, was gelaufen ist.
	 *
	 * @return string Bezeichnung für die Ausgabe
	 */
	public function getName(): string
	{
		return 'Topwertungszahlen: Geschlecht von m/f auf male/female umstellen';
	}

	/**
	 * Meldet, ob es überhaupt etwas umzuschreiben gibt.
	 *
	 * Geprüft wird in drei Stufen, weil die Migration auch auf einer
	 * Installation läuft, in der das Bundle gerade erst eingespielt wurde:
	 * Erst muß die Tabelle da sein, dann die Spalte, dann mindestens ein
	 * Datensatz mit einem alten Kürzel.
	 *
	 * @return bool true, wenn run() etwas zu tun hat
	 */
	public function shouldRun(): bool
	{
		if ([] === $this->connection->fetchFirstColumn("SHOW TABLES LIKE 'tl_topwertungszahlen'")) {
			return false;
		}

		if ([] === $this->connection->fetchFirstColumn("SHOW COLUMNS FROM tl_topwertungszahlen LIKE 'geschlecht'")) {
			return false;
		}

		$anzahl = $this->connection->fetchOne(
			'SELECT COUNT(*) FROM tl_topwertungszahlen WHERE LOWER(geschlecht) IN (?, ?, ?, ?)',
			array_keys(self::ZUORDNUNG)
		);

		return (int) $anzahl > 0;
	}

	/**
	 * Schreibt die Kürzel um.
	 *
	 * Ein UPDATE je Kürzel statt eines CASE-Ausdrucks: Das sind vier Abfragen
	 * auf eine Tabelle mit wenigen tausend Zeilen, und die Meldung kann so
	 * die Gesamtzahl der geänderten Datensätze nennen.
	 *
	 * @return MigrationResult Immer erfolgreich; die Meldung nennt die Anzahl
	 */
	public function run(): MigrationResult
	{
		$geaendert = 0;

		foreach (self::ZUORDNUNG as $alt => $neu) {
			$geaendert += (int) $this->connection->executeStatement(
				'UPDATE tl_topwertungszahlen SET geschlecht = ? WHERE LOWER(geschlecht) = ?',
				[$neu, $alt]
			);
		}

		return $this->createResult(
			true,
			sprintf('Bei %d Spielern wurde das Geschlecht auf die Werte des Auswahlfeldes umgestellt.', $geaendert)
		);
	}
}
