<?php

/**
 * Konfiguration des Bundles: Backend-Modul und dessen Schlüssel-Rückrufe.
 *
 * Hier stand bis Version 1.9.0 die Zeile
 * `if (!defined('TL_ROOT')) die('You cannot access this file directly!');`.
 * Die Konstante gibt es in Contao 5 nicht mehr — die Datei hätte sich dort
 * beim Laden kommentarlos beendet, und das Backend-Modul wäre spurlos
 * verschwunden. Einen Schutz braucht sie ohnehin nicht: Sie liegt unterhalb
 * von `src/` und ist über das Web nicht erreichbar.
 */

$GLOBALS['BE_MOD']['content']['topwertungszahlen'] = array
(
	'tables'            => array('tl_topwertungszahlen', 'tl_topwertungszahlen_ratings', 'tl_topwertungszahlen_photos'),
	// Beide Schlüssel führen in dieselbe Maske; sie unterscheidet die
	// Betriebsart am Schlüssel und arbeitet entweder alle 21 Schritte ab oder
	// nur den einen, der die Fotos auffrischt
	'importRating'      => array('Schachbulle\ContaoTopwertungszahlenBundle\Classes\Import', 'run'),
	'importPhotos'      => array('Schachbulle\ContaoTopwertungszahlenBundle\Classes\Import', 'run')
);

/**
 * Frontend-Module
 */
//$GLOBALS['FE_MOD']['application']['trainerlizenzen'] = '\Schachbulle\ContaoTrainerlizenzenBundle\Classes\Trainerliste';
