<?PHP
//---------------------------------------------------------------------
// FILE: plugin.xml.inc.php
// AUTHOR: Klemen Kenda
// DESCRIPTION: XML plugins file
// DATE: 20/12/2010
// HISTORY:
//---------------------------------------------------------------------


//---------------------------------------------------------------------
// FUNCTION: passthruXML
// Description: Reads XML from URL and passes it through localhost
//---------------------------------------------------------------------

function passthruHTTP($url) {	
	global $miner;
	
	if ($url == "") return "";
		
  $old = ini_set('default_socket_timeout', $miner["socket_timeout"]);
	ini_set('error_reporting', NULL);
	
  if ($fp = fopen($url, "r")) {
  	stream_set_timeout($fp, $miner["stream_timeout"]);
  	
  	ob_start();	
  	fpassthru($fp);
  	$buffer = ob_get_contents();
  	$size = ob_get_length();
  	ob_end_clean();
  
  	$info = stream_get_meta_data($fp);
  	
    fclose($fp);
  
    if ($info['timed_out']) {
      $buffer = "<error>%VAR:SERVER_TIMEOUT%</error>";
  		$size = sizeof($buffer);
    };
	} else {	
	  $buffer = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<errors><error>%VAR:SERVER_NO_CONNECTION%</error></errors>";
		$size = sizeof($buffer);
	}
	
	ini_set('default_socket_timeout', $old);   
  ini_set('error_reporting', 1);
		
	$HTML = $buffer;	
	
	return $HTML;
}


//---------------------------------------------------------------------
// PLUGIN: PassthruXML
// Description: Reads XML from server and passes it through localhost
//---------------------------------------------------------------------

function pluginPassthruXML($mycmdid = -1, $par1 = "", $par2 = "", $par3 = "", $par4 = "") {
  global $cmdid;
	global $parameters;
	global $miner;
	
	if (!isset($cmdid)) $cmdid = 0;
	if ($mycmdid != -1) $cmdid = $mycmdid;
	
	$command = array(
	  "current-state", // 0
		"stores",				 // 1
		"word-voc?keyid=" . $par1, // 2
		"get-events",		 // 3
		"get-aggregates?sid=" . $par1 . "&sd=" . $par2 . "&type=" . $par3 . "&timespan=" . $par4, // 4
	);
	
	$url = $miner["url"] . $command[$cmdid];
	
	// echo $url;
	
  $old = ini_set('default_socket_timeout', $miner["socket_timeout"]);
	ini_set('error_reporting', NULL);
	
	if ($fp = fopen($url, "r")) {
  	stream_set_timeout($fp, $miner["stream_timeout"]);
  	
  	ob_start();	
  	fpassthru($fp);
  	$buffer = ob_get_contents();
  	$size = ob_get_length();
  	ob_end_clean();
  
  	$info = stream_get_meta_data($fp);
  	
    fclose($fp);
  
    if ($info['timed_out']) {
      $buffer = "<error>%VAR:SERVER_TIMEOUT%</error>";
  		$size = sizeof($buffer);
    };
	} else {	
	  $buffer = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<errors><error>%VAR:SERVER_NO_CONNECTION%</error></errors>";
		$size = sizeof($buffer);
	}
	
	// $url = "http://localhost:9988/get-aggregates?sid=0&sd=2007-01-01&type=SUM&timespan=D";
	
	ini_set('default_socket_timeout', $old);   
  ini_set('error_reporting', 1);
	
	header('Cache-Control: no-store, no-cache, must-revalidate');     // HTTP/1.1 
  header('Cache-Control: pre-check=0, post-check=0, max-age=0');    // HTTP/1.1 
  header ("Pragma: no-cache"); 
  header("Expires: 0"); 
	header("Content-Type: text/xml");
  header("Content-Length: " . $size);
			
	$HTML = $buffer;	
	
	return $HTML;
}

//---------------------------------------------------------------------
// PLUGIN: aggregateXML2chartXML
// Description: Reads XML from server and changes it for charts format
//---------------------------------------------------------------------

