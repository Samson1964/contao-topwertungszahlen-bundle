<?php

// Contao einbinden
define('TL_MODE', 'FE');
define('TL_SCRIPT', 'php/pagescount.php'); 
require($_SERVER['DOCUMENT_ROOT'].'../../system/initialize.php'); 

$maxplatz = 50;
// Aktualität nach Tagen und in Farben
$aktuell = array(
	999999 => '#C0C0C0',
	365 => '#C7A896',
	180 => '#FF7D5E',
	120 => '#FFB9A8',
	60 => '#FFD5CA',
	30 => '#C99D05',
	20 => '#B1BA14',
	14 => '#E1EA39',
	11 => '#F1F5A0',
	7 => '#91FF91',
	3 => '#00F400',
	0 => '#00F400'
);
$monatsnamen = array(
	1 => "Januar",
	2 => "Februar",
	3 => "M&auml;rz",
	4 => "April",
	5 => "Mai",
	6 => "Juni",
	7 => "Juli",
	8 => "August",
	9 => "September",
	10 => "Oktober",
	11 => "November",
	12 => "Dezember"
);

$aktzeit = time();
// Stunde, Minute, Sekunde, Monat, Tag, Jahr
//$aktzeit = mktime(0, 0, 0, 8, 4, 2014);
$akttag = date("j", $aktzeit); // 1 - 31 im aktuellen Monat

// Datum des gestrigen Tages laden
$gestern = explode(".",date("Y.n.j",$gesternzeit = strtotime("-1 day", $aktzeit)));
// Vormonat ermitteln
$vormonat = explode(".",date("Y.n", $vormonat_tstamp = strtotime("-1 months", mktime(0,0,0,date("n",$aktzeit),1,date("Y",$aktzeit)))));
// Vorjahr ermitteln
$vorjahr = date("Y",$aktzeit) - 1;
//$von = mktime(0, 0, 0, $gestern[1], $gestern[2], $gestern[0]);
//$bis = mktime(23, 59, 59, $gestern[1], $gestern[2], $gestern[0]);

// Montag - Sonntag letzte Woche ermitteln (0 = Sonntag, 6 = Samstag)
$wochentag_heute = date('w', $aktzeit);
$plus = array(6, 5, 4, 3, 2, 1, 0);
$wert = $wochentag_heute + $plus[0];
$montag = explode(".",date("Y.n.j", $montagdatum = strtotime("-".$wert." day", $aktzeit)));
$wert = $wochentag_heute + $plus[1];
$dienstag = explode(".",date("Y.n.j", strtotime("-".$wert." day", $aktzeit)));
$wert = $wochentag_heute + $plus[2];
$mittwoch = explode(".",date("Y.n.j", strtotime("-".$wert." day", $aktzeit)));
$wert = $wochentag_heute + $plus[3];
$donnerstag = explode(".",date("Y.n.j", strtotime("-".$wert." day", $aktzeit)));
$wert = $wochentag_heute + $plus[4];
$freitag = explode(".",date("Y.n.j", strtotime("-".$wert." day", $aktzeit)));
$wert = $wochentag_heute + $plus[5];
$samstag = explode(".",date("Y.n.j", strtotime("-".$wert." day", $aktzeit)));
$wert = $wochentag_heute + $plus[6];
$sonntag = explode(".",date("Y.n.j", $sonntagdatum = strtotime("-".$wert." day", $aktzeit)));

// Zähler für Seiten einlesen
$ergebnis = \Database::getInstance()->prepare("SELECT * FROM tl_fh_counter WHERE source='tl_page'")
									->execute();
