<?PHP
// DEBUG
error_reporting(E_ALL & ~E_NOTICE);
ini_set("display_errors", 1);

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



// main program ---------------------------------------------------------------
$miner["url"] = "http://localhost:9988/";
$miner["stream_timeout"] = 200;
$miner["socket_timeout"] = 100;

import_request_variables("gPC");

$pars = str_replace("|", "&", $p);
$url = $miner["url"] . $cmd . "?" . urlencode($pars);

$url = str_replace("%3D", "=", $url);
$url = str_replace("%26", "&", $url);

//$url = "http://localhost:9988/op-search?q=%7B%22%24from%22%3A%22SensorAggregate%22%2C+%22Phenomenon%22%3A%22precipitation%22%7D";

// $url = "http://localhost:9988/op-search?event=a&q=%7B%22%24from%22%3A%22SensorAggregate%22%2C+%22Type%22%3A%22SUM%22%7D";

// echo $url;
// exit();

// $url = "http://xpack.ijs.si:9457/op-telco-alarms?op=rc&devid=1000&evid=99195&tmwin=5";
// $url = "http://xpack.ijs.si:9989/current-state";

$XML = passthruHTTP($url);
$size = strlen($XML);

header('Cache-Control: no-store, no-cache, must-revalidate');     // HTTP/1.1 
header('Cache-Control: pre-check=0, post-check=0, max-age=0');    // HTTP/1.1 
header ("Pragma: no-cache"); 
header("Expires: 0"); 
header("Content-Type: text/xml");
header("Content-Length: " . $size);


echo $XML;
?>