function aggregateXML2chartXML($buffer) {
	ini_set('error_reporting', 1);
	$aggregates = simplexml_load_string($buffer);
	
	$i = 0;
	foreach ($aggregates->children() as $aggregate) {
	  $category[$i] = $aggregate["timestamp"];
		$value[$i] = $aggregate["value"];
		
		if ($i % 3 != 0) $category[$i] = "";
		
		if ($i == 0) {
		  $rmin = floatval($aggregate["value"]);
			$rmax = floatval($aggregate["value"]);
		} else {		
		  // funny bug - operators wouldn't work on XML deduced variables
			// they need to be rewritten
		  $tmin = floatval($aggregate["value"]);
			$tmax = floatval($aggregate["value"]);

		  if ($tmin < $rmin) $rmin = $tmin;
			if ($tmax > $rmax) $rmax = $tmax;							  
		}
				
	  $rmin = floor($rmin);

		$i++;
	} 

	// create output XML
	$outXML = '<?xml version="1.0" encoding="ISO-8859-1" ?><graph></graph>'; 
  
  $XML = new SimpleXMLElement($outXML);	
	$XML->addAttribute("caption", "");	// title
	$XML->addAttribute("xAxisName", "Time"); 
	$XML->addAttribute("yAxisName", "%VAR:FTR_" . strToUpper($aggregates["phenomenon"]) . "% (" . $aggregates["uom"] . ")");
	
	$XML->addAttribute("showvalues", "0");
	$XML->addAttribute("showAlternateHGridColor", "1");
	$XML->addAttribute("AlternateHGridColor", 'ffffbb'); 	
	
	// if ($rmax / ($rmax - $rmin) > 2)
	$rmin = str_replace(",", ".", $rmin);
	$XML->addAttribute("yAxisMinValue", "$rmin");
	
	
	$dsCat = $XML->addChild('categories');

	$dsVal = $XML->addChild('dataset');
  $dsVal->addAttribute('seriesName', 'Value'); 
  $dsVal->addAttribute('color', '00ccff');
	
	for ($j = 0; $j < $i; $j++) {
	  $setCat = $dsCat->addChild('category');
		$setCat->addAttribute('name', $category[$j]);
	
		$setVal = $dsVal->addChild('set');
 	  	
		if ($val[$j] != "1e+030") {  		
  		$setVal->addAttribute("value", $value[$j]);  		
		}
		
		// if ($max[$j] == "-1e+030") $setMax->addAttribute('alpha', 0);

	}
	
  return $XML->asXML();	
}

//---------------------------------------------------------------------
// PLUGIN: MeasurementsXML
// Description: Reads XML from server and passes it through localhost
//---------------------------------------------------------------------

function minerXML2chartXML($buffer) {
	ini_set('error_reporting', 1);
	$theXML = simplexml_load_string($buffer);
	
	foreach ($theXML->children() as $child) {
	  if ($child->getName() == "sensor") $sensor = $child;
		if ($child->getName() == "measurements") $measurements = $child;
	}
	
	$i = 0;

	foreach ($measurements->children() as $measurement) {
		$category[$i] = $measurement["interval"];
		
		$min[$i] = $measurement["min"];
	  $max[$i] = $measurement["max"];
		$avg[$i] = $measurement["avg"];

		if ($i == 0) {
		  $rmin = floatval($measurement["min"]);
			$rmax = floatval($measurement["max"]);
		} else {		
		  // funny bug - operators wouldn't work on XML deduced variables
			// they need to be rewritten
		  $tmin = floatval($measurement["min"]);
			$tmax = floatval($measurement["max"]);

		  if ($tmin < $rmin) $rmin = $tmin;
			if ($tmax > $rmax) $rmax = $tmax;							  
		}
				
	   $rmin = floor($rmin);

		$i++;
	}
	
	// create output XML
	$outXML = '<?xml version="1.0" encoding="ISO-8859-1" ?><graph></graph>'; 
  
  $XML = new SimpleXMLElement($outXML);	
	$XML->addAttribute("caption", "");	// title
	$XML->addAttribute("xAxisName", "Cas"); 
	$XML->addAttribute("yAxisName", "%VAR:FTR_" . strToUpper($theXML->sensor["featureofmeasurement"]) . "% (" . $theXML->sensor["unitofmeasurement"] . ")");
	
	$XML->addAttribute("showvalues", "0");
	$XML->addAttribute("showAlternateHGridColor", "1");
	$XML->addAttribute("AlternateHGridColor", 'ffffbb'); 	
	
	// if ($rmax / ($rmax - $rmin) > 2)
	$rmin = str_replace(",", ".", $rmin);
	$XML->addAttribute("yAxisMinValue", "$rmin");
	
	
	$dsCat = $XML->addChild('categories');

	$dsMin = $XML->addChild('dataset');
  $dsMin->addAttribute('seriesName', '%VAR:MIN%'); 
  $dsMin->addAttribute('color', '%VAR:MIN_COLOR%');
	// $dsMin->addAttribute('anchorBorderColor', '%VAR:MIN_ANCHOR_BORDER_COLOR%');
	
	$dsMax = $XML->addChild('dataset');
  $dsMax->addAttribute('seriesName', '%VAR:MAX%'); 
  $dsMax->addAttribute('color', '%VAR:MAX_COLOR%');
	// $dsMax->addAttribute('anchorBorderColor', '%VAR:MAX_ANCHOR_BORDER_COLOR%');

	$dsAvg = $XML->addChild('dataset');
  $dsAvg->addAttribute('seriesName', '%VAR:AVG%'); 
  $dsAvg->addAttribute('color', '%VAR:AVG_COLOR%');
	// $dsAvg->addAttribute('anchorBorderColor', '%VAR:AVG_ANCHOR_BORDER_COLOR%');
	
	for ($j = 0; $j < $i; $j++) {
	  $setCat = $dsCat->addChild('category');
		$setCat->addAttribute('name', $category[$j]);
	
		$setAvg = $dsAvg->addChild('set');
 	  $setMin = $dsMin->addChild('set');
 		$setMax = $dsMax->addChild('set');
	
		if ($min[$j] != "1e+030") {  		
  		$setAvg->addAttribute("value", $avg[$j]);  		
  		$setMin->addAttribute("value", $min[$j]);
  		$setMax->addAttribute("value", $max[$j]);
		}
		
		// if ($max[$j] == "-1e+030") $setMax->addAttribute('alpha', 0);

	}
	
  return $XML->asXML();
}

