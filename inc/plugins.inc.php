<?PHP
//---------------------------------------------------------------------
// FILE: plugins.inc.php
// AUTHOR: Klemen Kenda
// DESCRIPTION: plugins file
// DATE: 15/04/2011
// HISTORY:
//---------------------------------------------------------------------

// includes of other plugins in the end of file

//---------------------------------------------------------------------
// PLUGIN: Menu
//---------------------------------------------------------------------

function getLangPageLink($lang, $lang_suffix, $page) {
  // ce page v drugem jeziku ni prazen
	if ($page["pa_title$lang_suffix"] != "") {
    if ($page["pa_uri$lang_suffix"] != "") $link = "/$lang/" . $page["pa_uri$lang_suffix"] . ".html";
   	  else $link = "/$lang/" . $page["id"];
  } else {
	  $SQL = "SELECT * FROM pages WHERE id = " . $page["pa_pid"];
		$result = mysql_query($SQL);
		$page = mysql_fetch_array($result);
		$link = getLangPageLink($lang, $lang_suffix, $page);
	}
		
	return $link;	
}

function getLangLink($lang, $page, $nid, $rid, $aid, $p) {
  // handle default language
  if ($lang != "sl") $lang_suffix = "_" . $lang;
	  else $lang_suffix = "";	
		
  $uri["news"]["sl"] = "novice";
	$uri["news"]["en"] = "news";

 	$link = getLangPageLink($lang, $lang_suffix, $page);
			
	// ce smo v novici
  if (isset($nid)) {	  
		$SQL = "SELECT * FROM news WHERE id = $nid";
		$result = mysql_query($SQL);
		$line = mysql_fetch_array($result);
		// ce je nastavljen novi naslov, vrnemo link z naslovom, sicer obicajni link na page z vsemi novicami
		if ($line["ne_title$lang_suffix"] != "") $link = "/$lang/" . $uri["news"][$lang] . "/" . $nid . "/" . $p . "/" . getNewsLink($line["ne_title$lang_suffix"]);		
	} elseif (isset($rid)) {
		$SQL = "SELECT * FROM clientreferences WHERE id = $rid";
		$result = mysql_query($SQL);
		$line = mysql_fetch_array($result);
		// ce je nastavljen novi naslov, vrnemo link z naslovom, sicer obicajni link na page z vsemi novicami
		if ($line["re_title$lang_suffix"] != "") $link = "/$lang/" . $uri["references"][$lang] . "/" . $rid . "/" . $p . "/" . getNewsLink($line["re_title$lang_suffix"]);			  
	} elseif (isset($aid)) {
		$SQL = "SELECT * FROM articles WHERE id = $aid";
		$result = mysql_query($SQL);
		$line = mysql_fetch_array($result);
		// ce je nastavljen novi naslov, vrnemo link z naslovom, sicer obicajni link na page z vsemi novicami
		if ($line["ar_title$lang_suffix"] != "") $link = "/$lang/" . $uri["articles"][$lang] . "/" . $aid . "/" . $p . "/" . getNewsLink($line["ar_title$lang_suffix"]);			  	
	}
		
	return $link;
}

function getLink($page) {
  global $lang;
	global $lang_suffix;
	
	if ($page["pa_uri$lang_suffix"] != "") $link = "/$lang/" . $page["pa_uri$lang_suffix"] . ".html";
	  else $link = "/$lang/" . $page["id"];
		
	return $link;
}

function pluginMenu() {
  global $page;
	global $lang;
	global $id;
	
	$abelid = getAbel($id);
	$SQL = "SELECT * FROM pages WHERE id = $abelid";
	$result = mysql_query($SQL);
	$abel = mysql_fetch_array($result);
	
	$SQL = "SELECT * FROM pages WHERE pa_title <> '' AND pa_pid = " . $id . " AND id <> 1 AND pa_weight < 10000 ORDER BY pa_weight";
	$result = mysql_query($SQL);	
	
	if (mysql_num_rows($result) == 0)	{
	  $newid = getAbel($id);
		// if ($id == $newid) $newid = 1;
		$SQL = "SELECT * FROM pages WHERE pa_title <> '' AND pa_pid = " . $newid . " AND id <> 1 AND pa_weight < 10000 ORDER BY pa_weight";
	  $result = mysql_query($SQL);	
	  
	}
	
	if (($id == 12) || ($id == 20) || ($id == 26)) {
	  $alterid = 1;
		if ($id == 20) $alterid = 5;
	  $SQL = "SELECT * FROM pages WHERE pa_title <> '' AND pa_pid = $alterid AND id <> 1 AND pa_weight < 10000 ORDER BY pa_weight";
	  $result = mysql_query($SQL);	
	}
	
	if ($id == 21) {
	  $alterid = 21;
	  $SQL = "SELECT * FROM pages WHERE pa_title <> '' AND pa_pid = $alterid AND id <> 1 AND pa_weight < 10000 ORDER BY pa_weight";
	  $result = mysql_query($SQL);	
	}
		
	$HTML .= "<ul class=\"list1\">";
	
	if (($id != 1) && ($abelid != 28) && ($lang != "en")) {
	  $link = getLink($abel);
	  $HTML .= "<li  class=\"parent\"><a href=\"$link\">" . $abel["pa_title"] . "</a></li>";
	}
	
	while ($line = mysql_fetch_array($result)) {
	  $link = getLink($line);
	  $HTML .= "<li><a href=\"$link\">" . $line["pa_title"] . "</a></li>";
	}
	
	$HTML .= "</ul>";
	
	return $HTML;
}

