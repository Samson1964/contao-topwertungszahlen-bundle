<html>
<head><title>Testseite</title>
<style type="text/css">
table {border-collapse:collapse;empty-cells:show}
</style>
</head>

<?php

$context = stream_context_create([
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true
    ]
]);

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
			//'stream_context'     => $context // Entfernt am 21.02.2019 da svw.info meldete: Error Fetching http body, No Content-Length, connection closed or chunked data
		)
	);

	Rangliste($client);

}
catch (SOAPFault $f) {
  print $f->faultstring;
}


function Rangliste($client)
{
    echo '<h1>DWZ-Bestenliste</h1>';
    
    // VKZ des Bezirks / (U-)LV
    // Achtung: diese Abfrage ist noch sehr langsam
    $ratingList = $client->bestOfFederation("00000",100,0,20,'f');
    
  echo "<h2>".$ratingList->organization->vkz." ".$ratingList->organization->name."</h2>";
  echo "<table border='1'>";
  $i = 0;
  foreach ($ratingList->members as $m) {
	    $tcard = $client->tournamentCardForId($m->pid);
        if($tcard->member->fideNation == 'GER')
        {
        	$i++;
        	echo "<tr>";
        	echo "<td>".$i."</td>";
        	echo "<td>".$m->pid."</td>";
        	echo "<td>".$m->surname."</td>";
        	echo "<td>".$m->firstname."</td>";
        	echo "<td>".$m->title."</td>";
        	echo "<td>".$m->vkz."</td>";
        	echo "<td>".$m->club."</td>";
        	echo "<td>".$m->state."</td>";
        	echo "<td>".$m->membership."</td>";
        	echo "<td align='center'>".$m->rating."-".$m->ratingIndex."</td>";
        	echo "<td>".$m->idfide."</td>";
        	echo "<td>".$m->elo."</td>";
        	echo "<td>".$m->fideTitle."</td>";
        	echo "<td>".$m->tcode."</td>";
        	echo "<td>".$m->finishedOn."</td>";
        	echo "<td>".$tcard->member->fideNation."</td>";
        	echo "</tr>";
        }
  }
  echo "</table>";
}


function fidenation($client, $vkz)
{
    // ID des Mitglieds
    $tcard = $client->tournamentCardForId($vkz);
    //return $tcard->member->fideNation;
	return '';
}

function tournament($client) {
    echo '<h1>Turnierauswertung</h1>';
    
    // Turniercode
    $tournament = $client->tournament("B148-C12-SLG");

    echo "<h3>".$tournament->tournament->tname." (".$tournament->tournament->tcode.") </h3>";
    echo "<dl>";
    echo "<dt>beendet am:</dt>";
    echo "<dd>".$tournament->tournament->finishedOn."</dd>";
    echo "<dt>berechnet am:</dt>";
    echo "<dd>".$tournament->tournament->computedOn."</dd>";
    echo "<dt>zuletzt berechnet am:</dt>";
    echo "<dd>".$tournament->tournament->recomputedOn."</dd>";
    echo "<dt>ID Auswerter 1:</dt>";
    echo "<dd>".$tournament->tournament->assessor1."</dd>";
    echo "<dt>ID Auswerter 2:</dt>";
    echo "<dd>".$tournament->tournament->assessor2."</dd>";
    echo "<dt>Anzahl Spieler</dt>";
    echo "<dd>".$tournament->tournament->cntPlayer."</dd>";
    echo "<dt>Anzahl Partien</dt>";
    echo "<dd>".$tournament->tournament->cntGames."</dd>";
    echo "</dl>";
        
  echo "<table border='1'>";
  
  foreach ($tournament->evaluation as $m) {
        echo "<tr>";
        echo "<td>".$m->pid."</td>";
        echo "<td>".$m->surname."</td>";
        echo "<td>".$m->firstname."</td>";
        echo "<td>".$m->ratingOld."</td>";
        echo "<td>".$m->ratingOldIndex."</td>";
        echo "<td>".$m->points."</td>";
        echo "<td>".$m->games."</td>";
        echo "<td>".$m->unratedGames."</td>";
        echo "<td>".$m->we."</td>";
        echo "<td>".$m->achievement."</td>";
        echo "<td>".$m->eCoefficient."</td>";
        echo "<td>".$m->ratingNew."</td>";
        echo "<td>".$m->ratingNewIndex."</td>";
        echo "<td>".$m->level."</td>";
        echo "</tr>";
  }
  echo "</table>";
}

function unionRatingList($client) {
    echo '<h1>DWZ-Liste eines Vereins</h1>';
    
    // VKZ des Vereins
    $unionRatingList = $client->unionRatingList("C0560");
  echo "<h3>".$unionRatingList->union->name." (".$unionRatingList->union->vkz.") </h3>";
  echo "<dt>";
  echo "<dt>ID Wertungsreferent:</dt><dd>".$unionRatingList->ratingOfficer."</dd>";
  echo "</dl>";
  echo "<table border='1'>";
  
  foreach ($unionRatingList->members as $m) {
        echo "<tr>";
        echo "<td>".$m->pid."</td>";
        echo "<td>".$m->surname."</td>";
        echo "<td>".$m->firstname."</td>";
        echo "<td>".$m->title."</td>";
        echo "<td>".$m->state."</td>";
        echo "<td>".$m->membership."</td>";
        echo "<td align='center'>".$m->rating."-".$m->ratingIndex."</td>";
        echo "<td>".$m->tcode."</td>";
        echo "<td>".$m->finishedOn."</td>";
        echo "</tr>";
  }
  echo "</table>";
}
//highlight_file(__FILE__);
?>