function pluginMeasurementsXML($sid_int = -1, $sd_int = -1, $tdt_int = -1, $adt_int = -1, $chartxml = 1) {
  global $cmdid;
	global $parameters;
	global $miner;
	global $sd, $sid, $tdt, $adt;
	
	if ($sd_int != -1) $sd = $sd_int;
	if ($sid_int != -1) $sid = $sid_int;
	if ($tdt_int != -1) $tdt = $tdt_int;
	if ($adt_int != -1) $adt = $adt_int;
		
	// check parameters
	if (!isset($sd)) $sd = date("Y-m-d");  // today's date
	if (!isset($tdt)) $tdt = 6;						 // time duration = day
	if (!isset($adt)) $adt = 4;						 // aggregation duration = hour
	
	if (
	  (!isset($sid)) || (!is_numeric($sid)) ||
		(!is_numeric($tdt)) || (!is_numeric($adt)) ||
		(strtotime($sd) == FALSE)
	) {
    $buffer = "<error>%VAR:PARAMETER_ERROR%</error>";
		$size = sizeof($buffer);
	} else {
	
  	$command = "get-measurements";		
  	$parameters = "?sid=$sid&sd=$sd&tdt=$tdt&adt=$adt";
  	$url = $miner["url"] . $command . $parameters;	
  	
		
    $old = ini_set('default_socket_timeout', $miner["socket_timeout"]);
  	ini_set('error_reporting', NULL);
  	
    if ($fp = fopen($url, "r")) {
    	stream_set_timeout($fp, $miner["stream_timeout"]);
    	
    	ob_start();	
    	fpassthru($fp);
    	$buffer = ob_get_contents();    	
    	ob_end_clean();
    
    	$info = stream_get_meta_data($fp);
    	
      fclose($fp);
    
      if ($info['timed_out']) {
        $buffer = "<error>%VAR:SERVER_TIMEOUT%</error>";
    		$size = sizeof($buffer);
      } else {				
				if ($chartxml != 0) $buffer = minerXML2chartXML($buffer);
				$size = sizeof($buffer);
			}
  	} else {	
  	  $buffer = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<errors><error>%VAR:SERVER_NO_CONNECTION%</error></errors>";
  		$size = sizeof($buffer);
  	}
	} // check parameters
	
	ini_set('default_socket_timeout', $old);   
 //  ini_set('error_reporting', 1);
	
	header('Cache-Control: no-store, no-cache, must-revalidate');     // HTTP/1.1 
  header('Cache-Control: pre-check=0, post-check=0, max-age=0');    // HTTP/1.1 
  header("Pragma: no-cache"); 
  header("Expires: 0"); 
	header("Content-Type: text/xml");

	$XML = $buffer;	
  
	return $XML;
}

