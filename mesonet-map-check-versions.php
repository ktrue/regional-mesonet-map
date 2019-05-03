<?php
/*
Utility diagnostic script to support the Saratoga-Weather.org mesonet-map.zip scripts.

Author: Ken True - webmaster@saratoga-weather.org

Note: there are no user customizations expected in this utility.  Please replace the
  entire script with a newer version when available.

*/

//Version 3.02 - 28-Jul-2016 - initial release
//Version 3.03 - 31-Jul-2016 - changed template Settings.php inclusion to fix Warning messages
//Version 4.00 - 23-May-2018 - rewrite to use Leaflet/OpenStreetMaps+others for map display
$Version = "mesonet-map-check-versions.php - Version 4.00 - 23-May-2018";

//--self downloader --
if(isset($_REQUEST['sce']) and strtolower($_REQUEST['sce']) == 'view') {
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
error_reporting(E_ALL);
global $SITE,$Debug;

if(file_exists('Settings.php')) { include_once('Settings.php'); }

printHeaders(); 
$doneHeaders = true;
$Debug = ''; 

// ------------------------------------------------------------------
//
// do version checking for key scripts as part of mesonet-map.php system
//
// updates are all based in Pacific time in the distribution .zip file
$ourTZ = 'America/Los_Angeles';
# Set timezone in PHP5/PHP4 manner
  if (!function_exists('date_default_timezone_set')) {
	 putenv("TZ=" . $ourTZ);
	} else {
	 date_default_timezone_set($ourTZ);
   }

$cacheFileDir = './cache/';
if(isset($SITE['cacheFileDir'])) {$cacheFileDir = $SITE['cacheFileDir'];}

$mesonetVersionsFile = 'mesonet-map-version-info.txt';
$mesonetVersionsURL = 'https://saratoga-weather.org/'.$mesonetVersionsFile;  


# fetch/cache template version info file from master (if available)
$TESTURL = $mesonetVersionsURL;
$CACHE = $cacheFileDir.$mesonetVersionsFile;
print "<pre>\n";  

if (!isset($_REQUEST['force']) and file_exists($CACHE) and filemtime($CACHE) + 600 > time()) {  // 1800
  print "..loading $CACHE for version information.\n";
} else {
  print "..fetching recent version information.\n";
  $rawhtml = fetchUrlWithoutHanging($TESTURL,false);
	$Debug = str_replace('<!-- ','',$Debug);
	$Debug = str_replace(' -->','',$Debug);
	print $Debug;
  $RC = '';
  if (preg_match("|^HTTP\/\S+ (.*)\r\n|",$rawhtml,$matches)) {
	  $RC = trim($matches[1]);
  }
  print "RC=$RC, bytes=" . strlen($rawhtml) . "\n";
  $i = strpos($rawhtml,"\r\n\r\n");
  $headers = substr($rawhtml,0,$i-1);
  $content = substr($rawhtml,$i+2);
  $html = explode("\n",$content);  // put HTML area as separate lines
  $age = -1;
  $udate = 'unknown';
  $budate = 0;
  if(preg_match('|\nLast-Modified: (.*)\n|Ui',$headers,$match)) {
	  $udate = trim($match[1]);
	  $budate = strtotime($udate);
	  $age = abs(time() - $budate); // age in seconds
	  print "Script set last updated $udate\n";
  }
	
  if (!preg_match('| 200|',$headers)) {
	print "------------\nHeaders returned:\n\n$headers\n------------\n";
	print "\nSkipped cache write to $CACHE file.\n";
  } elseif ($CACHE <> '') {
	  $fp = fopen($CACHE,'w');
	  if($fp) {
		$write = fputs($fp, $content); 
		fclose($fp);
		print "Wrote ".strlen($content). " bytes to $CACHE successfully.\n";
	  } else {
		print "Error: Unable to write to $CACHE file.\n";
	  }
  } 

} // end fetch new version info from saratoga-weather.org 

# now load up the version info which looks like:
/*
# template-version-info updated 2016-07-28 09:15 PDT by( version-info V1.00 - 05-Aug-2012 )
#Base	File	ModDate	Size	Index	ZipSize	MD5	Version	VDate	VersionDesc
mesonet-map	wxmesonetmap.php	2016-07-24 13:37 PDT	3519	164	3435	665a81126c1facb6afda4d81abf99e14	3.00	2016-07-24	3.00 - 24-Jul-2016 - Initial release
mesonet-map	mesonet-map.php	2016-07-24 13:37 PDT	2176	163	2109	0ed7d1d814479b6c39f9a1263066cabd	3.00	2016-07-24	3.00 - 24-Jul-2016 - initial release based on Global Map V3.00
mesonet-map	mesonet-map.js	2016-07-24 13:37 PDT	96816	162	93797	7a9a67599e291dcb5321519208fd7b0f	3.00	2016-07-24	3.00 - 24-Jul-2016 - update for Google Map API V3 required API key + clustering
mesonet-map	mesonet-map-settings.php	2016-07-27 17:38 PDT	6482	160	6347	1c36c0ddd6025bf93fd2f3d6b51665e5	3.01	2016-07-26	3.01 - 26-Jul-2016 - fixed support for $rmMapUseDefaults=false and more $SITE[] entries
mesonet-map	mesonet-map-lang-sr.txt	2016-07-24 13:37 PDT	6342	158	6146	797589e37337e066ed725a792bbb470a	3.00	2016-07-24	3.00 - 24-Jul-2016');
*/ 
  $MasterVersions = array();
  $nVersions = 0;
  $VFile = file($CACHE);
  if(count($VFile) < 10) {
	  print "Error: $CACHE file is not complete..skipping testing.\n";
	  return;
  }
  foreach ($VFile as $n => $rec) {
	$recin = trim($rec);
	if ($recin and substr($recin,0,1) <> '#') { // got a non comment record
	  list($Base,$File,$ModDate,$Size,$Index,$ZipSize,$FileMD5,$Fversion,$FvDate,$FvDesc) = explode("\t",$recin . "\t\t\t\t\t\t\t\t\t\t");
	  $MasterVersions["$Base\t$File"] = "$ModDate\t$Size\t$FileMD5\t$Fversion\t$FvDate\t$FvDesc";
	  $nVersions++;
	}
  }
print "..loaded $nVersions version descriptors from $CACHE file.\n";

# end of get new version info file
# set of files to do version checking  
$mesonetFiles = array( 

  'Common' => array(
	  'mesonet-map.css',
	  'mesonet-map.js',
	  'mesonet-map.php',
	  'wxmesonetmap.php',
	  'mesonet-map-settings.php',
	  'mesonet-map-common.php',
	  'mesonet-map-genhtml-inc.php',
	  'mesonet-map-genjs-inc.php',
	  'mesonet-map-inc.php',
	  'mesonet-map-json.php',
	  'mesonet-map-lang-ba.txt',
	  'mesonet-map-lang-bg.txt',
	  'mesonet-map-lang-cs.txt',
	  'mesonet-map-lang-ct.txt',
	  'mesonet-map-lang-de.txt',
	  'mesonet-map-lang-dk.txt',
	  'mesonet-map-lang-el.txt',
	  'mesonet-map-lang-en.txt',
	  'mesonet-map-lang-es.txt',
	  'mesonet-map-lang-fi.txt',
	  'mesonet-map-lang-fr.txt',
	  'mesonet-map-lang-hu.txt',
	  'mesonet-map-lang-it.txt',
	  'mesonet-map-lang-nl.txt',
	  'mesonet-map-lang-no.txt',
	  'mesonet-map-lang-pl.txt',
	  'mesonet-map-lang-pt.txt',
	  'mesonet-map-lang-ro.txt',
	  'mesonet-map-lang-se.txt',
	  'mesonet-map-lang-si.txt',
	  'mesonet-map-lang-sk.txt',
	  'mesonet-map-lang-sr.txt',
	  'mesonet-map-README.txt',
	  'mesonet-map-check-versions.php'
	)

   
);
// print "MasterVersions \n".print_r($MasterVersions,true)."\n";
$selectedVersions = array();
$selectedVersionsType = array();

$toCheckFiles = $mesonetFiles['Common'];
$toCheckLegend = 'Common Files';
foreach ($mesonetFiles['Common'] as $key => $val) {
	$selectedVersionsType[$val] = 'Common';
	$selectedVersions["$val"] = $MasterVersions["mesonet-map\t$val"];
}
$updateBasePlugin = '';
print "</pre>\n";

print "<h3>Version information for selected <strong>$toCheckLegend</strong> key files</h3>\n";

print "<table style=\"border: 1px;\" cellpadding=\"2\" cellspacing=\"2\">\n";
print "<tr><th>Script<br/>Origin</th><th>Script<br/>Name</th><th>Installed Script</br>Version Status</th><th>Release Script<br/>Version</th><th>Installed Script<br/>Version</th><th>Installed Script Internal<br/>Version Description</th></tr>\n";
$earliestDate = '9999-99-99';

natcasesort($toCheckFiles);
$idx = 0;
foreach ($toCheckFiles as $n => $checkFile) {
	if ($idx % 5 <> 0) { $TRclass = 'row-even'; } else { $TRclass = 'row-odd'; }
	list($mDate,$vNumber,$vDate,$vInfo,$FileMD5,$fStatus) = chk_file_version($checkFile);
	$instVer = '';
	if($vNumber <> '' and $vDate <> '') {$instVer = "V$vNumber - $vDate"; }
	$distVer = '';
	if(isset($selectedVersions[$checkFile])) { 
	   list($mstModDate,$mstSize,$mstFileMD5,$mstFversion,$mstFvDate,$mstFvDesc) = 
		  explode("\t",$selectedVersions[$checkFile]);
	   $distVer = "V$mstFversion - $mstFvDate";
	}
	$fSource = $selectedVersionsType[$checkFile];
	print "<tr class=\"$TRclass\"><td>$fSource</td><td><strong>$checkFile</strong></td><td>$fStatus</td><td>$distVer</td><td>$instVer</td><td>$vInfo</td></tr>\n";
	$idx++;
}
	
print "</table>\n";	  

   print "<h3>To update your mesonet-map scripts to current script version(s), download a 
   mesonet-map.zip from <a href=\"http://saratoga-weather.org/scripts-mesonet-map.php\">here</a>
   and replace the scripts shown as needing update (after merging in your mods if required) with the corresponding files from the mesonet-map.zip distribution.</h3>\n"; 


// END of main program

// ------------------------------------------------------------------

function printHeaders() {
  global $Version;
  print '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Saratoga-weather.org mesonet-map version checker</title>
<meta http-equiv="Robots" content="noindex,nofollow,noarchive" />
<meta name="author" content="Ken True" />
<meta name="copyright" content="&copy; 2012, Saratoga-Weather.org" />
<meta name="Description" content="Saratoga-weather.org AJAX/PHP mesonet-map version checker." />
<style type="text/css">
.row-odd  {background-color: #96C6F5; }
.row-even {background-color: #EFEFEF; }
.num { 
        float: left; 
        color: gray; 
        font-size: 13px;    
        font-family: monospace; 
        text-align: right; 
        margin-right: 6pt; 
        padding-right: 6pt; 
        border-right: 1px solid gray;
} 

body {margin: 0px; margin-left: 5px;} 
td {    vertical-align: top;
        font-size: 13px;    
        font-family: monospace; 
} 
code {white-space: nowrap;
        font-size: 13px;    
        font-family: monospace; 
} 
</style>
</head>
<body style="background-color:#FFFFFF; font-family:Arial, Helvetica, sans-serif;font-size: 10pt;">
<h3>'.$Version.'</h3>
';
	
}
// ------------------------------------------------------------------

function fetchUrlWithoutHanging($url,$useFopen) {
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
    'Mozilla/5.0 (mesonet-map-check-versions.php - saratoga-weather.org)');

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
				"User-agent: Mozilla/5.0 (mesonet-map-check-versions.php - saratoga-weather.org)\r\n" .
				"Accept: text/html,text/plain\r\n"
	  ),
	  'https'=>array(
	  'method'=>"GET",
	  'protocol_version' => 1.1,
	  'header'=>"Cache-Control: no-cache, must-revalidate\r\n" .
				"Cache-control: max-age=0\r\n" .
				"Connection: close\r\n" .
				"User-agent: Mozilla/5.0 (mesonet-map-check-versions.php - saratoga-weather.org)\r\n" .
				"Accept: text/html,text/plain\r\n"
	  )
	);
	
   $STRcontext = stream_context_create($STRopts);

   $T_start = fetch_microtime();
   $xml = file_get_contents($url,false,$STRcontext);
   $T_close = fetch_microtime();
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

}    // end fetchUrlWithoutHanging
// ------------------------------------------------------------------

