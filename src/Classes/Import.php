<?php

namespace Schachbulle\ContaoTopwertungszahlenBundle\Classes;

if(!defined('TL_ROOT')) die('You cannot access this file directly!');

/**
 * Class Import
  */
class Import extends \Backend
{

	function __construct()
	{
	}

	/**
	 * Importiert die Wertungszahlen
	 */
	public function run()
	{

		// jQuery einbinden
		$GLOBALS['TL_JAVASCRIPT'][] = 'bundles/contaotopwertungszahlen/js/jquery-3.5.1.min.js';

		$content = '<div id="rating_import" style="margin:10px;"></div>';
		$content .= '<div id="rating_import_status" style="margin:10px;"><img src="bundles/contaotopwertungszahlen/images/ajax-loader.gif"></div>';

		// Zurücklink generieren
		$backlink = str_replace('&key=importRating', '', \Environment::get('request'));
		$content .= '<div style="margin:10px;"><a href="'.$backlink.'">Zurück</a> | Alternativ: <a href="bundles/contaotopwertungszahlen/Rangliste.php" target="_blank">Rangliste.php im neuen Fenster ausführen</div>';

		$content .= '<script>'."\n";
		$content .= '$.ajax({'."\n";
		$content .= '  url: "bundles/contaotopwertungszahlen/Rangliste.php?modus=1&type=dwz",'."\n";
		$content .= '  cache: false,'."\n";
		$content .= '  success: function(response) {'."\n";
		$content .= '    $("#rating_import").append(response);'."\n";
		$content .= '    $.ajax({'."\n";
		$content .= '      url: "bundles/contaotopwertungszahlen/Rangliste.php?modus=2&type=dwz",'."\n";
		$content .= '      cache: false,'."\n";
		$content .= '      success: function(response) {'."\n";
		$content .= '        $("#rating_import").append(response);'."\n";
		$content .= '        $.ajax({'."\n";
		$content .= '          url: "bundles/contaotopwertungszahlen/Rangliste.php?modus=3&type=dwz",'."\n";
		$content .= '          cache: false,'."\n";
		$content .= '          success: function(response) {'."\n";
		$content .= '            $("#rating_import").append(response);'."\n";
		$content .= '            $.ajax({'."\n";
		$content .= '              url: "bundles/contaotopwertungszahlen/Rangliste.php?modus=4&type=dwz",'."\n";
		$content .= '              cache: false,'."\n";
		$content .= '              success: function(response) {'."\n";
		$content .= '                $("#rating_import").append(response);'."\n";
		$content .= '                $.ajax({'."\n";
		$content .= '                  url: "bundles/contaotopwertungszahlen/Rangliste.php?modus=5&type=dwz",'."\n";
		$content .= '                  cache: false,'."\n";
		$content .= '                  success: function(response) {'."\n";
		$content .= '                    $("#rating_import").append(response);'."\n";
		$content .= '                    $.ajax({'."\n";
		$content .= '                      url: "bundles/contaotopwertungszahlen/Rangliste.php?modus=6&type=dwz",'."\n";
		$content .= '                      cache: false,'."\n";
		$content .= '                      success: function(response) {'."\n";
		$content .= '                        $("#rating_import").append(response);'."\n";

		$content .= '                    $.ajax({'."\n";
		$content .= '                      url: "bundles/contaotopwertungszahlen/Rangliste.php?modus=7&type=dwz",'."\n";
		$content .= '                      cache: false,'."\n";
		$content .= '                      success: function(response) {'."\n";
		$content .= '                        $("#rating_import").append(response);'."\n";
		$content .= '                    $.ajax({'."\n";
		$content .= '                      url: "bundles/contaotopwertungszahlen/Rangliste.php?modus=8&type=dwz",'."\n";
		$content .= '                      cache: false,'."\n";
		$content .= '                      success: function(response) {'."\n";
		$content .= '                        $("#rating_import").append(response);'."\n";
		$content .= '                    $.ajax({'."\n";
		$content .= '                      url: "bundles/contaotopwertungszahlen/Rangliste.php?modus=9&type=dwz",'."\n";
		$content .= '                      cache: false,'."\n";
		$content .= '                      success: function(response) {'."\n";
		$content .= '                        $("#rating_import").append(response);'."\n";
		$content .= '                    $.ajax({'."\n";
		$content .= '                      url: "bundles/contaotopwertungszahlen/Rangliste.php?modus=10&type=dwz",'."\n";
		$content .= '                      cache: false,'."\n";
		$content .= '                      success: function(response) {'."\n";
		$content .= '                        $("#rating_import").append(response);'."\n";

		$content .= '                        $.ajax({'."\n";
		$content .= '                          url: "bundles/contaotopwertungszahlen/Rangliste.php?modus=1&type=elo",'."\n";
		$content .= '                          cache: false,'."\n";
		$content .= '                          success: function(response) {'."\n";
		$content .= '                            $("#rating_import").append(response);'."\n";
		$content .= '                            $.ajax({'."\n";
		$content .= '                              url: "bundles/contaotopwertungszahlen/Rangliste.php?modus=2&type=elo",'."\n";
		$content .= '                              cache: false,'."\n";
		$content .= '                              success: function(response) {'."\n";
		$content .= '                                $("#rating_import").append(response);'."\n";
		$content .= '                                $.ajax({'."\n";
		$content .= '                                  url: "bundles/contaotopwertungszahlen/Rangliste.php?modus=3&type=elo",'."\n";
		$content .= '                                  cache: false,'."\n";
		$content .= '                                  success: function(response) {'."\n";
		$content .= '                                    $("#rating_import").append(response);'."\n";
		$content .= '                                    $.ajax({'."\n";
		$content .= '                                      url: "bundles/contaotopwertungszahlen/Rangliste.php?modus=4&type=elo",'."\n";
		$content .= '                                      cache: false,'."\n";
		$content .= '                                      success: function(response) {'."\n";
		$content .= '                                        $("#rating_import").append(response);'."\n";
		$content .= '                                        $.ajax({'."\n";
		$content .= '                                          url: "bundles/contaotopwertungszahlen/Rangliste.php?modus=5&type=elo",'."\n";
		$content .= '                                          cache: false,'."\n";
		$content .= '                                          success: function(response) {'."\n";
		$content .= '                                            $("#rating_import").append(response);'."\n";
		$content .= '                                            $.ajax({'."\n";
		$content .= '                                              url: "bundles/contaotopwertungszahlen/Rangliste.php?modus=6&type=elo",'."\n";
		$content .= '                                              cache: false,'."\n";
		$content .= '                                              success: function(response) {'."\n";
		$content .= '                                                $("#rating_import").append(response);'."\n";

		$content .= '                                            $.ajax({'."\n";
		$content .= '                                              url: "bundles/contaotopwertungszahlen/Rangliste.php?modus=7&type=elo",'."\n";
		$content .= '                                              cache: false,'."\n";
		$content .= '                                              success: function(response) {'."\n";
		$content .= '                                                $("#rating_import").append(response);'."\n";
		$content .= '                                            $.ajax({'."\n";
		$content .= '                                              url: "bundles/contaotopwertungszahlen/Rangliste.php?modus=8&type=elo",'."\n";
		$content .= '                                              cache: false,'."\n";
		$content .= '                                              success: function(response) {'."\n";
		$content .= '                                                $("#rating_import").append(response);'."\n";
		$content .= '                                            $.ajax({'."\n";
		$content .= '                                              url: "bundles/contaotopwertungszahlen/Rangliste.php?modus=9&type=elo",'."\n";
		$content .= '                                              cache: false,'."\n";
		$content .= '                                              success: function(response) {'."\n";
		$content .= '                                                $("#rating_import").append(response);'."\n";
		$content .= '                                            $.ajax({'."\n";
		$content .= '                                              url: "bundles/contaotopwertungszahlen/Rangliste.php?modus=10&type=elo",'."\n";
		$content .= '                                              cache: false,'."\n";
		$content .= '                                              success: function(response) {'."\n";
		$content .= '                                                $("#rating_import").append(response);'."\n";

		$content .= '                                                $("#rating_import_status").html("<b>Fertig</b>");'."\n";

		$content .= '                                              }'."\n";
		$content .= '                                            });'."\n";
		$content .= '                                              }'."\n";
		$content .= '                                            });'."\n";
		$content .= '                                              }'."\n";
		$content .= '                                            });'."\n";
		$content .= '                                              }'."\n";
		$content .= '                                            });'."\n";
		$content .= '                                              }'."\n";
		$content .= '                                            });'."\n";
		$content .= '                                              }'."\n";
		$content .= '                                            });'."\n";
		$content .= '                                              }'."\n";
		$content .= '                                            });'."\n";
		$content .= '                                              }'."\n";
		$content .= '                                            });'."\n";

		$content .= '                                              }'."\n";
		$content .= '                                            });'."\n";
		$content .= '                                          }'."\n";
		$content .= '                                        });'."\n";
		$content .= '                                      }'."\n";
		$content .= '                                    });'."\n";
		$content .= '                                  }'."\n";
		$content .= '                                });'."\n";
		$content .= '                              }'."\n";
		$content .= '                            });'."\n";
		$content .= '                          }'."\n";
		$content .= '                        });'."\n";
		$content .= '                      }'."\n";
		$content .= '                    });'."\n";
		$content .= '                  }'."\n";
		$content .= '                });'."\n";
		$content .= '              }'."\n";
		$content .= '            });'."\n";
		$content .= '          }'."\n";
		$content .= '        });'."\n";
		$content .= '      }'."\n";
		$content .= '    });'."\n";
		$content .= '  }'."\n";
		$content .= '});'."\n";
		$content .= '</script>'."\n";

		return $content;

	}
}