function pluginKeywordsXML($nodes = "") {
  global $mysql_sd;
	
	/*
	TODO: use miner for the frequencies
	global $miner;
  $url = "http://xpack.ijs.si:9988/op-search?storeid=2&qtype=and&sample=1000&aggrfid=1&cache=true&hits=100&offset=0";
	// echo $url;
	$XML = passthruXML($url);
	*/
	
	if ($nodes != "") $where_in = " AND sensor_table.sn_uid IN (" . mysql_escape_string($nodes) . ")";
	else $where_in = "";
		
	$link = mysql_connect($mysql_sd["host"], $mysql_sd["user"], $mysql_sd["pass"], TRUE);
	mysql_select_db("sensor_data", $link);
	echo mysql_error();

	$SQL = "SELECT DISTINCT measured_phenomenon, COUNT(*) AS n FROM sensor_type_table, sensor_table " .
			   "WHERE sensor_table.st_uid = sensor_type_table.st_uid " . $where_in. " GROUP BY measured_phenomenon";
	 
	$result = mysql_query($SQL, $link);

	echo mysql_error();

	$i = 0;
	$all = 0;
	while ($line = mysql_fetch_array($result)) {
		$feature[$i]["name"] = $line["measured_phenomenon"];
		$feature[$i]["value"] = $line["n"];
		$all += $line["n"];
		$i++;
	}
	
	mysql_close($link);
	
	
	header('Cache-Control: no-store, no-cache, must-revalidate');     // HTTP/1.1 
  header('Cache-Control: pre-check=0, post-check=0, max-age=0');    // HTTP/1.1 
  header("Pragma: no-cache"); 
  header("Expires: 0"); 
	header("Content-Type: text/xml");
		
	// create output XML
	$outXML = '<?xml version="1.0" encoding="UTF-8" ?><keywords></keywords>'; 
  
  $XML = new SimpleXMLElement($outXML);	
	$XML->addAttribute("n", $i);	// number
	
	for ($n = 0; $n < $i; $n++) {
  	$dsCat = $XML->addChild('keyword');
  	$dsCat->addAttribute("name", "%VAR:FTR_" . strtoupper($feature[$n]["name"]) . "%");
		$ratio = $feature[$n]["value"] / $all;
		$ratio = str_replace(",", ".", $ratio);
		$dsCat->addAttribute("weight", $ratio);
	}
	
	return $XML->asXML();
 
}

function passthruCache($url) {
  $SQL = "SELECT * FROM webcache WHERE wc_url = '$url'";
	$result = mysql_query($SQL);
	
	if ($line = mysql_fetch_array($result)) {
	  $content = $line["wc_result"];
	} else {
	  $content = passthruHTTP($url);
		$SQL = "INSERT INTO webcache (wc_url, wc_result) VALUES ('$url', '" . mysql_escape_string($content) . "')";
		$result = mysql_query($SQL);
	}	
	
	return $content;
}

function simpleXMLToArray($xml, $flattenValues = true, $flattenAttributes = true, $flattenChildren = true,
  $valueKey = '@value', $attributesKey = '@attributes', $childrenKey = '@children') {

  $return = array();
  if(!($xml instanceof SimpleXMLElement)){return $return;}
  $name = $xml->getName();
  $_value = trim((string)$xml);
  if(strlen($_value)==0){$_value = null;};

  if($_value!==null){
    if(!$flattenValues){$return[$valueKey] = $_value;}
    else{$return = $_value;}
  }

  $children = array();
  $first = true;
  foreach($xml->children() as $elementName => $child){
    $value = simpleXMLToArray($child, $flattenValues, $flattenAttributes, $flattenChildren, $valueKey, $attributesKey, $childrenKey);
    if(isset($children[$elementName])){
      if($first){
        $temp = $children[$elementName];
        unset($children[$elementName]);
        $children[$elementName][] = $temp;
        $first=false;
      }
      $children[$elementName][] = $value;
    }
    else {
      $children[$elementName] = $value;
    }
  }
  if(count($children)>0){
    if(!$flattenChildren){$return[$childrenKey] = $children;}
    else{$return = array_merge($return,$children);}
  }

  $attributes = array();
  foreach($xml->attributes() as $name=>$value){
    $attributes[$name] = trim($value);
  }
  if(count($attributes)>0){
    if(!$flattenAttributes){$return[$attributesKey] = $attributes;}
    else{$return = array_merge($return, $attributes);}
  }
   
  return $return;
}