function fetch_microtime()
{
   list($usec, $sec) = explode(" ", microtime());
   return ((float)$usec + (float)$sec);
}
   
// ------------------------------------------------------------------
#---------------------------------------------------------  
# load file to string for version checking
#--------------------------------------------------------- 
function chk_file_version($inFile) {
   global $selectedVersions,$earliestDate;
   print "<!-- chk_file_version $inFile -->\n";
   if(!file_exists($inFile)) {
	  return(
	  array('n/a','','',"<strong>$inFile file not found.</strong>",'','<strong>File not installed</strong>')); 
   }
   $mDate = date('Y-m-d H:i T',filemtime($inFile));
   $tContents = file_get_contents($inFile);
   $vInfo = scan_for_version_string($tContents);
	$tContents = preg_replace('|\r|is','',$tContents);
	$FileMD5 = md5($tContents);
print "<!-- contents=".strlen($tContents)." bytes MD5=$FileMD5 vInfo='$vInfo' -->\n"; 
   if(strlen($vInfo) > 120) {$vInfo = '(not specified)'; }
   if(preg_match('!(\d+\.\d+)[^\d]*(\d+-\S{3}-\d{4})!',$vInfo,$matches)) {
	$vNumber = $matches[1];
	$vDate = date('Y-m-d',strtotime($matches[2]));
   } else {
	$vNumber = 'n/a';
	$vDate = 'n/a';
   }
   $fStatus = 'unknown';
   if(isset($selectedVersions[$inFile])) { 
     list($mstModDate,$mstSize,$mstFileMD5,$mstFversion,$mstFvDate,$mstFvDesc) = 
	    explode("\t",$selectedVersions[$inFile]);
	 $MD5matches = ($mstFileMD5 == $FileMD5)?true:false;
	 $VerMatches = ($vNumber <> 'n/a' and $mstFversion <> 'n/a' and 
	    strcmp($vNumber,$mstFversion) === 0)?true:false;

	 if ($MD5matches) { $fStatus = "Current<!-- MD5 matched -->"; }
	 if ($fStatus == 'unknown' and $VerMatches) {$fStatus = 'Current<!-- version matched -->'; }
	 if ($fStatus == 'unknown' and $vNumber <> 'n/a' and $mstFversion <> 'n/a' and 
	    strcmp($vNumber,$mstFversion) < 0) {
		  $fStatus = "<strong>Need update to<br/>V$mstFversion - $mstFvDate</strong>"; 
		  $earliestDate = ($mstFvDate < $earliestDate)?$mstFvDate:$earliestDate;
	 }
	 if ($fStatus == 'unknown' and $vNumber <> 'n/a' and $mstFversion <> 'n/a' and 
	    strcmp($vNumber,$mstFversion) > 0) {
		  $fStatus = "<strong>Installed version is more recent</strong>"; 

	 }
	 if ($fStatus == 'unknown' and $mstFversion <> 'n/a' and $mstFvDate <> 'n/a') {
		  $fStatus = "<strong>Need update to<br/>V$mstFversion - $mstFvDate</strong>";
		  $earliestDate = ($mstFvDate < $earliestDate)?$mstFvDate:$earliestDate;
	 }
  
   }
   return(array($mDate,$vNumber,$vDate,$vInfo,$FileMD5,$fStatus));
}
#---------------------------------------------------------  
# scan for a version string in a PHP/JS/TXT/CSS file
#---------------------------------------------------------  
function scan_for_version_string($input) {

	$vstring = '(not specified)';
	
	preg_match('/\$\S*Version\s+=\s+[\'|"]([^\'|"]+)[\'|"];/Uis',$input,$matches);
	if(isset($matches[1])) {
		$vstring = $matches[1];
//		print "--- 1:found $vstring ---\n";
		return(trim($vstring));
	}
    
	preg_match_all('![\/|#]\s*Version (.*)\n!Uis',$input,$matches);
	
//	print "---2:Matches\n".print_r($matches,true)."\n---\n";
	
	if (isset($matches[1]) and count($matches[1]) > 0) {
		for($i=count($matches[1])-1;$i>=0;$i--) {
           $tstring = $matches[1][$i];		    
		   if(preg_match('|\d+-\S{3}-\d{4}|',$tstring)) {
		     $vstring = $tstring;
//		     print "--- 2:found $vstring ---\n";
		     return(trim($vstring));
		   }
	   }

	}
	
	return($vstring);
	
} // end scan_for_version_string

?>
</body>
</html>