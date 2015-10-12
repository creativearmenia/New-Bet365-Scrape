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

$mysqli = new mysqli("127.0.0.1", "root", "livegoals", "livegoals");
if (!$mysqli) {
    echo "Error: Unable to connect to MySQL." . PHP_EOL;
    echo "Debugging errno: " . mysqli_connect_errno() . PHP_EOL;
    echo "Debugging error: " . mysqli_connect_error() . PHP_EOL;
    exit;
}

?>

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
				

$sql = "SELECT * from games where live='1'";
$result = $mysqli->query($sql);

if ($result->num_rows > 0) {
    // output data of each row
    while($row = $result->fetch_assoc()) {


  	///		$soccerEvent = $this->getSoccerEventInformation($events[$i]['ID']);


	//var_dump($events);
	//	echo "<br /><br />";
	

	?>
  			
  		
<?php 		if(!empty($row['fixture'])) {


			 $TUH = substr($row["TU"],8,2);
								$TUM = substr($row["TU"],10,2);
								$TUS = substr($row["TU"],12,2);
								$UM = $events[$i]["UM"];
								$TM = $row["TM"];
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
								//	echo "HNS";
								} else {
									if($TM<45){
										$time = ($minsElapsed.":".$secsElapsed);
									} else {
										if($TM>44 && $minsElapsed>45) {
										
										if($UM == "At Half Time") { echo "HT"; } 
										else {
									//	echo("45:00+");
										}
										} else {
										$time = (($minsElapsed+$TM).":".$secsElapsed);											
										}	
									}
								}				
if(empty($_GET['threshold']) || !is_numeric($_GET['threshold'])) {
$threshold = 31;
	} else {
		$threshold = $_GET['threshold'];
	}
						//	echo $time."- ".($row['home_goals'] + $row['away_goals'])."<br />";
							if($time < $threshold && ($row['home_goals'] + $row['away_goals'])  > 2) {
								
				?>
				
				

					<tr>
						<td>
							<?php echo $row['fixture']; ?>
						</td>
						
						<td> 
							<?php $TUH = substr($row["TU"],8,2);
								$TUM = substr($row["TU"],10,2);
								$TUS = substr($row["TU"],12,2);
								$UM = $events[$i]["UM"];
								$TM = $row["TM"];
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
								//	echo "HNS";
								} else {
									if($TM<45){
										echo($minsElapsed.":".$secsElapsed);
									} else {
										if($TM>44 && $minsElapsed>45) {
										
										if($UM == "At Half Time") { echo "HT"; } 
										else {
										//echo("45:00+");
										}
										} else {
										echo(($minsElapsed+$TM).":".$secsElapsed);											
										}	
									}
								}									
							?>
						</td>
						<td>
							<?php echo $row['competition']; ?>
						</td>
						<td>
							<?php echo $row['home_goals']." - ".$row['away_goals']; ?>
						</td>
						<td>
							<?php echo ($row['home_goals'] + $row['away_goals']); ?>
						</td>
						<td>
							<?php  $team1 = explode("/",$row['odds_homewin']);
							if($team1[1] > 0 && $team1[0] > 0) {	echo (number_format(1+$team1[0]/$team1[1],2)); } else { echo "N/A"; } ?>
						</td>
						<td>
							<?php  $draw = explode("/",$row['odds_draw']);
							if($draw[1] > 0 && $draw[0] > 0) {		  echo (number_format(1+$draw[0]/$draw[1],2)); } else { echo "N/A"; } ?>
						</td>
						<td>
							<?php  $team2 = explode("/",$row['odds_awaywin']);
								if($team2[1] > 0 && $team2[0] > 0) {		  echo (number_format(1+$team2[0]/$team2[1],2));  } else { echo "N/A"; } ?>
						</td>
						<td>
							<?php echo ($row['home_corners'] + $row['away_corners']); ?>
						</td>
						<td>
							<?php echo ($row['home_danger'] + $row['away_danger']); ?>
						</td>
							<td>
							<?php echo ($row['home_shotson'] + $row['away_shotson']); ?>
						</td>
								<td>
							<?php echo ($row['home_shotson']); ?>
						</td>
								<td>
							<?php echo ($row['away_shotson']); ?>
						</td>
							<td>
							<?php echo ($row['home_shotsoff'] + $row['away_shotsoff']); ?>
						</td>
					</tr>
				<?php
				}
    
} 
}
}
else {
    echo "0 results";
}
$mysqli->close();

			?>	
				</tbody>
			</table>
		</div>
	</div>
</body>
</html>