function pluginGetClusters($onlyXML = 0) {
	global $miner;

	// miner/get-clusters
	$url = $miner["url"] . "get-clusters?clusters=8";	// hard hack
	$XMLcode = passthruHTTP($url);
	
	// parse XML
	$XML = simplexml_load_string($XMLcode);

	// browse clusters
	foreach ($XML->children() as $child) {
	  $latitude = $child["latitude"];
		$longitude = $child["longitude"];
		$nodes = $child["nodes"];
		
		// get place
		$url = "http://api.geonames.org/findNearbyPlaceName?lat=" . $latitude . "&lng=" . $longitude . "&username=sensors_ijs";		
		$placeXMLcode = passthruCache($url);
		$placeXML = simplexml_load_string($placeXMLcode);
		
		// get nearby Wiki
		$url = "http://api.geonames.org/findNearbyWikipedia?lat=" . $latitude . "&lng=" . $longitude . "&username=sensors_ijs";
		$wikiXMLcode = passthruCache($url);
		$wikiXML = simplexml_load_string($wikiXMLcode);
		
		// extract geonamesid
		$arrayPlace = simpleXMLToArray($placeXML);
		$geonameId = $arrayPlace["geoname"]["geonameId"];
				
		// get hieararchy
		$url = "http://api.geonames.org/hierarchy?geonameId=" . $geonameId . "&username=sensors_ijs";
		$hierarchyXMLcode = passthruCache($url);
		$hierarchyXML = simplexml_load_string($hierarchyXMLcode);
		
		// prepare data					
		$hierarchyArray = simpleXMLToArray($hierarchyXML);
		$wikiArray = simpleXMLToArray($wikiXML);
				
		// update out XML
		$child->addAttribute("place", $arrayPlace["geoname"]["toponymName"]);
		// wiki
		/*echo "<pre>";
		print_r($wikiArray["entry"]);
		*/
		$summary = $wikiArray["entry"][0]["summary"];		
		// print_r($summary);
		if (sizeof($summary) == 0) $summary = "[empty]";
		
		$wikiChild = $child->addChild("wiki", $summary);
		$wikiChild->addAttribute("title", $wikiArray["entry"][0]["title"]);
		$wikiChild->addAttribute("url", $wikiArray["entry"][0]["wikipediaUrl"]);		
			
		// hierarchy
		$hierarchyChild = $child->addChild("hierarchy");		
		foreach($hierarchyXML->children() as $node) {
		  $nodeArray = simpleXMLToArray($node);
		  $toponym = $hierarchyChild->addChild("toponym");
			$toponym->addAttribute("name", $nodeArray["name"]);
			$toponym->addAttribute("fcode", $nodeArray["fcode"]);			
		}						
	}
		
	// return XML
	if ($onlyXML != 1) {
  	header('Cache-Control: no-store, no-cache, must-revalidate');     // HTTP/1.1 
    header('Cache-Control: pre-check=0, post-check=0, max-age=0');    // HTTP/1.1 
    header("Pragma: no-cache"); 
    header("Expires: 0"); 
  	header("Content-Type: text/xml");
	}
	$HTML = $XML->saveXML();
	
	return $HTML;
}

function pluginSensorsOnNode($nid_int = -1) {
  global $mysql_sd;	
	
	if ($nid_int != -1) $nid = $nid_int;
	
	if (
	  (!isset($nid)) || (!is_numeric($nid))
	) {
    $buffer = "<error>%VAR:PARAMETER_ERROR%</error>";
		$size = sizeof($buffer);
	} else {
		
  	$link = mysql_connect($mysql_sd["host"], $mysql_sd["user"], $mysql_sd["pass"], TRUE);
  	mysql_select_db("sensor_data", $link);
  	echo mysql_error();
  
  	$SQL = "SELECT * FROM sensor_table WHERE sn_uid = $nid";
		// echo $SQL;
  	$result = mysql_query($SQL, $link);
  
  	echo mysql_error();
  
		// create output XML
  	$outXML = '<?xml version="1.0" encoding="UTF-8" ?><node></node>'; 
    
    $XML = new SimpleXMLElement($outXML);	
  	$XML->addAttribute("n", mysql_num_rows($result));	// number
		$XML->addAttribute("id", $nid); // nodeid
	
  	while ($line = mysql_fetch_array($result)) {
		  $sensor = $XML->addChild('sensor');
			$sensor->addAttribute("id", $line["sensor_uid"]);
			$sensor->addAttribute("type", $line["st_uid"]);
  	}
  	
  	mysql_close($link);
		  	  	
  	$buffer = $XML->asXML();
	}		
	
	
	header('Cache-Control: no-store, no-cache, must-revalidate');     // HTTP/1.1 
  header('Cache-Control: pre-check=0, post-check=0, max-age=0');    // HTTP/1.1 
  header("Pragma: no-cache"); 
  header("Expires: 0"); 
	header("Content-Type: text/xml");

	$XML = $buffer;	
  
	return $XML;		 
}

