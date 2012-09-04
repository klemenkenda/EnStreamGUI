<?PHP
//---------------------------------------------------------------------
// FILE: plugin.blog.inc.php
// AUTHOR: Klemen Kenda
// DESCRIPTION: blog plugins file
// DATE: 20/04/2010
// HISTORY:
//---------------------------------------------------------------------

function makeNewsFront($line) {
  global $lang;
	
  $title = $line["ne_title"];
	$author = $line["au_name"];
	$date = formatDate($line["ne_date"]);
	$content = $line["ne_content"];
	$id = $line["id"];	
	
	$odmik = 280;
		
  $stripped = strip_tags($line["ne_content"]);
  
  if (strlen($stripped) < $odmik) $odmik = strlen($stripped);
  		
  do {		
  	$pos1 = strpos($stripped, ".", $odmik);
  	$pos2 = strpos($stripped, "?", $odmik);
  	$pos3 = strpos($stripped, "!", $odmik);
  	$pos4 = strpos($stripped, "...", $odmik);
  	
  	if ($pos1 === false) $pos1 = 350;
  	if ($pos2 === false) $pos2 = 350;
  	if ($pos3 === false) $pos3 = 350;
  	if ($pos4 === false) $pos4 = 350;			
  	
  	$pos = min($pos1, $pos2, $pos3);
  	if ($pos == $pos4) $pos += 3;
  	  else $pos += 1;
		$odmik = $pos;
  }	
	while (is_numeric($stripped[$pos - 2]));  			
  
  $content = substr($stripped, 0, $pos);
  
  if ($pos == 353) $news_content .= "...";
	$link = "/" . $lang . "/novice/" . $line["id"] . "/1/" . getNewsLink($line["ne_title"]);
	
	$template = loadTemplate('news/newsfront.html');
	
	$HTML = fillTemplate($template, array( 
	  	"title" => $title,
			"author" => $author,
			"date" => $date,
			"content" => $content,
			"link" => $link,
			"id" => $id
		)
	);  
	
	return $HTML;
}

function pluginNewsFront() {
  global $lang_suffix;
	
	$SQL = "SELECT * FROM news ORDER BY ne_date DESC LIMIT 1";
	$result = mysql_query($SQL);
	
	$line = mysql_fetch_array($result);
	
	$XML = new DOMDocument();
	$XML->formatOutput = true;
	
	$news = $XML->createElement("news");
	$XML->appendChild($news);
	
	$date_attr = $XML->createAttribute("id");
	$date_val  = $XML->createTextNode($line["id"]);
	$news->appendChild($date_attr);
	$date_attr->appendChild($date_val);
	
	// news title
	$title = $XML->createElement("title");
	$title_val = $XML->createTextNode($line["ne_title$lang_suffix"]);
	$title->appendChild($title_val);
	$news->appendChild($title);
	// news date
	$title = $XML->createElement("date");
	$title_val = $XML->createTextNode(formatDate($line["ne_date"]));
	$title->appendChild($title_val);
	$news->appendChild($title);
	// news content
	$title = $XML->createElement("content");
	$title_val = $XML->createCDATASection("\n" . $line["ne_content$lang_suffix"] . "\n");
	$title->appendChild($title_val);
	$news->appendChild($title);
	
	$HTML = formatDate($line["ne_date"], 1) . ": " . $line["ne_title"];
	
	$HTML = $XML->saveXML();
	
	header('Cache-Control: no-store, no-cache, must-revalidate');     // HTTP/1.1 
  header('Cache-Control: pre-check=0, post-check=0, max-age=0');    // HTTP/1.1 
  header ("Pragma: no-cache"); 
  header("Expires: 0"); 
	header("Content-Type: text/xml");
  header("Content-Length: " . sizeof($HTML));
	return $HTML;
}

