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
//  Version 3.00 - 24-Jul-2016 - repurposed *-mesomap.php for Google Map generation
//  Version 3.03 - 31-Jul-2016 - added CBI Fire Danger display functions to table, map labels and popups
//  Version 4.00 - 23-May-2018 - rewrite to use Leaflet/OpenStreetMaps+others for map display
//  Version 4.03 - 29-May-2018 - fixes for timezone display processing
//  Version 4.04 - 11-Jun-2018 - fixes for deprecated each() function
//  Version 4.07 - 21-Jan-2022 - added call to new_strftime() function for PHP 8.1
//  Version 4.08 - 31-Aug-2022 - fix for display of conditions updated time
//  Version 4.09 - 27-Dec-2022 - fixes for PHP 8.2
//
//  Note: distribution of this program is limited to members of the
//  Affiliated Regional Weather Networks (http://www.northamericanweather.net/ )
//  Distribution outside of the membership is prohibited.
//  Copyright 2006-2014, Ken True - Saratoga-weather.org
//
$Version = "V4.09 - 27-Dec-2022";


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

$lang = 'en'; // default language
require_once("mesonet-map-settings.php");

// Check parameters and force defaults/ranges
  $IncludeMode = true;
  $PrintMode = false;
  $showNoData = false;   // exclude table rows with no conditions data available.. screws up sort if enabled
  
  if (isset($doPrintRMNET) && ! $doPrintRMNET ) {
      $PrintMode = false;
  }
  if (isset($_REQUEST['inc']) && 
      strtolower($_REQUEST['inc']) == 'noprint' ) {
	  $PrintMode = false;
  }

if (isset($_REQUEST['inc']) && strtolower($_REQUEST['inc']) == 'y') {
  $IncludeMode = true;
}
if (isset($doIncludeRMNET)) {
  $IncludeMode = $doIncludeRMNET;
}


if ( ! isset($_REQUEST['inc']) )
        $_REQUEST['inc']="";
$includeOnly = strtolower($_REQUEST['inc']); // any nonblank is ok
if ($includeOnly) {$includeOnly = "Y";}

// show map with hotspots outlined
if (isset($_REQUEST['show']) and strtolower($_REQUEST['show']) == 'map' ) {
 $ShowMap = '&amp;show=map';
 $genJPEG = true;
} else {
 $ShowMap = '';  // no outlines for map
 $genJPEG = false;
}


if($windArrowDir && ! file_exists("$windArrowDir" . 'NNE.gif') ) {
   $windArrowDir = '';  // bad spec.. no arrows found
}

if($condIconsDir && ! file_exists("$condIconsDir" . 'day_clear.png') ) {
   $condIconsDir = '';  // bad spec.. no icons found
}
 
if (isset($_REQUEST['debug']) and strtolower($_REQUEST['debug']) == 'y') {
  $doDebug = true; 
  } else {
  $doDebug = false;
}
$doDebug = true;

if (isset($_REQUEST['cache']) and strtolower($_REQUEST['cache']) == 'no') {
  $refetchSeconds = 10;  // set short period for refresh of cache
}

$maxAge = $maxAge + $refetchSeconds + 10; // no age penalty from caching
$maxAgeMetar = $maxAgeMetar + $refetchSeconds + 10; // no cache penalty

// HTML specific overrides if desired
$otherParms = '';
if (isset($_REQUEST['gen']) and strtolower($_REQUEST['gen']) == 'xhtml' ) {
 $TARGET = '';  // no target for XHTML 1.0-Strict
 $otherParms = "&amp;gen=xhtml";
} else {
 $TARGET = "target=\"_blank\"";
 $otherParms = "&amp;gen=html";
 
}
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
 if($doDebug) {$Debug .= "<!-- ourHost='$ourHost' mH='$masterHost' onM='$onMasterHost' -->\n";}
