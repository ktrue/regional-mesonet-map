<?php
############################################################################
#  mesonet-map-common.php
#
#  provides global setup functions for the mesonet-map system based
#  on configurable values in mesonet-map-settings.php for the unique network
#
#  Author: Ken True - webmaster@saratoga-weather.org
#
# Version 3.00 - 24-Jul-2016 - initial release
# Version 3.03 - 31-Jul-2016 - added CBI Fire Danger display functions to table, map labels and popups
# Version 3.04 - 29-Nov-2016 - corrected intermittent -18 dewpt JSON under some conditions
# Version 3.06 - 14-Jan-2017 - corrected for https:// station URL links
# Version 3.08 - 12-Aug-2017 - added support for https-only networks-use curl for fetch
# Version 3.09 - 14-Aug-2017 - minor fixes for PHP 7.1 compatibility
# Version 3.10 - 09-Oct-2017 - additional PHP 7.1 compatibility fixes
# Version 4.00 - 23-May-2018 - rewrite to use Leaflet/OpenStreetMaps+others for map display
# Version 4.03 - 29-May-2018 - fixes for timezone display processing
# Version 4.04 - 11-Jun-2018 - fixes for incomplete weather station conditions handling
#
############################################################################

// -------------------------------------------------------------
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
############################################################################
# computed settings for script internal operation .. do not change these

$NetworksDefinitionFile = $cacheDir."mesonet-map-networks.txt";
$NetworksDefinitionURL  = 'http://www.northamericanweather.net/mesonet-map-networks.txt';

$Networks = array();
$NetLookup = array();
$DebugNetLoad = '';

RMNET_load_network_definitions(); // get the available networks list

if(!isset($NetLookup[M_NETID]['NetHomeURL'])) {
	echo "<h2>'".M_NETID."' network is not defined.<br/>Please correct Network ID for proper mesonet-map operation.</h2>\n";
	exit;
}
//
  define('M_MASTERHOST',$NetLookup[M_NETID]['NetHomeURL']); // Regional Network Hub/master domain name
  define('M_NETNAME',$NetLookup[M_NETID]['NetLongName']);
  define('M_REGION',$NetLookup[M_NETID]['Coverage']);
  if(isset($NetLookup[M_NETID]['GMTZ']) and $NetLookup[M_NETID]['GMTZ'] <> '') {
	  
    define('M_TZ',$NetLookup[M_NETID]['GMTZ']); // Regional timezone
  } else {
	define('M_TZ','America/New_York');
  }
 
  $masterCacheURL = $NetLookup[M_NETID]['NetHomeURL'].M_NETID.'-conditions.txt';
  if(isset($NetLookup[M_NETID]['MesoURL']) and $NetLookup[M_NETID]['MesoURL'] <> '') {
    $masterCacheURL = $NetLookup[M_NETID]['MesoURL'].M_NETID.'-conditions.txt';
  }
  $ourTZ = M_TZ;
  $masterConfigURL = preg_replace('|-conditions|','-stations-cc',$masterCacheURL);
    
  $RMNETlinksFile = $cacheDir.M_NETID."-stations-cc.txt"; // master control file
// cacheName is name of file used to store cached current conditions
//
  $RMNETcacheName = $cacheDir.M_NETID."-conditions.txt";  // used to store the file so we
//                                        don't have to fetch it each time
//                                        cache is normally filed from master site
//
//                        // used for rotating legend display :
  $windArrowSize = 'S';   // ='S' for Small 9x9 arrows   (*-sm.gif)
//                           ='L' for Large 14x14 arrows (*.gif)
//
  $showNoData = true;   // show table rows with no conditions data available
//
//

// Set defaults based on Network configuration
  list($tTemp,$tWind,$tBaro,$tRain) = explode(',',$NetLookup[M_NETID]['MesoUnits']);
  $Debug .= "<!-- using $tTemp,$tWind,$tBaro,$tRain units for netid=".M_NETID." -->\n";
  $myUOM =    preg_match('|F|i',$tTemp)?'E':'M';
  $useKnots = preg_match('|kts|i',$tWind)?true:false;
  $useMPH =   preg_match('|mph|i',$tWind)?true:false;
  $useMPS =   preg_match('|m/s|i',$tWind)?true:false;
  $useMB =    preg_match('|mb|i',$tBaro)?true:false;
  if($rmMapUseUnits) {
	$rmTempUOM = $tTemp;   // units for Temperature ='C' or ='F';
	$rmWindUOM = $tWind; // units for Wind Speed ='mph', ='km/h', ='m/s', ='kts'
	$rmWindUOM = preg_replace('|kmh|i','km/h',$rmWindUOM);
	$rmBaroUOM = $tBaro;// units for Barometer ='inHg', ='hPa', ='mb'
	$rmRainUOM = $tRain;  // units for Rain ='in', ='mm'
  }
  
  if($rmMapUseDefaults and $NetLookup[M_NETID]['GMdefaults'] <> '') {
	 $Debug .= "<!-- using default map lat/long/zoom settings for netid=".M_NETID." -->\n";
	 $t = explode(',',$NetLookup[M_NETID]['GMdefaults']);
	 $rmMapZoom = $t[0];
	 $rmMapCenter = $t[1].','.$t[2]; 
  }

