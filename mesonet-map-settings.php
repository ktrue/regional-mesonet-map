<?php
global $Debug;
############################################################################
#  mesonet-map-settings.php
#
#  provides global setup values for the mesonet-map system
#
#  Author: Ken True - webmaster@saratoga-weather.org
#
# Version 3.00 - 24-Jul-2016 - initial release
# Version 3.01 - 26-Jul-2016 - fixed support for $rmMapUseDefaults=false and more $SITE[] entries
# Version 3.03 - 31-Jul-2016 - added CBI Fire Danger display functions to table, map labels and popups
# Version 4.00 - 23-May-2018 - rewrite to use Leaflet/OpenStreetMaps+other tile provider for map
# Version 4.03 - 29-May-2018 - defaulted $MesoTimeFormat to show Y-M-D H:M:S Tzone
############################################################################
// ------------- Required settings for mesonet-map.* scripts ----------------------------------------
// *
  $rmNETID = 'SWN';  // default Regional Network ID -- must be one of the defined networks
  $lang = 'en';      // default language
  $rmMapUseUnits = true; // =true set map units per network, =false for T,W,B,R units default on map
  $rmMapUseDefaults = true; // =true, use built-in mapcenter, mapzoom settings for network.
  //                           =false, use center/zoom below.
	$rmProvider = 'Esri_WorldTopoMap'; // ESRI topo map - no key needed
	//$rmProvider = 'OSM';     // OpenStreetMap - no key needed
	//$rmProvider = 'Terrain'; // Terrain map by stamen.com - no key needed
	//$rmProvider = 'OpenTopo'; // OpenTopoMap.com - no key needed
	//$rmProvider = 'Wikimedia'; // Wikimedia map - no key needed
// 
	//$rmProvider = 'MapboxSat';  // Maps by Mapbox.com - API KEY needed in $mapboxAPIkey 
	//$rmProvider = 'MapboxTer';  // Maps by Mapbox.com - API KEY needed in $mapboxAPIkey 
	$mapboxAPIkey = '--mapbox-API-key--';  // use this for the Access Token (API key) to MapBox

  $rmShowFireDanger = true; // =true; show Fire Danger based on Chandler Burning Index; =false don't show
	$rmClusterRadius = 5;     // default =5 number of pixels difference marker points to cluster
	                          // should be number from 5 to 80=max clustering
  $tabHeight	= 831;			  // =831 default. Set to false if you do not want restricted tab height
//
############################################################################
// optional ettings for mesonet-map-inc.php
  $cacheDir  = './cache/'; // relative directory to store the updated conditions/network defs files
  $condIconsDir = './MESO-images/';  // relative directory for pin/cluster/conditions images
  $doLinkTarget = true; // =true to add target="_blank" to links in popups
  //   units-of-measure defaults for display IN THE GOOGLE MAP ONLY.
  //   Note: the Table will display UOM defaults for the network only.  That can't be changed.
  $rmTempUOM = 'F';   // units for Temperature ='C' or ='F';
  $rmWindUOM = 'mph'; // units for Wind Speed ='mph', ='km/h', ='m/s', ='kts'
  $rmBaroUOM = 'inHg';// units for Barometer ='inHg', ='hPa', ='mb'
  $rmRainUOM = 'in';  // units for Rain ='in', ='mm'
  //  Google map settings
  $rmMapCenter = '42.473,-129.339'; // latitude,longitude for initial map center display (decimal degrees)
  $rmMapZoom = 4; // initial map zoom level 1=world, 10=city;
  // SWN w/Hawaii 4,42.473,-129.339
  // SWN CA,NV,UT,AZ: 6,37.179,-116.441
  
  $MesoTimeFormat = '%Y-%m-%d %H:%M:%S %Z';  // 2006-03-31 14:03:22 TZone
  //  $MesoTimeFormat = '%a, %d-%b-%Y %H:%M:%S %Z';  // Fri, 31-Mar-2006 14:03:22 TZone
  //$MesoTimeFormat = '%a, %d-%b-%Y %H:%M:%S';  // Fri, 31-Mar-2006 14:03:22 TZone
  //
  $refetchSeconds = 300;     // refetch every nnnn seconds (300=5 minutes)
  //

  $windArrowDir = './MESO-images/'; // set to directory with wind arrows, or