if($onMasterHost) {
 if($doDebug) { 
   $Debug .= "<!-- using master config/conditions files -->\n"; 
   $Debug .= "<!-- cacheDir ='$cacheDir' -->\n";
 }
 $RMNETlinksFile = './'.M_NETID.'-stations-cc.txt';
 $RMNETcacheName = './'.M_NETID.'-conditions.txt';
 $Debug .= "<!-- using RMNETlinksFile='$RMNETlinksFile' -->\n";
 $Debug .= "<!-- using RMNETcacheName='$RMNETcacheName' -->\n";
 
}
// Establish timezone offset for time display
$originalTZ = date('e');
if (!function_exists('date_default_timezone_set')) {
	
	if (! ini_get('safe_mode') ) {
	   putenv("TZ="+M_TZ);  // set our timezone for 'as of' date on file
	}
  } else {
   date_default_timezone_set(M_TZ);
   $Debug .= "<!-- note: timezone set to '".M_TZ."' -->\n";
}
$locale = setlocale(LC_TIME,$lang.'_'.strtoupper($lang));
$Debug .= "<!-- time locale set to $locale -->\n";

if ($lang == 'el') {
//	error_reporting(E_ALL);
	$locale = setlocale(LC_TIME,'el_EL','ell','gre','grc','greek');
    $Debug .= "<!-- time locale set to $locale -->\n";
}
if ($lang == 'hu') {
//	error_reporting(E_ALL);
	$locale = setlocale(LC_TIME,'hu_HU','hun','hungary','hungarian');
    $Debug .= "<!-- time locale set to $locale -->\n";
}
if ($lang == 'nl') {
//	error_reporting(E_ALL);
	$locale = setlocale(LC_TIME,'nl_NL.ISO8859-1', 'nld_nld', 'nl_NL','nl_NLD',"nld","NLD", "dutch", "holland", "netherlands");
    $Debug .= "<!-- time locale set to $locale -->\n";
}
/* Used for setlocale() NL-BE
nl_BE.ISO8859-1  
nl_BE.ISO8859-15  
nl_BE.UTF-8  
nl_NL.ISO8859-1  
nl_NL.ISO8859-15  
nl_NL.UTF-8  
*/
if ($lang == 'fr') {
//	error_reporting(E_ALL);
	$locale = setlocale(LC_TIME,'fr_FR.ISO8859-1', 'fra_fra', 'fr_FR','fr_FRA',"fra","FRA", "french");
    $Debug .= "<!-- time locale set to $locale -->\n";
}

if ($lang == 'sr') {
//	error_reporting(E_ALL);
	$locale = setlocale(LC_TIME,'sr_SRP.ISO8859-2', 'sr_SRP', 'sr','srp','SRP','serbe');
    $Debug .= "<!-- time locale set to $locale -->\n";
}

// ---------------------main program -----------------------------------

global $Debug,$doDebug,$RMNETcacheName,$refetchSeconds,$Icons,$windArrowDir, $NAtext, $lang;
global $rmShowFireDanger, $cacheDir;


$Stations = array();  // storage area for the station info
$StationData = array(); // storage area for current conditions
$timeStamp = time();
$currentTime = RMNET_lang_fixTZname(new_strftime($MesoTimeFormat,$timeStamp));

RMNET_load_stations($RMNETlinksFile);  // load up the $Stations from the config file
	
$Units = RMNET_load_units($myUOM);
	
RMNET_load_weather_data();  // this is the time consumer :-)
	

reset($Stations);  // and reset the list from the config file
 
$lastState = '';   // to handle the sublists

// generate the links list sorted by State, Station name
$RMNET_ListHTML = '
<div id="RMNETlist"> 
  <ul style="list-style: square">
  ';
foreach ($Stations as $key => $val) {
  list($State,$Name) = explode("\t",$key);
  list($URL,$Coords,$Features,$DataPage) = explode("\t",$Stations["$key"]);
  
  if ($lastState <> $State) {
	 if ($lastState) {  // not first state
	   $RMNET_ListHTML .= "    </ul>\n";
	   $RMNET_ListHTML .= "  </li>\n";
	 }
	 $RMNET_ListHTML .= "  <li>".RMNET_lang_region($State)."\n";
	 $RMNET_ListHTML .= '    <ul style="list-style: circle">' . "\n";
	 $lastState = $State;
  }  // different state
  $t = '';
  if ($TARGET) {$t = " $TARGET";}
  $URL = preg_replace('|\&|','&amp;',$URL);

  $RMNET_ListHTML .= "	      <li><a href=\"$URL\"$t>$Name</a> 
			   [ " . RMNET_lang_stationfeatures($Features) . " ]</li>\n";
}  // end while
$RMNET_ListHTML .= "    </ul>\n";
$RMNET_ListHTML .= "  </li>\n";
$RMNET_ListHTML .= "</ul>\n";
$RMNET_ListHTML .= "</div>\n";
// end printList