// -------------------------------------------------------------
############################################################################
# DO NOT CHANGE THESE SETTINGS
# Google languages at https://developers.google.com/maps/faq#languagesupport
# This lookup changes our ISO 639-1 2-character language abbreviations from country domain 
# to Google languages usage for the Google Map controls/text only.
#
$GoogleLang = array ( 
  'af' => 'af',
  'bg' => 'bg',
  'cs' => 'cs',
  'ct' => 'ca',
  'dk' => 'da',
  'nl' => 'nl',
  'en' => 'en',
  'fi' => 'fi',
  'fr' => 'fr',
  'de' => 'de',
  'el' => 'el',
  'ga' => 'ga',
  'it' => 'it',
  'he' => 'iw',
  'hu' => 'hu',
  'no' => 'no',
  'pl' => 'pl',
  'pt' => 'pt',
  'ro' => 'ro',
  'es' => 'es',
  'se' => 'sv',
  'si' => 'sl',
  'sk' => 'sk',
);
  if (isset($lang)) { $lang_input = $lang; } else { $lang_input = 'en';}
  if (isset($SITE['lang'])) {$lang_input = $SITE['lang']; }
  if (isset($_REQUEST['lang'])) { $lang_input = strtolower($_REQUEST['lang']); }

  # allow valid input only
  # allows en, en-us, en-gb, dk, etc.
  if ((isset($lang_input) && preg_match('/^[a-z]{2}$/', $lang_input))
  || (isset($lang_input) && preg_match('/^[a-z]{2}-[a-z]{2}$/', $lang_input)) ) {
    $lang = $lang_input;
  }

  if(file_exists("mesonet-map-lang-$lang.txt") ) {
	 $Debug = "<!-- "."mesonet-map-lang-$lang.txt loaded. -->\n";
 	 include_once("mesonet-map-lang-$lang.txt");
	 if (RMNET_OFFLINE <> '') {$NAtext = RMNET_OFFLINE; }
  } else {
	$Debug = "<!-- "."mesonet-map-lang-$lang.txt not found.  Using "."mesonet-map-lang-en.txt -->\n";
	include_once("mesonet-map-lang-en.txt");
	 if (RMNET_OFFLINE <> '') {$NAtext = RMNET_OFFLINE; }
	$lang = 'en';
  }
  
  if(!function_exists("RMNET_CBItext")) {
	  function RMNET_CBItext($itxt) { return($itxt); }
  }
  if(!defined("RMNET_CBI")) {
	  define("RMNET_CBI","Fire<br/>Danger");
  }
  if(!defined("RMNET_CBILEGEND")) {
	  define("RMNET_CBILEGEND","Fire Danger [Chandler Burning Index]");
  }

// deprecated iconv_set_encoding("output_encoding", RMNET_CHARSET);
$Lang = $lang;
if(isset($GoogleLang[$lang])) {
	$Lang = $GoogleLang[$lang];
	$Debug .=  "<!-- lang=$lang used - Lang=$Lang -->\n";
}

