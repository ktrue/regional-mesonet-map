<?php
############################################################################
//  Generate the [X]HTML for the  Weather Network links page
//  Based on SWN-mesomap.php for Southwestern Weather Network
//  Version 1.00 - 29-Apr-2008  Author: Ken True, webmaster@saratoga-weather.org
//  Version 1.01 - 10-May-2008 -- added Metric/English units handling
//  Version 1.02 - 23-Aug-2008 -- added sortable table columns and temperatures to 1 decimal place
//  Version 1.03 - 26-Aug-2008 -- added dewpt, wind gust and units conversion for stickertags stations
//  Version 2.00 (ML) - 24-Feb-2009 -- added multingual support via language-meso-LL.txt files for static text
//  Version 2.01 (ML) - 06-Mar-2009 -- added optional rotational display of city name
//  Version 2.02 (ML) - 14-Mar-2009 -- translation for date/time + 'This station'
//  Version 2.03 (ML) - 04-Mar-2010 -- added support for town-name in main local language
//  Version 2.04 (ML) - 14-Jul-2010 -- added support for gzipped conditions data from stations
//  Version 2.05 (ML) - 01-Oct-2012 -- optional $printList added to suppress list display
//
//  Version 3.00 - 24-Jul-2016 -- retool for .json production with mesonet-map.php code
//
//  Version 3.06 - 14-Jan-2017 -- corrected for https:// station URL links
//
//  Version 4.00 - 23-May-2018 - rewrite to use Leaflet/OpenStreetMaps+others for map display
//  Version 4.03 - 29-May-2018 - fix timezone processing issues
//  Version 4.04 - 11-Jun-2018 - return content as application/javascript, fixes for NZWN2 JSON

//  Note: distribution of this program is limited to members of the
//  Affiliated Regional Weather Networks (http://www.northamericanweather.net/ )
//  Distribution outside of the membership is prohibited.
//  Copyright 2006-2014, Ken True - Saratoga-weather.org
//
$Version = "V4.04 - 11-Jun-2018";
//  Inputs:
//    a '|' -delimited data file with the information on the links to generate
//  PHP page parameters:
//   mode=[HTML] | [XHTML]  default=HTML 4.0 format

/// ------  begin code ----------
if (isset($_REQUEST['sce']) && strtolower($_REQUEST['sce']) == 'view' ) {
   //--self downloader --
   $filenameReal = __FILE__;
   $download_size = filesize($filenameReal);
   header('Pragma: public');
   header('Cache-Control: private');
   header('Cache-Control: no-cache, must-revalidate');
   header("Content-type: text/plain");
   header("Accept-Ranges: bytes");
   header("Content-Length: $download_size");
   header('Connection: close');

   readfile($filenameReal);
   exit;
}

if(isset($_REQUEST['net']) and preg_match('|[A-Z0-9]+|',$_REQUEST['net'])) {
	$useRMNET = $_REQUEST['net'];
}

require_once("mesonet-map-settings.php");

	define('ML_START','');
	define('ML_END','');


// Map specific settings .. DO NOT CHANGE unless graphic is changed to match these

$lang = 'en'; // default language for the JSON file .. no need to use language-specific stuff here

//------------ end of map-specific settings ---------------

// Check parameters and force defaults/ranges


$doDebug = false;
if(isset($_REQUEST['debug']) and strtolower($_REQUEST['debug']) == 'y') {$doDebug=true; }
// HTML specific overrides if desired
if(!isset($PHP_SELF)) {$PHP_SELF = $_SERVER['PHP_SELF']; }

$t = pathinfo($PHP_SELF);
$Program = $t['basename'];
$ourHost = $_SERVER['HTTP_HOST'];
$mc = parse_url($masterCacheURL);
if(isset($mc['host'])) {$masterHost = $mc['host']; } else {$masterHost = '';}
$onMasterHost = false;
$masterHost = preg_replace('|www\.|i','',$masterHost);
$ourHost = preg_replace('|www\.|i','',$ourHost);
if ($ourHost == $masterHost) {$onMasterHost = true; }
 if($doDebug) {$Debug .= " ourHost='$ourHost' mH='$masterHost' onM='$onMasterHost' \n";}
if($onMasterHost) {
 if($doDebug) { $Debug .= " using master config/conditions files \n"; }
  $RMNETlinksFile = M_NETID."-stations-cc.txt"; // master control file
  $RMNETcacheName = M_NETID."-conditions.txt";  // used to store the file so we
}

// Establish timezone offset for time display
$originalTZ = date('e');
if (!function_exists('date_default_timezone_set')) {
	
	if (! ini_get('safe_mode') ) {
	   putenv("TZ=".M_TZ);  // set our timezone for 'as of' date on file
	}
//	 print "<!-- using putenv(\"TZ=". $SITE['tz']. "\") -->\n";
  } else {
   date_default_timezone_set(M_TZ);
//	 print "<!-- using date_default_timezone_set(\"". $ourTZ. "\") -->\n";
 }

// ---------------------main program -----------------------------------
// open and read links file
global $Debug,$doDebug,$RMNETcacheName,$refetchSeconds,$Icons,$windArrowDir, $NAtext, $lang;


$Stations = array();  // storage area for the station info
$StationData = array(); // storage area for current conditions
$timeStamp = time();
$currentTime = RMNET_lang_fixTZname(strftime($MesoTimeFormat,$timeStamp));