if($ergebnis->numRows)
{
	while($ergebnis->next())
	{

		$zaehler_array = unserialize($ergebnis->counter);

		// Seiten für gestern
		$zaehler_gestern = $zaehler_array[$gestern[0]][$gestern[1]][$gestern[2]]['all'];
		if($zaehler_gestern)
		{
			$page = \Database::getInstance()->prepare("SELECT * FROM tl_page WHERE id=?")
											->execute($ergebnis->pid);
			// tl_news nach Details abfragen
			$gestern_zaehler[] = $zaehler_gestern;
			$gestern_alias[] = $page->alias;
			$gestern_titel[] = $page->pageTitle ? $page->pageTitle : $page->title;
			$gestern_datum[] = date("d.m.Y H:i",$page->tstamp);
			$tage_abstand = sprintf("%01d",($aktzeit - $page->tstamp) / 86400);
			foreach($aktuell as $key => $value)
			{
				if($tage_abstand >= $key)
				{
					$farbe = $value;
					break;
				}
			}
			$gestern_farbe[] = $farbe;
		}

		// Seiten letzte Woche
		if($wochentag_heute == 1) // Nur am Montag erstellen
		{
			$zaehler_woche = $zaehler_array[$montag[0]][$montag[1]][$montag[2]]['all'];
			$zaehler_woche += $zaehler_array[$dienstag[0]][$dienstag[1]][$dienstag[2]]['all'];
			$zaehler_woche += $zaehler_array[$mittwoch[0]][$mittwoch[1]][$mittwoch[2]]['all'];
			$zaehler_woche += $zaehler_array[$donnerstag[0]][$donnerstag[1]][$donnerstag[2]]['all'];
			$zaehler_woche += $zaehler_array[$freitag[0]][$freitag[1]][$freitag[2]]['all'];
			$zaehler_woche += $zaehler_array[$samstag[0]][$samstag[1]][$samstag[2]]['all'];
			$zaehler_woche += $zaehler_array[$sonntag[0]][$sonntag[1]][$sonntag[2]]['all'];
			if($zaehler_woche)
			{
				$page = \Database::getInstance()->prepare("SELECT * FROM tl_page WHERE id=?")
												->execute($ergebnis->pid);
				// tl_news nach Details abfragen
				$woche_zaehler[] = $zaehler_woche;
				$woche_alias[] = $page->alias;
				$woche_titel[] = $page->pageTitle ? $page->pageTitle : $page->title;
				$woche_datum[] = date("d.m.Y H:i",$page->tstamp);
				$tage_abstand = sprintf("%01d",($aktzeit - $page->tstamp) / 86400);
				foreach($aktuell as $key => $value)
				{
					if($tage_abstand >= $key)
					{
						$farbe = $value;
						break;
					}
				}
				$woche_farbe[] = $farbe;
			}
		}

		// Nachrichten für Vormonat
		$zaehler_vormonat = $zaehler_array[$vormonat[0]][$vormonat[1]]['all'];
		if($zaehler_vormonat)
		{
			$page = \Database::getInstance()->prepare("SELECT * FROM tl_page WHERE id=?")
											->execute($ergebnis->pid);
			// tl_news nach Details abfragen
			$vormonat_zaehler[] = $zaehler_vormonat;
			$vormonat_alias[] = $page->alias;
			$vormonat_titel[] = $page->pageTitle ? $page->pageTitle : $page->title;
			$vormonat_datum[] = date("d.m.Y H:i",$page->tstamp);
			$tage_abstand = sprintf("%01d",($aktzeit - $page->tstamp) / 86400);
			foreach($aktuell as $key => $value)
			{
				if($tage_abstand >= $key)
				{
					$farbe = $value;
					break;
				}
			}
			$vormonat_farbe[] = $farbe;
		}
	}
}

// Daten sortieren
array_multisort($gestern_zaehler, SORT_NUMERIC, SORT_DESC, $gestern_datum, $gestern_alias, $gestern_titel, $gestern_farbe);
if($wochentag_heute == 1) array_multisort($woche_zaehler, SORT_NUMERIC, SORT_DESC, $woche_datum, $woche_alias, $woche_titel, $woche_farbe);
if($akttag == 1) array_multisort($vormonat_zaehler, SORT_NUMERIC, SORT_DESC, $vormonat_datum, $vormonat_alias, $vormonat_titel, $vormonat_farbe);