// ------------------------------------------------------------------
// load overall Regional Network definitions
function RMNET_load_network_definitions() {
  global $Networks,$NetLookup,$NetworksDefinitionFile,$NetworksDefinitionURL,$Debug,$DebugNetLoad;


  $refreshTime = 6*3600; // normal update for network definitions is 6 hours (6*3600 seconds)
  if (isset($_REQUEST['cache']) and strtolower($_REQUEST['cache']) == 'no') {
    $refreshTime = 1;  // set short period for refresh of cache
  }

  if(!file_exists($NetworksDefinitionFile) or 
    (file_exists($NetworksDefinitionFile) and filemtime($NetworksDefinitionFile)+$refreshTime < time())) { 
    RMNET_get_file($NetworksDefinitionFile,$NetworksDefinitionURL);
	$DebugNetLoad = $Debug;
  }

  $rawlinks = file($NetworksDefinitionFile); // read file into array
  $sNetworks = array();

  // strip comment records, build $Stations indexed array
  $nrec = 0;
  $Seqno = 0;
  foreach ($rawlinks as $rec) {
	$Seqno++;
	$rec = preg_replace("|[\n\r]*|","",$rec);
	$len = strlen($rec);
	if($rec and substr($rec,0,1) <> "#") {  //only take non-comments
//         echo "Rec $nrec ($len): $rec\n";
//# NetID, NetName, NetHomeURL, GeoRegion, GeoAbbrev, Coverage,MAPWH, Offset

	   list($NetID, $NetName, $NetLongName, $NetHomeURL, $GeoRegion, $GeoAbbrev, $Coverage, $MapWH, $MapOffset, $MesoURL,$MesoUnits,$GMdefaults,$GMTZ) = explode("|",trim($rec).'||||||||||');
	   $sNetworks["$GeoRegion\t$NetName\t$NetID"] = "$NetName\t$NetLongName\t$NetHomeURL\t$GeoAbbrev\t$Coverage\t$MapWH\t$MapOffset\t$MesoURL\t$MesoUnits\t$GMdefaults\t$GMTZ";  
	   // Save for lookups later
	   $NetLookup[$NetID]['GeoRegion'] 	= trim($GeoRegion);
	   $NetLookup[$NetID]['NetName'] 	= trim($NetName);
	   $NetLookup[$NetID]['NetLongName'] = trim($NetLongName);
	   $NetLookup[$NetID]['NetHomeURL'] = trim($NetHomeURL);
	   $NetLookup[$NetID]['GeoAbbrev'] 	= trim($GeoAbbrev);
	   $NetLookup[$NetID]['Coverage'] 	= trim($Coverage);
	   $NetLookup[$NetID]['MapWH'] 		= trim($MapWH);
	   $NetLookup[$NetID]['MapOffset'] 	= trim($MapOffset);
	   $NetLookup[$NetID]['MesoURL'] 	= trim($MesoURL);
	   $NetLookup[$NetID]['MesoUnits'] 	= trim($MesoUnits);
	   $NetLookup[$NetID]['GMdefaults'] = trim($GMdefaults);
	   $NetLookup[$NetID]['GMTZ']       = trim($GMTZ);
	   
	} elseif (strlen($rec) > 0) {
//         echo "comment $nrec ($len): $rec\n";
	} else {
//         echo "blank record ignored\n";
	}
	$nrec++;
  }

  ksort($sNetworks);
  foreach ($sNetworks as $key => $rec) {
	list($GeoRegion,$NetName,$NetID) = explode("\t",$key);
	$Networks["$GeoRegion\t$NetID"] = $rec;
  }

}

// ------------------------------------------------------------------
function RMNET_get_file ($fName,$fURL) {
	global $Debug;
	$Debug .= "<!-- RMNET_get_file: loading $fName from master $fURL -->\n";

    $rawcache = RMNET_fetchURLWithoutHanging($fURL,false);
	$i = strpos($rawcache,"\r\n\r\n");
	$headers = substr($rawcache,0,$i-1);
	$content = substr($rawcache,$i+2);
  if (preg_match('| 200 OK|i',$headers) and strlen(trim($content)) > 10) {
	  $content = trim($content); // remove any leading/trailing new lines
      $fp = fopen($fName, "w");
	  if (! $fp ) { 
	    $Debug .= "<!-- RMNET_get_file: WARNING: cache $fName not writable -->\n";
	  } else {
        $write = fputs($fp, $content);
        fclose($fp);
		$age = time()-filemtime($fName);  
        $Debug .= "<!-- RMNET_get_file: wrote local cache to $fName. age=$age seconds. -->\n";
	  }
      return;  
    } else { // failed to get master cache so have to load the hard way
	  $Debug .= "<!-- RMNET_get_file: failed to get master $fURL -->\n";
	  $Debug .= "<!-- RMNET_get_file: content length=".strlen($content)." bytes. -->\n";
	  $Debug .= "<!-- RMNET_get_file: headers: \n " . $headers . " -->\n";
	}


}

// ------------------------------------------------------------------

function RMNET_microtime_float()
{
   list($usec, $sec) = explode(" ", microtime());
   return ((float)$usec + (float)$sec);
}
// ------------------------------------------------------------------

function RMNET_fetchUrlWithoutHanging($url,$useFopen) {
// get contents from one URL and return as string 
  global $Debug, $needCookie;

  $overall_start = time();
  if (! $useFopen) {
   // Set maximum number of seconds (can have floating-point) to wait for feed before displaying page without feed
   $numberOfSeconds=6;   

// Thanks to Curly from ricksturf.com for the cURL fetch functions

  $data = '';
  $domain = parse_url($url,PHP_URL_HOST);
  $theURL = str_replace('nocache','?'.$overall_start,$url);        // add cache-buster to URL if needed
  $Debug .= "<!-- curl fetching '$theURL' -->\n";
  $ch = curl_init();                                           // initialize a cURL session
  curl_setopt($ch, CURLOPT_URL, $theURL);                         // connect to provided URL
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);                 // don't verify peer certificate
  curl_setopt($ch, CURLOPT_USERAGENT, 
    'Mozilla/5.0 (mesonet-map.php - saratoga-weather.org)');

  curl_setopt($ch,CURLOPT_HTTPHEADER,                          // request LD-JSON format
     array (
         "Accept: text/html,text/plain"
     ));

  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $numberOfSeconds);  //  connection timeout
  curl_setopt($ch, CURLOPT_TIMEOUT, $numberOfSeconds);         //  data timeout
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);              // return the data transfer
  curl_setopt($ch, CURLOPT_NOBODY, false);                     // set nobody
  curl_setopt($ch, CURLOPT_HEADER, true);                      // include header information