function pluginNodeFromSensor($sid_int = -1) {
  global $mysql_sd;	
	
	if ($sid_int != -1) $sid = $sid_int;
	
	if (
	  (!isset($sid)) || (!is_numeric($sid))
	) {
    $buffer = "<error>%VAR:PARAMETER_ERROR%</error>";
		$size = sizeof($buffer);
	} else {
		
  	$link = mysql_connect($mysql_sd["host"], $mysql_sd["user"], $mysql_sd["pass"], TRUE);
  	mysql_select_db("sensor_data", $link);
  	echo mysql_error();
  
  	$SQL = "SELECT * FROM sensor_table WHERE sensor_uid = $sid";
		// echo $SQL;
  	$result = mysql_query($SQL, $link);
  	if ($line = mysql_fetch_array($result)) {      
  		// create output XML
    	$outXML = '<?xml version="1.0" encoding="UTF-8" ?><node></node>'; 
      
      $XML = new SimpleXMLElement($outXML);	
    	$XML->addAttribute("id", $line["sn_uid"]);	// number
  		$XML->addAttribute("sensorid", $sid); // nodeid	
    	
    	mysql_close($link);
  		  	  	
    	$buffer = $XML->asXML();
		} else {
		  $buffer = apiError("Wrong sensor id!");
		}
  	echo mysql_error();
	}		
	
	
	header('Cache-Control: no-store, no-cache, must-revalidate');     // HTTP/1.1 
  header('Cache-Control: pre-check=0, post-check=0, max-age=0');    // HTTP/1.1 
  header("Pragma: no-cache"); 
  header("Expires: 0"); 
	header("Content-Type: text/xml");

	$XML = $buffer;	
  
	return $XML;		 
}

function pluginSensorType($stid_int = -1) {
  global $mysql_sd;	
	
	if ($stid_int != -1) $stid = $stid_int;
	
	if (
	  (!isset($stid)) || (!is_numeric($stid))
	) {
    $buffer = "<error>%VAR:PARAMETER_ERROR%</error>";
		$size = sizeof($buffer);
	} else {
		
  	$link = mysql_connect($mysql_sd["host"], $mysql_sd["user"], $mysql_sd["pass"], TRUE);
  	mysql_select_db("sensor_data", $link);
  	echo mysql_error();
  
  	$SQL = "SELECT * FROM sensor_type_table WHERE st_uid = $stid";
  	$result = mysql_query($SQL, $link);
  	if ($line = mysql_fetch_array($result)) {      
  		// create output XML
    	$outXML = '<?xml version="1.0" encoding="UTF-8" ?><sensortype></sensortype>'; 
      
      $XML = new SimpleXMLElement($outXML);	
    	$XML->addAttribute("id", $line["st_uid"]);
  		$XML->addAttribute("type", $line["sensor_type"]);
			$XML->addAttribute("featureofmeasurement", $line["measured_phenomenon"]);
			$XML->addAttribute("unitofmeasurement", $line["unit_of_measurement"]);			 	
    	
    	mysql_close($link);
  		  	  	
    	$buffer = $XML->asXML();
		} else {
		  $buffer = apiError("Wrong sensor type id!");
		}
  	echo mysql_error();
	}		
	
	
	header('Cache-Control: no-store, no-cache, must-revalidate');     // HTTP/1.1 
  header('Cache-Control: pre-check=0, post-check=0, max-age=0');    // HTTP/1.1 
  header("Pragma: no-cache"); 
  header("Expires: 0"); 
	header("Content-Type: text/xml");

	$XML = $buffer;	
  
	return $XML;		 
}

include("/resource/xml.inc.php");

function pluginAddEvents($data) {
  global $miner;

	// miner/get-clusters
	$url = $miner["url"] . "load-events";
	
	$xml = getURLPost($url, $data);
	
	if ($xml == -1) return "<error>Error connecting to EnStreaM.</error>";
	  return $xml;
}