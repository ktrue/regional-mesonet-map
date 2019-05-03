<?php
// Sample PHP Regional Mesonet Map page
// Author: Ken True - 27-Nov-2013  http://saratoga-weather.org/
// Version 3.00 - 24-Jul-2016 - initial release based on Global Map V3.00
// Version 3.07 - 07-Apr-2017 - changed to https for Google access
// Version 3.11 - 15-Feb-2018 - force Google Map API to 3.31 to fix breakage with 3.32
// Version 4.00 - 23-May-2018 - rewrite to use Leaflet/OpenStreetMaps+others for map display

if(isset($_REQUEST['net']) and preg_match('|[A-Z0-9]+|',$_REQUEST['net'])) {
	$useRMNET = $_REQUEST['net'];
}

require_once("mesonet-map-settings.php");

$showGizmo = false; // needed to fake-out lack of template support
header('Content-type: text/html; charset='.RMNET_CHARSET);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo RMNET_CHARSET; ?>" />
<title><?php echo M_NETNAME; ?></title>
<meta http-equiv="Pragma" content="no-cache" />
<meta http-equiv="Cache-Control" content="no-cache" />
<?php echo "<!-- lang=$lang used - Google Lang=$Lang -->\n"; ?>
<link rel="stylesheet" href="mesonet-map.css"/>
<script type="text/javascript" src="mesonet-map.js"></script>
<?php
$tNet = '';
if(isset($useRMNET)) { $tNet = "?net=$useRMNET"; } else {$tNet = '';}
?>
<script src="mesonet-map-json.php<?php echo $tNet; ?>" type="text/javascript"></script>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<style type="text/css">
body {
  color: black;
  background-color: #F3F2EB;
  font-family: verdana, helvetica, arial, sans-serif;
  font-size: 73%;  /* Enables font size scaling in MSIE */
  margin: 0;
  padding: 0;
}

html > body {
  font-size: 9pt;
}

#page {
        margin: 20px 20px;
        color: black;
        background-color: white;
        padding: 0 0 0 2em;
        width: 93%;
        border: 1px solid #959596;
}

</style>
</head>
<body>
<div id="page">
<h1><?php echo M_NETNAME; ?></h1>  
<?php
  if(file_exists("mesonet-map-inc.php")) { 
    include_once("mesonet-map-inc.php"); 
  } else {
	print "<p>Sorry. The Regional Mesonet map is not currently available.</p>\n";  
  }
?>
</div>
</body>
</html>