// Generate the table of station conditions in $RMNET_table

RMNET_prt_tablehead(); // initialize the $RMNET_table area with col heads

reset($Stations);  // and reset the list so we can start at begining

// print the individual station's data in the $RMNET_table
foreach ($Stations as $key => $val) {
  list($State,$Name) = explode("\t",$key);
  list($URL,$Coords,$Features,$DataPage) = explode("\t",$Stations["$key"]);

  RMNET_prt_tabledata($key);

}  // end while

$RMNET_table .= "</tbody>\n</table>\n<p>&nbsp;</p>\n\n";
$RMNET_table .= "<!-- end of included ".M_NETID." table text -->\n";
// $RMNET_table now completed

// find the oldest/newest data times
$oldestData = 9999999999999999;
$newestData = 0;

foreach ($StationData as $key => $vals) { 
  list($TEMP,$HUMID,$WDIR,$WSPD,$RAIN,$BARO,$BTRND,$COND,$CTXT,$DEWPT,$GUST,$UDATE,$FTIME) = preg_split("/\,/",$vals);
  if($UDATE > 1000) {
	$oldestData = min($UDATE,$oldestData);
	$newestData = max($UDATE,$newestData);
  }
}

$Debug .= "<!-- oldest data=$oldestData (".gmdate(DATE_COOKIE,$oldestData).") Lcl=(".date(DATE_COOKIE,$oldestData).") RMNET=(".RMNET_lang_fixTZname(new_strftime($MesoTimeFormat,$oldestData)).") -->\n";
$Debug .= "<!-- newest data=$newestData (".gmdate(DATE_COOKIE,$newestData).") Lcl=(".date(DATE_COOKIE,$newestData).") RMNET=(".RMNET_lang_fixTZname(new_strftime($MesoTimeFormat,$newestData)).") -->\n";


$RMNET_CondsDates = 
 "<p>" . RMNET_CONDSFROM. " " . RMNET_lang_fixTZname(new_strftime($MesoTimeFormat,$oldestData)) . " ". RMNET_CONDSTO . " " . RMNET_lang_fixTZname(new_strftime($MesoTimeFormat,$newestData)) . " </p>\n"; 

return;

//------------------------end of main program -----------------------------


//----------------------------functions ----------------------------------- 

//  produce the table heading row 
function RMNET_prt_tablehead ( ){

global $Units, $RMNET_table, $LegendX, $LegendY, $showTownName,$rmShowFireDanger;
// --------------- customize HTML if you like -----------------------
	    $RMNET_table .= "
<p class=\"RMNETtable\"><small>". RMNET_COLSORT . "</small></p>
<table border=\"0\" class=\"sortable RMNETtable\" cellspacing=\"2\">
 <thead>
 <tr>
  <th style=\"text-align: left;vertical-align: top;cursor: n-resize;\"><br />".RMNET_STATE."</th>
  <th style=\"text-align: left;vertical-align: top;cursor: n-resize;\"><br />".RMNET_STATION."</th>
  <th style=\"text-align: center;vertical-align: top;\" class=\"sorttable_nosort\">".RMNET_CURHEAD."</th>
  <th style=\"text-align: center;vertical-align: top;cursor: n-resize;\">".RMNET_TEMP."<br />" . $Units['temp'] . "</th>
  <th style=\"text-align: center;vertical-align: top;cursor: n-resize;\">".RMNET_DEWPT."<br />" . $Units['temp'] . "</th>
  <th style=\"text-align: center;vertical-align: top;cursor: n-resize;\">".RMNET_HUM."<br />" . $Units['humid'] . "</th>
  <th style=\"text-align: center;vertical-align: top;cursor: n-resize;\" class=\"sorttable_numeric\">".RMNET_AVGWIND."<br/>" . $Units['wind'] . "</th>
  <th style=\"text-align: center;vertical-align: top;cursor: n-resize;\" class=\"sorttable_numeric\">".RMNET_GUSTWIND."<br/>" . $Units['wind'] . "</th>
  <th style=\"text-align: center;vertical-align: top;cursor: n-resize;\">".RMNET_PRECIPS."<br />" . $Units['rain'] . "</th>
  <th style=\"text-align: center;vertical-align: top;cursor: n-resize;\">".RMNET_BAROB."<br />" . $Units['baro'] . "</th>
  <th style=\"text-align: center;vertical-align: top;\" class=\"sorttable_nosort\">".RMNET_BAROT."</th>\n";
 if($rmShowFireDanger) {
   $RMNET_table .= "  <th style=\"text-align: center;vertical-align: top; cursor: n-resize;\" class=\"sorttable_numeric\">". RMNET_CBI."</th>\n";
 }
     $RMNET_table .= "	 <th style=\"text-align: center;vertical-align: top;cursor: n-resize;\">".RMNET_DATAUPDT."</th>
</tr>
</thead>
<tbody>
";

return;
}  // end function RMNET_prt_tablehead
// ------------------------------------------------------------------

