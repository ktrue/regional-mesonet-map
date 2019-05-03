<?php
############################################################################
# Main processing for Affiliated Regional Networks Global Google Map
#
# Version 3.00 - 24-Jul-2016 - initial release for Google Maps V3 API
# Version 3.01 - 26-Jul-2016 - fixes for override zoom/center for default map
# Version 3.03 - 31-Jul-2016 - added CBI Fire Danger display functions to table, map labels and popups
# Version 4.00 - 23-May-2018 - rewrite to use Leaflet/OpenStreetMaps+others for map display
#
# note: settings for this script should be done in mesonet-map-settings.php, not here.
############################################################################

$RMNETVersion = "Version 4.00 - 23-May-2018";
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
  print "<!-- mesonet-map-inc.php - $RMNETVersion -->\n";
  $doRotatingLegends = true; // =true for rotating legends (much slower display)

global $DebugNetLoad;
require_once("mesonet-map-settings.php");
print $Debug;
$Debug = '';
print $DebugNetLoad;

print "<!-- masterCacheURL='$masterCacheURL' -->\n";
print "<!-- masterConfigURL='$masterConfigURL' -->\n";
print "<!-- NetLookup[".M_NETID."] \n".print_r($NetLookup[M_NETID],true)." -->\n";

// First, generate the embedded JavaScript for the map+pins
if(!$rmMapUseDefaults) {
	print "<!-- using custom ".M_NETID." zoom=$rmMapZoom center=$rmMapCenter -->\n";
} else {
	print "<!-- using default ".M_NETID." zoom=$rmMapZoom center=$rmMapCenter -->\n";
}
?>
    
<div class="tabber" style="width: 99%; margin: 0 auto;"><!-- MAP tab begin -->
  <div class="tabbertab" style="padding: 0;">
    <h2><?php langtrans(M_NETID); ?></h2>
    <div style="width: 99%;">


<div id="RMNETmap-container">
  <div id="RMNETmap"></div>
  <table width="100%" style="border: none">
  <tr>
    <?php if($doRotatingLegends) { ?>
    <td style="width: 180px">
    <form action="#">
      <div id="RMNETcontrols">
        <input type="button" value="<?php echo RMNET_RUN; ?>" name="run" onclick="RMNET_set_run(1);" />
        <input type="button" value="<?php echo RMNET_PAUSE; ?>" name="pause" onclick="RMNET_set_run(0);" />
        <input type="button" value="<?php echo RMNET_STEP; ?>" name="step" onclick="RMNET_step_content();" />
      </div>
    </form>
    <?php } else { ?>
    <td>&nbsp;
    <?php } // end no rotating legends ?>
    </td>
    <?php if($doRotatingLegends) { ?>
    <td style="text-align: center;">
    <div id="RMNETlegend">
      <span class="RMNETcontent0" style="text-align: left;"><?php echo RMNET_TEMPL; ?> [ <span id="curTempUOM"><?php print $rmTempUOM; ?></span>&deg; ]</span>
      <span class="RMNETcontent1" style="text-align: left;"><?php echo RMNET_DEWPT; ?> [ <span id="curTempUOM2"><?php print $rmTempUOM; ?></span>&deg; ]</span>
      <span class="RMNETcontent2" style="text-align: left;"><?php echo RMNET_HUML; ?> [ % ]</span>
      <span class="RMNETcontent3" style="text-align: left;"><?php echo RMNET_WIND; ?> [ <span id="curWindUOM"><?php print $rmWindUOM; ?></span> ]</span>
      <span class="RMNETcontent4" style="text-align: left;"><?php echo RMNET_PRECIPSL; ?> [ <span id="curRainUOM"><?php print $rmRainUOM; ?></span> ]</span>
      <span class="RMNETcontent5" style="text-align: left;"><?php echo RMNET_BAROB; ?> [ <span id="curBaroUOM"><?php print $rmBaroUOM; ?></span> ]</span>
  <!--    <span class="RMNETcontent6" style="text-align: left;"><?php echo RMNET_BAROT; ?></span> -->
      <?php if($rmShowFireDanger) { ?>
      <span class="RMNETcontent6" style="text-align: left;"><?php 
	    echo preg_replace('|<[^>]+>|',' ',RMNET_CBILEGEND); ?></span>
      <?php }// end ShowFireDanger headings ?>
    </div>
    <?php } else { ?>
    <td>&nbsp;
    <?php } // end no rotating legends ?>
    </td>
    <td style="text-align:right;">
    <form action="#">
      <div id="RMNETcontrolsUOM">
      <select id="selTemp" name="selTemp" onchange="RMNET_ChangeSelTemp(this.value);">
