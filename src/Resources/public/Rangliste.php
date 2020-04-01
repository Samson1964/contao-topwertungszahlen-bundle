<?php
ini_set('display_errors', '1');
set_time_limit(0);

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
		try
		{
			$client = new \SOAPClient(
				NULL,
				array(
					'location'           => 'https://dwz.svw.info/services/soap/index.php',
					'uri'                => 'https://soap',
					'style'              => SOAP_RPC,
					'use'                => SOAP_ENCODED,
					'connection_timeout' => 15,
				)
			);
		
			$this->getRangliste($client);
		
		}
		catch (SOAPFault $f) {
			print $f->faultstring;
		}
	}
	
	private function getRangliste($client)
	{
		$top = 10;
		$startzeit = microtime(true); // Startzeit
		// Ausgabedatei
		$fp = fopen('rangliste.csv', 'w');
		fputs($fp, 'liste|nr|pid|surname|firstname|title|club|state|membership|rating|ratingIndex|idfide|elo|fideTitle'."\n");

		for($x = 0; $x < 6; $x++)
		{
			switch($x)
			{
				case 0: // Alle Spieler
					$anzahl = 1000; $von = 0; $bis = 120; $geschlecht = ''; $liste = 'alle';
					break;
				case 1: // Alle Frauen
					$anzahl = 500; $von = 0; $bis = 120; $geschlecht = 'f'; $liste = 'w';
					break;
				case 2: // Alle U20
					$anzahl = 1000; $von = 0; $bis = 20; $geschlecht = ''; $liste = 'u20';
					break;
				case 3: // Alle U20w
					$anzahl = 500; $von = 0; $bis = 20; $geschlecht = 'f'; $liste = 'u20w';
					break;
				case 4: // Alle Ü60
					$anzahl = 1000; $von = 60; $bis = 120; $geschlecht = ''; $liste = '60+';
					break;
				case 5: // Alle Ü60w
					$anzahl = 500; $von = 60; $bis = 120; $geschlecht = 'f'; $liste = '60w+';
					break;
				default:
			}

			// Abfrage starten
			$ratingList = $client->bestOfFederation("00000",$anzahl,$von,$bis,$geschlecht);
			$i = 0;
			$j = 0;

			// Rückgabe nach Deutschen durchsuchen und in Datei speichern
			foreach($ratingList->members as $m)
			{
				$j++;
				$tcard = $client->tournamentCardForId($m->pid);
				if($tcard->member->fideNation == 'GER')
				{
					$i++;
					fputs($fp, $liste.'|');
					fputs($fp, $i.'|');
					fputs($fp, $m->pid.'|');
					fputs($fp, $m->surname.'|');
					fputs($fp, $m->firstname.'|');
					fputs($fp, $m->title.'|');
					fputs($fp, $m->club.'|');
					fputs($fp, $m->state.'|');
					fputs($fp, $m->membership.'|');
					fputs($fp, $m->rating.'|');
					fputs($fp, $m->ratingIndex.'|');
					fputs($fp, $m->idfide.'|');
					fputs($fp, $m->elo.'|');
					fputs($fp, $m->fideTitle);
					fputs($fp, "\n");
					// Spieler in Contao eintragen
					// Zuerst prüfen, ob es den Spieler bereits gibt
					$player = \Database::getInstance()->prepare("SELECT * FROM tl_topwertungszahlen WHERE dewis_id=?")
					                                  ->limit(1)
					                                  ->execute($m->pid);
					if($player->numRows)
					{
						// Spieler schon vorhanden
					}
					else
					{
						// Spieler nicht vorhanden
						$set = array
						(
							'tstamp'     => time(),
							'vorname'    => $m->firstname,
							'nachname'   => $m->surname,
							'titel'      => $m->title,
							'geschlecht' => '',
							'dewis_id'   => $m->pid,
							'published'  => 1
						);
						$objInsert = \Database::getInstance()->prepare("INSERT INTO tl_topwertungszahlen %s")
						                                     ->set($set)
						                                     ->execute();
						//echo $objInsert->insertId;
					}

					if($i == $top) break; // Abbrechen, wenn Anzahl der Topspieler gefunden sind
				}
			}
			// Ausgabe
			echo "Liste: $liste - $j Spieler - $i Deutsche<br>\n";
		}
		fclose($fp);
		$stopzeit = microtime(true); // Stopzeit
		$laufzeit = $stopzeit-$startzeit; // Berechnung
		$laufzeit = str_replace(".", ",", $laufzeit); // Trennzeichen ersetzen
		echo "Abfragezeit: $laufzeit Sekunden<br>\n";
	}
}

/**
 * Instantiate controller
 */
$objClick = new Rangliste();
$objClick->run();