//  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);              // follow Location: redirect
//  curl_setopt($ch, CURLOPT_MAXREDIRS, 1);                      //   but only one time
  if (isset($needCookie[$domain])) {
    curl_setopt($ch, $needCookie[$domain]);                    // set the cookie for this request
    curl_setopt($ch, CURLOPT_COOKIESESSION, true);             // and ignore prior cookies
    $Debug .=  "<!-- cookie used '" . $needCookie[$domain] . "' for GET to $domain -->\n";
  }

  $data = curl_exec($ch);                                      // execute session

  if(curl_error($ch) <> '') {                                  // IF there is an error
   $Debug .= "<!-- curl Error: ". curl_error($ch) ." -->\n";        //  display error notice
  }
  $cinfo = curl_getinfo($ch);                                  // get info on curl exec.
/*
curl info sample
Array
(
[url] => http://saratoga-weather.net/clientraw.txt
[content_type] => text/plain
[http_code] => 200
[header_size] => 266
[request_size] => 141
[filetime] => -1
[ssl_verify_result] => 0
[redirect_count] => 0
  [total_time] => 0.125
  [namelookup_time] => 0.016
  [connect_time] => 0.063
[pretransfer_time] => 0.063
[size_upload] => 0
[size_download] => 758
[speed_download] => 6064
[speed_upload] => 0
[download_content_length] => 758
[upload_content_length] => -1
  [starttransfer_time] => 0.125
[redirect_time] => 0
[redirect_url] =>
[primary_ip] => 74.208.149.102
[certinfo] => Array
(
)

[primary_port] => 80
[local_ip] => 192.168.1.104
[local_port] => 54156
)
*/
  $Debug .= "<!-- HTTP stats: " .
    " RC=".$cinfo['http_code'];
	if(isset($cinfo['primary_ip'])) {
    $Debug .= " dest=".$cinfo['primary_ip'] ;
	}
	if(isset($cinfo['primary_port'])) { 
	  $Debug .= " port=".$cinfo['primary_port'] ;
	}
	if(isset($cinfo['local_ip'])) {
	  $Debug .= " (from sce=" . $cinfo['local_ip'] . ")";
	}
	$Debug .= 
	"\n      Times:" .
    " dns=".sprintf("%01.3f",round($cinfo['namelookup_time'],3)).
    " conn=".sprintf("%01.3f",round($cinfo['connect_time'],3)).
    " pxfer=".sprintf("%01.3f",round($cinfo['pretransfer_time'],3));
	if($cinfo['total_time'] - $cinfo['pretransfer_time'] > 0.0000) {
	  $Debug .=
	  " get=". sprintf("%01.3f",round($cinfo['total_time'] - $cinfo['pretransfer_time'],3));
	}
    $Debug .= " total=".sprintf("%01.3f",round($cinfo['total_time'],3)) .
    " secs -->\n";

  //$Debug .= "<!-- curl info\n".print_r($cinfo,true)." -->\n";
  curl_close($ch);                                              // close the cURL session
  //$Debug .= "<!-- raw data\n".$data."\n -->\n"; 
  $i = strpos($data,"\r\n\r\n");
  $headers = substr($data,0,$i);
  $content = substr($data,$i+4);
  if($cinfo['http_code'] <> '200') {
    $Debug .= "<!-- headers returned:\n".$headers."\n -->\n"; 
  }
  return $data;                                                 // return headers+contents

 } else {
//   print "<!-- using file_get_contents function -->\n";
   $STRopts = array(
	  'http'=>array(
	  'method'=>"GET",
	  'protocol_version' => 1.1,
	  'header'=>"Cache-Control: no-cache, must-revalidate\r\n" .
				"Cache-control: max-age=0\r\n" .
				"Connection: close\r\n" .
				"User-agent: Mozilla/5.0 (mesonet-map.php - saratoga-weather.org)\r\n" .
				"Accept: text/html,text/plain\r\n"
	  ),
	  'https'=>array(
	  'method'=>"GET",
	  'protocol_version' => 1.1,
	  'header'=>"Cache-Control: no-cache, must-revalidate\r\n" .
				"Cache-control: max-age=0\r\n" .
				"Connection: close\r\n" .
				"User-agent: Mozilla/5.0 (mesonet-map.php - saratoga-weather.org)\r\n" .
				"Accept: text/html,text/plain\r\n"
	  )
	);
	
   $STRcontext = stream_context_create($STRopts);

   $T_start = RMNET_fetch_microtime();
   $xml = file_get_contents($url,false,$STRcontext);
   $T_close = RMNET_fetch_microtime();
   $headerarray = get_headers($url,0);
   $theaders = join("\r\n",$headerarray);
   $xml = $theaders . "\r\n\r\n" . $xml;

   $ms_total = sprintf("%01.3f",round($T_close - $T_start,3)); 
   $Debug .= "<!-- file_get_contents() stats: total=$ms_total secs -->\n";
   $Debug .= "<-- get_headers returns\n".$theaders."\n -->\n";
//   print " file() stats: total=$ms_total secs.\n";
   $overall_end = time();
   $overall_elapsed =   $overall_end - $overall_start;
   $Debug .= "<!-- fetch function elapsed= $overall_elapsed secs. -->\n"; 
//   print "fetch function elapsed= $overall_elapsed secs.\n"; 
   return($xml);
 }

}    // end RMNET_fetchUrlWithoutHanging

