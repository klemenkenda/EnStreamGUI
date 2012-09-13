<?PHP
  // ---------------------------------------------------------------------------
  // FILE: resource/index.php
  // DESCRIPTION: Browsing Resource module for SOS services and parsing
  //              basic properties of the service.
  // AUTHOR: Klemen Kenda (klemen.kenda@ijs.si) - KK
  // ---------------------------------------------------------------------------
  // HISTORY:
  //   
  // ---------------------------------------------------------------------------

  // Report all PHP errors (see changelog)
	error_reporting(E_ALL);
	ini_set('error_reporting', E_ALL);
	
	// includes 
	include("../inc/config.inc.php");
	include("../inc/sql.inc.php");
	include("SOS_XML_commands.inc.php");
	include("xml.inc.php");
	include("mysqltime.inc.php");

  // 1. SELECT PROP-OFFERING BIND with EARLIEST DATE
	$SQL = "SELECT * FROM sos_offering, sos_index, sos_properties, sos_offeringprop WHERE " .
	       "  sos_offering.id = so_offeringid AND sos_properties.id = so_propertyid AND " .
				 "  sos_index.id = sf_sosid AND so_active = 1 ORDER BY so_dateupdated LIMIT 1";
	$result = mysql_query($SQL);
	$offering = mysql_fetch_array($result);

	// ADD EXCEPTION FOR INTENSIVE SOS SERVICES
	
	// 2. SOS GETMEASUREMENTS
	$command = $sos_command['getObservation'];
	$command = str_replace("%offering%", $offering["sf_offeringname"], $command);
	$command = str_replace("%property%", $offering["sp_name"], $command);
	
	$beginDate = mktime(0, 0, 0, getMonth($offering["so_dateupdated"]), getDay($offering["so_dateupdated"]), getYear($offering["so_dateupdated"])); 	
	$endDate = $beginDate + $sos_interval_secs;	
	$beginDateStr = date("Y-m-d", $beginDate);
	$endDateStr = date("Y-m-d", $endDate);
	
	$command = str_replace("%beginPosition%", $beginDateStr, $command);
	$command = str_replace("%endPosition%", $endDateStr, $command);
	
	echo "Asking " . $offering["so_name"] . " for " . $offering["sf_offeringname"] . "/" . $offering["sp_name"] . " from " . $beginDateStr . " to " . $endDateStr . ".<br>\n";

	echo $offering["so_url"] . "<br>\n";
	echo $command;
	
	
	$response = getURLPost($offering["so_url"], $command);
	
	// 3. VALIDATE RESPONSE
	// simple hack - can we find <om:Observation>
	if (getXMLStart($response, "<om:Observation") != -1) {
	  // validation OK - update offering data
		echo "SOS response OK: updating DB";
		$SQL_update = "UPDATE sos_offeringprop SET so_dateupdated = '$endDateStr' WHERE id = " . $offering["id"];
		$result_update = mysql_query($SQL_update);
		echo "; " . mysql_error();
					
	  // 4. FILL MEASUREMENTS TO ENSTREAM
		// send via post
		$miner_url = $miner["base_url"] . ":" . ($miner["start_port"] + $offering["sf_sosid"]) . "/";
		$xml = getURLPost($miner_url . "sos-om-update", $response);
		if ($xml == -1) echo "Error connecting to EnStreaM.";
		  else echo $xml;
	} else {
	  echo "SOS response NOK";
		echo $response;
	}
	 
	
		
?>