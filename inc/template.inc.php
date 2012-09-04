<?PHP
//---------------------------------------------------------------------
// FILE: template.inc.php
// AUTHOR: Klemen Kenda
// DESCRIPTION: template handling file
// DATE: 22/11/2005
// HISTORY:
//   17/03/2009: Returning value of template HTML, not writing
//               into $HTML (Klemen Kenda)
//---------------------------------------------------------------------

//---------------------------------------------------------------------
// loadTemplate - loads template from template file
//---------------------------------------------------------------------
function loadTemplate($file) {
  global $filesystem_root;
  $fp = fopen($filesystem_root . "template/$file", "r");
	$template = fread($fp, 65535);
	return $template;
}

//---------------------------------------------------------------------
// handlePlugin - replaces plugin tag with proper plugin return
//---------------------------------------------------------------------
function handlePlugin($name) {
  global $HTML, $page;
	
	if ($page["pa_$name"] != "") {
	  // dobimo listo vseh pluginov
		$pluginslist = explode("&", $page["pa_$name"]);
	
		for ($i = 0; $i < count($pluginslist); $i++) {	
    	$plugin = "plugin" . $pluginslist[$i];
  	  if (function_exists($plugin)) $pluginreturn .= $plugin();
  	    else $pluginreturn .= "Plug-in $plugin ne obstaja!<br><br>";
		} // for			
			
	} else {
	  $pluginreturn = "";
	}	

  $HTML = str_replace("%$name%", $pluginreturn, $HTML);  				
}

//---------------------------------------------------------------------
// handlePermanentPlugin - replaces plugin tag with proper plugin return
//---------------------------------------------------------------------
function handlePermanentPlugin($name, $plugin) {
  global $HTML;
	
 	$plugin = "plugin" . $plugin;
  if (function_exists($plugin)) $pluginreturn = $plugin();
    else $pluginreturn = "Plug-in $plugin ne obstaja!<br><br>";

  $HTML = str_replace("%$name%", $pluginreturn, $HTML);  				
}

//---------------------------------------------------------------------
// handlePlugins - replaces all the plugin tags with proper HTML
//---------------------------------------------------------------------
function handlePlugins() {
  global $lang;
	
  handlePlugin("plugin1");
  handlePlugin("plugin2");
  handlePlugin("plugin3");
	
	handlePermanentPlugin("keywords", "Keywords");
	handlePermanentPlugin("description", "Description");
  /*
	handlePermanentPlugin("menu", "Menu");
	handlePermanentPlugin("menutop", "MenuTop");
  handlePermanentPlugin("lang", "Lang");
	handlePermanentPlugin("login", "LogIn");
	*/
	/* handlePermanentPlugin("footer", "Footer");
	handlePermanentPlugin("printjs", "PrintJS");				
	handlePermanentPlugin("language", "Language");
	*/
}

//---------------------------------------------------------------------
// handleTitle - replaces title tag with the real title
//---------------------------------------------------------------------
function handleTitle() {
  global $HTML, $page;	
	global $lang_suffix, $lang;
	global $title_add;
	global $htmltitle_add;
	global $nid, $aid, $jid;
	
	$title = $page["pa_title$lang_suffix"] . $title_add;
	$htmltitle = $page["pa_title$lang_suffix"] . $htmltitle_add;		
	
 	$HTML = str_replace("%title%", $title, $HTML);
	$HTML = str_replace("%htmltitle%", $htmltitle, $HTML);		
}

function add2Title($add) {
  global $title_add;	
	$title_add .= $add;
	add2HTMLTitle($add);
}

function add2HTMLTitle($add) {
  global $htmltitle_add;
	
	$htmltitle_add .= $add;
}


//---------------------------------------------------------------------
// handleContent - replaces content tag with the real content
//---------------------------------------------------------------------
function handleContent() {
  global $HTML, $page;
	global $lang_suffix;
	
	$content = $page["pa_content$lang_suffix"];

	// preverimo redirect
	$plain_content = strip_tags($content);
	if (substr($plain_content, 0, 9) == "#REDIRECT") {
	  $url = substr($plain_content, 10, 1024);
		header("Location: " . $url . "\n\n");
		exit();
	}
		
	$HTML = str_replace("%content%", $content, $HTML); 
}

