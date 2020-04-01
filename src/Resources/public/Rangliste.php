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
		
			//$this->getRangliste_DWZ($client);
			$this->getRangliste_Elo($client);
		
		}
		catch (SOAPFault $f) {
			print $f->faultstring;
		}
	}
	
	private function getRangliste_DWZ($client)
	{
		$top = 50; // Anzahl der deutschen Spieler, die benötigt werden
		$listendatum = date('Ymd'); // Datum der Liste als JJJJMMTT (DWZ täglich)
		$ausgabeArr = array(); // Die Ranglisten werden hier als Array gespeichert
		$startzeit = microtime(true); // Startzeit

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
							'link'       => 'spieler/'.$m->pid.'.html',
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

		// JSON schreiben
		$fp = fopen('dwz.json', 'w');
		fputs($fp, json_encode($ausgabeArr));
		fclose($fp);

		$stopzeit = microtime(true); // Stopzeit
		$laufzeit = $stopzeit-$startzeit; // Berechnung
		$laufzeit = str_replace(".", ",", $laufzeit); // Trennzeichen ersetzen
		echo "Abfragezeit: $laufzeit Sekunden<br>\n";
		echo "<pre>";
		print_r($ausgabeArr);
		echo "</pre>";
		
	}

	private function getRangliste_Elo($client)
	{
		/* Funktionsweise
		 * ==============
		 * 1. Listentyp
		 * 2. Jeweilige Liste aus tl_elo laden
		 * 3. Nach Spieler in DeWIS suchen, um DeWIS-ID zu ermitteln
		 * 4. Zuordnung des Spielers zu tl_topwertungszahlen
		 * 4.1. Ratingplatz eintragen in tl_topwertungszahlen_ratings
		 */

		// Aktuelle Elo-Liste ermitteln
		$objActiv = \Database::getInstance()->prepare('SELECT * FROM tl_elo_listen WHERE published=? ORDER BY datum DESC')
		                                    ->limit(1)
		                                    ->execute(1);

		// Listentypen abarbeiten
		for($x = 0; $x < 6; $x++)
		{
			switch($x)
			{
				case 0: // Alle Spieler
					$sql = 'ORDER BY rating DESC';
					break;
				case 1: // Alle Frauen
					$sql = 'AND sex=\'F\' ORDER BY rating DESC';
					break;
				case 2: // Alle U20
					$jahr = date('Y') - 20;
					$sql = 'AND birthday>='.$jahr.' ORDER BY rating DESC';
					break;
				case 3: // Alle U20w
					$sql = 'AND birthday>='.$jahr.' AND sex=\'F\' ORDER BY rating DESC';
					break;
				case 4: // Alle Ü60
					$jahr = date('Y') - 60;
					$sql = 'AND birthday<='.$jahr.' ORDER BY rating DESC';
					break;
				case 5: // Alle Ü60w
					$sql = 'AND birthday<='.$jahr.' AND sex=\'F\' ORDER BY rating DESC';
					break;
				default:
			}

			// Elo-Liste laden
			$objElo = \Database::getInstance()->prepare('SELECT * FROM tl_elo WHERE pid=? AND published=? AND flag NOT LIKE ? '.$sql)
			                                  ->limit(10)
			                                  ->execute($objActiv->id, 1, '%i%');

			if($objElo->numRows)
			{
				// Spieler in DeWIS suchen
				$result = $client->searchByName($objElo->surname, $objElo->prename);
				if($result->members)
				{
					// Spieler in DeWIS wahrscheinlich gefunden, jetzt die Treffer prüfen
					foreach($result->members as $m)
					{
						//[pid] => 10018254
						//[surname] => Blübaum
						//[firstname] => Matthias
						//[title] => 
						//[gender] => m
						//[yearOfBirth] => 1997
						//[membership] => 1101
						//[vkz] => C0303
						//[club] => SF Deizisau
						//[state] => 
						//[rating] => 2643
						//[ratingIndex] => 174
						//[tcode] => B944-K00-EOP
						//[finishedOn] => 2019-11-02
						//[idfide] => 24651516
						//[nationfide] => GER
						//[elo] => 2646
						//[fideTitle] => GM
						echo $m->surname;
						echo $m->firstname;
					}
				}
			}

		}

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
