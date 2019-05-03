<?php
############################################################################
# Generate JavaScript processing for Affiliated Regional Networks Global Google Map
#
# Version 3.00 - 24-Jul-2016 - initial release for Google Maps V3 API
# Version 3.03 - 31-Jul-2016 - added CBI Fire Danger display functions to table, map labels and popups
# Version 3.06 - 14-Jan-2017 - corrected for https:// station URL links
# Version 4.00 - 23-May-2018 - rewrite to use Leaflet/OpenStreetMaps+others for map display
# Version 4.01 - 25-May-2018 - fixed rmProvider selection for default map choice
#
# note: settings for this script should be done in mesonet-map-settings.php, not here.
############################################################################

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

if(!isset($mapboxAPIkey)) {
	$mapboxAPIkey = '--mapbox-API-key--';
}

if(!isset($rmProvider)) {
	$rmProvider = 'Esri_WorldTopoMap';
}

// table of available map tile providers
$mapTileProviders = array(
  'OSM' => array( 
	   'name' => 'Street',
	   'URL' =>'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
		 'attrib' => '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, Points &copy 2012 LINZ',
		 'maxzoom' => 18
		  ),
  'Wikimedia' => array(
	  'name' => 'Street2',
    'URL' =>'https://maps.wikimedia.org/osm-intl/{z}/{x}/{y}.png',
	  'attrib' =>  '<a href="https://wikimediafoundation.org/wiki/Maps_Terms_of_Use">Wikimedia</a>',
	  'maxzoom' =>  18
    ),		
  'Esri_WorldTopoMap' =>  array(
	  'name' => 'Terrain',
    'URL' => 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Topo_Map/MapServer/tile/{z}/{y}/{x}',
	  'attrib' =>  'Tiles &copy; <a href="https://www.esri.com/en-us/home" title="Sources: Esri, DeLorme, NAVTEQ, TomTom, Intermap, iPC, USGS, FAO, NPS, NRCAN, GeoBase, Kadaster NL, Ordnance Survey, Esri Japan, METI, Esri China (Hong Kong), and the GIS User Community">Esri</a>',
	  'maxzoom' =>  18
    ),
	'Terrain' => array(
	   'name' => 'Terrain2',
		 'URL' =>'http://{s}.tile.stamen.com/terrain/{z}/{x}/{y}.jpg',
		 'attrib' => '<a href="https://creativecommons.org/licenses/by/3.0">CC BY 3.0</a> <a href="https://stamen.com">Stamen.com</a> | Data &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors.',
		 'maxzoom' => 14
		  ),
	'NatGeo' => array(
	   'name' => 'NatGeo',
		 'URL' =>'http://server.arcgisonline.com/ArcGIS/rest/services/NatGeo_World_Map/MapServer/tile/{z}/{y}/{x}',
		 'attrib' => 'Tiles &copy; <a href="https://www.esri.com/en-us/home" title="Sources: Esri, DeLorme, NAVTEQ, TomTom, Intermap, iPC, USGS, FAO, NPS, NRCAN, GeoBase, Kadaster NL, Ordnance Survey, Esri Japan, METI, Esri China (Hong Kong), and the GIS User Community">Esri NatGeo</a>',
		 'maxzoom' => 16
		  ),
	'OpenTopo' => array(
	   'name' => 'Topo',
		 'URL' =>'https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png',
		 'attrib' => ' &copy; <a href="https://opentopomap.org/">OpenTopoMap</a> (<a href="https://creativecommons.org/licenses/by-sa/3.0/">CC-BY-SA</a>) | Data &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors.',
		 'maxzoom' => 15
		  ),
	'MapboxTer' => array(
	   'name' => 'Terrain3',
		 'URL' =>'https://api.mapbox.com/styles/v1/mapbox/outdoors-v10/tiles/256/{z}/{x}/{y}?access_token='.
		 $mapboxAPIkey,
		 'attrib' => '&copy; <a href="https://mapbox.com">MapBox.com</a> | Data &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors.',
		 'maxzoom' => 18
		  ),
	'MapboxSat' => array(
	   'name' => 'Satellite',
		 'URL' =>'https://api.mapbox.com/styles/v1/mapbox/satellite-streets-v10/tiles/256/{z}/{x}/{y}?access_token='.
		 $mapboxAPIkey,
		 'attrib' => '&copy; <a href="https://mapbox.com">MapBox.com</a> | Data &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors.',
		 'maxzoom' => 18
		  ),
			
	);
  if(isset($mapTileProviders[$rmProvider]) ) {
		print "<!-- using \$rmProvider = '$rmProvider' as default map tiles. -->\n";
	} else {
		print "<!-- invalid \$rmProvider = '$rmProvider' - using OSM for map tiles instead. -->\n";
		$rmProvider = 'OSM';
 }
 $sTarget = $doLinkTarget?' target="_blank"':'';
 $swxAttrib = ' | Script by <a href="https://saratoga-weather.org/scripts-mesonet-map.php"'.
 $sTarget.'>Saratoga-weather.org</a>';

