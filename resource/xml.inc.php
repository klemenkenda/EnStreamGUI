<?PHP
  // ---------------------------------------------------------------------------
  // FILE: resource/xml.inc.php
  // DESCRIPTION: Functions for working with raw XML
  // AUTHOR: Klemen Kenda (klemen.kenda@ijs.si) - KK
  // ---------------------------------------------------------------------------
  // HISTORY:
  //   
  // ---------------------------------------------------------------------------

  function getRMLink($item) {
	  $start = strpos($item, "href");
		$start += 6;
		$end = strpos($item, "\">");
		$itemLink = substr($item, $start, $end - $start);
		return $itemLink;		
	}
	
	function getRMTitle($item) {
	  $start = strpos($item, "\">") + 2;
		$end = strpos($item, "</a>");
		$itemTitle = substr($item, $start, $end - $start);
		return $itemTitle;		
	}
	
	function getRMSOSUrl($item) {
	  $start = strpos($item, "<dc:source>") + strlen("<dc:source>");
		$end = strpos($item, "</dc:source>");
		$itemTitle = substr($item, $start, $end - $start);
		return $itemTitle;		
	}
	
	function getURL($myurl, $user, $pass) {
	  $url = "http://" . $user . ":" . $pass . "@" . substr($myurl, 7, strlen($myurl) - 7);
	
	  $c = curl_init($url);
  	curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);	
  	curl_setopt($c, CURLOPT_VERBOSE, 1); 
  	curl_setopt($c, CURLOPT_HEADER, 0);
  	// curl_setopt(... whatever other options you want...)
  
    $html = curl_exec($c);
  
  	if (curl_error($c))
      die(curl_error($c));
  
  	// Get the status code
  	$status = curl_getinfo($c, CURLINFO_HTTP_CODE);
  	curl_close($c);		
		
		return $html;
	}
	
	function getURLPost ($url, $fields, $raw = 1) {
  	//url-ify the data for the POST
		$fields_string = $fields;
    if ($raw == 0) {
		  $fields_string = "";
			foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
      rtrim($fields_string, '&');
		}
		    
    //open connection
    $ch = curl_init();
    
    //set the url, number of POST vars, POST data
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, count($fields));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0); 
		curl_setopt($ch, CURLOPT_TIMEOUT, 15); //timeout in seconds
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);	
		
		//execute post
    $html = curl_exec($ch);
    
		if (curl_error($ch)) return -1;
		
    //close connection
    curl_close($ch);
		
		return $html;
	}
	
	
	function getXMLStart($xml, $item, $start = 0, $end = -1) {
	  if (($start == -1) || ($start > strlen($xml))) return -1;
		$pos = strpos($xml, $item, $start);
		if ($pos === false) {
		  return -1;
		} else {
		  if ($end != -1) 
			  if ($pos > $end) return -1;
		  return ($pos);
		}
	}
	
	function getXMLEnd($xml, $item, $start = 0, $end = -1) {
	  $pos = strpos($xml, $item, $start);
		if ($pos === false) {
		  return -1;
		} else {
		  if ($end != -1) 
			  if ($pos > $end) return -1;
		  return ($pos + strlen($item));
		}
	}
	
	function getXML($xml, $itemstart, $itemstop, $start = 0) {
		$startItem = getXMLEnd($xml, $itemstart, $start);			
		$endItem = getXMLStart($xml, $itemstop, $startItem);
		
		if (($startItem == -1) || ($endItem == -1)) return -1;
		
		return substr($xml, $startItem, $endItem - $startItem);
	}

?>