// ------------------------------------------------------------------
// convert units (if necessary) to metric C,m/s,hPa,mm,m
//
function RMNET_convertUnits($data,$uomTemp,$uomWind,$uomBaro,$uomRain,$uomDistance) {
  
   list($TEMP,$HUMID,$WDIR,$WSPD,$RAIN,$BARO,$BTRND,$COND,$CTXT,$DEWPT,$GUST,$UDATE,$FTIME) = 
      explode(',',$data.',-,-,-,,');
	  
  if($FTIME == '-') {
	 $DEWPT = '-';
  }
  if($DEWPT == "'--'") { // Nordic network special
//Nordic: 14.7,73,NNE,0.0,0.0,1021.0,Steady,night_clear.gif, clear,'--',0.0,,'--'
//Normal: 15.4,75,WSW,0,0.0,30.0,Rising Slowly,night_clear.gif,Metar EGPD: Clear,11.0,0,1249502069,0.031
//OLD:    20,82,WNW,0,0.0,1020.0,Rising Slowly,day_cloudy.gif,Overcast,0.102
    $DEWPT = '-';
		$FTIME = '-';
		$UDATE = '-';
  }

  if(is_numeric($TEMP) and $TEMP <> '-') {
		if (!preg_match('|C|',$uomTemp)) { // convert F to C
			$TEMP = (float)(($TEMP - 32) / 1.8 );
			$TEMP = round($TEMP,2);
		}
  }
  if(is_numeric($DEWPT) and $DEWPT <> '-') {
		if (!preg_match('|C|',$uomTemp)) { // convert F to C
			$DEWPT = (float)(($DEWPT - 32) / 1.8 );
			$DEWPT = round($DEWPT,2);
		}
  }
  if(strlen($WDIR) < 1 || $WDIR == 'N/A') {$WDIR = 'n/a'; }
  if (is_numeric($WSPD) and $WSPD <> '-') {
		if (!preg_match('|m/s|',$uomWind)) { // convert wind to meters-per-second
			 switch ($uomWind) {
				case "mph":
				$WSPD = (float)($WSPD * 0.44704); // mph -> m/s
				break;
				case "kmh":
				case "km/h":
				$WSPD = (float)($WSPD * 0.277778); // km/h -> m/s
				break;
				case "kts":
				$WSPD = (float)($WSPD * 0.514444); // Knots -> m/s
				break;
			}
			$WSPD = round($WSPD,2);
		}
  }
  
  if ($GUST <> '-' and is_numeric($GUST)) {
		if (!preg_match('|m/s|',$uomWind)) { // convert wind to meters-per-second
			 switch ($uomWind) {
				case "mph":
				$GUST = (float)($GUST * 0.44704); // mph -> m/s
				break;
				case "kmh":
				case "km/h":
				$GUST = (float)($GUST * 0.277778); // km/h -> m/s
				break;
				case "kts":
				$GUST = (float)($GUST * 0.514444); // Knots -> m/s
				break;
			}
			$GUST = round($GUST,2);
		}
  }
  if(is_numeric($RAIN) and $RAIN <> '-') {
		if ($uomRain <> 'mm') { // convert rain from inches to millimeters
			$RAIN = (float)($RAIN * 25.4);
			$RAIN = round($RAIN,2);
		}
  }
  if(is_numeric($BARO) and $BARO <> '-') {
		if ($uomBaro <> 'hPa' and $uomBaro <> 'mb' ) { // convert inHg to hPa(mb)
			$BARO = (float)($BARO * 33.86 );  // inHg -> hPa(or mb)
		}
		$BARO = round($BARO,2);
  }
  $UDATE = date('H:i:s T',$UDATE);

  return("$TEMP,$HUMID,$WDIR,$WSPD,$RAIN,$BARO,$BTRND,$COND,$CTXT,$DEWPT,$GUST,$UDATE,$FTIME");

}