// produce one row of current conditions data
function RMNET_prt_tabledata($key) {

 global $StationData,$Stations,$Units,$RMNET_table,$Debug;
 global $skipNoData,$windArrowDir,$showNoData,$TARGET, $windArrowSize,$Icons,$condIconsDir;
 global $IconsText, $showBaroTrendArrow, $showMapCondIcons, $NAtext, $showTownName, $mainLang, $lang;
 global $rmShowFireDanger;

  if ($skipNoData && ! isset($StationData["$key"])) { return; }
  
    list($State,$Name,$Seqno) = explode("\t",$key);
	list($URL,$Coords,$Features,$DataPage,$RawDataType,$RawDataURL) = explode("\t",$Stations["$key"]);
	$URLparts = parse_url($URL);
	if(!isset($URLparts['host'])) {
		$Debug .= "<!-- $State $Name $Seqno has bad URL='$URL' -->\n";
		$hostalias = "C$Seqno";
	} else {
	  $host = $URLparts['host'];
	  $hostalias = preg_replace('/www\.|\.\w{3}|\W/s','',$host);
	  $hostalias = preg_replace('/weather/','wx',$hostalias);
	  $hostalias = "C".$hostalias."S$Seqno";
	}
	
	if ($windArrowSize == 'S') {
	  $windGIF = '-sm.gif';
	  $windSIZE = 'height="9" width="9"';
	} else {
	  $windGIF = '.gif';
	  $windSIZE = 'height="14" width="14"';
	}
//	$Debug .= "<!-- lang=$lang mainLang=$mainLang Name=$Name DataPage=$DataPage -->\n";
    if ($lang == $mainLang and $DataPage <> 'none' and $DataPage <> '') { // substitute display name from stations-cc.txt 
	   $Name = $DataPage;
	}
//    $Debug .= "<!-- Name=$Name -->\n";
 
  if (! isset($StationData["$key"])) {
  
    if ($showNoData) {
      $RMNET_table .= "
<tr>
  <td>".RMNET_lang_region($State)."</td>
  <td><a href=\"$URL\">$Name</a></td>
  <td colspan=\"10\" align=\"left\">".RMNET_NOCOND."</td>
</tr>
 ";
     } // end showNoData

   return;
   }
// got data for one of our stations.. format the table entry

 	list($TEMP,$HUMID,$WDIR,$WSPD,$RAIN,$BARO,$BTRND,$COND,$CTXT,$DEWPT,$GUST,$UDATE,$FTIME) = preg_split("/\,/",$StationData["$key"]);

// --------------- customize HTML if you like -----------------------
	$t = '';
	if ($TARGET) {$t = " $TARGET";}
	if (preg_match('/Metar/i',$CTXT)) {
		$tCTXT = explode(': ',$CTXT);
		if(isset($cTXT[1])) {$CTXT = $cTXT[1]; }
	}
	$URL = preg_replace('|\&|','&amp;',$URL);
	$RMNET_table .= "
<tr>
  <td>".RMNET_lang_region($State)."</td>
  <td><a href=\"$URL\"$t>$Name</a></td>
  <td align=\"center\">";
  if (preg_match('|.gif$|',$COND) && $condIconsDir) {
	$tImg = preg_replace('|.gif$|','.png',$COND);
    $RMNET_table .= "<img src=\"$condIconsDir" . $tImg . "\" height=\"25\" width=\"25\"
	alt=\"".RMNET_lang_WXconds($CTXT)."\" title=\"".RMNET_lang_WXconds($CTXT)."\" />";
  } else {
    $RMNET_table .= RMNET_lang_WXconds($COND);
  }
  $RMNET_table .= "</td>
  <td align=\"center\">$TEMP</td>
  <td align=\"center\">$DEWPT</td>
  <td align=\"center\">$HUMID</td>
  <td align=\"right\" style=\"padding-right: 10px;\">"; 

  if($WDIR == "0") {$WDIR = 'N';}
	if($WDIR == '---') {$WDIR = 'n/a';}
  if(strlen($WDIR) < 1 || $WDIR == 'N/A') {$WDIR = 'n/a'; }

  if ($WDIR == 'n/a') {
    $RMNET_table .= $WDIR . "&nbsp;" . $WSPD; 
  } else {
    $wda = $WDIR;
	$RMNET_table .= RMNET_lang_winddir($wda);
	if ($windArrowDir) {
       $RMNET_table .= "&nbsp;<img src=\"$windArrowDir{$wda}.gif\" height=\"14\" width=\"14\" 
	    alt=\"".RMNET_WINDFROM." ".RMNET_lang_winddir($wda) ."\" title=\"".RMNET_WINDFROM." ".RMNET_lang_winddir($wda) ."\" />";
	}
    $RMNET_table .=  "&nbsp;" . $WSPD;
  }
  $RMNET_table .= "</td>
  <td align=\"center\">$GUST</td>
  <td align=\"center\">$RAIN</td>
  <td align=\"center\"><table class=\"RMNETtable\"><tr><td>$BARO</td><td>";
  if($showBaroTrendArrow) {
    $RMNET_table .= RMNET_getBaroTrendArrow($BTRND);
  } 
  $RMNET_table .="</td></tr></table></td>
  <td align=\"center\">".RMNET_lang_barotrend($BTRND)."</td>\n";
  if($rmShowFireDanger) {
    $RMNET_table .="  <td align=\"center\">".RMNET_getFireDanger($TEMP,$HUMID)."</td>\n";
  }
	$tDate = is_numeric($UDATE)?date('H:i:s',$UDATE):$UDATE;
  $RMNET_table .="  <td align=\"center\">" . $tDate . "</td>
  <!-- $RawDataType load time: $FTIME sec -->
</tr>\n";

// generate the data for the changing conditions display 
// NOTE: changes here may break the rotating conditions display..


return;
} // end RMNET_prt_tabledata
// ------------------------------------------------------------------