// Daten gestern ausgeben als Tabelle
$titel = "Top-".$maxplatz." Seiten ".date("d.m.Y", $gesternzeit);
$hinweis = "<i><p>Die Zugriffe werden anhand der IP-Adressen der Besucher gezählt. Kehrt ein Besucher innerhalb von 10 Minuten wieder auf diese Seite zurück, wird er NICHT neu gezählt. Je geringer die Speicherzeit ist, desto höher ist die Besucherzahl.</p><p>Das Alter der Seiten (nach dem Aktualisierungsdatum) ist farblich gekennzeichnet von grün (ganz aktuell) über gelb bis rot und grau (älter als 1 Jahr).</p></i>\n<p><b>Diese E-Mail wurde automatisch generiert!</b></p>";
$css = "<style type=\"text/css\">\n";
$css .= "* {font-family:Verdana,Calibri,sans-serif;}\n";
$css .= "body, th, td {font-size:13px;}\n";
$css .= "table {border:1px solid #B5B5B5;}\n";
$css .= "th, td {border-right:1px solid #B5B5B5; border-bottom:1px solid #B5B5B5;}\n";
$css .= ".platz {text-align:center; width:25px;}\n";
$css .= ".hits {text-align:center; font-weight:bold; width:60px;}\n";
$css .= ".titel {width:300px;}\n";
$css .= ".alias {width:250px;}\n";
$css .= ".archiv {width:200px;}\n";
$css .= ".datum {text-align:center; font-weight:bold; width:150px;}\n";
$css .= "</style>\n";

$content = "<html>\n";
$content .= "<head>\n";
$content .= '<meta http-equiv="content-type" content="text/html; charset=utf-8">';
$content .= $css;
$content .= "</head>\n";
$content .= "<body>\n";
$content .= "<h1>".$titel."</h1>\n";
$content .= "<p>\n";
$content .= "<table>\n";
$content .= "  <tr>\n";
$content .= "    <th class=\"platz\">Pl.</th>\n";
$content .= "    <th class=\"hits\">Zugriffe</th>\n";
$content .= "    <th class=\"titel\">Seite</th>\n";
$content .= "    <th class=\"alias\">Alias</th>\n";
$content .= "    <th class=\"datum\">Erstellt</th>\n";
$content .= "  </tr>\n";
for($x=0;$x<count($gestern_zaehler);$x++)
{
	$platz = $x+1;
	$content .= '  <tr style="background-color:'.$gestern_farbe[$x]."\">\n";
	$content .= "    <td class=\"platz\">".$platz.".</td>\n";
	$content .= "    <td class=\"hits\">".$gestern_zaehler[$x]."</td>\n";
	$content .= "    <td class=\"titel\">".$gestern_titel[$x]."</td>\n";
	$content .= "    <td class=\"alias\">".$gestern_alias[$x]."</td>\n";
	$content .= "    <td class=\"datum\">".$gestern_datum[$x]."</td>\n";
	$content .= "  </tr>\n";
	if($platz == $maxplatz) break;
}
$content .= "</table>\n";
$content .= "</p>\n";
$content .= $hinweis."\n";
$content .= "</body>\n";
$content .= "</html>\n";

echo "Versende $titel ...<br>\n";
EmailSenden($titel, $content);