//---------------------------------------------------------------------
// handleVars - replaces var tags with the real content
//---------------------------------------------------------------------
function handleVars() {
  global $HTML;
	global $lang_suffix;
	global $link_mysql;
	
	$SQL = "SELECT * FROM variables";
	$result = mysql_query($SQL, $link_mysql);
	while ($line = mysql_fetch_array($result)) {
	  $var = $line["va_value$lang_suffix"];
		if ($line["va_nohtml"] == 1) $var = strip_tags($var);
		$HTML = str_replace("%VAR:" . $line["va_name"] . "%", $var, $HTML);
	}	 	 
}

//---------------------------------------------------------------------
// handleContent - replaces content tag with the real content
//---------------------------------------------------------------------
function handleVarContent() {
  global $HTML, $page;
	global $lang;
	
	$SQL = "SELECT * FROM variables WHERE va_name = 'MAIN_" . strtoupper($lang) . "'";
	$result = mysql_query($SQL);
	$line = mysql_fetch_array($result);
	
	$content = $line["va_value"];
		
	$HTML = str_replace("%content%", $content, $HTML); 
}

//---------------------------------------------------------------------
// handleGraphics - replaces image / screenshot tags
//---------------------------------------------------------------------
function handleGraphics() {
  // handleImages();
	handleScreenshots();
  
}

// -----------------------------------------------
// PLUGIN: Screenshots
// DESCRIPTION: Izpise screenshot
// -----------------------------------------------

function handleScreenshots() {
  global $HTML;
	global $id;
	global $lang_suffix;
	
	// poiscemo, ce imamo screenshot
	if ($pos = strpos($HTML, "%screenshots")) {
	  $pos_del = strpos($HTML, "|", $pos + 1);
		$pos_end = strpos($HTML, "%", $pos + 1);
  }
	
	// parsamo parameter (left, right, center)
	$par = substr($HTML, $pos_del + 1, $pos_end - $pos_del - 1);
	
	// pogledamo, ce imamo sploh kaksen aktualen screenshot
	$SQL = "SELECT * FROM screenshots WHERE sc_pageid = $id AND sc_title$lang_suffix <> '' ORDER BY sc_weight";
	$result = mysql_query($SQL);

	$screenshots = "<div class=\"pirobox_in pirobox_$par\">";	
	
	// ce ni nobenih zadetkov
	if (mysql_num_rows($result) == 0) {
	  // samo pobrisemo tag
	  $screenshots = "";
	} else {
	  // sicer naredimo, kar je narediti treba :)
		
		$screenshots .= "<ul class=\"thumbs_all\">";
		
		while ($line = mysql_fetch_array($result)) {
		  $screenshots .= "<li><a href=\"/images/picdb.php?id=" . $line["id"] . "\" rel=\"lightbox\" class=\"lightbox\" title=\"" . strip_tags($line["sc_description$lang_suffix"]) . "\"><img src=\"/images/picdbcrop.php?id=" . $line["id"] . "&max=197\" alt=\"" . $line["sc_title$lang_suffix"] . "\"></a></li>";
		
		}		
		$screenshots .= "</ul>";		

    $screenshots .= "<div id=\"div-sc-nav\">";
    
    $screenshots .= loadTemplate("screenshots/nav_prev.html");
    $screenshots .= loadTemplate("screenshots/nav.html");
    $screenshots .= loadTemplate("screenshots/nav_next.html");	
    
    
    $screenshots .= "</div>";	
		
		$screenshots .= "</div>";	
	}
	
	$HTML = str_replace("%screenshots|" . $par . "%", $screenshots, $HTML);	
}


//---------------------------------------------------------------------
// formatDate - SQL --> readable date conversion
//---------------------------------------------------------------------

function formatDateObsolete($datum) {
  /*
	$dan = (substr($datum, 8, 2) + 0);
	$mesec = (substr($datum, 5, 2) + 0);
	$leto = (substr($datum, 2, 2) + 0);
	$dnevi = array("nedelja", "ponedeljek", "torek", "sreda", "četrtek", "petek", "sobota");
	$dantxt = $dnevi[date("w", mktime(0,0,0,$mesec,$dan,$leto))];
	*/
	
  return /* $dantxt . ", " . */(substr($datum, 8, 2)) . "." . (substr($datum, 5, 2)) . "." . substr($datum, 2, 2);
};