if(isset($_GET['cluster']) and is_numeric($_GET['cluster'])) {
	$t = $_GET['cluster'];
	if($t >= 5 and $t <= 80) {$rmClusterRadius = $t;}
}

?><!-- mesonet-map-genjs.php included - Version 4.01 - 25-May-2018 -->
<script type="text/javascript">
//<![CDATA[
<?php
$doDebug = (isset($_REQUEST['debug']))?true:false;
print "var langTransLookup = new Object;\n";
RMNET_genTranslate();
print "var doDebug = ";
print $doDebug?'true':'false';
print "; // enable debug\n";
print "var doLinkTarget = ";
print $doLinkTarget?'true':'false';
print "; // generate links with target=\"_blank\"\n";
print "var doRotatingLegends = ";
print $doRotatingLegends?'true':'false';
print "; // generate rotating legends\n\n";
print "var doShowFireDanger = ";
print $rmShowFireDanger?'true':'false';
print "; // Show CBI Fire Danger\n\n";

?>
var condIconsDir = '<?php echo $condIconsDir;?>';
//*
var gmTempUOM = '<?php echo $rmTempUOM; ?>';  // units for Temperature ='C' or ='F';
var gmWindUOM = '<?php echo $rmWindUOM; ?>';  // units for Wind Speed ='mph', ='km/h', ='m/s', ='kts'
var gmBaroUOM = '<?php echo $rmBaroUOM; ?>';  // units for Barometer ='inHg', ='hPa', ='mb'
var gmRainUOM = '<?php echo $rmRainUOM; ?>';  // units for Rain ='in', ='mm'
// global variables
var map = null;
var gmInfoWindow = null;
var gmCurrentInfoWindowMarker = null;
var markerImageRed = null;
var markerImageYellow = null;
var markerImageGreen = null;
var markerImageBlue = null;
var markerImageShadow = null;
var markersArray = [];
var labelsArray = [];
var popupArray = [];
var tooltipOptions = {
	noHide: true,
	permanent: true,
	direction: "center",
	offset: [0,0],
	labelAnchor: [6,22],
	interactive: true
			 };
<?php
	// Generate map options
	$mOpts = array();
	$mList = '';  
	$mFirstMap = '';
	$mSelMap = '';
	$mScheme = $_SERVER['SERVER_PORT']==443?'https':'http';
	foreach ($mapTileProviders as $n => $M ) {
		$name = $M['name'];
		$vname = 'M'.strtolower($name);
		if(empty($mFirstMap)) {$mFirstMap = $vname; }  // default map is first in list
		if(strpos($n,'Mapbox') !== false and 
		   strpos($mapboxAPIkey,'-API-key-') !== false) { 
			 $mList .= "\n".'// skipping Mapbox - '.$name.' since $mapboxAPIkey is not set'."\n\n"; 
			 continue;
		}
		if($mScheme == 'https' and parse_url($M['URL'],PHP_URL_SCHEME) == 'http') {
			$mList .= "\n".'// skipping '.$name.' due to http only map tile link while our page is https'."\n\n";
			continue;
		}
		if($rmProvider == $n) {$mSelMap = $vname;}
		$mList .= 'var '.$vname.' = L.tileLayer(\''.$M['URL'].'\', {
			maxZoom: '.$M['maxzoom'].',
			attribution: \''.$M['attrib'].$swxAttrib.'\'
			});
