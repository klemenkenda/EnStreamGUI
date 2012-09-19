<?PHP
//---------------------------------------------------------------------
// FILE: config.inc.php
// AUTHOR: Klemen Kenda
// DESCRIPTION: EnStreamM GUI config file
// DATE: 16/12/2011
// HISTORY:
//  2012/08/31: Added resource module config, EPS and SR config, SOS
//              config
//---------------------------------------------------------------------

// mysql config -------------------------------------------------------
$mysql_user = "root";
$mysql_pass = "";
$mysql_host = "localhost";
$mysql_dbase = "envision";

// sensor_data mysql config -------------------------------------------
$mysql_sd["user"] = "sensor_data";
$mysql_sd["pass"] = "data4miner";
$mysql_sd["host"] = "localhost";
$mysql_sd["dbase"] = "sensor_data";

// mail config --------------------------------------------------------
$webmaster_mail = "klemen.kenda@ijs.si";

// filesystem config --------------------------------------------------
$filesystem_root = "C:\Users\Klemen\Programs\wamp\www\\";

// miner config -------------------------------------------------------
$miner["base_url"] = "http://localhost";
$miner["start_port"] = 9980;
if (!isset($sosid)) $sosid = 4;
$miner["url"] = $miner["base_url"] . ":" . ($miner["start_port"] + $sosid) . "/";
// echo $miner["url"];
$miner["stream_timeout"] = 20;
$miner["socket_timeout"] = 10;

// tweet config -------------------------------------------------------
$tweet_cfg["node_repeat"] = 4;
$tweet_cfg["statement_repeat"] = 3;
$tweet_cfg["num_tweets_history"] = 20;

// resource module config ---------------------------------------------
$resource_module["user"] = "guest";
$resource_module["pass"] = "guest";
$resource_module["url"] = "http://giv-wfs.uni-muenster.de:8080/jcr/repository/y2review/";

// EPS config ---------------------------------------------------------
$eps_endpoint_url = "";

// StreamReasoner config ----------------------------------------------
$sr_endpoint_url = "";

// SOS config ---------------------------------------------------------
$sos_interval_secs = 180 * 24 * 60 * 60; // 180 days

?>