if($wochentag_heute == 1)
{
	// Daten Woche ausgeben als Tabelle
	$titel = "Top-".$maxplatz." Seiten ".date("d.m.Y", $montagdatum)." bis ".date("d.m.Y", $sonntagdatum);

	$content = "<html>\n";
	$content .= "<head>\n";
	$content .= '<meta http-equiv="content-type" content="text/html; charset=utf-8">';
	$content .= $css;
	$content .= "</head>\n";
	$content .= "<body>\n";
	$content .= "<h1>".$titel."</h1>\n";
	$content .= "<p>\n";
	$content .= "<table>\n";
	$content .= "  <tr>\n";
	$content .= "    <th class=\"platz\">Pl.</th>\n";
	$content .= "    <th class=\"hits\">Zugriffe</th>\n";
	$content .= "    <th class=\"titel\">Seiten</th>\n";
	$content .= "    <th class=\"alias\">Alias</th>\n";
	$content .= "    <th class=\"datum\">Erstellt</th>\n";
	$content .= "  </tr>\n";
	for($x=0;$x<count($woche_zaehler);$x++)
	{
		$platz = $x+1;
		$content .= '  <tr style="background-color:'.$woche_farbe[$x]."\">\n";
		$content .= "    <td class=\"platz\">".$platz.".</td>\n";
		$content .= "    <td class=\"hits\">".$woche_zaehler[$x]."</td>\n";
		$content .= "    <td class=\"titel\">".$woche_titel[$x]."</td>\n";
		$content .= "    <td class=\"alias\">".$woche_alias[$x]."</td>\n";
		$content .= "    <td class=\"datum\">".$woche_datum[$x]."</td>\n";
		$content .= "  </tr>\n";
		if($platz == $maxplatz) break;
	}
	$content .= "</table>\n";
	$content .= "</p>\n";
	$content .= $hinweis."\n";
	$content .= "</body>\n";
	$content .= "</html>\n";

	echo "Versende $titel ...<br>\n";
	EmailSenden($titel, $content);
}

if($akttag == 1)
{
	// Daten Woche ausgeben als Tabelle
	$titel = "Top-".$maxplatz." Seiten ".$monatsnamen[date("n", $vormonat_tstamp)]." ".date("Y", $vormonat_tstamp);

	$content = "<html>\n";
	$content .= "<head>\n";
	$content .= '<meta http-equiv="content-type" content="text/html; charset=utf-8">';
	$content .= $css;
	$content .= "</head>\n";
	$content .= "<body>\n";
	$content .= "<h1>".$titel."</h1>\n";
	$content .= "<p>\n";
	$content .= "<table>\n";
	$content .= "  <tr>\n";
	$content .= "    <th class=\"platz\">Pl.</th>\n";
	$content .= "    <th class=\"hits\">Zugriffe</th>\n";
	$content .= "    <th class=\"titel\">Seiten</th>\n";
	$content .= "    <th class=\"alias\">Alias</th>\n";
	$content .= "    <th class=\"datum\">Erstellt</th>\n";
	$content .= "  </tr>\n";
	for($x=0;$x<count($vormonat_zaehler);$x++)
	{
		$platz = $x+1;
		$content .= '  <tr style="background-color:'.$vormonat_farbe[$x]."\">\n";
		$content .= "    <td class=\"platz\">".$platz.".</td>\n";
		$content .= "    <td class=\"hits\">".$vormonat_zaehler[$x]."</td>\n";
		$content .= "    <td class=\"titel\">".$vormonat_titel[$x]."</td>\n";
		$content .= "    <td class=\"alias\">".$vormonat_alias[$x]."</td>\n";
		$content .= "    <td class=\"datum\">".$vormonat_datum[$x]."</td>\n";
		$content .= "  </tr>\n";
		if($platz == $maxplatz) break;
	}
	$content .= "</table>\n";
	$content .= "</p>\n";
	$content .= $hinweis."\n";
	$content .= "</body>\n";
	$content .= "</html>\n";

	echo "Versende $titel ...<br>\n";
	EmailSenden($titel, $content);
}

function EmailSenden($titel, $content)
{
	$objEmail = new \Email();
	$objEmail->from = 'webmaster@schachbund.de';
	$objEmail->fromName = 'DSB-Webstatistik';
	$objEmail->subject = '[DSB-Webinfo] '.$titel;
	$objEmail->html = $content;
	$objEmail->sendCc(array
	(
		'Ullrich Krause <praesident@schachbund.de>',
		'Christian Eichner <christian.eichner@schachbund.de>',
		'Anja Gering <anja.gering@schachbund.de>',
	)); 
	$objEmail->sendTo(array('Frank Hoppe <webmaster@schachbund.de>')); 
}

?>