';
		$mOpts[$name] = $vname;
		
	}
	print "// Map tile providers:\n";
  print $mList;
	print "// end of map tile providers\n\n";
	print "var baseLayers = {\n";
  $mtemp = '';
	foreach ($mOpts as $n => $v) {
		$mtemp .= '  "'.$n.'": '.$v.",\n";
	}
	$mtemp = substr($mtemp,0,strlen($mtemp)-2)."\n";
	print $mtemp;
	print "};	\n";
	if(empty($mSelMap)) {$mSelMap = $mFirstMap;}
	// end Generate map tile options
?>
// --- main code ----------------------
//function RMNET_initialize() {
var map = L.map('RMNETmap', {
		center: new L.latLng(<?php echo $rmMapCenter; ?>), 
		zoom: <?php echo $rmMapZoom; ?>,
		minZoom: 2,
		layers: [<?php echo $mSelMap;?>],
		scrollWheelZoom: false,
		contextmenu: true,
    contextmenuWidth: 140,
	  contextmenuItems: [{
				text: 'Show Cursor LatLng',
				callback: RMNET_showCoordinates
			}, {
				text: 'Show Current Map Settings',
				callback: RMNET_showMapInfo
			}, {
				text: 'Center map here',
				callback: RMNET_centerMap
			}, '-', {
				text: 'Zoom in',
				icon: 'MESO-images/zoom-in.png',
				callback: RMNET_zoomIn
			}, {
				text: 'Zoom out',
				icon: 'MESO-images/zoom-out.png',
				callback: RMNET_zoomOut
			}]
		});

var markers = L.markerClusterGroup( { maxClusterRadius: <?php echo $rmClusterRadius; ?> });

var markerImageRed    = new L.icon({ 
		iconUrl: condIconsDir+"mma_20_red.png",
		iconSize: [12, 20],
		iconAnchor: [6, 20]
    });
var markerImageBlue   = new L.icon({ 
		iconUrl: condIconsDir+"mma_20_blue.png",
		iconSize: [12, 20],
		iconAnchor: [6, 20]
    });
var markerImageGreen  = new L.icon({ 
		iconUrl: condIconsDir+"mma_20_green.png",
		iconSize: [12, 20],
		iconAnchor: [6, 20]
    });

var markerImageYellow = new L.icon({ 
		iconUrl: condIconsDir+"mma_20_yellow.png",
		iconSize: [12, 20],
		iconAnchor: [6, 20]
    });

  RMNET_generateMarkers();
  
  L.control.scale().addTo(map);
  L.control.layers(baseLayers).addTo(map);

// end of map generation script	

  //map.on('idle', RMNET_redraw_content());
  map.on('viewreset', RMNET_updateConditions());
  if(doRotatingLegends) {RMNET_rotate_content();} // start the rotation of the content lables
