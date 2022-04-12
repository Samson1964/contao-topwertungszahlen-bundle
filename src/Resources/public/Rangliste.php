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
	public $verbandsname = array
	(
		'1' => 'BAD',
		'2' => 'BAY',
		'3' => 'BER',
		'4' => 'HAM',
		'5' => 'HES',
		'6' => 'NRW',
		'7' => 'NDS',
		'8' => 'RLP',
		'9' => 'SAR',
		'A' => 'SH',
		'B' => 'BRE',
		'C' => 'WÜR',
		'D' => 'BRA',
		'E' => 'MVP',
		'F' => 'SAC',
		'G' => 'SAA',
		'H' => 'THÜ',
		'L' => 'BSB',
		'M' => 'SWA'
	);

	public function __construct()
	{
	}

	public function run()
	{
		try
		{

			$context = stream_context_create([
				'ssl' => [
					'verify_peer' => false,
					'verify_peer_name' => false,
					'allow_self_signed' => true
				]
			]);

			$client = new \SOAPClient(
				NULL,
				array(
					'location'           => 'https://dwz.svw.info/services/soap/index.php',
					'uri'                => 'https://soap',
					'style'              => SOAP_RPC,
					'use'                => SOAP_ENCODED,
					'connection_timeout' => 15,
					'stream_context'     => $context // Entfernt am 21.02.2019 da svw.info meldete: Error Fetching http body, No Content-Length, connection closed or chunked data
					// Wieder aktiviert am 23.03.2021 weil die Schnittstelle meldete: Could not connect to host
				)
			);

			if(\Input::get('type') == 'dwz')
			{
				// DWZ-Liste ermitteln
				$this->getRangliste_DWZ($client, \Input::get('modus'));
				$alles = false;
			}
			elseif(\Input::get('type') == 'elo')
			{
				// Elo-Liste ermitteln
				$this->getRangliste_Elo($client, \Input::get('modus'));
				$alles = false;
			}
			else
			{
				// Cron-Modus, alles ermitteln
				for($x = 1; $x <= 10; $x++)
				{
					$this->getRangliste_DWZ($client, $x);
				}
				for($x = 1; $x <= 10; $x++)
				{
					$this->getRangliste_Elo($client, $x);
				}
				$alles = true;
			}

			// Endgültige Dateien schreiben, wenn letzte Liste erstellt
			if((\Input::get('type') == 'elo' && \Input::get('modus') == 10) || $alles)
			{
				echo "JSON-Dateien schreiben<br>\n";
				$datenDWZ = array();
				$datenELO = array();
				for($x = 1; $x <= 10; $x++)
				{
					$tempDWZ = unserialize(file_get_contents('dwz-'.$x.'.json'));
					$tempELO = unserialize(file_get_contents('elo-'.$x.'.json'));
					//print_r($tempDWZ);
					$datenDWZ = array_merge($datenDWZ, $tempDWZ);
					$datenELO = array_merge($datenELO, $tempELO);
					unlink('dwz-'.$x.'.json');
					unlink('elo-'.$x.'.json');
				}
				$fp = fopen('dwz.json', 'w');
				fputs($fp, json_encode($datenDWZ));
				fclose($fp);
				$fp = fopen('elo.json', 'w');
				fputs($fp, json_encode($datenELO));
				fclose($fp);
			}

		}
		catch (SOAPFault $f) {
			print $f->faultstring;
		}
	}

	private function getRangliste_DWZ($client, $modus)
	{
		$top = 50; // Anzahl der deutschen Spieler, die benötigt werden
		$listendatum = date('Ymd'); // Datum der Liste als JJJJMMTT (DWZ täglich)
		$ausgabeArr = array(); // Die Ranglisten werden hier als Array gespeichert
		$startzeit = microtime(true); // Startzeit
		$liste_pids = array(); // Nimmt die Spieler-IDs auf um Mehrfacheinträge eines Spielers zu verhindern

		for($x = $modus - 1; $x < $modus; $x++)
		{
			switch($x)
			{
				case 0: // Alle Spieler
					$anzahl = 1000; $von = 0; $bis = 120; $geschlecht = ''; $liste = 'dwz_alle';
					break;
				case 1: // Alle Frauen
					$anzahl = 500; $von = 0; $bis = 120; $geschlecht = 'f'; $liste = 'dwz_w';
					break;
				case 2: // Alle U20
					$anzahl = 1000; $von = 0; $bis = 20; $geschlecht = ''; $liste = 'dwz_u20';
					break;
				case 3: // Alle U20w
					$anzahl = 500; $von = 0; $bis = 20; $geschlecht = 'f'; $liste = 'dwz_u20w';
					break;
				case 4: // Alle Ü50
					$anzahl = 1000; $von = 50; $bis = 120; $geschlecht = ''; $liste = 'dwz_50+';
					break;
				case 5: // Alle Ü50w
					$anzahl = 500; $von = 50; $bis = 120; $geschlecht = 'f'; $liste = 'dwz_50w+';
					break;
				case 6: // Alle Ü65
					$anzahl = 1000; $von = 65; $bis = 120; $geschlecht = ''; $liste = 'dwz_65+';
					break;
				case 7: // Alle Ü65w
					$anzahl = 500; $von = 65; $bis = 120; $geschlecht = 'f'; $liste = 'dwz_65w+';
					break;
				case 8: // Alle Ü75
					$anzahl = 1000; $von = 75; $bis = 120; $geschlecht = ''; $liste = 'dwz_75+';
					break;
				case 9: // Alle Ü75w
					$anzahl = 500; $von = 75; $bis = 120; $geschlecht = 'f'; $liste = 'dwz_75w+';
					break;
				default:
			}

			// Abfrage starten
			$ratingList = $client->bestOfFederation("00000",$anzahl,$von,$bis,$geschlecht);
			$i = 0;
			$j = 0;
			$altplatz = 0; // Speichert den letzten ermittelten Platz
			$altrating = 0; // Speichert die letzte ermittelte Wertungszahl
			$liste_pids[$liste] = array();

			// Rückgabe nach Deutschen durchsuchen und in Datei speichern
			foreach($ratingList->members as $m)
			{
				$j++;
				$tcard = $client->tournamentCardForId($m->pid);
				if($tcard->member->fideNation == 'GER')
				{
					if(!in_array($m->pid, $liste_pids[$liste]))
					{
						$liste_pids[$liste][] = $m->pid; // Spieler-ID noch nicht in Liste, hier hinzufügen
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
								'fide_title'   => $m->fideTitle ? $m->fideTitle : '',
								'fide_title_w' => '',
								'association'  => $this->Verbandskuerzel($m->pid, $client),
								'published'    => 1
							);
							$objInsert = \Database::getInstance()->prepare("INSERT INTO tl_topwertungszahlen_ratings %s")
							                                     ->set($set)
							                                     ->execute();
						}

						//echo "Vorher : altplatz = $altplatz | i = $i | altrating = $altrating | m-rating = $m->rating<br>";

						if($altrating != $m->rating)
						{
							// Wertungszahl des vorhergehenden Spielers ungleich mit Wertungszahl vom aktuellen Spieler
							$altplatz = $i; // Platzziffer auf aktuellen Schleifenwert setzen
							$altrating = $m->rating; // Neue Wertungszahl zuweisen
						}

						//echo "Nachher: altplatz = $altplatz | i = $i | altrating = $altrating | m-rating = $m->rating<br>";

						// Ausgabe schreiben, wenn Top-10-Spieler
						if($altplatz < 11)
						{
							$fotoArr = $this->Spielerfoto($id, $listendatum); // Foto laden
							$ausgabeArr[$liste][] = array
							(
								'rang'       => $altplatz,
								'vorname'    => $m->firstname,
								'nachname'   => $m->surname,
								'verband'    => $this->Verband($m->pid, $client),
								'link'       => 'spieler/'.$m->pid.'.html',
								'rating'     => $m->rating,
								'title'      => $m->fideTitle,
								'foto'       => $fotoArr['pfad'],
								'quelle'     => $fotoArr['quelle']
							);
							//echo "Ausgabe: $m->surname, $m->firstname auf Platz $altplatz<br>";
						}

						if($i == $top) break; // Abbrechen, wenn Anzahl der Topspieler gefunden sind
					}
				}
			}
			// Ausgabe
			echo "Liste $modus/20 - Top-$top: <b>$liste</b> (Abfrage <b>$anzahl</b> Spieler) - <b>$j Spieler</b> getestet - <b>$i Deutsche</b> gefunden - ";
		}

		// JSON schreiben
		$fp = fopen('dwz-'.$modus.'.json', 'w');
		fputs($fp, serialize($ausgabeArr));
		fclose($fp);

		$stopzeit = microtime(true); // Stopzeit
		$laufzeit = $stopzeit-$startzeit; // Berechnung
		$laufzeit = str_replace(".", ",", sprintf('%.3f', $laufzeit)); // Trennzeichen ersetzen
		echo "Zeit: $laufzeit Sekunden<br>\n";
		//echo "<pre>";
		//print_r($ausgabeArr);
		//echo "</pre>";

	}

	private function getRangliste_Elo($client, $modus)
	{
		/* Funktionsweise
		 * ==============
		 * 1. Listentyp
		 * 2. Jeweilige Liste aus tl_elo laden
		 * 3. Nach Spieler in DeWIS suchen, um DeWIS-ID zu ermitteln
		 * 4. Zuordnung des Spielers zu tl_topwertungszahlen
		 * 4.1. Ratingplatz eintragen in tl_topwertungszahlen_ratings
		 */

		$startzeit = microtime(true); // Startzeit
		// Aktuelle Elo-Liste ermitteln
		$objActiv = \Database::getInstance()->prepare('SELECT * FROM tl_elo_listen WHERE published=? ORDER BY datum DESC')
		                                    ->limit(1)
		                                    ->execute(1);
		$listendatum = date('Ymd', $objActiv->datum);
		$ausgabeArr = array(); // Die Ranglisten werden hier als Array gespeichert
		$top = 50; // Anzahl der Spieler, die benötigt werden

		// Listentypen abarbeiten
		for($x = $modus - 1; $x < $modus; $x++)
		{
			switch($x)
			{
				case 0: // Alle Spieler
					$sql = 'ORDER BY rating DESC';
					$liste = 'elo_alle';
					break;
				case 1: // Alle Frauen
					$sql = 'AND sex=\'F\' ORDER BY rating DESC';
					$liste = 'elo_w';
					break;
				case 2: // Alle U20
					$jahr = date('Y') - 20;
					$sql = 'AND birthday>='.$jahr.' ORDER BY rating DESC';
					$liste = 'elo_u20';
					break;
				case 3: // Alle U20w
					$jahr = date('Y') - 20;
					$sql = 'AND birthday>='.$jahr.' AND sex=\'F\' ORDER BY rating DESC';
					$liste = 'elo_u20w';
					break;
				case 4: // Alle Ü50
					$jahr = date('Y') - 50;
					$sql = 'AND birthday<='.$jahr.' ORDER BY rating DESC';
					$liste = 'elo_50+';
					break;
				case 5: // Alle Ü50w
					$jahr = date('Y') - 50;
					$sql = 'AND birthday<='.$jahr.' AND sex=\'F\' ORDER BY rating DESC';
					$liste = 'elo_50w+';
					break;
				case 6: // Alle Ü65
					$jahr = date('Y') - 65;
					$sql = 'AND birthday<='.$jahr.' ORDER BY rating DESC';
					$liste = 'elo_65+';
					break;
				case 7: // Alle Ü65w
					$jahr = date('Y') - 65;
					$sql = 'AND birthday<='.$jahr.' AND sex=\'F\' ORDER BY rating DESC';
					$liste = 'elo_65w+';
					break;
				case 8: // Alle Ü75
					$jahr = date('Y') - 75;
					$sql = 'AND birthday<='.$jahr.' ORDER BY rating DESC';
					$liste = 'elo_75+';
					break;
				case 9: // Alle Ü75w
					$jahr = date('Y') - 75;
					$sql = 'AND birthday<='.$jahr.' AND sex=\'F\' ORDER BY rating DESC';
					$liste = 'elo_75w+';
					break;
				default:
			}

			// Elo-Liste laden
			$objElo = \Database::getInstance()->prepare('SELECT * FROM tl_elo WHERE pid=? AND published=? AND flag NOT LIKE ? '.$sql)
			                                  ->limit($top)
			                                  ->execute($objActiv->id, 1, '%i%');

			$rank = 0;
			$altplatz = 0; // Speichert den letzten ermittelten Platz
			$altrating = 0; // Speichert die letzte ermittelte Wertungszahl
			if($objElo->numRows)
			{
				// Elo-Rangliste Spieler für Spieler abarbeiten
				while($objElo->next())
				{
					$rank++;
					$result = $client->searchByName($objElo->surname, $objElo->prename); // Spieler in DeWIS suchen
					$found = FALSE; // Spieler in DeWIS auf "nicht gefunden" setzen
					// Suchergebnis prüfen
					if($result->members)
					{
						// Spieler in DeWIS wahrscheinlich gefunden, jetzt die Treffer prüfen
						foreach($result->members as $m)
						{
							// FIDE-ID vergleichen
							if($m->idfide == $objElo->fideid)
							{
								// Spieler gefunden
								$found = TRUE;
								break;
							}
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
						}
						if($found)
						{
							// Spieler gefunden, jetzt Datensatz aus tl_topwertungszahlen laden
							//echo "JA ".$m->surname.','.$m->firstname."<br>";
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
									'rank'         => $rank,
									'rating'       => $objElo->rating,
									'rating_info'  => '',
									'rating_id'    => $objElo->fideid,
									'fide_title'   => $objElo->title,
									'fide_title_w' => $objElo->w_title,
									'association'  => substr($m->vkz,0,1),
									'published'    => 1
								);
								$objInsert = \Database::getInstance()->prepare("INSERT INTO tl_topwertungszahlen_ratings %s")
								                                     ->set($set)
								                                     ->execute();
							}

							if($altrating != $objElo->rating)
							{
								// Wertungszahl des vorhergehenden Spielers ungleich mit Wertungszahl vom aktuellen Spieler
								$altplatz = $rank; // Platzziffer auf aktuellen Schleifenwert setzen
								$altrating = $objElo->rating; // Neue Wertungszahl zuweisen
							}

							// Ausgabe schreiben, wenn Top-10-Spieler
							if($altplatz < 11)
							{
								$fotoArr = $this->Spielerfoto($id, $listendatum); // Foto laden
								$ausgabeArr[$liste][] = array
								(
									'rang'       => $altplatz,
									'vorname'    => $m->firstname,
									'nachname'   => $m->surname,
									'verband'    => $this->verbandsname[substr($m->vkz,0,1)],
									'link'       => 'http://ratings.fide.com/profile/'.$m->idfide,
									'rating'     => $objElo->rating,
									'title'      => self::getFideTitel($objElo->title, $objElo->w_title),
									'foto'       => $fotoArr['pfad'],
									'quelle'     => $fotoArr['quelle']
								);
							}
						}
						else
						{
							//echo "NEIN ".$m->surname.','.$m->firstname."<br>";
						}
					}
				}
			}
			echo "Liste ".($modus+10)."/20 - Top-$top: <b>$liste</b> (Abfrage <b>$anzahl</b> Spieler) - <b>$j Spieler</b> getestet - <b>$i Deutsche</b> gefunden - ";
		}

		// JSON schreiben
		$fp = fopen('elo-'.$modus.'.json', 'w');
		fputs($fp, serialize($ausgabeArr));
		fclose($fp);

		$stopzeit = microtime(true); // Stopzeit
		$laufzeit = $stopzeit-$startzeit; // Berechnung
		$laufzeit = str_replace(".", ",", sprintf('%.3f', $laufzeit)); // Trennzeichen ersetzen
		echo "Zeit: $laufzeit Sekunden<br>\n";

		//echo "<pre>";
		//print_r($ausgabeArr);
		//echo "</pre>";

	}


	/**
	 * Ermittelt zu einem Spieler das aktuelle Foto und dessen Quelle
	 * ==============================================================
	 * param id           DeWIS-ID des Spielers
	 * param listendatum  Listendatum im Format JJJJMMTT
	 * return array       array('pfad', 'quelle')
	 */
	private function Spielerfoto($id, $listendatum)
	{
		// Aktuelles Spielerfoto ermitteln
		$foto = \Database::getInstance()->prepare("SELECT * FROM tl_topwertungszahlen_photos WHERE pid=? AND date<=? ORDER BY date DESC")
		                                ->limit(1)
		                                ->execute($id, $listendatum);
		if($foto->numRows)
		{
			// Foto vorhanden
			$bildid = $foto->singleSRC;
			$objFile = \FilesModel::findByPk($foto->singleSRC);
			$pfad = $objFile->path;
			// Metadaten laden und Quelle aus Bildunterschrift extrahieren (funktioniert nicht!)
			$meta = deserialize($objFile->meta);
			$caption = $meta['de']['caption']; // $GLOBALS['TL_LANGUAGE'] kann nicht benutzt werden, da dort "en" drinsteht
			//$quelle = $this->getCopyright($caption);
			$quelle = $foto->source; // Nur das funktioniert im Moment
		}
		else
		{
			// Kein Foto vorhanden
			$pfad = '';
			$quelle = '';
		}
		return array('pfad' => $pfad, 'quelle' => $quelle);
	}

	/**
	 * Holt aus der Bildunterschrift den String mit dem Copyright
	 * @param mixed
	 * @return mixed
	 */
	private function getCopyright($string)
	{
		static $begrenzer = array('[', ']');

		// Nach Copyright per Regex suchen
		$found = preg_match("/(\[.+\])/",$string,$treffer,PREG_OFFSET_CAPTURE);
		if($found)
		{
			$cpstr = str_replace($begrenzer, '', $treffer[0][0]); // Begrenzer entfernen und Copyright zurückgeben
		}
		else $cpstr = ''; // Kein Copyright

		return $cpstr;
	}

	/**
	 * Gibt den höherwertigeren FIDE-Titel zurück
	 * @param
	 * @return
	 */
	private function getFideTitel($titel1, $titel2 = false)
	{
		$maxwert = 0;
		$maxtitel = '';
		for($x = 0; $x < 2; $x++)
		{
			if($x == 0) $titel = $titel1;
			elseif($x == 1) $titel = $titel2;
			
			switch($titel)
			{
				case 'WCM': $wert = 1; break;
				case 'WFM': $wert = 2; break;
				case 'CM': $wert = 3; break;
				case 'WIM': $wert = 4; break;
				case 'FM': $wert = 5; break;
				case 'WGM': $wert = 6; break;
				case 'IM': $wert = 7; break;
				case 'GM': $wert = 8; break;
				default: $wert = 0;
			}

			if($wert > $maxwert)
			{
				$maxwert = $wert;
				$maxtitel = $titel;
			}

		}
		return $maxtitel;
	}


	/**
	 * Ermittelt zu einem Spieler den Landesverband
	 * ============================================
	 * param id       DeWIS-ID des Spielers
	 * return string  Kurzkennzeichen des Verbandes
	 */
	private function Verband($id, $client)
	{

		$tcard = $client->tournamentCardForId($id);
		// Mitgliedschaften prüfen
		foreach ($tcard->memberships as $m)
		{
			if($m->state == 'P') $temp = $m->vkz;
			else
			{
				$temp = $m->vkz;
				break;
			}
		}

		return $this->verbandsname[substr($temp,0,1)];
	}

	/**
	 * Ermittelt zu einem Spieler den Landesverband
	 * ============================================
	 * param id       DeWIS-ID des Spielers
	 * return string  Kurzkennzeichen des Verbandes
	 */
	private function Verbandskuerzel($id, $client)
	{

		$tcard = $client->tournamentCardForId($id);
		// Mitgliedschaften prüfen
		foreach ($tcard->memberships as $m)
		{
			if($m->state == 'P') $temp = $m->vkz;
			else
			{
				$temp = $m->vkz;
				break;
			}
		}

		return substr($temp,0,1);
	}

}

/**
 * Instantiate controller
 */
$objClick = new Rangliste();
$objClick->run();
