<?PHP
//---------------------------------------------------------------------
// FILE: index.php
// AUTHOR: Klemen Kenda
// DESCRIPTION: EnStreaM GUI main page
// DATE: 11/04/2011
// HISTORY:
//--------------------------------------------------------------------- 

// DEBUG
error_reporting(E_ALL & ~E_NOTICE);
ini_set("display_errors", 1);

// includes -----------------------------------------------------------
include("inc/config.inc.php");
include("inc/sql.inc.php");
include("inc/template.inc.php");
include("inc/plugins.inc.php");

// main program -------------------------------------------------------
import_request_variables("gPC");

// funny bug!!!
$lang = $_GET["lang"];
$lang = "en";

// default language
if (!isset($lang) || ($lang == "")) $lang = "en";

// get lang suffix (sl = prime)
switch ($lang) {
  case "sl": $lang_suffix = "";
	  break;
  case "en": $lang_suffix = "_en";
	break;
}

// virtual file - URL rewriting
$vfile_main = $vfile;

// if there is a virtual file ...
if (isset($vfile_main)) {
	// vfile is filename_parameter.html
	list ($vfile_main, $parameter) = explode("_", $vfile_main, 2);
  
  $SQL = "SELECT * FROM pages WHERE pa_uri = '" . $vfile_main . "'";
	$result = mysql_query($SQL);
	if ($line = mysql_fetch_array($result)) {
  	$id = $line["id"];
	}	
}

// get english page
if ($lang == "en") {
  if (isset($vfile_main)) {
		
    $SQL = "SELECT * FROM pages WHERE pa_uri = '" . $vfile_main . "'";
  	$result = mysql_query($SQL);
  	while ($line = mysql_fetch_array($result)) {
			if (getAbel($line["id"]) == 21) {
			  $id = $line["id"];
			}
  	}
  }
}

// if there is no page id set,
// first page should be displayed
if (!isset($id)) {
  if ($lang == "en") $id = 1;
	else $id = 1;
}

// check hackers
if (!is_numeric($id)) exit();

// load from dB
$SQL = "SELECT * FROM pages WHERE id = $id";
$result = mysql_query($SQL);
$page = mysql_fetch_array($result);

// load templaet file
$HTML = loadTemplate("default.html");

if ($page["id"] == 1) $HTML = loadTemplate("index.html");
// english?
if ($page["pa_weight"] > 100000) $HTML = loadTemplate("blank.html");

// print?
if ($print == 1) $HTML = loadTemplate("print.html");

// let's work on content
handleContent();

handlePlugins();
handleTitle();
handleVars();

// display page
echo $HTML;

// end -----------------------------------------------------------------
exit();	
?>