// end main code
//}
//
function RMNET_generateMarkers() {
// Create the markers based on the data array

//var data = {"markers": [
// {"town":"Saratoga, California",
//  "lat":"37.27471",
//  "long":"-122.02295",
//  "surl":"saratoga-weather.org/",
//  "fcode":"all",
//  "nets":"SWN",
//  "conds":"day_clear.gif,Dry,89 &amp;deg;F,39%,61 &amp;deg;F,NE,1 mph,1,0.00 in,29.86 in,Steady,14:34:54 PDT"},
 

  var i;
  for (i = 0; i < data.markers.length; i++) {
		var lat = parseFloat(data.markers[i].lat);
		var lng = parseFloat(data.markers[i].long);
		var town = data.markers[i].town;
		var stationURL = data.markers[i].surl;
		var rawnets = data.markers[i].nets;
		var nets = RMNET_gen_netlinks(rawnets);
		var fcode = data.markers[i].fcode;
		var features = RMNET_gen_features(fcode);
		var rawconds = data.markers[i].conds;
		var conds = RMNET_gen_conds(rawconds);
		var latLng = L.latLng(lat,lng);
		var title = data.markers[i].town+' ('+data.markers[i].nets+")";
		var useMarkerIcon = markerImageBlue;  // default to WX marker
		if (fcode == 'all') { useMarkerIcon = markerImageRed;     }
		if (fcode == 'lgt') { useMarkerIcon = markerImageYellow;  }
		if (fcode == 'cam') { useMarkerIcon = markerImageGreen;   }
		var tgt = '';
		if(doLinkTarget) {tgt = ' target="_blank"'; }
		
		var popupHtmlTemplate = "<div class=\"RMNETpopup\"><small><a href=\""+stationURL+"\""+tgt+">"+town+"</a><br/>"+features+
			 " Nets: "+nets+"<br clear=\"left\"/><hr/></small>"+
			 "CONDITIONS"+"&nbsp;<br clear=\"left\"/></div>";
	  popupArray.push(popupHtmlTemplate); // save for updates
		
		var rotateHtml = 'B';
		if (doRotatingLegends) {rotateHtml = RMNET_genRotateHtml(rawconds);}

		var marker = new L.marker(latLng,{
			clickable: true,
			draggable: false,
			icon: useMarkerIcon,
			title: title,
		});
		var popupHtml = popupHtmlTemplate;
		popupHtml = popupHtml.replace("CONDITIONS",conds);
	
		marker.bindPopup(popupHtml);

    if (rawconds == "Offline" ) {
      // do nothing
		} else {
		  marker.bindTooltip(rotateHtml,tooltipOptions).openTooltip();
		}
	
    markersArray.push(marker);
		markers.addLayer(marker);
  }
	map.addLayer(markers);

}

// context menu functions
function RMNET_showCoordinates (e) {
	alert("Mouse clicked over "+e.latlng);
}

function RMNET_centerMap (e) {
	map.panTo(e.latlng);
}

function RMNET_zoomIn (e) {
	map.zoomIn();
}

function RMNET_zoomOut (e) {
	map.zoomOut();
}

function RMNET_showMapInfo(ev) {
	var txt = "Use these settings to replicate this map:\n\n$rmMapZoom = "+map.getZoom()+";\n"+
		 "$rmMapCenter = '"+map.getCenter().lat.toFixed(4)+","+map.getCenter().lng.toFixed(4)+"';\n"+
		 "$rmClusterRadius = "+markers.options.maxClusterRadius+"; // in pixels";
		 alert(txt);
}

// generate HTML for features based on fcode (only four options there)
function RMNET_gen_features(fcode) {
  var features = '';

  if (fcode == 'all') {
	 features = "<img src=\""+condIconsDir+"feat_all.jpg\" alt=\"<?php RMNET_lang_stationfeatures('Weather, Lightning, WebCam'); ?>\" title=\"<?php RMNET_lang_stationfeatures('Weather, Lightning, WebCam'); ?>\" align=\"left\" />";
  }
  else if (fcode == 'lgt') {
	 features = "<img src=\""+condIconsDir+"feat_li.jpg\" alt=\"<?php RMNET_lang_stationfeatures('Weather, Lightning'); ?>\" title=\"<?php RMNET_lang_stationfeatures('Weather, Lightning'); ?>\" align=\"left\" />";
  }
  else if (fcode == 'cam') {
	 features = "<img src=\""+condIconsDir+"feat_cam.jpg\" alt=\"<?php RMNET_lang_stationfeatures('Weather, WebCam'); ?>\" title=\"<?php RMNET_lang_stationfeatures('Weather, WebCam'); ?>\" align=\"left\" />";
  }
  else {
	 features = "<img src=\""+condIconsDir+"feat_we.jpg\" alt=\"<?php RMNET_lang_stationfeatures('Weather'); ?>\" title=\"<?php RMNET_lang_stationfeatures('Weather'); ?>\" align=\"left\" />";
  }
  return(features);
}

