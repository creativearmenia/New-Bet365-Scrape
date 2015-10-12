<?php
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