function formatDate($datum, $type = 1) {
  global $lang;
	
	$month = array(
	  "en" => array("January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"),
		"sl" => array("januar", "februar", "marec", "april", "maj", "junij", "julij", "avgust", "september", "oktober", "november", "december")
	);

	$dan = (substr($datum, 8, 2) + 0);
	$mesec = (substr($datum, 5, 2) + 0);
	$leto = (substr($datum, 2, 2) + 0);	
	$dnevi = array("nedelja", "ponedeljek", "torek", "sreda", "četrtek", "petek", "sobota");
	$dantxt = $dnevi[date("w", mktime(0,0,0,$mesec,$dan,$leto))];
	
  switch ($type) {
	  case 1:
		  return /* $dantxt . ", " . */(substr($datum, 8, 2)) . "." . (substr($datum, 5, 2)) . "." . substr($datum, 2, 2);
		break;
		case 2:
		  return $month[$lang][substr($datum, 5, 2) - 1] . " " . substr($datum, 0, 4);			
		break;
		case 3:
		  return $dantxt . ", " . (substr($datum, 8, 2)) . "." . (substr($datum, 5, 2)) . "." . substr($datum, 0, 4);
		break;
	} 
  /*
	$dan = (substr($datum, 8, 2) + 0);
	$mesec = (substr($datum, 5, 2) + 0);
	$leto = (substr($datum, 2, 2) + 0);
	$dnevi = array("nedelja", "ponedeljek", "torek", "sreda", "četrtek", "petek", "sobota");
	$dantxt = $dnevi[date("w", mktime(0,0,0,$mesec,$dan,$leto))];
	*/
	
  // return /* $dantxt . ", " . */(substr($datum, 8, 2)) . "." . (substr($datum, 5, 2)) . "." . substr($datum, 2, 2);
};

//---------------------------------------------------------------------
// formatSize - size in bytes --> human readable size
//---------------------------------------------------------------------

function formatSize($size) {
  if ($size < 1000) return $size . "B";
	$size = $size / 1024;
	if ($size < 1000) return round($size) . "kB";
	$size = $size / 1024;
	if ($size < 1000) return round($size) . "MB";
	$size = $size / 1024;
	return round($size) . "GB";
}

//---------------------------------------------------------------------
// filterEmails - replaces exposed e-mail addresses with encoded JS
//---------------------------------------------------------------------

function emailEncode($match) {
	$matchenc = "naslov" . $match;
	$matchenc = base64_encode($matchenc);
  return "<script language=\"JavaScript\" type=\"text/javascript\">decode_mail('$matchenc');</script>";  
}

function filterEmails() {
  global $HTML;
	// najdemo vse e-naslove
	preg_match_all("/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}/", $HTML, $matches);

  // poiscemo vse pojavitve mailto: in celoten pripadajoci <a href="mailto:*" *> string	
	foreach($matches[0] as $match) {
	  $i = 0;	  
	  while (($pos = strpos($HTML, "mailto:" . $match)) && ($i < 40)) {
		  $i++;
		  $start = strrpos(substr($HTML, 0, $pos), "<a href");
			$stop = strpos($HTML, ">", $pos);			
			
			$string = substr($HTML, $start, $stop - $start + 1);
			
			$HTML = str_replace($string, emailEncode($string), $HTML);
		};    
		//	$HTML = str_replace("mailto:" . $match, emailEncode("mailto:" . $match), $HTML);
		$HTML = str_replace($match, emailEncode($match), $HTML);
	}
}

//---------------------------------------------------------------------
// getAbel - gets the first ancestor of article after Adam (main)
//---------------------------------------------------------------------
function getAbel($id) {
 	$pid = $id;
  $i = 0;
	$abelid = 0;
  while (($pid != 1) && ($i < 10)) {
    $i++;
	  $SQL = "SELECT pa_pid, id FROM pages WHERE id = $pid";
 	  $result = mysql_query($SQL);
    $row = mysql_fetch_array($result);
		$pid = $row["pa_pid"];
	  $abelid = $row["id"];
  }				
  return $abelid;
}

//---------------------------------------------------------------------
// getEnos - gets the second ancestor of article after Adam (main) and 
//           Abel/Seth his other brother 
//---------------------------------------------------------------------
function getEnos($id) {
 	$pid = $id;
  $i = 0;
	$enosid = 0;
	$abelid = getAbel($id);
  while (($pid != $abelid) && ($i < 10)) {
    $i++;
	  $SQL = "SELECT pa_pid, id FROM pages WHERE id = $pid";
 	  $result = mysql_query($SQL);
    $row = mysql_fetch_array($result);
		$pid = $row["pa_pid"];
	  $enosid = $row["id"];
  }				
  return $enosid;
}

// month format
function formatMonth($month) {
  $months = array("januar", "februar", "marec", "april", "maj", "junij", "julij", "avgust", "september", "oktober", "november", "december");
	return $months[$month - 1];
};  

?>