// generate HTML for network links based on rawnets net membership
function RMNET_gen_netlinks(rawnets) {
  var nets = rawnets.split(',');
  var netHtml = '';
  var tgt = '';
  if(doLinkTarget) {tgt = ' target="_blank"'; }

  // JSON "AKWN":{
  // "name":"Alaskan Weather Network",
  // "url":"http://alaskanweather.net/",
  // "short":"Alaska",
  // "region":"USA",
  // "units":"F,mph,inHg,in,ft"}

  for (var i = 0;i<nets.length;i++) {
	var net = nets[i];
	netHtml += "<a href=\""+data.nets[net]['url']+"\" title=\""+data.nets[net]['name']+"\""+tgt+">"+
	   data.nets[net]['short']+"</a> ";
  }

  return(netHtml);
}

// generate infowindow popup HTML from the rawconds JSON input
function RMNET_gen_conds(rawconds) {
// 'day_partly_cloudy.gif,Partly Cloudy,78 F,67%,66 F,ENE,10 mph,11,0.00 in,29.94 inHg,Steady'
//   0                     1             2    3  4    5   6       7  8       9          10

  var conds = rawconds.split(',');
  if (conds[0] == 'Offline') {
	  return ("<small><?php echo RMNET_NOCOND; ?>.</small>");
  }

  var condsHtml = '<small>';
  var testPattern = /.gif$/;
  if (testPattern.test(conds[0]) && condIconsDir) {
	conds[0] = conds[0].replace(".gif",".png"); // use the .png images
	condsHtml += "<img src=\""+condIconsDir+conds[0]+"\" height=\"25\" width=\"25\" alt=\""+RMNET_langTrans(conds[1])+"\" title=\""+RMNET_langTrans(conds[1])+"\" align=\"left\" /> "+RMNET_langTrans(conds[1])+" <br clear=\"left\"/> ";
  } else {
	condsHtml += "";
  }

	condsHtml += "<?php echo RMNET_TEMP; ?>: <b>"+RMNET_convertTemp(conds[2])+"&deg;"+gmTempUOM+"</b>, <?php echo RMNET_HUM; ?>: <b>"+conds[3]+"</b>,";
  if(conds[4] != '') {
	condsHtml += " <?php echo RMNET_DEWPT; ?>: <b>"+RMNET_convertTemp(conds[4])+"&deg;"+gmTempUOM+"</b>";
  }
  condsHtml += "<br/>";

  condsHtml += "<?php echo RMNET_WIND; ?>: ";
	if(conds[5] == '---') {conds[5] = 'N/A';}
  var windTranslated = RMNET_langTrans(conds[5]);
  var gustUOM = conds[6].split(' ');

  if (conds[5].length < 1 || conds[5] == '--' || conds[5] == 'N/A') {
	condsHtml += "<b>N/A</b>, ";
  } else {
	condsHtml += "<b>"+windTranslated+"</b> <img src=\""+condIconsDir+conds[5]+".gif\" height=\"14\" width=\"14\" alt=\"<?php echo RMNET_WINDFROM; ?> "+windTranslated+"\" title=\"<?php echo RMNET_WINDFROM; ?> "+windTranslated+"\" />";
	condsHtml +=  " <b>"+RMNET_convertWind(conds[6])+" "+gmWindUOM+"</b>, ";
	if(conds[7] != '') {condsHtml +=  "<?php echo RMNET_GUSTWIND; ?>: <b>"+RMNET_convertWind(conds[7]+" "+gustUOM[1])+"</b>, ";}
  }

  condsHtml += "<?php echo RMNET_PRECIPS; ?>: <b>"+RMNET_convertRain(conds[8])+" "+gmRainUOM+"</b><br/>";

  condsHtml += "<?php echo RMNET_BAROB; ?>: <b>"+RMNET_convertBaro(conds[9])+" "+gmBaroUOM+"</b> ("+RMNET_langTrans(conds[10])+")";
  if(doShowFireDanger) {
	  condsHtml += "<br/><?php echo preg_replace('|<[^>]+>|',' ',RMNET_CBI); ?>:"+RMNET_getCBI(conds[2],conds[3]);
  }
  condsHtml += "<br/><?php echo preg_replace('|<[^>]+>|',' ',RMNET_DATAUPDT); ?>: "+conds[11];
  if(doDebug) {
    condsHtml += "<br/><small>Station units<br/>T="+conds[2]+", DP="+conds[4]+", W="+conds[6]+", G="+conds[7]+", R="+conds[8]+", B="+conds[9]+"</small>";
  }
  condsHtml += "</small>";
  return(condsHtml)
}

