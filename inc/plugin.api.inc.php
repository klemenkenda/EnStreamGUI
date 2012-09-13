<?PHP
//---------------------------------------------------------------------
// FILE: plugin.xml.inc.php
// AUTHOR: Klemen Kenda
// DESCRIPTION: XML plugins file
// DATE: 20/12/2011
// HISTORY:
//---------------------------------------------------------------------

function apiError($e) {
  ini_set('error_reporting', 1);
	
	header('Cache-Control: no-store, no-cache, must-revalidate');     // HTTP/1.1 
  header('Cache-Control: pre-check=0, post-check=0, max-age=0');    // HTTP/1.1 
  header("Pragma: no-cache"); 
  header("Expires: 0"); 
	header("Content-Type: text/xml");
		
	$XML = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n<errors><error>" . $e . "</error></errors>";	
	
  return $XML;
}

//---------------------------------------------------------------------
// PLUGIN: APIGET
// Description: Switch for API requests
//---------------------------------------------------------------------

function pluginAPIGET() {
  global $cmd; // command
	global $p; 	 // parameters
		
	$par = explode(":", $p);	// tokenize the parameters
  $pars = sizeof($par);			// get number of parameters
			
	// cross site scripting
	header('Access-Control: allow <*>');
	
	switch ($cmd) {
	  case "current-state": $HTML = pluginPassthruXML(0);
		  break;
		case "stores": $HTML = pluginPassthruXML(1);
		  break;
		case "word-voc": $HTML = pluginPassthruXML(2, $par[0]);
		  break;
		case "get-events": $HTML = pluginPassThruXML(3);
		  break;
		case "get-aggregates":
		  if ($pars == 4) {			 
			  $XML = pluginPassthruXML(4, $par[0], $par[1], $par[2], $par[3]);
				$HTML = aggregateXML2chartXML($XML);				
			} else {
			  $HTML = apiError("Wrong parameter count!");
			}
			break;
		case "get-measurements": 
		  if ($pars == 4) { 
			  $HTML = pluginMeasurementsXML($par[0], $par[1], $par[2], $par[3], 0);
		  } else {
			  $HTML = apiError("Wrong parameter count!");
			}
			break;
		case "get-keywords":
		  if ($pars == 0) {
			  $HTML = pluginKeywordsXML(); 
			} elseif ($pars == 1) {
			  $HTML = pluginKeywordsXML($par[0]);
			} else {
			  $HTML = apiError("Wrong parameter count!");
			}
			break;
		case "get-clusters":
			$HTML = pluginGetClusters();
			break;
		case "sensors-on-node":
		  if ($pars == 1) {
			  $HTML = pluginSensorsOnNode($par[0]);
			} else {
			  $HTML = apiError("Wrong parameter count!");
			} 
			break;
		case "node-from-sensor":
		  if ($pars == 1) {
			  $HTML = pluginNodeFromSensor($par[0]);
			} else {
			  $HTML = apiError("Wrong parameter count!");
			} 
			break;	
		case "sensor-type":
		  if ($pars == 1) {
			  $HTML = pluginSensorType($par[0]);
			} else {
			  $HTML = apiError("Wrong parameter count!");
			} 
			break;
		case "add-events":
		  if ($pars == 1) {
 			  $HTML = pluginAddEvents($par[0]);
			} else {
			  $HTML = apiError("Wrong parameter count!");
			} 
		  break;
		default:
		  $HTML = apiError("Command not correct!");
		  break;
	}
	
	return $HTML;
}

?>
