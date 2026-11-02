<?php

declare(strict_types=1);

namespace Schachbulle\ContaoTopwertungszahlenBundle\Helper;

/**
 * Zugriff auf Spielerfotos, der einen verwaisten Dateiverweis aushält.
 *
 * Ein Fotodatensatz in `tl_topwertungszahlen_photos` zeigt über `singleSRC`
 * auf einen Eintrag der Dateiverwaltung. Dieser Verweis kann auf zwei Arten
 * ins Leere gehen:
 *
 * 1. Der Eintrag in `tl_files` fehlt. Dann liefert `FilesModel` ein `null`,
 *    und ein ungeprüfter Zugriff auf `->path` erzeugt unter PHP 8 die Warnung
 *    „Attempt to read property "path" on null" — einmal je Zeile der Liste.
 * 2. Der Eintrag ist da, die Datei auf der Platte nicht. Dann schreibt die
 *    Bilderzeugung „Image … could not be processed" ins Systemprotokoll,
 *    ebenfalls bei jedem Aufruf.
 *
 * Beide Fälle liefern hier einen leeren String und keinen Protokolleintrag.
 * Der Aufrufer entscheidet, was er anzeigt — in einer Redaktionsliste
 * sinnvollerweise einen Hinweis, damit der verwaiste Datensatz auffällt.
 *
 * Erzeugt wird das Vorschaubild über `contao.image.factory` statt über
 * `Image::get()`: Die statische Methode gibt es in Contao 5 nicht mehr.
 */
class Bilder
{
	/**
	 * Eine leere UUID, wie Contao sie in ein `binary(16)`-Feld schreibt.
	 *
	 * Ein solcher Wert ist kein Verweis, sondern das Gegenteil davon. Ohne
	 * diese Vorabprüfung liefe für jeden Datensatz ohne Foto eine
	 * Datenbankabfrage ins Leere.
	 */
	private const LEERE_UUID = "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00";

	/**
	 * Gibt den Pfad einer Bilddatei zurück, wenn sie wirklich vorhanden ist.
	 *
	 * Gemeint ist der Pfad des Originals relativ zum Projektverzeichnis, so
	 * wie ihn die Dateiverwaltung führt (`files/…`). Er wandert unverändert in
	 * die Ausgabedateien des Ranglistenlaufs.
	 *
	 * @param mixed $uuid Wert des Feldes `singleSRC`: eine binäre UUID, eine
	 *                    UUID in Textform, null oder ein leerer Wert
	 *
	 * @return string Pfad relativ zum Projektverzeichnis, oder ein leerer
	 *                String, wenn der Verweis leer ist, der Eintrag in der
	 *                Dateiverwaltung fehlt oder die Datei nicht auf der Platte
	 *                liegt
	 */
	public static function pfad($uuid): string
	{
		$objFile = self::datei($uuid);

		return null === $objFile ? '' : (string) $objFile->path;
	}

	/**
	 * Erzeugt ein quadratisch beschnittenes Vorschaubild und gibt dessen URL
	 * zurück.
	 *
	 * Gedacht für die Backend-Listen, in denen das Foto neben dem Datensatz
	 * steht. Die Erzeugung läuft über die Bildfabrik des Containers, weil die
	 * früher benutzte statische Methode `Image::get()` in Contao 5 entfallen
	 * ist.
	 *
	 * Seiteneffekt: Beim ersten Aufruf legt Contao das verkleinerte Bild unter
	 * `assets/images` ab. Schlägt das fehl — fehlende Schreibrechte, kaputte
	 * Bilddatei —, wird die Ausnahme geschluckt und ein leerer String
	 * zurückgegeben. Eine Redaktionsliste soll wegen eines unbrauchbaren
	 * Fotos nicht mit einem Fehler abbrechen.
	 *
	 * @param mixed $uuid  Wert des Feldes `singleSRC`, siehe pfad()
	 * @param int   $kante Kantenlänge des Vorschaubildes in Pixeln
	 *
	 * @return string URL des Vorschaubildes, oder ein leerer String, wenn kein
	 *                brauchbares Bild dahintersteht
	 */
	public static function vorschau($uuid, int $kante): string
	{
		$objFile = self::datei($uuid);

		if (null === $objFile) {
			return '';
		}

		try {
			$wurzel = \Contao\System::getContainer()->getParameter('kernel.project_dir');

			return (string) \Contao\System::getContainer()
				->get('contao.image.factory')
				->create($wurzel.'/'.$objFile->path, [$kante, $kante, 'crop'])
				->getUrl($wurzel)
			;
		} catch (\Throwable $e) {
			return '';
		}
	}

	/**
	 * Sucht den Eintrag der Dateiverwaltung und prüft, ob die Datei existiert.
	 *
	 * Gemeinsamer Unterbau von pfad() und vorschau(). Die Prüfung auf die
	 * tatsächlich vorhandene Datei gehört hierher und nicht in die Aufrufer:
	 * Ein Eintrag in `tl_files` ohne Datei dahinter ist in der Praxis der
	 * häufigere Fall, weil ein Löschen per FTP die Dateiverwaltung nicht
	 * mitnimmt.
	 *
	 * @param mixed $uuid Wert des Feldes `singleSRC`
	 *
	 * @return \Contao\FilesModel|null Das Modell, oder null in allen
	 *                                 Fehlerfällen
	 */
	private static function datei($uuid): ?\Contao\FilesModel
	{
		if (empty($uuid) || self::LEERE_UUID === $uuid) {
			return null;
		}

		try {
			$objFile = \Contao\FilesModel::findByUuid($uuid);

			if (null === $objFile || '' === (string) $objFile->path) {
				return null;
			}

			$wurzel = \Contao\System::getContainer()->getParameter('kernel.project_dir');

			return is_file($wurzel.'/'.$objFile->path) ? $objFile : null;
		} catch (\Throwable $e) {
			return null;
		}
	}
}