function pluginNewsTocFront() {
  global $lang;
	global $p;
	
	// per page
	$pp = 8;
	
	if (!isset($p)) $p = 1;
	
	$start = ($p - 1) * $pp;
	
  $SQL = "SELECT * FROM authors, news WHERE authors.id = news.ne_authorid ORDER BY ne_date DESC LIMIT $start, $pp";
	$result = mysql_query($SQL);
	
	$i = -1;
	
	while ($line = mysql_fetch_array($result)) {
	  $i++;
	  $link = "/" . $lang . "/novice/" . $line["id"] . "/1/" . getNewsLink($line["ne_title"]);
	  $title = $line["ne_title"];
		$date = formatDate($line["ne_date"], 3);
		$author = $line["au_name"];
		
		$odmik = 320;
		
    $stripped = strip_tags($line["ne_content"]);
    
    if (strlen($stripped) < $odmik) $odmik = strlen($stripped);
    		
    //do {		
    	$pos1 = strpos($stripped, ".", $odmik);
    	$pos2 = strpos($stripped, "?", $odmik);
    	$pos3 = strpos($stripped, "!", $odmik);
    	$pos4 = strpos($stripped, "...", $odmik);
    	
    	if ($pos1 === false) $pos1 = 350;
    	if ($pos2 === false) $pos2 = 350;
    	if ($pos3 === false) $pos3 = 350;
    	if ($pos4 === false) $pos4 = 350;			
    	
    	$pos = min($pos1, $pos2, $pos3);
    	if ($pos == $pos4) $pos += 3;
    	  else $pos += 1;
  		$odmik = $pos;
    //}
  	// while (is_numeric($stripped[$pos - 2]));  			
    
    $content = substr($stripped, 0, $pos);
    
    if ($pos == 353) $content .= "...";
	
	
  	if ($line["ne_image"] == "") $image = "/slike/news-image0.jpg";
		else $image = "/slike/rcrop.php?t=news&f=ne_image&id=" . $line["id"] . "&w=126&h=122";
  	
		$i = $i % 2;
		
  	$template = loadTemplate("news/newstocfront-$i.html");
  	$HTML .= fillTemplate($template, array( 
  	  	"news" => $content,
  			"title" => $title,
  			"author" => $author,
  			"date" => $date,
  			"link" => $link,
  			"image" => $image
  		)
  	);  
	}
	
	if ($i == 0) $HTML .= "</div>";
	
	$HTML .= "%VAR:PAGE%: ";
	// paginacija
	$SQL = "SELECT COUNT(*) AS n FROM news";
	$result = mysql_query($SQL);
	$line = mysql_fetch_array($result);
	$n = $line["n"];
	
	$ps = ceil($n / $pp);
	
	if ($p != 1) $HTML .= "<a href=\"/sl/index.html/" . ($p - 1) . "\">%VAR:PREV%</a> | ";
	
	$HTML .= $p . " / " .$ps;
	
	if ($p != $ps) $HTML .= " | <a href=\"/sl/index.html/" . ($p + 1) . "\">%VAR:NEXT%</a>";
	
	return $HTML;
}

function pluginNewsEngTocFront() {
  global $lang;
	global $p;
	
	// per page
	$pp = 8;
	
	if (!isset($p)) $p = 1;
	
	$start = ($p - 1) * $pp;
	
  $SQL = "SELECT * FROM authors, newseng WHERE authors.id = newseng.ne_authorid ORDER BY ne_date DESC LIMIT $start, $pp";
	$result = mysql_query($SQL);
	
	$i = -1;
	
	while ($line = mysql_fetch_array($result)) {
	  $i++;
	  $link = "/" . $lang . "/news/" . $line["id"] . "/1/" . getNewsLink($line["ne_title"]);
	  $title = $line["ne_title"];
		$date = formatDate($line["ne_date"], 1);
		$author = $line["au_name"];
		
		$odmik = 120;
		
    $stripped = strip_tags($line["ne_content"]);
    
    if (strlen($stripped) < $odmik) $odmik = strlen($stripped);
    		
    //do {		
    	$pos1 = strpos($stripped, ".", $odmik);
    	$pos2 = strpos($stripped, "?", $odmik);
    	$pos3 = strpos($stripped, "!", $odmik);
    	$pos4 = strpos($stripped, "...", $odmik);
    	
    	if ($pos1 === false) $pos1 = 150;
    	if ($pos2 === false) $pos2 = 150;
    	if ($pos3 === false) $pos3 = 150;
    	if ($pos4 === false) $pos4 = 150;			
    	
    	$pos = min($pos1, $pos2, $pos3);
    	if ($pos == $pos4) $pos += 3;
    	  else $pos += 1;
  		$odmik = $pos;
    //}
  	// while (is_numeric($stripped[$pos - 2]));  			
    
    $content = substr($stripped, 0, $pos);
    
    if ($pos == 153) $content .= "...";
	
	
  	if ($line["ne_image"] == "") $image = "/slike/news-image0.jpg";
		else $image = "/slike/rcrop.php?t=news&f=ne_image&id=" . $line["id"] . "&w=294&h=122";
  	
		$i = $i % 2;
		
  	$template = loadTemplate("news/newstocfronteng-$i.html");
  	$HTML .= fillTemplate($template, array( 
  	  	"news" => $content,
  			"title" => $title,
  			"author" => $author,
  			"date" => $date,
  			"link" => $link,
  			"image" => $image
  		)
  	);  
	}
	
	if ($i == 0) $HTML .= "</div>";
	
	$HTML .= "Page: ";
	// paginacija
	$SQL = "SELECT COUNT(*) AS n FROM newseng";
	$result = mysql_query($SQL);
	$line = mysql_fetch_array($result);
	$n = $line["n"];
	
	$ps = ceil($n / $pp);
	
	if ($p != 1) $HTML .= "<a href=\"/en/index.html/" . ($p - 1) . "\">&lt; prev</a> | ";
	
	$HTML .= $p . " / " .$ps;
	
	if ($p != $ps) $HTML .= " | <a href=\"/en/index.html/" . ($p + 1) . "\">next &gt;</a>";
	
	return $HTML;
}