// generate rotating HTML from the rawconds JSON input
function RMNET_genRotateHtml(rawconds) {
// 'day_partly_cloudy.gif,Partly Cloudy,78 F,67%,66 F,ENE,10 mph,11,0.00 in,29.94 inHg,Steady'
//   0                     1             2    3  4    5   6       7  8       9          10

  var conds = rawconds.split(',');
  if (conds[0] == 'Offline') {
	  return ("N/A");
  }

  var rotateHtml = '';
  
  rotateHtml += '<span class="RMNETcontent0">'+RMNET_convertTemp(conds[2])+'</span>';
  rotateHtml += '<span class="RMNETcontent1">'+RMNET_convertTemp(conds[4])+'</span>';
  rotateHtml += '<span class="RMNETcontent2">'+conds[3]+'</span>';
  rotateHtml += '<span class="RMNETcontent3">'+RMNET_langTrans(conds[5])+' '+RMNET_convertWind(conds[6])+'</span>';
  rotateHtml += '<span class="RMNETcontent4">'+RMNET_convertRain(conds[8])+'</span>';
  rotateHtml += '<span class="RMNETcontent5">'+RMNET_convertBaro(conds[9])+'</span>';
//  rotateHtml += '<span class="RMNETcontent6">'+RMNET_langTrans(conds[10])+'</span>';
  if(doShowFireDanger) {
    rotateHtml += '<span class="RMNETcontent6"><span class="RMNETcbiMap">'+
	RMNET_getCBI(conds[2],conds[3])+'</span></span>';
  }

  return (rotateHtml);
}

// utility functions to handle conversions from JSON data to desired units-of-measure

// convert input temperature to C then to target gmTempUOM value
function RMNET_convertTemp ( inrawtemp ) {
  var p = inrawtemp.split(" ");
  var cpat=/C/i;
  var rawtemp = p[0];
  if(cpat.test(p[1])) { // temperature already in C
	  rawtemp = p[0] * 1.0;
  } else { // convert F to C
	  rawtemp = (p[0] - 32.0) * (100.0/(212.0-32.0));
  }
  // now convert to gmTempUOM value
  if (cpat.test(gmTempUOM)) { // leave as C
	  return (rawtemp * 1.0).toFixed(0);
  } else {  // convert C to F
	  return ((1.8 * rawtemp) + 32.0).toFixed(0);
  }
}

// convert input wind to knots, then to target gmWindUOM value
function RMNET_convertWind  ( inrawwind ) {
  var p = inrawwind.split(" ");
  var rawwind = p[0];
  var cpat=/kts|knots/i;
  if(cpat.test(p[1])) { // wind already in knots
	  rawwind = p[0] * 1.0;
  }
  cpat=/mph/i;
  if(cpat.test(p[1])) { // wind in mph -> knots
	  rawwind = p[0] * 0.868976242;
  }
  cpat=/kmh|km\/h/i;
  if(cpat.test(p[1])) { // wind in kmh -> knots
	  rawwind = p[0] * 0.539956803;
  }
  cpat=/mps|m\/s/i;
  if(cpat.test(p[1])) { // wind in mps -> knots
	  rawwind = p[0] * 1.9438444924406;
  }

  // now convert knots to desired gmWindUOM value
  cpat=/kts|knots/i;
  if(cpat.test(gmWindUOM)) {
    return (rawwind * 1.0).toFixed(0);
  }
  cpat=/mph/i;
  if (cpat.test(gmWindUOM)) { // convert knots to mph
	 return (rawwind * 1.1507794).toFixed(0);
  }
  cpat=/mps|m\/s/i;
  if (cpat.test(gmWindUOM)) { // convert knots to m/s
	return (rawwind * 0.514444444).toFixed(1);
  }
  // convert knots to km/hr
	return (rawwind * 1.852).toFixed(0);
}

