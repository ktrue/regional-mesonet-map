<?php
############################################################################
# A Project of TNET Services, Inc. and Saratoga-Weather.org (Canada/World-ML template set)
############################################################################
#
#   Project:    Sample Included Website Design
#   Module:     sample.php
#   Purpose:    Sample Page
#   Authors:    Kevin W. Reed <kreed@tnet.com>
#               TNET Services, Inc.
#
# 	Copyright:	(c) 1992-2007 Copyright TNET Services, Inc.
############################################################################
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA
#
# Version 3.00 - 24-Jul-2016 - Initial release
# Version 3.07 - 07-Apr-2017 - added https for Google access
# Version 3.11 - 15-Feb-2018 - force Google Map API to 3.31 to fix breakage with 3.32
# Version 4.00 - 23-May-2018 - rewrite to use Leaflet/OpenStreetMaps+others for map display

############################################################################
#	This document uses Tab 4 Settings
############################################################################
require_once("Settings.php");
require_once("common.php");
############################################################################
$TITLE = langtransstr($SITE['organ']) . " - " .langtransstr('Mesonet Map');
$showGizmo = true;  // set to false to exclude the gizmo
if(isset($_REQUEST['net']) and preg_match('|[A-Z0-9]+|',$_REQUEST['net'])) {
	$useGMNET = $_REQUEST['net'];
}

require_once("mesonet-map-settings.php");

include("top.php");
############################################################################
?>
<?php echo "<!-- lang=$lang used - Google Lang=$Lang -->\n"; ?>
<link rel="stylesheet" href="mesonet-map.css"/>
<script type="text/javascript" src="mesonet-map.js"></script>
<?php
$tNet = '';
if(isset($useRMNET)) { $tNet = "?net=$useRMNET"; } else {$tNet = '';}
?>
<script src="mesonet-map-json.php<?php echo $tNet; ?>" type="text/javascript"></script>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
</head>
<body>
<?php
############################################################################
include("header.php");
############################################################################
include("menubar.php");
############################################################################
?>

<div id="main-copy">
  
<h1><?php langtrans(M_NETNAME); ?></h1>  
<p>&nbsp;</p>
<?php
  if(file_exists("mesonet-map-inc.php")) { 
    include_once("mesonet-map-inc.php"); 
  } else {
	print "<p>Sorry. The Regional Mesonet map is not currently available.</p>\n";  
  }
?>
</div><!-- end main-copy -->

<?php
############################################################################
include("footer.php");
############################################################################
# End of Page
############################################################################
?>