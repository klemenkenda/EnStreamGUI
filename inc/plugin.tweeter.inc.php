<?PHP
//---------------------------------------------------------------------
// FILE: plugin.tweeter.inc.php
// AUTHOR: Klemen Kenda
// DESCRIPTION: Tweeter 4 Sensors
// DATE: 08/07/2011
// HISTORY:
//---------------------------------------------------------------------

function timeDiff($time) {
  $phpTime = strtotime($time);
	if ($phpTime == FALSE) return 99999 * 3600;
	$nowTime = time();
	
	return $nowTime - $phpTime;		
}

function pluginTweeter() {
  global $tweet_cfg;

  // get rules
  $SQL = "SELECT * FROM tweet_rules";
	$result = mysql_query($SQL);
	$i = 0;
	while ($line = mysql_fetch_array($result)) {
	  $rule[$i] = $line;
		// parse rules
		$rules = explode("&", $line["tr_rule"]);
		// number of subrules
		$rule[$i]["n"] = sizeof($rules);
		$ruleN = 0;
		foreach ($rules as $irule) {
		  $rule[$i][$ruleN] = array();
			
		  // find operator
			if (strpos($irule, ">=") != 0) {
			  $rule[$i][$ruleN]["operator"] = ">=";
			} elseif (strpos($irule, "<=") != 0) {
			  $rule[$i][$ruleN]["operator"] = "<=";
			} elseif (strpos($irule, ">") != 0) {
			  $rule[$i][$ruleN]["operator"] = ">";				
			} elseif (strpos($irule, "<") != 0) {
			  $rule[$i][$ruleN]["operator"] = "<";
			} elseif (strpos($irule, "=") != 0) {
 			  $rule[$i][$ruleN]["operator"] = "=";
			} else {
			  echo "Error in rule " . $line["id"] . "!";
				exit();
			}
			// extract feature and value			
			list($rule[$i][$ruleN]["feature"], $rule[$i][$ruleN]["value"]) = 
			  explode($rule[$i][$ruleN]["operator"], $irule);			
				
			$ruleN++;
		}
		
	  $i++;
	}
	
	//echo "<pre>";
	//print_r($rule);
	
	// get current state
	$url = "http://sensors.ijs.si/xml/current-state";
	$XMLcode = passthruHTTP($url);
	// parse XML
	$XML = simplexml_load_string($XMLcode);
  
  // print_r($XMLcode);
  
  // TODO: get series
  
  // apply rules on data - generate all possible tweets
	// parse current state XML
	foreach ($XML->children() as $nodesXML) {
	  $nodesN = 0;
	  foreach($nodesXML->children() as $nodeXML) {
		  // create node mapping table
		  $nodeid = $nodeXML["id"] + 0;
						
			$mapnodes[$nodesN] = $nodeid;

		  $sensorN = 0;			
			foreach ($nodeXML->children() as $sensorXML) {

			  if (
				     ($sensorXML->getName() == "sensor") && 
						 (timeDiff($sensorXML["measurementtime"]) < 2*3600)
					  ) {
				  $sensor[$nodeid][$sensorN]["feature"] = $sensorXML["featureofmeasurement"];
					$sensor[$nodeid][$sensorN]["value"] = $sensorXML["lastmeasurement"];
					$sensor[$nodeid][$sensorN]["time"] = $sensorXML["measurementtime"];					
					$sensorN++;					
				} elseif ($sensorXML->getName() == "virtualsensor") {
				  $sensor[$nodeid][$sensorN]["feature"] = $sensorXML["name"];
					$sensor[$nodeid][$sensorN]["value"] = $sensorXML["value"];
					// TODO TIME
					$sensorN++;				
				}												
			}			
			$nodesN++;
		}
	}
	
	// get clusters
	$url = "http://sensors.ijs.si/xml/get-clusters";
	$XMLcode = passthruHTTP($url);
	// parse XML
	$XML = simplexml_load_string($XMLcode);
	
	foreach ($XML->children() as $clusterXML) {
	  $cluster_name = (string)$clusterXML["place"];
		foreach ($clusterXML->children() as $nodeXML) {
		  if ($nodeXML->getName() == "node") {
		    $nodemeta[$nodeXML["id"] + 0] = $cluster_name;
			}
		}
	}		
  
	$statementN = 0;
		
	// generate tweets
	for ($i = 0; $i < $nodesN; $i++) {
	  $nodeid = $mapnodes[$i];		
		for($j = 0; $j < sizeof($rule); $j++) {		 
		  $ok = TRUE;
			// browse the subrules
			for ($k = 0; $k < $rule[$j]["n"]; $k++) {
  			// check all sensors on the node
				$featureFound = FALSE;
				for ($l = 0; $l < sizeof($sensor[$nodeid]); $l++) {				 					
  			  $tempOK = FALSE;
					// check if feature is not found
  			  if ($rule[$j][$k]["feature"] == $sensor[$nodeid][$l]["feature"]) {
					  $featureFound = TRUE;
					  switch($rule[$j][$k]["operator"]) {
						  case ">": 
  				      $tempOK = ($sensor[$nodeid][$l]["value"] > $rule[$j][$k]["value"]);
							break;
							case "=":
							  $tempOK = ($sensor[$nodeid][$l]["value"] == $rule[$j][$k]["value"]);
							break;
							case "<":
							  $tempOK = ($sensor[$nodeid][$l]["value"] < $rule[$j][$k]["value"]);
							break;
							case ">=":
							  $tempOK = ($sensor[$nodeid][$l]["value"] >= $rule[$j][$k]["value"]);
							break;
							case "<=":
							  $tempOK = ($sensor[$nodeid][$l]["value"] <= $rule[$j][$k]["value"]);
							break;
						}
						if ($tempOK == FALSE) $ok = FALSE;
  				}
				}
				// if feature was not found, then ...
				if ($featureFound == FALSE) $ok = FALSE;				
			}
			$statement = $rule[$j]["tr_statement"];
			$nodeStr = "in " . $nodemeta[$nodeid] . " (" . $nodeid . ")";
		  $statement = str_replace("%n", $nodeStr, $statement);
			if ($ok == TRUE) {
			  $HTML .= "node $i; rule $j (" . $rule[$j]["tr_rule"] . "): $ok - <b>$statement</b><br>";
				$statements[$statementN]["node"] = $i;
				$statements[$statementN]["rule"] = $j;
				$statements[$statementN]["statement"] = $statement;
			  $statementN++;				
			}
		}
	}				
	
	// TODO: agreggate by nodes/clusters
	
  // select tweet
	$SQL = "SELECT * FROM tweets ORDER BY id DESC LIMIT " . max($tweet_cfg["node_repeat"], $tweet_cfg["statement_repeat"]);	
	$result = mysql_query($SQL);
	
	$lastnodes = array();
	$lastrules = array();
	
	$i = 0;
	while ($line = mysql_fetch_array($result)) {
	  if ($i < $tweet_cfg["node_repeat"]) 
		  $lastnodes[$i] = $line["tw_nodeids"];
		if ($i < $tweet_cfg["statement_repeat"])
		  $lastrules[$i] = $line["tw_ruleid"];
		$i++;
	}
	
	// echo "<pre>";
	
	// print_r($lastnodes);
	// print_r($lastrules);
	// print_r($statements);
	
	$i = 0;
	// let's have 100 trials
	while (($i < 100) && ($statementN > 0)) {
	  // select random statement
	  $j = rand(0, $statementN - 1);
		if (!in_array($statements[$j]["node"], $lastnodes) &&
			  !in_array($statements[$j]["rule"], $lastrules)) {
		
		  // input into database
			$SQL = "INSERT INTO tweets SET tw_ruleid = " . $statements[$j]["rule"] . ", tw_nodeids = " . $statements[$j]["node"] .
			       ", tw_text = '" . $statements[$j]["statement"] . "'";
			$result = mysql_query($SQL);
			echo mysql_error();
			$HTML = "<font color='red'>Selected statement: </font> " . $statements[$j]["statement"] . "<br><br>All possibilities:<br>$HTML";
			break;		
		}				
		$i++;
	}		 	
	
	if ($HTML == "") return "Warning: no recent measurements!";
	
	return $HTML;
}

function pluginTweetHistory() {
  global $tweet_cfg;
	
	$SQL = "SELECT *, UNIX_TIMESTAMP(ts) as uts FROM tweets ORDER BY id DESC LIMIT " . $tweet_cfg["num_tweets_history"];
	$result = mysql_query($SQL);
	
	$HTML = "";
	
	while ($line = mysql_fetch_array($result)) {
	  $HTML .= "<font color=\"lightgray\">[" . date('H:i:s', $line["uts"]) . "]</font> " . $line["tw_text"] . "<br>";
	}
	
	return $HTML;
}

function pluginLastTweet() {
	$SQL = "SELECT *, UNIX_TIMESTAMP(ts) as uts FROM tweets ORDER BY id DESC LIMIT 1";
	$result = mysql_query($SQL);
	
	$HTML = "";
	
	while ($line = mysql_fetch_array($result)) {
	  $HTML .= "<font color=\"lightgray\">[" . date('H:i:s', $line["uts"]) . "]</font> " . $line["tw_text"] . "<br>";
	}
	
	return $HTML;

}

?>