<?php
foreach (array('C','F') as $i => $val) {
  if($val == $rmTempUOM) {
    print "        <option value=\"$val\" selected=\"selected\">&deg;$val</option>\n";
  } else {
    print "        <option value=\"$val\">&deg;$val</option>\n";
  }
}
?>
      </select>
      <select id="selWind" name="selWind" onchange="RMNET_ChangeSelWind(this.value);">
<?php
foreach (array('km/h','mph','m/s','kts') as $i => $val) {
  if($val == $rmWindUOM) {
    print "        <option value=\"$val\" selected=\"selected\">$val</option>\n";
  } else {
    print "        <option value=\"$val\">$val</option>\n";
  }
}
?>
      </select>
      <select id="selRain" name="selRain" onchange="RMNET_ChangeSelRain(this.value);">
<?php
foreach (array('mm','in') as $i => $val) {
  if($val == $rmRainUOM) {
    print "        <option value=\"$val\" selected=\"selected\">$val</option>\n";
  } else {
    print "        <option value=\"$val\">$val</option>\n";
  }
}
?>
      </select>
      <select id="selBaro" name="selBaro" onchange="RMNET_ChangeSelBaro(this.value);">
<?php
foreach (array('hPa','inHg','mb') as $i => $val) {
  if($val == $rmBaroUOM) {
    print "        <option value=\"$val\" selected=\"selected\">$val</option>\n";
  } else {
    print "        <option value=\"$val\">$val</option>\n";
  }
}
?>
      </select>
      </div>
    </form>
    </td>
   </tr>
  </table>
  </div>

  <noscript><b>JavaScript must be enabled in order for you to use Google Maps.</b> 
    However, it seems JavaScript is either disabled or not supported by your browser. 
    To view Google Maps, enable JavaScript by changing your browser options, and then 
    try again.
  </noscript>


<p><small>[<img src="./MESO-images/mma_20_red.png" height="20" width="12" alt="<?php echo RMNET_lang_stationfeatures('Weather, Webcam, Lightning'); ?>" style="vertical-align:middle"/>] <?php echo RMNET_lang_stationfeatures('Weather, Lightning, WebCam'); ?>,

[<img src="./MESO-images/mma_20_yellow.png" height="20" width="12" alt="<?php echo RMNET_lang_stationfeatures('Weather, Lightning'); ?>" style="vertical-align:middle"/>] <?php echo RMNET_lang_stationfeatures('Weather, Lightning'); ?>,

[<img src="./MESO-images/mma_20_green.png" height="20" width="12" alt="<?php echo RMNET_lang_stationfeatures('Weather, Webcam'); ?>" style="vertical-align:middle"/>] <?php echo RMNET_lang_stationfeatures('Weather, WebCam'); ?>,

[<img src="./MESO-images/mma_20_blue.png" height="20" width="12" alt="<?php echo RMNET_lang_stationfeatures('Weather'); ?>"  style="vertical-align:middle"/>] <?php echo RMNET_lang_stationfeatures('Weather'); ?></small>&nbsp;&nbsp;
<span style="width: 25px; height: 25px; background-color: rgba(110, 204, 57, 0.6); border-radius: 10px;">&nbsp;&nbsp;&nbsp;&nbsp;</span> <?php langtrans("Cluster - click to expand details"); ?></p>
<?php
include_once("mesonet-map-genjs-inc.php");

