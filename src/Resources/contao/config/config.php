<?php if (!defined('TL_ROOT')) die('You cannot access this file directly!');

/**
 * Contao Open Source CMS
 *
 * Copyright (C) 2005-2013 Leo Feyer
 *
 * @package   bdf
 * @author    Frank Hoppe
 * @license   GNU/LGPL
 * @copyright Frank Hoppe 2014
 */

$GLOBALS['BE_MOD']['content']['topwertungszahlen'] = array
(
	'tables'            => array('tl_topwertungszahlen', 'tl_topwertungszahlen_ratings', 'tl_topwertungszahlen_photos'),
	'importRating'      => array('Schachbulle\ContaoTopwertungszahlenBundle\Classes\Import', 'run')
);

/**
 * Frontend-Module
 */
//$GLOBALS['FE_MOD']['application']['trainerlizenzen'] = '\Schachbulle\ContaoTrainerlizenzenBundle\Classes\Trainerliste';