// ------------------------------------------------------------
// utility functions to handle conversions from clientraw data to desired units-of-measure
function convertTemp ( $intemp , $tempUOM = 'C') {
   global $myUOM,$dpTemp,$Debug;
   $rawtemp = $intemp;
   if(!preg_match('|C|',$tempUOM)) { // convert to C first
     $rawtemp = (float)(($intemp - 32.0) / 1.8 );
     $Debug .= "<!-- convertTemp($intemp,$tempUOM) to C $rawtemp (unrounded) -->\n";
   }
   
	if ($myUOM == 'E') { // convert C to F
		return( round((1.8 * $rawtemp) + 32.0,$dpTemp));
	} else {  // leave as C
		return (round($rawtemp * 1.0,$dpTemp));
	}
}

function convertWind  ( $inwind, $inUOM = 'KTS' ) {
   global $myUOM,$useKnots,$useMPH,$useMPS,$dpWind,$Debug;
  
   $using = '';
   $WIND = '';
   $cvt = '';
   $rawwind = $inwind;
   if($inUOM <> 'KTS') { // first convert to knots
      if(preg_match('!km!i',$inUOM)) {
	     $rawwind = $rawwind * 0.539956803;  // kmh -> knots
		 $cvt = ',kmh';
	  }
	  if(preg_match('!mph!i',$inUOM)) {
	     $rawwind = $rawwind * 0.868976242;  // mph -> knots
		 $cvt = ',mph';
	  }
	  if(preg_match('!m/s|mps!i',$inUOM)) {
	     $rawwind = $rawwind * 1.94384449;  // m/s -> knots
		 $cvt = ',m/s';
	  }
   
   }
   
	if (($myUOM == 'E' || $useMPH) and (! $useKnots and ! $useMPS)) { // convert knots to mph
		$WIND = sprintf($dpWind?"%02.{$dpWind}f":"%d",round($rawwind * 1.1507794,$dpWind));
		$using = 'MPH';
	}   
	if ($useKnots) { $WIND = round($rawwind * 1.0,$dpWind) ;
	  $using='KTS';
	} //force usage of knots for speed
    if ($myUOM == 'M' and $useMPS and ! $useKnots and ! $useMPH ) { // convert knots to m/s
		  $WIND = sprintf($dpWind?"%02.{$dpWind}f":"%d",round($rawwind * 0.514444444,$dpWind));
		  $using = 'MPS';
	} 
	if ($myUOM == 'M' and ! $useMPS and ! $useKnots and ! $useMPH) { // convert knots to km/hr
		  $WIND = sprintf($dpWind?"%02.{$dpWind}f":"%d",round($rawwind * 1.852,$dpWind));
		  $using = 'KMH';
	}
	$Debug .= "<!-- convertWind($inwind$cvt) using $inUOM to $using is '$WIND' -->\n";
	return($WIND);
}

