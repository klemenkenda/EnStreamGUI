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
	
	
	// ---------------------------------------------------------------------------
	// MAIN FUNCTIONS
	// ---------------------------------------------------------------------------
	function getSOSServices() {
	  global $resource_module;
		global $sos_command;
		
  	$url = $resource_module["url"];	
  	$html = getURL($url, $resource_module["user"], $resource_module["pass"]);	
  	
  	// 1. GET LIST OF AVAILABLE SOS SERVERS
  	// a little bit of dirty hacking; simplicity is a bliss
    $ulstart = strpos($html, "<ul>");
  	$ulstart = $ulstart + strlen("<ul>");
  	$ulstop = strpos($html, "</ul>");
  	
  	$ulhtml = substr($html, $ulstart, $ulstop - $ulstart);
  	$ulhtml = str_replace("</li><li>", "&&", $ulhtml);
  	$ulhtml = str_replace("<li>", "", $ulhtml);
  	$ulhtml = str_replace("</li>", "", $ulhtml);
  	
  	$items = explode("&&", $ulhtml);
   
    $i = 0;
    foreach($items as $item) {
  	  $link = getRMLink($item);
  		$title = getRMTitle($item);
  		 
  		if (substr($title, 0, 3) == "SOS") {		 
  		  $links[$i] = $link;
  			$titles[$i] = $title;
  			// 2. CHECK INSIDE ... resource.xml, find SOS URL
  			$resourcexml = getURL($link . "resource.xml", $resource_module["user"], $resource_module["pass"]);
  			$sosurls[$i] = getRMSOSUrl($resourcexml);
  			// EXCEPTIONS - BUGS in resource module (!?)
  			if ($sosurls[$i] == "http://envision.c-s.ro:8080/EnvisionSOS/sos?Request=GetCapabilities&amp;Service=WPS")
  			  $sosurls[$i] = "http://envision.c-s.ro/EnvisionSOS/sos";			
  			$i++;
  		}
  	}
  	
  	// DEBUG INFO
  	// print_r($titles);
  	// print_r($links);
  	// print_r($sosurls);
  		
  	// 3. CHECK IF SOS is ACTIVE
  	$N = $i;
  	
  	for ($i = 0; $i < $N; $i++) {	
  	  echo $sosurls[$i] . ": ";
  		flush();
  	  $html = getURLPost($sosurls[$i], $sos_command['getCapabilities']);
  		if ($html != -1) {
			  echo "<font color=\"green\">OK</font>";
				// enter service in $SQL 
				$SQL = "INSERT INTO sos_index (so_url, so_name) VALUES ('" . $sosurls[$i] . "', '" . $titles[$i] . "')";
				$result = mysql_query($SQL);
				echo "; " . mysql_error();
				
				
			} else echo "<font color=\"red\">NOK</font>";
  		echo "<br>";		
  	}
		
		echo "<br><br>[<a href=\"index.php?step=parse\">Get offerings, properties, start times</a>]";
		
	}
		
	function parseSOSServices() {
	  global $sos_command;
		
		$SQL = "SELECT * FROM sos_index WHERE so_active = 1";
		$result = mysql_query($SQL);
		
		while ($line = mysql_fetch_array($result)) {
		  $response = getURLPost($line["so_url"], $sos_command['getCapabilities']);			
		
			echo "<b>" . $line["so_name"] . ":</b> ";
					
  		// 4. CHECK OFFERINGS and PROPERTIES
  		// due to possible very large files we use simple parsing of text
 		  $startOP = getXMLEnd($response, '<ows:Parameter name="observedProperty">');
			$endOP = getXMLStart($response, '</ows:Parameter>', $startOP);
 			$opN = 0;
  	  // find all allowed values
  		while ($startOP < $endOP) {
  			$startVal = getXMLEnd($response, '<ows:Value>', $startOP);
  			
  		  if ($startVal != -1) {
  			  $startOP = $startVal;
  				$endVal = getXMLStart($response, '</ows:Value>', $startVal);
  				
  				if (($endVal < $endOP) && ($startVal < $endOP)) {						  
  				  $observedProperty[$opN] = substr($response, $startVal, $endVal - $startVal);							
  					$opN++;
  				}						
  			}
  		}
  	
		  // insert properties into DB
			echo "<br><br>Entering observedProperties into DB (reports duplicates already in DB):<br>";
			
			for ($i = 0; $i < $opN; $i++) {
		    echo $observedProperty[$i] . ": ";
			  $SQL_prop = "INSERT INTO sos_properties (sp_name, sp_sosid) VALUES('" . $observedProperty[$i] . "', " . $line["id"] . ")";
				$result_prop = mysql_query($SQL_prop);				
				echo mysql_error();
				echo "<br>";
			}
			
			// 5. BROWSE THROUGH OFFERINGS AND GET START TIME AND OFFERING-PROPERTY BINDS		
  		echo "<br><i>Parse offerings<br>";
			// do we have sos predicate or not (for NOAA)
			$startOL = getXMLEnd($response, '<ObservationOfferingList>');			
			$predicate = "";						
			if ($startOL == -1) {
			  $predicate = "sos:";				
			};
			
			$offeringXML = getXML($response, '<' . $predicate . 'ObservationOfferingList', '</' . $predicate . 'ObservationOfferingList>');
			
			// repeat through all offerings
			$oneOfferingXML = "";
			
			$i = 0;
			$start = 0;
			while (($oneOfferingXML != -1) && ($i < 10000)) {			  
			  $oneOfferingXML = getXML($offeringXML, '<' . $predicate . 'ObservationOffering', '</' . $predicate . 'ObservationOffering>', $start);
				
				// extract offering name
				$startName = getXMLEnd($oneOfferingXML, "gml:id=\"");
				$endName = getXMLStart($oneOfferingXML, "\"", $startName);
				$name = substr($oneOfferingXML, $startName, $endName - $startName);
				
				// extract offering start time
				$timeXML = getXML($oneOfferingXML, "<gml:TimePeriod", "</gml:TimePeriod>");
				// echo $timeXML;
				
				$time = getXML($timeXML, "<gml:beginPosition>", "</gml:beginPosition>");
			
				// ignore false offerings
				if ($time != -1) {
				  // exceptions for combined offerings (NOAA and BRGM tested)
				  if (($name != "network-all") && ($name != "offering-allSensor")) {										
				    echo $name . " - startTime(" . $time . "): ";
						
						// insert offering into DB
						$SQL_offr = "INSERT INTO sos_offering (sf_offeringname, sf_sosid) VALUES ('" . $name . "', " . $line["id"] . ")";
						$result_offr = mysql_query($SQL_offr);
						echo mysql_error();
						$SQL_offr2 = "SELECT * FROM sos_offering WHERE sf_offeringname = '" . $name . "' AND sf_sosid = " . $line["id"];
						$result_offr2 = mysql_query($SQL_offr2);
						$line_offr2 = mysql_fetch_array($result_offr2);
						$offeringId = $line_offr2["id"];
						
						echo " ID: " . $offeringId;
									
						echo "<blockquote><b>Property binds:</b><br>";					
						
						// find offering-property binds
						$startOP = 0;
						while ($startOP != -1) {
						  $startOP = getXMLEnd($oneOfferingXML, '<' . $predicate . 'observedProperty xlink:href="', $startOP);
							$endOP = getXMLStart($oneOfferingXML, '"/>', $startOP);
							if (($startOP != -1) && ($endOP != -1)) { 
							  $propertyStr = substr($oneOfferingXML, $startOP, $endOP - $startOP);
								// workaround for NOAA SOS
								$propertiesArray = explode("/", $propertyStr);								
								$property = $propertiesArray[count($propertiesArray) - 1];
								echo $property . ": ";
								
								// get property from DB
								$SQL_prop = "SELECT * FROM sos_properties WHERE sp_name = '" . $property . "' AND sp_sosid = " . $line["id"];
								$result_prop = mysql_query($SQL_prop);
								$line_prop = mysql_fetch_array($result_prop);
								$propertyId = $line_prop["id"];
								
								$mysqlDate = substr($time, 0, 10);							 
								
								// insert bind into DB
								$SQL_bind = "INSERT INTO sos_offeringprop (so_propertyid, so_offeringid, so_dateupdated) VALUES ($propertyId, $offeringId, '$mysqlDate')";
								$result_bind = mysql_query($SQL_bind);
								echo mysql_error();
								
  							echo "<br>";
							}
						}
						echo "</blockquote>";
						
						
				    echo "<br>";
					}
				}
				
				$start += strlen($oneOfferingXML);
				$i++;
			}
			
			
			echo "</i>"; // end parsing offerings
  					
			echo "<br>";
		}
		echo "<br><br>Parsing finished. Ensure that CRON is running for reading data from SOS into EnStreaM!";
	}
	
	// ---------------------------------------------------------------------------
	// MAIN PROGRAM
	// ---------------------------------------------------------------------------
	$step = "";
	import_request_variables("gPC");
	if ($step == "parse") parseSOSServices();
	else getSOSServices();
	
?>
