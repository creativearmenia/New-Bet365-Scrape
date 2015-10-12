<?php
	include('proto.php');

	$timeStart = microtime(true);

	$p = new proto();
//	var_dump($p->connect());
	$p->connect();
	$timeEnd = microtime(true);

	echo("<br /><br />Script took " . (($timeEnd - $timeStart)) . " seconds!\n");
?>
