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
define('TL_SCRIPT', 'bundles/contaotopwertungszahlen/Rangliste.php');
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
		$top = 50; // Anzahl der deutschen Spieler, die benötigt werden
		$listendatum = date('Ymd'); // Datum der Liste als JJJJMMTT (DWZ täglich)
		$ausgabeArr = array(); // Die Ranglisten werden hier als Array gespeichert

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
					//[pid] => 10266090
					//[surname] => Caruana
					//[firstname] => Fabiano
					//[title] => 
					//[gender] => m
					//[yearOfBirth] => 1992
					//[idfide] => 2020009
					//[fideNation] => USA
					//[elo] => 2842
					//[fideTitle] => GM
					//[rating] => 2859
					//[ratingIndex] => 135
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
						// Spieler schon vorhanden, dann nur die ID merken
						$id = $player->id;
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
							'geschlecht' => $m->gender,
							'dewis_id'   => $m->pid,
							'published'  => 1
						);
						$objInsert = \Database::getInstance()->prepare("INSERT INTO tl_topwertungszahlen %s")
						                                     ->set($set)
						                                     ->execute();
						$id = $objInsert->insertId; // Zugeordnete ID merken
					}

					// Wertungszahl und Rang eintragen
					// Zuerst prüfen, ob es einen Eintrag bereits gibt
					$rating = \Database::getInstance()->prepare("SELECT * FROM tl_topwertungszahlen_ratings WHERE pid=? AND type=? AND date=?")
					                                  ->limit(1)
					                                  ->execute($id, $liste, $listendatum);
					if($rating->numRows)
					{
						// Eintrag schon vorhanden, ggfs. aktualisieren
					}
					else
					{
						// Eintrag nicht vorhanden
						$set = array
						(
							'pid'          => $id,
							'tstamp'       => time(),
							'type'         => $liste,
							'date'         => $listendatum,
							'rank'         => $i,
							'rating'       => $m->rating,
							'rating_info'  => '',
							'rating_id'    => '',
							'fide_title'   => '',
							'fide_title_w' => '',
							'published'    => 1
						);
						$objInsert = \Database::getInstance()->prepare("INSERT INTO tl_topwertungszahlen_ratings %s")
						                                     ->set($set)
						                                     ->execute();
					}

					// Ausgabe schreiben, wenn Top-10-Spieler
					if($i < 11)
					{
						// Aktuelles Spielerfoto ermitteln
						$foto = \Database::getInstance()->prepare("SELECT * FROM tl_topwertungszahlen_photos WHERE pid=? AND date>=? ORDER BY date DESC")
						                                ->limit(1)
						                                ->execute($id, $listendatum);
						if($foto->numRows)
						{
							// Foto vorhanden
							$bildid = $foto->singleSRC;
							$objFile = \FilesModel::findByPk($foto->singleSRC);
							$bildid = $objFile->path;
						}
						else
						{
							// Kein Foto vorhanden
							$bildid = '';
						}
						$ausgabeArr[$liste][$i] = array
						(
							'vorname'    => $m->firstname,
							'nachname'   => $m->surname,
							'dewis_id'   => $m->pid,
							'rating'     => $m->rating,
							'foto'       => $bildid
						);
					}

					if($i == $top) break; // Abbrechen, wenn Anzahl der Topspieler gefunden sind
				}
			}
			// Ausgabe
			echo "Liste Top-$top: $liste (Abfrage $anzahl Spieler) - $j Spieler getestet - $i Deutsche gefunden<br>\n";
		}
		fclose($fp);
		$stopzeit = microtime(true); // Stopzeit
		$laufzeit = $stopzeit-$startzeit; // Berechnung
		$laufzeit = str_replace(".", ",", $laufzeit); // Trennzeichen ersetzen
		echo "Abfragezeit: $laufzeit Sekunden<br>\n";
		echo "<pre>";
		print_r($ausgabeArr);
		echo "</pre>";
		
		// JSON schreiben
		$fp = fopen('dwz.json', 'w');
		fputs($fp, json_encode($ausgabeArr));
		fclose($fp);
	}
}

/**
 * Instantiate controller
 */
$objClick = new Rangliste();
$objClick->run();
