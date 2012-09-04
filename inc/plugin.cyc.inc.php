<?PHP

//$order has to be either asc or desc
function sort_multi ($array, $index, $order, $natsort=FALSE, $case_sensitive=FALSE) {
  if(is_array($array) && count($array)>0) {
            foreach(array_keys($array) as $key)
            $temp[$key]=$array[$key][$index];
            if(!$natsort) {
                if ($order=='asc')
                    asort($temp);
                else   
                    arsort($temp);
            }
            else
            {
                if ($case_sensitive===true)
                    natsort($temp);
                else
                    natcasesort($temp);
            if($order!='asc')
                $temp=array_reverse($temp,TRUE);
            }
            foreach(array_keys($temp) as $key)
                if (is_numeric($key))
                    $sorted[]=$array[$key];
                else   
                    $sorted[$key]=$array[$key];
            return $sorted;
        }
    return $sorted;
}

// pretvorba sumnikov
function convertUTF8($title) {
  $title = trim($title, " \t.!?,-");
  $chars = array("š", "č", "ž", "đ", "ć", "Š", "Č", "Ž", "Đ", "Ć", " "); 
	$newchars = array("s",  "c",  "z",  "d",  "c",  "S",  "C",  "Z",  "D",  "C", "");   
	$title = str_replace($chars, $newchars, $title);
  // $title = strtolower($title);
	
	return $title;
}

function pluginCycUpdate() {
  global $mysql_sd;
	
	// find out last updated node_id
	$SQL = "SELECT * FROM cyc ORDER BY ts DESC LIMIT 1";
	$result = mysql_query($SQL);
	if ($line = mysql_fetch_array($result)) {
	  $lastNodeId = $line["cy_nodeid"];
	} else {
	  $lastNodeId = -1;
	}
	
	// find next node (according to node id)
  // parse get-clusters for the right cluster id & concept name = geonames + "Node" + N

  // get clusters and parse XML
	$XMLcode = pluginGetClusters(1);
  $XML = simplexml_load_string($XMLcode);
	
	
	$nodeN = 0;
	foreach ($XML->children() as $cluster) {	
	  foreach($cluster->children() as $node) {
		  // create table
			if (($node->getName() == "node") && (trim($cluster["place"]) != "")) { 
  			$nodeTbl[$nodeN]["id"] = trim($node["id"]);
  			$nodeTbl[$nodeN]["name"] = trim($cluster["place"]);
  			$nodeN++;
			}
		}
	}
	
	// sort the table
	$sortedNodeTbl = sort_multi($nodeTbl, "id", "asc");
	
	// add numbering
	$oldName = "";
	$N = 0;
	for ($i = 0; $i < sizeof($sortedNodeTbl); $i++) {
	  if ($oldName != $sortedNodeTbl[$i]["name"]) {
		  $N = 0;
			$oldName = $sortedNodeTbl[$i]["name"];
		}
		$N++;
		$sortedNodeTbl[$i]["N"] = $N;	  
	}

	// get next value
	for($i = 0; $i < sizeof($sortedNodeTbl); $i++) {
	  if ($sortedNodeTbl[$i]["id"] > $lastNodeId)
	  	 break;
	}	
	
	if ($i == sizeof($sortedNodeTbl)) { 
	  $i = 0;		
	};
	
	$currentNode = $sortedNodeTbl[$i]["id"];
	
	// build conceptName
	$conceptName = convertUTF8($sortedNodeTbl[$i]["name"] . "Node" . $sortedNodeTbl[$i]["N"]);
	
	// delete old statements from MySQL
	$SQL = "DELETE FROM cyc WHERE cy_nodeid = " . $sortedNodeTbl[$i]["id"];
	$result = mysql_query($SQL);
	echo mysql_error();
	
	// preparing the URL
  if ($conceptName != "") {
    $url = 'http://shodan.ijs.si:8080/ApacheProphet/Cure/getFollowUps?conceptName=' . $conceptName . '&oauth_token=SensorLabToken';
  	// $url = 'http://shodan.ijs.si';
  	$json_string = passthruHTTP($url);
  	echo "URL: " . $url . "<br><br>";
  	// echo $json_string;
  }
  
  $obj = json_decode($json_string);
  
	$sentenceN = 0;
	
  echo "Sentences:";
  echo "<ol>";
  // crawl on typeSet (what exactly is it)?
  foreach($obj->types->typeSet as $typeSet) {
    // crawl on sentences
    foreach($typeSet->type as $type) {
  	  // build NL sentence
  		$sentenceNL = "";
  		// crawl renderers
  		foreach($type->renderers->renderer as $renderer) {
  		  if ($renderer->type == ":STRING-RENDERER") {
  			  $sentenceNL .= $renderer->string;
  			} else if ($renderer->type == ":OPEN-SELECT-RENDERER") {
  			  // new select box with terms
  				// if only term is newValue, than this is a question, else
  				// it can be a statement
  				/*
  				TODO: complete swap
  				foreach ($renderer->type->selectionRenderer as $selectionRenderer) {				  
  				}
  				*/
  				// let's just take first selection renderer term
  				$sentenceNL .= $renderer->selectionRenderer[0]->string;
  			 
  			} else {
  			  $sentenceNL .= "?";
  			}
  		}
			
			$realSentenceNL = urldecode($sentenceNL);

			// add into MySQL
			// detect question
			if (strpos($realSentenceNL, "?") > 0) {
			  // we have a question
				$cyctype = 1;
			} else 
			  // usual statement
			  $cyctype = 0;
			
			// insert into DB
			$SQL = "INSERT INTO cyc SET cy_nodeid = " . $sortedNodeTbl[$i]["id"] . ", cy_statement = '" . $realSentenceNL . "', cy_statementtype = " . $cyctype;
			$result = mysql_query($SQL);			
			echo mysql_error();
			
  		echo "<li>" . $realSentenceNL . " ($cyctype)</li>";
			$sentenceN++;			 
  	}
  }
  echo "</ol>";
	
	// if we have nothing for the node, we just enter fake statement
	if (sentenceN == 0) {
		// insert into DB
		$SQL = "INSERT INTO cyc SET cy_nodeid = " . $sortedNodeTbl[$i]["id"] . ", cy_statement = 'No sentences for this node.', cy_statementtype = 2";
		$result = mysql_query($SQL);			
		echo mysql_error();
	}
	
}

function pluginCycStatement() {
	$SQL = "SELECT * FROM cyc WHERE cy_statementtype = 0 ORDER BY RAND() LIMIT 1";
	$result = mysql_query($SQL);
	
	$HTML = "";
	
	while ($line = mysql_fetch_array($result)) {
	  $HTML .= $line["cy_statement"];
	}
	
	return $HTML;

}

?>