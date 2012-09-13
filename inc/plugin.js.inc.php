<?PHP
//---------------------------------------------------------------------
// FILE: plugin.xml.inc.php
// AUTHOR: Klemen Kenda
// DESCRIPTION: JS plugins file
// DATE: 20/12/2010
// HISTORY:
//---------------------------------------------------------------------

//---------------------------------------------------------------------
// PLUGIN: JS Translations
// Description: Transfer variables into JS
//---------------------------------------------------------------------

function pluginJSTranslations() {
  global $lang_suffix;
	
	$SQL = "SELECT * FROM variables";
	$result = mysql_query($SQL);
	
	$HTML .= "var translationSQL = new Array();\n";
	
	while ($line = mysql_fetch_array($result)) {
	  $HTML .= "translationSQL[\"" . $line["va_name"] . "\"] = '";
		if ($line["va_nohtml"] == 0) $HTML .= $line["va_value$lang_suffix"]; 
		else $HTML .= strip_tags($line["va_value$lang_suffix"]);
		$HTML .= "';\n";
	}
	
	$HTML .= "translationSQL[\"MINER_URL\"] = '" . $miner["url"] . "';\n";
	
	$func =<<<EOJ
	
function getTranslation(name) {
  if (translationSQL[name] != undefined)
    return translationSQL[name];
  else return name;
};
EOJ;
	
	$HTML .= $func;
	
	header("Content-Type: text/javascript");
  header("Content-Length: " . sizeof($HTML));
	
	return $HTML;
}