if(file_exists("mesonet-map-genhtml-inc.php")) {
  $doIncludeRMNET = true;
  $doPrintRMNET = false;
  include_once("mesonet-map-genhtml-inc.php");

  if(isset($RMNET_CondsDates)) { print $RMNET_CondsDates; }
}
?>
 </div> <!-- end map display area tab -->
 <?php print "<!-- $RMNETVersion; -->\n"; 
 $sTarget = $doLinkTarget?' target="_blank"':'';
 ?> 
 <p><small>Regional mesonet-map script by 
 <a href="https://saratoga-weather.org/scripts-mesonet-map.php"<?php echo $sTarget; ?>>Saratoga-Weather.org</a></small></p>
 </div> <!-- end first tab --> 

<?php
// generate the rest of the tabs 
if(file_exists("mesonet-map-genhtml-inc.php")) {
  if ($tabHeight <> false) {
	$tabHeight	= 'height: '.$tabHeight.'px;';
    } else {
	$tabHeight	= '';
  }

  ?>
  <div class="tabbertab" style="padding: 0; <?php echo $tabHeight; ?>"><!-- begin second tab -->
    <h3><?php echo RMNET_CONDL ?></h3>
    <div style="width: 99%;">
    <?php
      print $Debug;
      print $RMNET_CondsDates;
      print $RMNET_table;
    ?>
    </div><!-- end conditions include -->
  </div><!-- end second tab -->

  <?php 
    if($RMNET_ListHTML <> '') {
		$featLegend = preg_replace('|<[^>]+>|',' ',RMNET_FEAT);
  ?>	  
  <div class="tabbertab" style="padding: 0; <?php echo $tabHeight; ?>"><!-- begin third tab -->
    <h3><?php echo $featLegend ?></h3>
    <div style="width: 99%;">
    <?php print $RMNET_ListHTML; ?>
    </div><!-- end station list include -->
  </div><!-- end third tab -->
  <?php	  
    }
  ?>    
  <div class="tabbertab" style="padding: 0; <?php echo $tabHeight; ?>"><!-- begin fourth tab -->
    <h3><?php langtrans("Regional Mesonets"); ?></h3>
    <div style="width: 99%;">
    <ul>
    <?php
  $StationsIn = 'Stations in';
  $lastGeo = '';
	$sTarget = $doLinkTarget?' target="_blank"':'';
  $langArg = ($Lang <> 'en')?'&amp;lang='.$Lang:'';
  foreach ($Networks as $key => $rec) {
  list($GeoRegion,$NetID) = explode("\t",$key);
  list($NetName,$NetLongName,$NetHomeURL,$GeoAbbrev,$Coverage,$MapWH,$MapOffset,$MesoURL)
    = explode("\t",$rec);
  
  $NetHomeURL = htmlspecialchars($NetHomeURL);
  
  if($lastGeo <> $GeoRegion) {
     if ($lastGeo) { print "    </ul>  </li>\n"; }
     print "  <li><strong>".langtransstr($GeoRegion)."</strong>\n";
     print "    <ul>\n";
     $lastGeo = $GeoRegion;
  }
  print "      <li><a href=\"$NetHomeURL\"$sTarget><strong>" . langtransstr($NetLongName) . 
  "</strong></a> ($NetID)" . " " .
  langtransstr($StationsIn)." ".langtransstr($Coverage)."</li>\n";
  }
  
  print "    </ul>\n  </li>\n</ul><!-- list end -->\n";
      ?>
  <p>Regional Networks created by <a href="https://saratoga-weather.org/"<?php echo $sTarget; ?>>Saratoga-Weather.org</a> along with the Global Afilliated Regional Networks hub site at <a href="http://www.northamericanweather.net/"<?php echo $sTarget; ?>>NorthAmericanWeather.net</a>. 
  [<a href="http://www.northamericanweather.net/about.php"<?php echo $sTarget; ?>>About</a>]</p>
    </div><!-- end Regional Mesonet list include -->
  </div><!-- end fourth tab -->
<?php
} // end display of tabs 2, 3, 4
?>
</div><!-- end tabs display -->
<?php
# ------------- functions ---------------------------------------------------
   