function pluginMenuTop() {
  global $page;
	global $lang;
	global $id;
	
	$pid = 1;
	
	if ($lang == "en") $pid = 21;
	
	$SQL = "SELECT * FROM pages WHERE pa_title <> '' AND pa_pid = " . $pid . " AND id <> $pid AND pa_weight < 10000 ORDER BY pa_weight";
	$result = mysql_query($SQL);			
		
	$HTML .= "<ul class=\"menutop\">";
	
	while ($line = mysql_fetch_array($result)) {
	  $link = getLink($line);
		if (($line["id"] == getAbel($id)) || 
		    (($line["id"] == 2) && (($id == 1) || ($id == 12))) ||
				(($line["id"] == 22) && (($id == 21)))
			 ) $myclass = "class=\"current\"";
		  else $myclass = "";
	  $HTML .= "<li><a href=\"$link\" $myclass>" . $line["pa_title"] . "</a></li>";
	}
	
	$HTML .= "</ul>";
	
	return $HTML;
}

function subMenu($page) {
  $SQL = "SELECT * FROM pages WHERE pa_pid = " . $page["id"] . " AND pa_weight < 10000 ORDER BY pa_weight";	
	$result = mysql_query($SQL); 

  $link = getLink($page);
	
	if (mysql_num_rows($result) > 0) {
    $HTML .= "<li><a href=\"$link\" class=\"parent\"><span>" . $page["pa_title"] . "</span></a>";
		$HTML .= "<ul>";
   	
		while ($line = mysql_fetch_array($result)) {
		  $HTML .= subMenu($line);
		}

		$HTML .= "</ul>";
		$HTML .= "</li>";
	} else {
	  $HTML .= "<li><a href=\"$link\"><span>" . $page["pa_title"] . "</span></a></li>";
	}
	
	return $HTML;
}

function fillTemplate($template, $args) {
  foreach($args as $key => $val) {
	  $template = str_replace("%" . $key . "%", $val, $template); 
	}	
	
  return $template;
}

// -----------------------------------------------
// PLUGIN: PrintJS
// DESCRIPTION: Izpise JS za print
// -----------------------------------------------
function pluginPrintJS() {
	foreach ($_GET as $key => $val) {
	  $parameters .= "&";
		$parameters .= $key . "=" . $val;	  
	};
	
	foreach ($_POST as $key => $val) {	  
		$parameters .= "&";
		$parameters .= $key . "=" . $val;	  
	};

 
  $JS = <<<EOF
  function printWindow(){
    window.open("/index.php?print=1$parameters","print", "width=620,height=550,location=no,menubar=no,directories=no,toolbar=no,scrollbars=yes,resizable=no,status=no");
  }
EOF;
return $JS;
}


// -----------------------------------------------
// PLUGIN: SiteMap
// DESCRIPTION: Izpise sitemap
// -----------------------------------------------

function sitemapRecursion($pid) {
  global $lang;
	global $lang_suffix;
	
  $SQL = "SELECT * FROM pages WHERE pa_title$lang_suffix <> '' AND pa_pid = $pid AND id <> 1 ORDER BY pa_weight";
  $result = mysql_query($SQL);

	$i = 0;
	while($line = mysql_fetch_array($result)) {
    $i++;
		if ($pid == 1) {
		  $b = "<b>";
			$nb = "</b>";
		} else {
		  $b = ""; 
			$nb = "";
		}
		$link = getLangPageLink($lang, $lang_suffix, $line);
		$wholelink = "<a href=\"$link\">";
		$nwholelink = "</a>";
		if (($line["pa_content$lang_suffix"] == "") && ($line["pa_plugin1"] == "") && ($line["pa_plugin2"] == "") && ($line["pa_plugin3"] == "")) {
		  $wholelink = "";
			$nwholelink = "";
		}
  	$lsitemap .= "<li>$b$wholelink" . $line["pa_title$lang_suffix"] . "$nb$nwholelink";					
	  if ($line["id"] != $pid) $lsitemap .= sitemapRecursion($line["id"]);			
		$lsitemap .= "</li>\n";					
	}

	if ($i > 0) {
  	return "<ul class=\"ul-blue\">" . $lsitemap . "</ul>";
	} else {
	  return "";
	}						
}

