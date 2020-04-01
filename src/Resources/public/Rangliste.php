<?php
ini_set('display_errors', '1');

/**
 * Contao Open Source CMS, Copyright (C) 2005-2013 Leo Feyer
 *
 */

/**
 * Run in a custom namespace, so the class can be replaced
 */
use Contao\Controller;

/**
 * Initialize the system
 */
define('TL_MODE', 'FE');
define('TL_SCRIPT', 'bundles/contaodwzranglisten/Rangliste.php');
require($_SERVER['DOCUMENT_ROOT'].'/../system/initialize.php');

/**
 * Class LinkSearch
 *
 */
class Rangliste
{
	public function __construct()
	{
	}

	public function run()
	{
		echo "Hallo";
	}
}

/**
 * Instantiate controller
 */
$objClick = new Rangliste();
$objClick->run();