// -------------------------------------------------------------------------------
// decode feature set, generate img for features
// 
function RMNET_decode_features($features) {
	global $condIconsDir;
	
	$hasWX = preg_match('|weather|i',$features);
	$hasCAM = preg_match('|webcam|i',$features);
	$hasLGT = preg_match('|lightning|i',$features);
	$features = preg_replace('|weather|is','Weather',$features);
	$features = preg_replace('|webcam|is','WebCam',$features);
	$features = preg_replace('|lightning|is','Lightning',$features);
	
	if ($hasWX and $hasCAM and $hasLGT ) {
		return("<img src=\"" . $condIconsDir . "feat_all.jpg\" alt=\"$features\" title=\"".ML_START."$features".ML_END."\" align=\"left\" />");
	}
	if ($hasWX and $hasCAM) {
		return("<img src=\"" . $condIconsDir . "feat_cam.jpg\" alt=\"$features\" title=\"".ML_START."$features".ML_END."\" align=\"left\" />");
	}
	if ($hasWX and $hasLGT) {
		return("<img src=\"" . $condIconsDir . "feat_li.jpg\" alt=\"$features\" title=\"".ML_START."$features".ML_END."\" align=\"left\" />");
	}
	if ($hasWX) {
		return("<img src=\"" . $condIconsDir . "feat_we.jpg\" alt=\"$features\" title=\"".ML_START."$features".ML_END."\" align=\"left\" />");
	}

  return("");	
	
} // end RMNET_decode_features

// -------------------------------------------------------------------------------
// decode feature set, generate img for features
// 
function RMNET_decode_features_code($features) {
	global $condIconsDir;
	
	$hasWX = preg_match('|weather|i',$features);
	$hasCAM = preg_match('|webcam|i',$features);
	$hasLGT = preg_match('|lightning|i',$features);
	
	if ($hasWX and $hasCAM and $hasLGT ) {
		return("all");
	}
	if ($hasWX and $hasLGT) {
		return("lgt");
	}
	if ($hasWX and $hasCAM) {
		return("cam");
	}
	if ($hasWX) {
		return("wx");
	}

  return("");	
	
} // end RMNET_decode_features_code

// -------------------------------------------------------------------------------
// format conditions display - google maps for JSON
// 
function RMNET_format_conditions_google_short ($rawconds,$uomTemp,$uomWind,$uomRain,$uomBaro) {
	global $condIconsDir;


   list($TEMP,$HUMID,$WDIR,$WSPD,$RAIN,$BARO,$BTRND,$COND,$CTXT,$DEWPT,$GUST,$UDATE,$FTIME) = 
      explode(',',$rawconds.',-,-,-,,');
   
   if (count(explode(',',$rawconds)) < 10) {
     return(RMNET_OFFLINE);
   }
   if ($UDATE == '-') { // old conditions tag record w/o DEWPT or GUST
	   $FTIME = $DEWPT;
	   $DEWPT = '-';
	   $GUST = '-';
   }
   // units conversion if needed
   if($TEMP !== '-' and is_numeric($TEMP)) { 
		 if(!preg_match('|C|',$uomTemp)) {
			 $TEMP = ($TEMP * 1.8) + 32.0;
		 }
		 $TEMP = round($TEMP,0);
	 } else {
		 $TEMP = 'n/a';
   }

   if($DEWPT !== '-' and is_numeric($DEWPT)) { 
		 if(!preg_match('|C|',$uomTemp)) {
			 $DEWPT = ($DEWPT * 1.8) + 32.0;
		 }
		 $DEWPT = round($DEWPT,0);
   } else {$DEWPT = 'n/a';}

   if(strlen($WDIR) < 1 || $WDIR == 'N/A') {$WDIR = 'n/a'; }

   if ($WSPD !== '-' and is_numeric($WSPD)) {
		 if ($uomWind <> 'm/s') { // convert wind from meters-per-second
			 switch ($uomWind) {
				case "mph":
				$WSPD = (float)($WSPD * 2.23693629); // m/s -> mph
				break;
				case "kmh":
				case "km/h":
				$WSPD = (float)($WSPD * 3.6); // m/s -> kmh
				break;
				case "kts":
				$WSPD = (float)($WSPD * 1.94384449); // m/s -> knots
				break;
			}
			$WSPD = round($WSPD,0); // no decimal point for mph, kmh, knots
		} else {
			$WSPD = sprintf("%01.1f",round($WSPD,1)); // one dp for m/s
		}
   } else {
	   $WSPD = 'n/a'; $WDIR = 'n/a';
   }

   if ($GUST !== '-' and is_numeric($GUST)) {
		 if ($uomWind <> 'm/s') { // convert wind from meters-per-second
			 switch ($uomWind) {
				case "mph":
				$GUST = (float)($GUST * 2.23693629); // m/s -> mph
				break;
				case "kmh":
				case "km/h":
				$GUST = (float)($GUST * 3.6); // m/s -> kmh
				break;
				case "kts":
				$GUST = (float)($GUST * 1.94384449); // m/s -> knots
				break;
			}
			$GUST = round($GUST,0); // no decimal point for mph, kmh, knots
		} else {
			$GUST = sprintf("%01.1f",round($GUST,1)); // one dp for m/s
		}
   } else {
	  $GUST = 'n/a';
   }

  if ($RAIN !== '-' and is_numeric($RAIN)) {
		if ($uomRain <> 'mm') { // convert rain from mm to inches
			$RAIN = (float)($RAIN * 0.0393700787);
			$RAIN = sprintf("%01.2f",round($RAIN,2));
		} else {
			$RAIN = sprintf("%01.1f",round($RAIN,1));
		}
  } else { $RAIN = "n/a"; }

  if ($BARO !== '-' and is_numeric($BARO)) {
		if ($uomBaro <> 'hPa' and $uomBaro <> 'mb' ) { // convert hPa(mb) to inHG
			$BARO = (float)($BARO * 0.0295333727 );  // hPa(or mb) to inHg
			$BARO = sprintf("%01.2f",round($BARO,2));
		} else {
			 $BARO = sprintf("%01.1f",round($BARO,1));
		}
  } else {$BARO = 'n/a'; $BTRND = 'n/a'; }
  
  if($HUMID == '-' or !is_numeric($HUMID)) {$HUMID = 'n/a'; }
  
  // format the string for conditions
  $cString = '';
  if (preg_match('|.gif$|',$COND) && $condIconsDir) {
    $tCTXT = preg_replace('|Metar \S+: |i','',$CTXT);
    $cString .= "$COND,$tCTXT,";
  } else {
    $cString .= ",,";
  }

  $cString .= "$TEMP $uomTemp,$HUMID%,";
  if($DEWPT !== 'n/a') {
    $cString .= "$DEWPT $uomTemp,";
  } else {
	$cString .= ",";
  }

  if ($WDIR == 'n/a') {
    $cString .= "$WDIR,,"; 
  } else {
    $cString .= "$WDIR,";
    $cString .=  "$WSPD $uomWind,";
	if($GUST <> 'n/a') {
		$cString .=  "$GUST,";
	} else {
		$cString .= ","; }
  }

  $cString .= "$RAIN $uomRain,";
  
  $cString .= "$BARO $uomBaro,$BTRND,$UDATE";
  
  return($cString);
  

} // end RMNET_format_conditions_google_short