function pluginOtherNewsTOCFront() {
  global $lang;
	
  // mednarodne novice
	$SQL = "SELECT * FROM authors, news WHERE ne_categoryid = 2 AND authors.id = news.ne_authorid ORDER BY ne_date DESC LIMIT 3";
	$result = mysql_query($SQL);
	
	while ($line = mysql_fetch_array($result)) {
	  $link = "/" . $lang . "/novice/" . $line["id"] . "/1/" . getNewsLink($line["ne_title"]);
	  $international .= "<div class=\"div-newsother-toc\"><table><tr><td class=\"td-news-date\"><font class=\"font-date\">" . formatDate($line["ne_date"]) . ":</font></td><td>" . $line["ne_title"] . " <a href=\"$link\">%VAR:MORE%</a></td></tr></table></div>";
 	}
	
	/*while ($line = mysql_fetch_array($result)) {
	  $international .= "<div style=\"clear: both; padding-bottom: 10px;\"><font class=\"font-date\">" . formatDate($line["ne_date"]) . ":</font> <span style=\"display: block; float: right; width: 150px;\">" . $line["ne_title"] . "</span></div>";
	}*/
	
	// slovenija novice
	$SQL = "SELECT * FROM authors, news WHERE ne_categoryid = 3 AND authors.id = news.ne_authorid ORDER BY ne_date DESC LIMIT 3";
	$result = mysql_query($SQL);
	
	while ($line = mysql_fetch_array($result)) {
	  $link = "/" . $lang . "/novice/" . $line["id"] . "/1/" . getNewsLink($line["ne_title"]);
	  $slovenia .= "<div class=\"div-newsother-toc\"><table><tr><td class=\"td-news-date\"><font class=\"font-date\">" . formatDate($line["ne_date"]) . ":</font></td><td>" . $line["ne_title"] . " <a href=\"$link\">%VAR:MORE%</a></td></tr></table></div>";
 	}
	/*while ($line = mysql_fetch_array($result)) {
	  $slovenia .= "<div style=\"clear: both; padding-bottom: 10px;\"><font class=\"font-date\">" . formatDate($line["ne_date"]) . ":</font> <span style=\"display: block; float: right; width: 150px;\">" . $line["ne_title"] . "</span></div>";
	}*/
	
	$template = loadTemplate("news/newsothertocfront.html");
	$HTML = fillTemplate($template, array( 
	  	"international" => $international,
			"slovenia" => $slovenia
		)
	);  
	return $HTML;
}

//---------------------------------------------------------------------
// PLUGIN: News
//---------------------------------------------------------------------