// convert input pressure to hPa then to gmBaroUOM value
function RMNET_convertBaro ( inrawpress ) {
  var cpat=/mb|hpa/i;
  var p = inrawpress.split(" ");
  var rawbaro = p[0];
  if(cpat.test(p[1])) { // baro already in mb/hPa
	  rawbaro = p[0] * 1.0;
  } else { // convert inHg to mb/hPa
	  rawbaro = p[0] * 33.86;
  }
  // now convert to target gmBaroUOM value
  if (cpat.test(gmBaroUOM)) {
	 return(rawbaro * 1.0).toFixed(1); // leave in hPa
  } else { // convert hPa to inHg
	 return(rawbaro  / 33.86388158).toFixed(2);
  }
}

// convert input rain to mm then to target gmRainUOM value
function RMNET_convertRain ( inrawrain ) {
  var cpat=/mm/i;
  var p = inrawrain.split(" ");
  var rawrain = p[0];
  if(cpat.test(p[1])) { // rain already in mm
	  rawrain = p[0] * 1.0;
  } else { // convert inches to mm
	  rawrain = p[0] * 25.4;
  }
  // now convert to gmRainUOM value
  if (cpat.test(gmRainUOM)) {  // leave in mm
	 return (rawrain * 1.0).toFixed(1);
  } else { // convert mm to inches
	 return (rawrain * .0393700787).toFixed(2);
  }
}

// regenerate rotating and popup conditions after change of UOM
function RMNET_updateConditions () {
  for (var i=0;i<data.markers.length;i++) {
		var rawconds = data.markers[i].conds;
		var conds = RMNET_gen_conds(rawconds);
		var rotateHtml = RMNET_genRotateHtml(rawconds);
		var popupHtmlTemplate = popupArray[i];
		var marker = markersArray[i];

		var popupHtml = popupHtmlTemplate.replace("CONDITIONS",conds);
		marker.bindPopup(popupHtml);

		if(doRotatingLegends) {
			if (rawconds == "Offline" ) {
				// do nothing
			} else {
				marker.bindTooltip(rotateHtml,tooltipOptions).openTooltip();
			}
		}
  }
  RMNET_redraw_content();
}

// Change Temperature UOM to selection from combo-box
function RMNET_ChangeSelTemp (selValue) {
	gmTempUOM = selValue;
	var element = document.getElementById('curTempUOM');
	if (element) {element.innerHTML = selValue;	}
	var element = document.getElementById('curTempUOM2');
	if (element) {element.innerHTML = selValue;	}
	if(RMNETtimeoutID != null) {clearTimeout(RMNETtimeoutID); }
	var tRun = RMNET_get_run();
	RMNET_set_run(0); // stop rotation
	RMNET_updateConditions();
	RMNET_set_run(tRun); // start rotation if was started
}

// Change Wind UOM to selection from combo-box
function RMNET_ChangeSelWind (selValue) {
	gmWindUOM = selValue;
	var element = document.getElementById('curWindUOM');
	if (element) {element.innerHTML = selValue;	}
	if(RMNETtimeoutID != null) {clearTimeout(RMNETtimeoutID); }
	RMNET_set_run(0); // stop rotation
	RMNET_updateConditions();
	RMNET_set_run(1); // start rotation
}