function convertBaro ( $inpress, $baroUOM = 'hPa' ) {
  global $myUOM,$dpBaro,$Debug;
   $rawpress = $inpress;
   if(preg_match('|in|i',$baroUOM)) { // convert to hPa first
     $rawpress = (float)($inpress * 33.86388158 );  // inHg -> hPa(or mb)
     $Debug .= "<!-- convertBaro($inpress,$baroUOM) to hPa $rawpress (unrounded) -->\n";
   }

	if ($myUOM == 'E') { // convert hPa to inHg
	   return (sprintf("%02.{$dpBaro}f",round($rawpress  / 33.86388158,$dpBaro)));
	} else {
	   return (sprintf("%02.{$dpBaro}f",round($rawpress * 1.0,$dpBaro))); // leave in hPa
	}
}

function convertRain ( $inrain, $rainUOM = 'mm' ) {
  global $myUOM,$dpRain,$Debug;
  $rawrain = $inrain;
   if(!preg_match('|mm|i',$rainUOM)) { // convert to mm first
     $rawrain = (float)($inrain * 25.4);
     $Debug .= "<!-- convertRain($inrain,$rainUOM) to mm $rawrain (unrounded) -->\n";
   }
	if ($myUOM == 'E') { // convert mm to inches
	   return (sprintf("%02.{$dpRain}f",round($rawrain * .0393700787,$dpRain)));
	} else {
	   return (sprintf("%02.{$dpRain}f",round($rawrain * 1.0,$dpRain))); // leave in mm
	}
}

// ------------------------------------------------------------------


function RMNET_getBaroTrendArrow($BTRND) {
  global $windArrowDir,$windArrowSize,$showBaroTrendArrow;
  
  $arrows = array(
    'S' => 'W',
	'RS' => 'WSW',
	'R' => 'SW',
	'RR' => 'S',
	'FS' => 'WNW',
	'F'  =>  'NW',
	'FR' =>  'N'
  );
  
  	if ($windArrowSize == 'S') {
	  $windGIF = '-sm.gif';
	  $windSIZE = 'height="9" width="9"';
	} else {
	  $windGIF = '.gif';
	  $windSIZE = 'height="14" width="14"';
	}

  $trend = trim(strtoupper($BTRND));
  $words = explode(" ",$trend . '  ');
  $abbrev= substr($words[0],0,1) . substr($words[1],0,1);
//  $windArrowDir = './arrows/';
  if ($showBaroTrendArrow and $windArrowDir <> '' and isset($arrows[$abbrev])) {
   return("<img src=\"$windArrowDir" . $arrows[$abbrev] . "$windGIF\" $windSIZE 
   alt=\"".RMNET_lang_barotrend($BTRND)."\" title=\"".RMNET_lang_barotrend($BTRND)."\" />");
  } else {
   return("");
  }
}
// ---------------------------------------------------------

?>