// -------------------------------------------------------------------------------
// load the configuration file and set up $Stations
function RMNET_load_stations($localCacheFile) {
   global $Stations,$masterConfigURL,$onMasterHost,$refetchSeconds,$Debug;

   if(!file_exists($localCacheFile) or 
      (file_exists($localCacheFile) and filemtime($localCacheFile)+$refetchSeconds < time() 
	    and !$onMasterHost) ) {
		  RMNET_get_file($localCacheFile,$masterConfigURL); 
   }
   if(file_exists($localCacheFile)) {
	  $age = time() - filemtime($localCacheFile); 
      $rawlinks = file($localCacheFile); // read file into array
      $Debug .= "<!-- RMNET_load_stations: using Cached version from $localCacheFile age=$age seconds.-->\n\n";
	  // strip comment records, build $Stations indexed array
	  $nrec = 0;
	  $Seqno = 0;
      foreach ($rawlinks as $rec) {
	    $Seqno++;
	    $rec = preg_replace("|[\n\r]*|","",$rec);
	    $len = strlen($rec);
	    if($rec and substr($rec,0,1) <> "#") {  //only take non-comments
//	 	   echo "Rec $nrec ($len): $rec\n";
		   list($State,$URL,$Name,$Coords,$Features,$DataPage,$RawDataType,$RawDataURL,$Offsets,$METAR,$LatLong) = explode("|",trim($rec) . '||||||||||||');
		   $LatLong = preg_replace('|[\+ ]+|','',$LatLong);
		   $URL = trim($URL);
		   if(!preg_match('|^http[s]{0,1}://|i',$URL)) {$URL = 'http://'.$URL; }
		   $Stations["$State\t$Name\t$Seqno"] = "$URL\t$Coords\t$Features\t$DataPage\t$RawDataType\t$RawDataURL\t$Offsets\t$METAR\t$LatLong";  
		   // prepare for sort
//		   echo "<a href=\"$URL\">$Name</a> $State, coord=\"$Coords\"\n";
		   
		} elseif (strlen($rec) > 0) {
//		   echo "comment $nrec ($len): $rec\n";
		} else {
//		   echo "blank record ignored\n";
		}
	    $nrec++;
	  }

     ksort($Stations);  // now sort the keys (state, station name)
   }
}
// ------------------------------------------------------------------