function makeNews($line) {
  global $lang;
	
  $title = $line["ne_title"];
	$author = $line["au_name"];
	$date = formatDate($line["ne_date"]);
	$content = $line["ne_content"];
	$id = $line["id"];	
	if ($line["ne_image"] <> "") $photo = "<img style=\"float: right; margin-left: 20px; margin-bottom: 20px;\" src=\"/slike/resize.php?t=news&f=ne_image&w=350&id=$id\" class=\"p1\">";
	  else $photo = "";
	
	$template = loadTemplate('news/news.html');
	if ($lang == "en") $template = loadTemplate('news/newseng.html');
	
	$HTML = fillTemplate($template, array( 
	  	"title" => $title,
			"author" => $author,
			"date" => $date,
			"content" => $content,
			"photo" => $photo,
			"id" => $id
		)
	);  
	
	return $HTML;
}

function pluginNews() {
  global $nid;
	
	if ((!isset($nid)) || ($nid == "")) $SQL = "SELECT * FROM authors, news WHERE news.ne_authorid = authors.id ORDER BY ne_date DESC LIMIT 5";
	else $SQL = "SELECT * FROM authors, news WHERE news.ne_authorid = authors.id AND news.id = $nid";
	
	$result = mysql_query($SQL);
	$line = mysql_fetch_array($result);
	
	$HTML = makeNews($line);
	
	return $HTML;
}

function pluginNewsEng() {
  global $nid;
	
	if ((!isset($nid)) || ($nid == "")) $SQL = "SELECT * FROM authors, newseng WHERE newseng.ne_authorid = authors.id ORDER BY ne_date DESC LIMIT 1";
	else $SQL = "SELECT * FROM authors, newseng WHERE newseng.ne_authorid = authors.id AND newseng.id = $nid";
	
	$result = mysql_query($SQL);
	$line = mysql_fetch_array($result);

	$HTML = makeNews($line);
	
	return $HTML;
}

// -----------------------------------------------
// PLUGIN: News
// DESCRIPTION: Izpise novice na 1. strani
// -----------------------------------------------

// News: oblikovanje linka brez sumnikov ipd
function getNewsLink($title) {
  $title = trim($title, " \t.!?,-");
  $chars = array("š", "č", "ž", "đ", "ć", "Š", "Č", "Ž", "Đ", "Ć", " ", "@",    ".", "!", "?", ","); 
	$newchars = array("s",  "c",  "z",  "d",  "c",  "S",  "C",  "Z",  "D",  "C",  "-", "-na-", "",  "",  "",  "");   
	$title = str_replace($chars, $newchars, $title);
  $title = strtolower($title);
	
	return $title;
}

function newsTOC($categoryid) {
  global $lang;
	global $p;
	
	if (!isset($p)) $p = 1;
	
  $SQL = "SELECT * FROM authors, news WHERE ne_categoryid = $categoryid AND authors.id = news.ne_authorid ORDER BY ne_date DESC LIMIT 10";
	$result = mysql_query($SQL);
	
	while ($line = mysql_fetch_array($result)) {
	  $link = "/" . $lang . "/novice/" . $line["id"] . "/" . $p . "/" . getNewsLink($line["ne_title"]);
	  $news .= "<div class=\"div-news-toc\"><table><tr><td class=\"td-news-date\"><font class=\"font-date\">" . formatDate($line["ne_date"]) . ":</font></td><td>" . $line["ne_title"] . " <a href=\"$link\">%VAR:MORE%</a></td></tr></table></div>";
 	}
	
	switch ($categoryid) {
	  case 1: $template = loadTemplate("news/newsmsstoc.html");
	    break;
		case 2: $template = loadTemplate("news/newsinternationaltoc.html");
		  break;
		case 3: $template = loadTemplate("news/newssloveniatoc.html");
		  break;
	}
	
	$HTML = fillTemplate($template, array( 
	  	"news" => $news
		)
	);  
	return $HTML;
}

function pluginNewsTOC() {
  global $nid;

  // v katerem podro�ju se nahajamo	
	if ((!isset($nid)) || ($nid == "")) $SQL = "SELECT * FROM authors, news WHERE news.ne_authorid = authors.id AND ne_categoryid = 1 ORDER BY ne_date DESC LIMIT 1";
	else $SQL = "SELECT * FROM authors, news WHERE news.ne_authorid = authors.id AND news.id = $nid";
	
	$result = mysql_query($SQL);
	$line = mysql_fetch_array($result);
	
	$categoryid = $line["ne_categoryid"];
	
  $HTML .= newsTOC($categoryid);
	
	for ($i = 1; $i <= 3; $i++) {
	  if ($i != $categoryid) $HTML .= newsTOC($i);
	}
	
	return $HTML;
}

?>