//                        set to '' if wind arrows are not wanted
//                        the program will test to see if images are
//                        available, and set it to '' if no images are
//                        found in the directory specified.
  
  $NAtext = 'Offline';  // text to display on rotating conditions if no station report
  $maxAge = 62*60;     // max age in seconds for conditions
//                       (62*60 = 62 minutes)
  $maxAgeMetar = 90*60; // max age for Metar is 90 minutes V2.10


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

// Use overrides from Saratoga template Settings.php if available
//
global $SITE, $Debug;
if (file_exists("Settings.php"))  { include_once("Settings.php"); }
if (isset($SITE['lang']))         { $lang = $SITE['lang']; }
if (isset($SITE['cacheFileDir'])) { $cacheDir = $SITE['cacheFileDir']; }
if (isset($SITE['rmNETID']))      { $rmNETID = $SITE['rmNETID']; }
if (isset($SITE['rmMapUseDefaults'])) {$rmMapUseDefaults = $SITE['rmMapUseDefaults']; }
if (isset($SITE['rmMapZoom']))    { $rmMapZoom = $SITE['rmMapZoom']; }
if (isset($SITE['rmMapCenter']))  { $rmMapCenter = $SITE['rmMapCenter']; }
if (isset($SITE['rmMapUseUnits'])) {$rmMapUseUnits = $SITE['rmMapUseUnits']; }
if (isset($SITE['rmTempUOM']))    { $rmTempUOM = $SITE['rmTempUOM']; }
if (isset($SITE['rmWindUOM']))    { $rmWindUOM = $SITE['rmWindUOM']; }
if (isset($SITE['rmBaroUOM']))    { $rmBaroUOM = $SITE['rmBaroUOM']; }
if (isset($SITE['rmRainUOM']))    { $rmRainUOM = $SITE['rmRainUOM']; }
if (isset($SITE['rmShowFireDanger']))    { $rmShowFireDanger = $SITE['rmShowFireDanger']; }
if (isset($SITE['rmProvider']))   { $rmProvider = $SITE['rmProvider']; }
if (isset($SITE['mapboxAPIkey'])) { $mapboxAPIkey = $SITE['mapboxAPIkey'];}
if (isset($SITE['rmClusterRadius'])) {$rmClusterRadius = $SITE['rmClusterRadius']; }

// end of overrides from Settings.php

if(isset($_REQUEST['net']) and 
   preg_match('|[A-Z0-9]+|',$_REQUEST['net']) and 
   $_REQUEST['net'] !== $rmNETID) {
	   
	$useRMNET = $_REQUEST['net'];
	if (!$rmMapUseDefaults and isset($rmNETID) and $rmNETID !== $useRMNET) {
	  #special case: user selects zoom/center ($rmMapUseDefaults) for $rmNETID network
	  # and the request is for another network via ?net=NETID
	  # then reverse the $rmMapUseDefaults for this access
	  $rmMapUseDefaults = true;
	}
}

if(isset($useRMNET)) { 
  define('M_NETID',$useRMNET); 
  } else { 
// default network to use:
  define('M_NETID',$rmNETID);  // Regional Network ID -- must be one of the defined ones
}
if(!function_exists('langtransstr')) {
	// shim function if not running in Saratoga template set
	function langtransstr($input) { return($input); }
}
if(!function_exists('langtrans')) {
	// shim function if not running in Saratoga template set
	function langtrans($input) { echo $input; return($input); }
}

require_once("mesonet-map-common.php");
// end mesonet-map-settings.php