// fetch the weather data from stations and place in $StationData
function RMNET_load_weather_data() {
  global $Stations,$StationData,$StationRawData,$Debug,$maxAge,
         $RMNETcacheName,$refetchSeconds,$masterCacheURL,$onMasterHost,$MesoTimeFormat;
		 
// use cache if current and available
   if(!file_exists($RMNETcacheName) or 
      (file_exists($RMNETcacheName) and filemtime($RMNETcacheName)+$refetchSeconds < time() 
	   and !$onMasterHost) ) {
		  RMNET_get_file($RMNETcacheName,$masterCacheURL); 
   }
		 
// use cache if current and available
if ($RMNETcacheName and file_exists($RMNETcacheName)) {
	  $age = time() - filemtime($RMNETcacheName);
      $rawcache = file($RMNETcacheName);
	  if (preg_match('|\t\d+\||',$rawcache[0])) {
		  $Debug .= "<!-- RMNET_load_weather_data: using Cached version from $RMNETcacheName age=$age seconds.-->\n\n";
		  foreach ($rawcache as $rec) {
			list($key,$val) = explode('|',trim($rec).'|');
			if ($key <> '') {
			  $StationData["$key"] = $val;
			}
		  }
		  return;
	  } else {
		  $Debug .= "<!-- RMNET_load_weather_data: invalid local cache in $RMNETcacheName -->\n";
	  }      
	  
    }
}

// ------------------------------------------------------------------
//  select units to display based on units of measure (UOM)
function RMNET_load_units($UOM) {

global $dpRain,$dpWind,$dpBaro,$dpTemp,$useKnots,$useMPH,$useMPS,$useMB;

$Units = array();

if ($UOM == 'M') {
    $Units =  array(  // metric with native wind units
    'wind' => 'km/h',
	'temp' => '&deg;C',
	'baro' => 'hPa',
	'humid' => '%',
	'rain' => 'mm',
	'dist' => 'km');
	$dpRain = 1;
	$dpBaro = 1;
	$dpTemp = 1;
	$dpWind = 0;
  } else {
    $Units =  array(  // english with native wind units
    'wind' => 'mph',
	'temp' => '&deg;F',
	'baro' => 'in',
	'humid' => '%',
	'rain' => 'in',
	'dist' => 'nm');
	$dpRain = 2;
	$dpBaro = 2;
	$dpTemp = 0;
	$dpWind = 0;

}

 $Units['time'] = '';
 if ($useKnots) { $Units['wind'] = 'kts'; }
 if ($useMPH)   { $Units['wind'] = 'mph'; }
 if ($useMPS)   { $Units['wind'] = 'm/s'; $dpWind = 1; }
 if ($useMB and $UOM == 'M')    { $Units['baro'] = 'mb';}
 
 return $Units;
} // end RMNET_load_units
// ------------------------------------------------------------------

function RMNET_getFireDanger( $intemp, $rh) {
  global $Units;
  // from SLOWeather.com = calculate fire danger based on temperature and relative humidity
  
  // Convert F temp to C temp if needed
	if(!is_numeric($intemp) or !is_numeric($rh)) {

    $val = '<span style="display:none;">'.$intemp.', '.$rh.'%'.
	   '</span>';
		return($val);
	}
	if (preg_match('|F|',$Units['temp'])) {
    $ctemp = ($intemp - 32) * 0.5556;
	$ftemp = $intemp;
  } else {
	$ctemp = $intemp;
	$ftemp = (1.8 * $intemp) + 32.0;
  }
  // Start Index Calcs
  if($rh < 1 or ($ctemp < 1 and $rh < 1)) { return("n/a"); }
  // Chandler Index
  $cbi = (((110 - 1.373 * $rh) - 0.54 * (10.20 - $ctemp)) * (124 * pow(10,(-0.0142*"$rh"))))/60;
  // CBI = (((110 - 1.373*RH) - 0.54 * (10.20 - T)) * (124 * 10**(-0.0142*RH)))/60
  
  //Sort out the Chandler Index
  
  $cbi = round($cbi,1);
  
  if ($cbi > "97.5") {
	  $cbitxt = RMNET_CBI_EXTREME;
	  $cbiclass = 'RMNETcbiEX';
  
  } elseif ($cbi >="90") {
	  $cbitxt = RMNET_CBI_VERYHIGH;
	  $cbiclass = 'RMNETcbiVH';
  
  } elseif ($cbi >= "75") {
	  $cbitxt = RMNET_CBI_HIGH;
	  $cbiclass = 'RMNETcbiH';
  
  } elseif ($cbi >= "50") {
	  $cbitxt = RMNET_CBI_MODERATE;
	  $cbiclass = 'RMNETcbiM';
  
  } else  {
	  $cbitxt = RMNET_CBI_LOW;
	  $cbiclass = 'RMNETcbiL';
  }
   
  $ftemp = sprintf("%01.1f",round($ftemp,1)); // one dp
  $ctemp = sprintf("%01.1f",round($ctemp,1)); // one dp

  $val = '<span style="display:none;">'.$cbi.'</span><span class="' . $cbiclass . 
	   '" title="CBI='.$cbi.' @ '.$ctemp.'C/'.$ftemp.'F, '.$rh.'%'.
	   '">&nbsp;' .
	   RMNET_CBItext($cbitxt) . '&nbsp;</span>';

  return("$val"); // (CBI " . round($cbi,0) . ")");

} // end RMNET_getFireDanger
// ------------------------------------------------------------

?>