// Change Rain UOM to selection from combo-box
function RMNET_ChangeSelRain (selValue) {
	gmRainUOM = selValue;
	var element = document.getElementById('curRainUOM');
	if (element) {element.innerHTML = selValue;	}
	if(RMNETtimeoutID != null) {clearTimeout(RMNETtimeoutID); }
	RMNET_set_run(0); // stop rotation
	RMNET_updateConditions();
	RMNET_set_run(1); // start rotation
}

// Change Barometer UOM to selection from combo-box
function RMNET_ChangeSelBaro (selValue) {
	gmBaroUOM = selValue;
	var element = document.getElementById('curBaroUOM');
	if (element) {element.innerHTML = selValue;	}
	if(RMNETtimeoutID != null) {clearTimeout(RMNETtimeoutID); }
	var tRun = RMNET_get_run();
	RMNET_set_run(0); // stop rotation
	RMNET_updateConditions();
	RMNET_set_run(1); // start rotation
}
function RMNET_langTrans(words) {

	var newwords = words;

	if(langTransLookup[words]) { newwords = langTransLookup[words]; }

	return(newwords);

}
function RMNET_getCBI(inrawtemp,inhum) {
  // from SLOWeather.com = calculate fire danger based on temperature and relative humidity
  // Chandler Burning Index
  // javascript conversion by saratoga-weather.org
    
  var p = inrawtemp.split(" ");
  var cpat=/C/i;
  var ctemp = p[0]; // just defining the variable
  var ftemp = p[0]; // just defining the variable
  var inUOM = '';
  if(cpat.test(p[1])) { // temperature already in C
	  ctemp = p[0] * 1.0;
	  ftemp = (1.8 * p[0]) + 32.0;
	  inUOM = 'C';
  } else { // convert F to C
	  ctemp = (p[0] - 32.0) * (100.0/(212.0-32.0));
	  ftemp = p[0] * 1.0;
	  inUOM = 'F';
  }
  
  var hum = inhum.replace('%','');
  
  // Start Index Calcs
  if(inhum < 1 || (ctemp < 1 && inhum < 1)) { return("n/a"); }
  // Chandler Index
  var cbi = (((110 - 1.373 * hum) - 0.54 * (10.20 - ctemp)) * (124 * Math.pow(10,(-0.0142*hum))))/60;
  // CBI = (((110 - 1.373*RH) - 0.54 * (10.20 - T)) * (124 * 10**(-0.0142*RH)))/60

  cbi = cbi.toFixed(1);
  
  var cbitxt = 'n/a';
  var cbiclass = 'RMNETcbi';

  if (cbi > 97.5) {
	  cbitxt = "<?php echo RMNET_CBI_EXTREME; ?>";
	  cbiclass = 'RMNETcbiEX';
  
  } else if (cbi >= 90) {
	  cbitxt = "<?php echo RMNET_CBI_VERYHIGH; ?>";
	  cbiclass = 'RMNETcbiVH';
  
  } else if (cbi >= 75) {
	  cbitxt = "<?php echo RMNET_CBI_HIGH; ?>";
	  cbiclass = 'RMNETcbiH';
  
  } else if (cbi >= 50) {
	  cbitxt = "<?php echo RMNET_CBI_MODERATE; ?>";
	  cbiclass = 'RMNETcbiM';
  
  } else  {
	  cbitxt="<?php echo RMNET_CBI_LOW; ?>";
	  cbiclass = 'RMNETcbiL';
  
  }
  ctemp = ctemp.toFixed(1);
  ftemp = ftemp.toFixed(1);

  var cbiout = '<span class="'+cbiclass+'"'+
	   ' title="CBI=' +cbi + ' @ '+ctemp+'C/'+ftemp+'F, '+hum+'%'+
	   '">&nbsp;' +
	   RMNET_langTrans(cbitxt) + '&nbsp;</span>';
  return(cbiout); // (CBI " . round(cbi,0) . ")");
 

}

// ]]>
</script><!-- mesonet-map-genjs.php end -->
