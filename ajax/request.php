<?php
	$timeStart = microtime(true);

require_once("getRow.php");

$p = new proto();

$p->connect();

$soccerEvent = $p->getSoccerEventInformation($_GET['bet365id']);

echo $_GET['bet365id'];

var_dump($soccerEvent);

	$timeEnd = microtime(true);

	echo("<br /><br />Script took " . (($timeEnd - $timeStart)) . " seconds!\n");
?>