function RMNET_genTranslate() {
// Generate the JavaScript lookups for wind, features, Baro Trend, Conditions from
// the associated [netid]-meso-lang-LL.txt configuration file.	
	$txtdir =  array(
	'N' => 'N',
	'NNE' => 'NNE',
	'NE' => 'NE',
	'ENE' => 'ENE',
	'E' => 'E',
	'ESE' => 'ESE',
	'SE' => 'SE',
	'SSE' => 'SSE',
	'S' => 'S',
	'SSW' => 'SSW',
	'SW' => 'SW',
	'WSW' => 'WSW',
	'W' => 'W',
	'WNW' => 'WNW',
	'NW' => 'NW',
	'NNW' => 'NNW',
	);
	
	foreach ($txtdir as $key) {
		print "  langTransLookup['$key'] = '".RMNET_lang_winddir($key)."';\n";
	
	}
	
	$txtbarot =  array(  
	'Rising' => 'Rising',
	'Falling' => 'Falling',
	'Rising Rapidly' => 'Rising Rapidly',
	'Falling Rapidly' => 'Falling Rapidly',
	'Rising Slowly' => 'Rising Slowly',
	'Falling Slowly' => 'Falling Slowly',
	'Steady' => 'Steady'
	);
	foreach ($txtbarot as $key) {
		print "  langTransLookup['$key'] = '".RMNET_lang_barotrend($key)."';\n";
	
	}
	
	$txtfeat =  array(  
	'Weather, Lightning, WebCam' => 'Weather, WebCam, Lightning',
	'Weather, WebCam, Lightning' => 'Weather, WebCam, Lightning',
	'Weather, WebCam' => 'Weather, WebCam',
	'Weather, Lightning' => 'Weather, Lightning',
	'Weather' => 'Weather'
	);
	foreach ($txtfeat as $key) {
		print "  langTransLookup['$key'] = '".RMNET_lang_stationfeatures($key)."';\n";
	
	}
	$txticon =  array( 
	'Sunny' =>  'Sunny',
	'Clear' =>  'Clear',
	'Cloudy' =>  'Cloudy',
	'Cloudy2' =>  'Cloudy2',
	'Partly Cloudy' =>  'Partly Cloudy',
	'Dry' =>  'Dry',
	'Fog' =>  'Fog',
	'Haze' =>  'Haze',
	'Heavy Rain' =>  'Heavy Rain',
	'Mainly Fine' =>  'Mainly Fine',
	'Mist' => 'Mist',
	'Fog' => 'Fog',
	'Heavy Rain' => 'Heavy Rain',
	'Overcast' => 'Overcast',
	'Rain' => 'Rain',
	'Showers' => 'Showers',
	'Snow' => 'Snow',
	'Thunder' => 'Thunder',
	'Overcast' => 'Overcast',
	'Partly Cloudy' => 'Partly Cloudy',
	'Rain' => 'Rain',
	'Rain2' => 'Rain2',
	'Showers2' => 'Showers2',
	'Sleet' => 'Sleet',
	'Sleet Showers' => 'Sleet Showers',
	'Snow' => 'Snow',
	'Snow Melt' => 'Snow Melt',
	'Snow Showers2' => 'Snow Showers2',
	'Sunny' => 'Sunny',
	'Thunder Showers' => 'Thunder Showers',
	'Thunder Showers2' => 'Thunder Showers2',
	'Thunder Storms' => 'Thunder Storms',
	'Tornado' => 'Tornado',
	'Windy' => 'Windy',
	'Stopped Raining' => 'Stopped Raining',
	'Wind/Rain' => 'Wind/Rain'
	);
	foreach ($txticon as $key) {
		$t = str_replace("'","\'",RMNET_lang_WXconds($key));
		
		print "  langTransLookup['$key'] = '$t';\n";
	
	}

	return;
}

?>
