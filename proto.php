<?php
//error_reporting(E_ALL);
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <title>Bet 365 analyser</title>
    
<!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
<link rel="stylesheet" href="http://cdn.datatables.net/1.10.8/css/jquery.dataTables.min.css">
<!-- Optional theme -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap-theme.min.css">

<!-- Latest compiled and minified JavaScript -->
<script src="http://code.jquery.com/jquery-1.11.3.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
<script src="http://cdn.datatables.net/1.10.8/js/jquery.dataTables.min.js"></script>
<script type="text/javascript"> 
$(document).ready(function(){
    $('#fixtures').DataTable();
});
</script>

    </head>
    <body>
<?php
	require_once('http.php');

	class proto
	{
		var $homePage;
		var $sessionId;
		var $powConnectionDetails;
		var $clientRn;
		var $clientId;
		var $serverNum = 0;

		var $readItConstants = array(
			'RECORD_DELIM' 				=> "\x01",
			'FIELD_DELIM' 				=> "\x02",
			'MESSAGE_DELIM' 			=> "\b",
			'CLIENT_CONNECT' 			=> 0,
			'CLIENT_POLL' 				=> 1,
			'CLIENT_SEND' 				=> 2,
			'INITIAL_TOPIC_LOAD' 		=> 20,
			'DELTA' 					=> 21,
			'CLIENT_SUBSCRIBE' 			=> 22,
			'CLIENT_UNSUBSCRIBE' 		=> 23,
			'CLIENT_SWAP_SUBSCRIPTIONS' => 26,
			'NONE_ENCODING'	 			=> 0,
			'ENCRYPTED_ENCODING' 		=> 17,
			'COMPRESSED_ENCODING' 		=> 18,
			'BASE64_ENCODING' 			=> 19,
			'SERVER_PING' 				=> 24,
			'CLIENT_PING' 				=> 25,
			'CLIENT_ABORT' 				=> 28,
			'CLIENT_CLOSE' 				=> 29,
			'ACK_ITL' 					=> 30,
			'ACK_DELTA' 				=> 31,
			'ACK_RESPONSE' 				=> 32
		);

		function getPropData($name) {
			if(preg_match_all('#"' . $name . '":(\x20|)\{(.*?)\},#ims', $this->homePage, $matches)) {
				return json_decode('{' . $matches[2][0] . '}', true);
			}

			if(preg_match_all('#"' . $name . '"(\x20|):\[(.*?)\]#ims', $this->homePage, $matches)) {
				return json_decode('[' . $matches[2][0] . ']', true);
			}

			if(preg_match_all('#"' . $name . '":(.*?),#ims', $this->homePage, $matches)) {
				$var = rtrim(ltrim($matches[1][0]));

				if(substr($var, 0, 1) == '"') {
					return substr($var, 1, -1);
				}

				return $var;
			}
			echo "Name: ".$name."<br />";
			echo "Home page:". $this->homePage."<br />";
			echo "Matches: ". $matches."<br />";
			
			return NULL;
		}

		function powRequest($sid, $specialHeaders = array(), $postData = '') {
			$defaultHeaders = array(
				'Content-Type:  ; charset=UTF-8',
				'Referer: https://mobile.bet365.com/',
				'Origin: https://mobile.bet365.com'
			);

			if(!empty($this->clientId)) {
				array_push($defaultHeaders, 'clientid: ' . $this->clientId);
			}

			if($sid != 0) {
				array_push($defaultHeaders, 's: ' . $this->serverNum);
				$this->serverNum++;
			}

			$totalHeaders = array_merge($specialHeaders, $defaultHeaders);

			//var_dump($totalHeaders);

			return http::post($this->powConnectionDetails[1]['Host'] . '/pow/?sid=' . $sid . '&rn=' . $this->clientRn, $postData, $totalHeaders);
		}


		function parameterizeLine($line) {
			$chunk = explode(';', $line);

			if(empty($chunk))
				return FALSE;

			$cmd = $chunk[0];

			// Remove cmd element
			array_shift($chunk);

			$params = array();

			foreach($chunk as $pstr) {
				$pdata = explode('=', $pstr);

				if(count($pdata) != 2)
					continue;

				$params[$pdata[0]] = $pdata[1];
			}

			return array('cmd' => $cmd, 'params' => $params);
		}

		function connect() {
			http::setCookieJar('cookie.txt');

			$this->homePage = http::get('http://mobile.bet365.com');

			if($this->homePage === FALSE || empty($this->homePage))
				return FALSE;

			$this->sessionId = $this->getPropData('sessionId');

			if($this->sessionId === NULL || empty($this->sessionId))
				return FALSE;

		//echo("Session ID: " . $this->sessionId . "\n");

			$this->powConnectionDetails = $this->getPropData('ConnectionDetails');

			if($this->powConnectionDetails === NULL || empty($this->powConnectionDetails))
				return FALSE;

			if(!isset($this->powConnectionDetails[0]) || !isset($this->powConnectionDetails[0]['Host']))
				return FALSE;

		//echo("Pow HTTPS Host: {$this->powConnectionDetails[1]['Host']}:{$this->powConnectionDetails[1]['Port']}\n");

			$this->clientRn = substr(str_shuffle("0123456789"), 0, 16);

			// echo("Pow Random Number: {$this->clientRn}\n");

			$requestPow = $this->powRequest(0, array(
				'method: 0',
				'transporttimeout: 20',
				'type: F',
				'topic: S_' . $this->sessionId
			));

		//	var_dump($requestPow);

			if($requestPow === FALSE || empty($requestPow))
				return FALSE;

			$data = explode($this->readItConstants['FIELD_DELIM'], $requestPow);

			if(empty($data) || count($data) == 0 || count($data) == 1)
				return FALSE;

		//	echo("Constant: {$data[0]}\n");
		//	echo("Pow Session Id: {$data[1]}\n");

			$this->clientId = $data[1];

			$sslStatus = urlencode($this->powConnectionDetails[1]['Host'] . ':' . $this->powConnectionDetails[1]['Port']);

			// Inform the main site of our connection
			http::post('https://mobile.bet365.com/pushstatus/logpushstatus.ashx?state=true', 
				'sslStatus=' . $sslStatus . '&connectionID=' . $this->clientId . '&uid=' . $this->clientRn . '&connectionStatus=0&stk=' . $this->sessionId,
				array(
					'X-Requested-With: XMLHttpRequest',
					'Content-Type: application/x-www-form-urlencoded; charset=UTF-8'
				));

			$requestPow = $this->powRequest(2, array(
				'method: 1'
			));

			// Subscribe to the InPlay list
			$this->subscribe('OVInPlay_1_3//');

			$requestPow = $this->powRequest(2, array(
				'method: 1'
			));

			if(substr($requestPow, 0, 1) != "\x14") {
				echo("Unexpected InPlay packet header");
				
			//	echo "PoW: ". $requestPow;

				//return FALSE;
			}

			// Here we have some soccer data!!! wow!!
			$gameData = explode($this->readItConstants['RECORD_DELIM'], $requestPow);
			$gameData = explode("|", $gameData[count($gameData) - 1]);

			array_shift($gameData); // "F"

			$initialCL = $this->parameterizeLine($gameData[0]);

			if($initialCL === FALSE)
				return FALSE;

			if($initialCL['cmd'] != 'CL')
				return FALSE;

			if($initialCL['params']['NA'] != 'Soccer')
				return FALSE; // It isn't soccer!!??

			$events = array();

			// skip the initial CL (soccer)
			for($i = 1; $i < count($gameData); $i++) {
				$lineData = $this->parameterizeLine($gameData[$i]);
				
				if($lineData === FALSE)
					continue;

				// "EV" == EVENT
				// "CT" == COMPETITION_NAME
				// "PA" == PARTICIPANT
				// "MA" == MARKET
				// "CL" == CLASSIFICATION
				// "OR" == ORDER
				//var_dump($lineData['cmd']);;
				if($lineData['cmd'] == 'EV') {
					//if($lineData['params']['ID'] != '1')
					//	continue;

					array_push($events, $lineData['params']);
				} elseif ($lineData['cmd'] == 'CT') {
			
					if($lineData['params']['NA'] == 'Coupons') {
					//	break; // It adds some kind of coupon stuff... what
					}
					
				array_push($events, $lineData['params']);


				} elseif ($lineData['cmd'] == 'CL') {
					break; // This isn't soccer m8
				} 
				
			}

			$requestPow = $this->powRequest(2, array(
				'method: 1'
			));

		//	echo("Trying for ID: {$events[0]['ID']}\n");

			$this->unsubscribe('OVInPlay_1_3//');
$i = 0;
		
		?>
			<div class="row">
		<div class="col-md-12">
			<table class="table" id="fixtures">
				<thead>
					<tr>
						<th>
							Fixture
						</th>
						<th>
							Time 
						</th>
						<th>
							League
						</th>
						<th>
							Score
						</th>
						<th>
							Goals
						</th>
						<th>
							Goal Line Odds
						</th>
						<th>
							UC
						</th>
						<th>
							Home
						</th>
						<th>
							Draw
						</th>
						<th>
							Away
						</th>
						<th>
							Corners
						</th>
						<th>
							Dangerous Attacks
						</th>
						<th> Shots on Target </th>
					
							<th> Shots on Target (Home Team) </th>
						<th> Shots on Target (Away Team) </th>
							<th> Shots off Target </th>
					</tr>
				</thead>
				<tbody>
				<?php 	
				
foreach ($events as $value) {
  			
  	

  			$soccerEvent = $this->getSoccerEventInformation($events[$i]['ID']);
/*
$goals = ($soccerEvent['team1']['IGoal'] + $soccerEvent['team2']['IGoal']);
var_dump($soccerEvent['Goal Line ('.$soccerEvent['team1']['IGoal']."-".$soccerEvent['team2']['IGoal'].')']);

if ($soccerEvent['Alternative Match Goals']['Over '.$goals.'.5']) {

var_dump($soccerEvent['Alternative Match Goals']);
}
else {
var_dump($soccerEvent['Match Goals']);

}
echo 'Over '.$goals.'.5<br />';
	var_dump($soccerEvent['Goal Line ('.$soccerEvent['team1']['IGoal']."-".$soccerEvent['team2']['IGoal'].')']['Over '.$goals.'.5'] );
		echo "<br /><br />";
	
/**/

 echo "<h1>".$events[$i]["NA"]."</h1>"; 

echo "<b>GAME DATA:</b> ";

echo var_export($gameData[$i])."<br />";

echo "<b>EVENTS DUMP</b>: ";

echo var_export($events[$i])."<br />";

echo "<b>SOCCEREVENT:</b> ";

echo var_export($soccerEvent);


	
	?>
	
	<br /><br /><br />
  			
  		
<?php 		if(!empty($events[$i]["NA"])) {
				?>

					<tr>
						<td>
							<?php echo $events[$i]["NA"]; ?>
						</td>
						
						<td> 
							<?php $TUH = substr($events[$i]["TU"],8,2);
								$TUM = substr($events[$i]["TU"],10,2);
								$TUS = substr($events[$i]["TU"],12,2);
								$UM = $events[$i]["UM"];
								$TM = $events[$i]["TM"];
								$CT = explode(":",date("H:i:s"));
								//$CT[0] = 6+$CT[0];
								//echo($CT[0].":".$CT[1].":".$CT[2]." - ".$TUH.":".$TUM.":".$TUS);
								if($CT[2]<$TUS){
									$CT[2] = $CT[2] +60;
									$CT[1] = $CT[1] -1;
								}
								if($CT[0]-$TUH>0){
									$CT[1] = $CT[1]+60;
								}
								$secsElapsed = ($CT[2]-$TUS);
								if($secsElapsed < 10){
									$secsElapsed = "0".$secsElapsed;
								}
								$minsElapsed = ($CT[1]-$TUM);
								if($TUH=="") {
									echo "HNS";
								} else {
									if($TM<45){
										echo($minsElapsed.":".$secsElapsed);
									} else {
										if($TM>44 && $minsElapsed>45) {
										
										if($UM == "At Half Time") { echo "HT"; } 
										else {
										echo("45:00+");
										}
										} else {
										echo(($minsElapsed+$TM).":".$secsElapsed);											
										}	
									}
								}									
								?>
						</td>
						<td>
							<?php echo $events[$i]["CT"]; ?>
						</td>
						<td>
							<?php echo $soccerEvent['team1']['IGoal']." - ".$soccerEvent['team2']['IGoal']; ?>
						</td>
						<td>
							<?php $totalgoals= ($soccerEvent['team1']['IGoal'] + $soccerEvent['team2']['IGoal']);
							echo $totalgoals; ?>
						</td>
							<td>
							<?php 
							if(!empty($soccerEvent['Goal Line ('.$soccerEvent['team1']['IGoal']."-".$soccerEvent['team2']['IGoal'].')']['Over '.$totalgoals.'.5'])) {
								
								echo "Over ".$totalgoals.".5 goals - Odds:".$soccerEvent['Goal Line ('.$soccerEvent['team1']['IGoal']."-".$soccerEvent['team2']['IGoal'].')']['Over '.$totalgoals.'.5'];
								
							} elseif(!empty($soccerEvent['Goal Line ('.$soccerEvent['team1']['IGoal']."-".$soccerEvent['team2']['IGoal'].')']['Over '.$totalgoals])) {

				
							echo "Over ".$totalgoals." goals (<b>Asian</b>) - Odds:".$soccerEvent['Goal Line ('.$soccerEvent['team1']['IGoal']."-".$soccerEvent['team2']['IGoal'].')']['Over '.$totalgoals.'.5'];

							}
							elseif(!empty($soccerEvent['Alternative Goal Line ('.$soccerEvent['team1']['IGoal']."-".$soccerEvent['team2']['IGoal'].')']['Over '.$totalgoals.'.5'])) {
								echo "Over ".$totalgoals.".5 goals - Odds:".$soccerEvent['Alternative Goal Line ('.$soccerEvent['team1']['IGoal']."-".$soccerEvent['team2']['IGoal'].')']['Over '.$totalgoals.'.5'];

							} 
							elseif(!empty($soccerEvent['Alternative Goal Line ('.$soccerEvent['team1']['IGoal']."-".$soccerEvent['team2']['IGoal'].')']['Over '.$totalgoals])) {
							echo "Over ".$totalgoals." goals (<b>Asian</b>) - Odds:".$soccerEvent['Alternative Goal Line ('.$soccerEvent['team1']['IGoal']."-".$soccerEvent['team2']['IGoal'].')']['Over '.$totalgoals.'.5'];

							}
							else {
								
								echo "No Market Yet";
							}
						 ?>
						</td>
						<td>
							<?php echo $gameData['UC']; ?>
						</td>
						<td>
							<?php  $team1 = explode("/",$soccerEvent['Fulltime Result'][$soccerEvent["team1"]["name"]]);
							if($team1[1] > 0 && $team1[0] > 0) {	echo (number_format(1+$team1[0]/$team1[1],2)); } else { echo "N/A"; } ?>
						</td>
						<td>
							<?php  $draw = explode("/",$soccerEvent['Fulltime Result']["Draw"]);
							if($draw[1] > 0 && $draw[0] > 0) {		  echo (number_format(1+$draw[0]/$draw[1],2)); } else { echo "N/A"; } ?>
						</td>
						<td>
							<?php  $team2 = explode("/",$soccerEvent['Fulltime Result'][$soccerEvent["team2"]["name"]]);
								if($team2[1] > 0 && $team2[0] > 0) {		  echo (number_format(1+$team2[0]/$team2[1],2));  } else { echo "N/A"; } ?>
						</td>
						<td>
							<?php echo ($soccerEvent['team1']['ICorner'] + $soccerEvent['team2']['ICorner']); ?>
						</td>
						<td>
							<?php echo ($soccerEvent['team1']['Dangerous Attacks'] + $soccerEvent['team2']['Dangerous Attacks']); ?>
						</td>
							<td>
							<?php echo ($soccerEvent['team1']['On Target'] + $soccerEvent['team2']['On Target']); ?>
						</td>
								<td>
							<?php echo ($soccerEvent['team1']['On Target']); ?>
						</td>
								<td>
							<?php echo ($soccerEvent['team2']['On Target']); ?>
						</td>
							<td>
							<?php echo ($soccerEvent['team1']['Off Target'] + $soccerEvent['team2']['Off Target']); ?>
						</td>
					</tr>
				<?php
					}

		
				$i++;
}
	
			?>	
				</tbody>
			</table>
		</div>
	</div>
</body>
</html>

	<?php
  		

			return FALSE;
		}

		function getSoccerEventInformation($id) {
			$this->subscribe("$id//");

			// Update
			$requestPow = $this->powRequest(2, array(
				'method: 1'
			));

			$eventExpandedData = explode($this->readItConstants['RECORD_DELIM'], $requestPow);
			$eventExpandedData = explode('|', $eventExpandedData[count($eventExpandedData) - 1]);

			//var_dump($eventExpandedData);

			$res = array();
			$res['team1'] = array();
			$res['team2'] = array();

			$evParsedData = array();

			for($i = 0; $i < count($eventExpandedData); $i++) {
				$parsedLine = $this->parameterizeLine($eventExpandedData[$i]);

				if($parsedLine === FALSE)
					continue;

				if($parsedLine['cmd'] == 'EV') { // Event
					$evParsedData = $parsedLine['params'];

					//var_dump($evParsedData);
				}
				elseif($parsedLine['cmd'] == 'TE') { // "TE" = TEAM
    				$currentArrayTeam = 'team' . ($parsedLine['params']['OR'] + 1);

					$res[$currentArrayTeam]['name'] = $parsedLine['params']['NA'];

					for($stat = 1; $stat < 9; $stat++) {
						if(array_key_exists('S' . $stat, $evParsedData)) {
							if(empty($parsedLine['params']['S' . $stat]))
								continue;

							$res[$currentArrayTeam][$evParsedData['S' . $stat]] = $parsedLine['params']['S' . $stat];
						}
					}
				}
				elseif($parsedLine['cmd'] == 'MA') { // Column Data
					$res[$parsedLine['params']['NA']] = array();

					$columnData = $this->parameterizeLine($eventExpandedData[$i + 1]);

					if($columnData['cmd'] == 'CO' && isset($columnData['params']['CN']) && is_numeric($columnData['params']['CN'])) {
						for($cn = 1; $cn < ($columnData['params']['CN'] + 1); $cn++) {
							$columnFillData = $this->parameterizeLine($eventExpandedData[$i + 1 + $cn]);

							if($columnFillData['cmd'] == 'PA') {
								$res[$parsedLine['params']['NA']][$columnFillData['params']['NA']] = $columnFillData['params']['OD'];
							}
						}
					}
				}
				elseif ($parsedLine['cmd'] == 'SC') { // "SCORES_COLUMN"?
					if(empty($parsedLine['params']['NA']))
						continue; // no?

					$dc11 = $this->parameterizeLine($eventExpandedData[$i + 1]);
					$dc12 = $this->parameterizeLine($eventExpandedData[$i + 2]);

					$res['team1'][$parsedLine['params']['NA']] = $dc11['params']['D1'];
					$res['team2'][$parsedLine['params']['NA']] = $dc12['params']['D1'];
				}
			}

			return $res;
		}

		function unsubscribe($channel) {
			$this->powRequest(2, array(
				'method: 23',
				"topic: " . $channel
			));
		}

		function subscribe($channel) {
			$this->powRequest(2, array(
				'method: 22',
				"topic: " . $channel
			));
		}
	};

?>



