<?php

namespace Schachbulle\ContaoTopwertungszahlenBundle\Classes;

if (!defined('TL_ROOT')) die('You cannot access this file directly!');

/**
 * Class dsb_trainerlizenzImport
  */
class Import extends \Backend
{

	function __construct()
	{
	}

	/**
	 * Exportiert alle noch nicht übertragenen Lizenzen zum DOSB
	 */
	public function run()
	{

		// jQuery einbinden
		$GLOBALS['TL_JAVASCRIPT'][] = 'bundles/contaotopwertungszahlen/js/jquery-3.5.1.min.js';

		$content .= '<div id="rating_import" style="margin:10px;"><img src="bundles/contaotopwertungszahlen/images/ajax-loader.gif"></div>';

		// Zurücklink generieren
		$backlink = str_replace('&key=importRating', '', \Environment::get('request'));
		$content .= '<div style="margin:10px;"><a href="'.$backlink.'">Zurück</a> | Alternativ: <a href="bundles/contaotopwertungszahlen/Rangliste.php" target="_blank">Rangliste.php im neuen Fenster ausführen</div>';

		$content .= '<script>'."\n";
		$content .= '$.ajax({'."\n";
		$content .= '  url: "bundles/contaotopwertungszahlen/Rangliste.php",'."\n";
		$content .= '  cache: false,'."\n";
		$content .= '  success: function(response) {'."\n";
		$content .= '    $("#rating_import").html(response);'."\n";
		$content .= '  }'."\n";
		$content .= '});'."\n";
		$content .= '</script>'."\n";

		return $content;
	}
}