function pluginSitemap() {
  global $lang;
  $start = 1;
	if ($lang == "en") $start = 74;
	//$sitemap = "<br>";		
	$sitemap .= sitemapRecursion($start);		
  return $sitemap;
}

// -----------------------------------------------
// PLUGIN: Breadcrumb
// DESCRIPTION: Doda breadcrumb
// -----------------------------------------------

function breadCrumb($id, $crumb) {
  global $lang_suffix;
	global $lang;
	global $breadcrumb_next;
	
	if ($id == 1) return "<a href=\"/$lang/index.html\">Sinergise</a>" . $crumb;	
	
	$SQL = "SELECT * FROM pages WHERE id = $id";
	$result = mysql_query($SQL);
	$line = mysql_fetch_array($result);
	
 	$link_before = "<a href=\"" . getLink($line) . "\">";
	$link_after = "</a>";	
	
	$crumb = breadCrumb($line["pa_pid"], $breadcrumb_next . $link_before . $line["pa_title$lang_suffix"] . $link_after . $crumb);	
	return $crumb;		
}

function pluginBreadcrumb() {
  global $page;
	global $lang_suffix;
	global $breadcrumb_next;
	
	$breadcrumb_next = loadTemplate("breadcrumb/next.html");
	
	return loadTemplate("breadcrumb/intro.html") . breadCrumb($page["id"], $crumb);	
}

// -----------------------------------------------
// PLUGIN: TranslateJS
// DESCRIPTION: Doda Google JS API za prevajanje v head
// -----------------------------------------------

function pluginTranslateJS() {
  global $page;
	global $lang;
	
	$HTML = <<<EOJS
	 <script type="text/javascript" src="http://www.google.com/jsapi">
   </script>
   <script type="text/javascript">
    google.load("language", "1");

    function initialize() {
      var text = document.getElementById("div-content").innerHTML;
      google.language.detect(text, function(result) {
        if (!result.error && result.language) {
          google.language.translate(text, result.language, "$lang",
                                    function(result) {
            var translated = document.getElementById("div-content");
            if (result.translation) {
              translated.innerHTML = result.translation;
            }
          });
        }
      });
    }
    google.setOnLoadCallback(initialize);

    </script>
	
EOJS;

  if (($lang == "sl") || ($lang == "en")) $HTML = "";
	return $HTML;
}
	
// -----------------------------------------------
// PLUGIN: Language
// DESCRIPTION: Naredi lang izbiro na vrhu
// -----------------------------------------------


function makeLanguage($template, $slo_before, $slo_after, $eng_before, $eng_after) {
  return fillTemplate($template, 
	  array(
		  "slo_before" => $slo_before,
			"slo_after" => $slo_after,
		  "eng_before" => $eng_before,
			"eng_after" => $eng_after
		)
	);
}
function pluginLanguage() {
  global $page;
	global $lang, $lang_suffix;
	// id novic, referenc ali articla
	global $nid, $rid, $aid, $p;
	

  // nalozimo template
  $template = loadTemplate("language/language.html");
	
	// nastavimo vse na prazen niz
	$slo_before = $slo_after = $eng_before = $eng_after = "";
	
	if ($lang == "en") {
  	// nastavimo linke
	  $slo_before = "<a href=\"" . getLangLink("sl", $page, $nid, $rid, $aid, $p) . "\">";
		$slo_after = "</a>";
	}	elseif ($lang == "sl") {
	  $eng_before = "<a href=\"" . getLangLink("en", $page, $nid, $rid, $aid, $p) . "\">";
		$eng_after = "</a>";
	}
	
	// obdelamo in vrnemo template
	return makeLanguage($template, $slo_before, $slo_after, $eng_before, $eng_after);
}

// -----------------------------------------------
// PLUGIN: Lang
// DESCRIPTION: Vrne kodo jezika
// -----------------------------------------------

function pluginLang() {
  global $lang;
	
	if ($lang == "en") $HTML = "<a href=\"/sl/index.html\"><img src=\"/template/images/sl.gif\" alt=\"Slovensko\"></a>";
	  else $HTML = "<a href=\"/en/index.html\"><img src=\"/template/images/en.gif\" alt=\"English\"></a>";
	
	return $HTML;
}
					
// -----------------------------------------------
// PLUGIN: Keywords
// DESCRIPTION: Doda kljuène besede v HTML
// -----------------------------------------------

function pluginKeywords() {
  global $page;
	return $page["pa_keywords"];
}

// -----------------------------------------------
// PLUGIN: Description
// DESCRIPTION: Doda opis v stran
// -----------------------------------------------

function pluginDescription() {
  global $page;
	return $page["pa_description"];
}

// include ------------------------------------------------------------
include("plugin.news.inc.php");
include("plugin.xml.inc.php");
include("plugin.js.inc.php");
include("plugin.api.inc.php");
include("plugin.tweeter.inc.php");
include("plugin.cyc.inc.php");
include("plugin.sos.inc.php");
?>