RMNET_load_stations($RMNETlinksFile);  // load up the $Stations from the config file

$Units = RMNET_load_units($myUOM);
	
RMNET_load_weather_data();  // from the local file only

reset($Stations);  // and reset the list from the config file

RMNET_make_JSON(); // generate the JSON file

// change all HTML comment delimiters to blanks, and make all lines in $Debug as JavaScript comments //
$Debug = preg_replace("|<!--|is",'',$Debug);
$Debug = preg_replace("|-->|is",'',$Debug);
$Debug = preg_replace("|^(.)|mis",'//'."$1",$Debug);
print $Debug;

return;

//-------------------end of main program -------------------

function RMNET_make_JSON() {
  global $Stations,$StationData,$StationRawData,$Debug,$doDebug,$maxAge,$Units,
         $RMNETcacheName,$refetchSeconds,$masterCacheURL,$onMasterHost,$MesoTimeFormat;
  global $NetLookup,$Networks,$ourTZ,$originalTZ;

/*
$Stations["$State\t$Name\t$Seqno"] = "$URL\t$Coords\t$Features\t$DataPage\t$RawDataType\t$RawDataURL\t$Offsets\t$METAR\t$LatLong";  

$StationData["$State\t$Name\t$Seqno"] = "$TEMP,$HUMID,$WDIR,$WSPD,$RAIN,$BARO,$BTRND,$COND,$CTXT,$DEWPT,$GUST,$utimestamp,$time_fetch";

*/
    header('Content-type: application/javascript; charset='.RMNET_CHARSET);
    print 'var data = {"markers": [';

	$comma = '';
    foreach ($Stations as $key => $rec) {
	   list($State,$Name,$Seqno) = explode("\t",$key);
	   list($URL,$Coords,$Features,$DataPage,$RawDataType,$RawDataURL,$Offsets,$METAR,$LatLong) = 
	     explode("\t",$rec."\t\t\t\t");
	   list($Lat,$Long) = explode(',',$LatLong.',,');
	   $Lat = trim($Lat);
	   $Long = trim($Long);
	   if($Long == '' or ($Lat == '0' and $Long == '0')) {
	     $State = htmlspecialchars(trim($State));
		 $Name = htmlspecialchars(trim($Name));
	     if($doDebug) { print "\n//-- skipped .. no lat/long for '$State,$Name,$Seqno' latlong='$LatLong'\n";}
	     continue;
		}
	   $fList = RMNET_decode_features($Features);
	   $URL = htmlspecialchars($URL);
	   $nets = M_NETID;
//	   $nets .= "<a href=\"http://" . M_MASTERHOST . "/\" title=\"" . M_NETNAME ."\">" . M_NETNAME . "</a> ";
	   $nets = htmlspecialchars($nets);
	   $feat = htmlspecialchars(RMNET_decode_features($Features));
	   $featcode = RMNET_decode_features_code($Features);
	   $conds = '<small>Current conditions not available</small>';
	   $condsAbbrev = 'Offline';
	   if(isset($StationData[$key])) {
		 
	     $Mconds = RMNET_convertUnits($StationData[$key],$Units['temp'],$Units['wind'],$Units['baro'],$Units['rain'],'mi');
		 $condsAbbrev = RMNET_format_conditions_google_short ($Mconds,$Units['temp'],$Units['wind'],$Units['rain'],$Units['baro']);
		 if($doDebug) {
			print "\n// Raw=   '".$StationData[$key]."'";
			print "\n// Mconds='$Mconds'";
			print "\n// condsA='$condsAbbrev'\n";
		 }
	   } else {
		 if($doDebug) {
			print "\n//-- StationData['$key'] not found\n"; 
		 }
	   }
	   $condsAbbrev = htmlspecialchars($condsAbbrev);
	   
	  $tState = str_replace('&','&amp;',trim($State));
	  $tName = str_replace('&','&amp;',trim($Name));
      
	   $URLshort = preg_replace('|http://|i','',$URL);
	   $URLshort = preg_replace('|\\\\|i','/',$URLshort);
	   print "$comma";
	   print " {\"town\":\"$tName, $tState\",\"lat\":\"$Lat\",\"long\":\"$Long\",\"surl\":\"$URL\",\"fcode\":\"$featcode\",\"nets\":\"$nets\",\"conds\":\"$condsAbbrev\"}";
       $comma = ",\n";
	}
	print "  ],\n";

	print "\"nets\":{\n";
	$comma = '';
	foreach ($NetLookup as $net => $nrec) {
	   print "$comma";
	   print "\"$net\":{\"name\":\"". $NetLookup[$net]['NetLongName'] .
	   "\",\"url\":\"" . $NetLookup[$net]['NetHomeURL'] . 
	   "\",\"short\":\"" . $NetLookup[$net]['NetName'] . 
	   "\",\"region\":\"" . $NetLookup[$net]['GeoRegion'] . 
	   "\",\"units\":\"" . $NetLookup[$net]['MesoUnits'] . 
	   "\",\"tz\":\"" . $NetLookup[$net]['GMTZ'] ."\"}";
       $comma = ",\n";
	}
	
	print "}}\n";
	print "// tz=$ourTZ net=".M_NETID." origTZ=$originalTZ\n";
}

?>