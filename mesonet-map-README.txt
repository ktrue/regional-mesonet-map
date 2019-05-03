// Version 3.00 - 24-Jul-2016 - initial release
// Version 3.02 - 28-Jul-2016 - added mesonet-map-check-versions.php
// Version 3.03 - 31-Jul-2016 - added CBI/Fire Danger displays
// Version 4.00 - 23-May-2018 - rewrite to use Leaflet/OpenStreetMaps+others for map display


Note: see full documentation (and download) at
   http://saratoga-weather.org/scripts-mesonet-map.php

Background:

The first Regional network (Southwestern aka "SWN") was founded by Chris 
Arndt San Luis Obispo, CA with Matt Pace Gold Canyon, AZ and Ken 
Capitola, CA in March, 2006, and by the end of that month, there were 7 
stations in the network, including Las Vegas, NV, Tahoe Vista, CA, El 
Dorado Hills, CA, and Saratoga, CA. 

The SWN started as a private forum where weather enthusiast members 
could meet and discuss topics of mutual interest. One of the topics of 
discussion what how to increase visibility of the member's personal 
weather websites in search engine rankings. The idea was to create a 
mesomap display with links to member websites would be a useful addition 
to our respective websites, and due to the member sites posting links to 
the other member sites, the search engine rankings would be improved. In 
late March, 2006, Matt started generating the first mesomap graphic and 
hand-made CSS/HTML to display rotating conditions on the graphic. Ken 
True (Saratoga, CA) created PHP scripts to automate the generation of 
the manual CSS/HTML. As more members joined the SWN, the original 
mesomap became sluggish as it would need to fetch conditions from each 
member site before displaying the mesomap. To fix the issue, a two-part 
function with a data collector running via cron on the network hub site, 
and a mesomap that would use/cache the collected data to dynamically 
generate the mesomap with rotating conditions for member sites was 
developed. This system became the primary method to collect data/display 
mesomap on the Affiliated Regional Weather Network websites. 

In May, 2008 Jack Ahern (Stillweather.com) asked if the mesomap could be 
offered for the NorthEast and have a regional network set up there. The 
SWN members agreed, and so the Affiliated Regional Weather Network 
concept was born, and implementations began. From the 45 stations in the 
Southwestern Weather Network in May, 2008, the number has grown to 1622
stations in affiliated networks worldwide as of Mon, 23-May-2018 11:07:26 EDT

Package contents:

  mesonet-map-README.txt  (this file)
  mesonet-map.php  (standalone script to display the mesonet map)
  wxmesonetmap.php  (Saratoga template page to display the mesonet map)

  mesonet-map-settings.php (control file for the default map displays)
  
  mesonet-map-check-versions.php (utility to check installed v.s. current versions)
  
  mesonet-map.css  (CSS definitions for map)
  mesonet-map.js    (supporting JavaScripts for map)
  mesonet-map-inc.php (main code script - included by the mesonet-map.php or wxmesonetmap.php)
  
  mesonet-map-common.php (support script - common loading functions)
  mesonet-map-genhtml-inc (support script - generate HTML for conditions table and member features list)
  mesonet-map-genjs-inc (support script - generate main JavaScript for Google Map markers+labels)
  
  mesonet-map-json.php (convert control and conditions files to JSON format for data display in main page)
  
  mesonet-map-lang-[LL].txt (language files [LL] is the ISO 639-1 2-character language
                             abbreviations from country domain)

  MESO-images/*.jpg *.gif *.png  (images for display.  Same as the global-map script set)

The following files are downloaded as needed and cached in the cache/ directory:

  cache/mesonet-maps-networks.txt (regional networks specs 
								   downloaded from northamericanweather.net global site)
  cache/[netID]-stations-cc.txt    (regional network station list
								   downloaded from the [netID] regional hub site)
  
  cache/[netID]-conditions.txt     (regional network conditions
								   downloaded from the [netID] regional hub site)
								   
Setup of the mesonet-map:

1)  OPTIONAL: In order to use the two Mapbox maps, you must register. 
    You can get map ID and ACCESS_TOKEN from:

		https://www.mapbox.com/projects
		
		Standalone use (NOT Saratoga template)
		Edit mesonet-map-settings.php and put the Mapbox API key in:
		
		$mapboxAPIkey = '-replace-this-with-your-API-key-here-';
		
		If you're using wxmesonetmap.php in the Saratoga template, put a line in the site Settings.php
		in a convenient area (likely near the WeatherUnderground spec) saying
		
		$SITE['mapboxAPIkey'] = '-replace-this-with-your-API-key-here-';
	
2) Make any modifications to the settings as required.  The most important ones are to
  pick the default network (by Network ID) and the default language to use:
  
  $rmNETID = 'SWN';  // default Regional Network ID -- must be one of the defined networks
  $lang = 'en';      // default language

  Look in the cache/mesonet-map-networks.txt file.  The first entry in each line (without a #)
  is the [netID] to use for your selected network, or use Appendix I below.

  Use Appendix II below for a current list of languages supported.
  
	Example:  you want the Benelux network with Dutch as the default language
	
	$rmNETID = 'BNLWN';  // default Regional Network ID -- must be one of the defined networks
	$lang = 'nl';      // default language
	
	Example:  you want the Czech network with Czech as the default language
	
	$rmNETID = 'CZWN';  // default Regional Network ID -- must be one of the defined networks
	$lang = 'cs';      // default language

  Save the mesonet-map-settings.php after editing.
  
3) Upload the contents to your site, including the cache/ and MESO-images/ directories.
  If your site already has a MESO-images/ directory from installation of the global-map
  software, you can safely replace it with the contents of this set.
  
Optional: for Saratoga templates, you can add additional translations to the appropriate
language-LL.txt file for each language your site supports.  See the listing of entries
to be added in Appendix III.
  
---------------------------------------------------------------------------------------  
Appendix I

Current (11-May-2018) Regional networks.  The Regional Network ID is in parenthesis after 
the text name of the network.

    Africa
        Namibia Weather Network (NAMWN) Stations in Namibia
    Canada
        Canadian Atlantic Weather Network (CAWN) Stations in E-QC,NB,NS,PE,NL
        Ontario Weather Network (COWN) Stations in ON,W-QC
        Quebec Weather Network (CQWN) Stations in E-ON,QC,NB,NS
        Saskatchewan Weather Network (CSKWN) Stations in SK
        Western Canada Weather Network (WCWN) Stations in BC,AL
    Europe
        Austria Weather Network (ATWN) Stations in Austria
        Benelux Weather Network (BNLWN) Stations in Belgium, Netherlands, Luxembourg
        Bulgarian Weather Network (BGWN) Stations in Bulgaria
        Czech Republic Weather Weather Network (CZWN) Stations in Czech Republic
        European Weather Network (ZEUR) Stations in Europe
        French Weather Network (FRWN) Stations in France
        Germany Weather Network (DEWN) Stations in Germany
        Hellas Meteo Network (GRWN) Stations in Greece
        Iberian Peninsula Weather Network (IPWN) Stations in Spain, Portugal, Andorra
        Poland Weather Network (PLWN) Stations in Poland
        Scottish Weather Network (SCWN) Stations in Scotland
        Slovakia Weather Network (SVKWN) Stations in Slovakia
        Slovenia Weather Network (SIWN) Stations in Slovenia
        United Kingdom Weather Network (UKWN) Stations in England, Wales, Scotland, N.I.
    Pacific
        Australian Weather Network (AUWN) Stations in ACT, NSW, NT, QLD, SA, TAS, VIC, WA
        New Zealand Local Weather Network (NZWN2) Stations in North Island, South Island
    USA
        Alaskan Weather Network (AKWN) Stations in AK
        Mid-Atlantic Weather Network (MAWN) Stations in PA, NJ, WV, VA, DE, MD, DC
        Mid-South Weather Network (MSWN) Stations in TX, OK, AR, LA
        Midwestern Weather Network (MWWN) Stations in MN, WI, MI, IA, IL, IN, OH, MO, KY
        Northeastern Weather Network (NEWN) Stations in PA, NJ, NY,CT, RI, MA, VT, NH, ME
        Northwest Weather Network (NWWN) Stations in WA, OR, ID, MT
        Plains Weather Network (PWN) Stations in OK, KS, ND, NE, SD
        Rocky Mountain Weather Network (RMWN) Stations in WY, CO, NM
        Southeastern Weather Network (SEWN) Stations in TN, NC, SC, MS, AL, GA, FL
        Southwestern Weather Network (SWN) Stations in AZ, CA, HI, NV, UT

---------------------------------------------------------------------------------------  
Appendix II - Languages supported:

  'ba' => 'Bosnian' 
  'bg' => 'Bulgarian'
  'ct' => 'Catalan'
  'cs' => 'Czech'
  'dk' => 'Danish'
  'nl' => 'Dutch'
  'en' => 'English'
  'fi' => 'Finnish'
  'fr' => 'French'
  'de' => 'German'
  'el' => 'Greek'
  'hu' => 'Hungarian'
  'it' => 'Italian'
  'no' => 'Norwegian'
  'pl' => 'Polish'
  'pt' => 'Portugese'
  'ro' => 'Romanian'
  'sr' => 'Serbian'
  'es' => 'Spanish'
  'se' => 'Swedish'
  'si' => 'Slovenian'
  'sk' => 'Slovak'

---------------------------------------------------------------------------------------  
Appendix III - Saratoga Template language-LL.txt entries needed.

Replace the SECOND instance of the string with the equivalent in the selected language.
Remember to use the correct character set; VERY unexpected results will display if the
incompatible character set is used.  Do NOT use UTF-8 as it is incompatible with most
of the translations in the Saratoga template set.

Default: ISO-8859-1

 'ba' => 'ISO-8859-1'
 'bg' => 'ISO-8859-5'
 'cs' => 'ISO-8859-2'
 'ct' => 'ISO-8859-1'
 'de' => 'ISO-8859-1'
 'dk' => 'ISO-8859-1'
 'el' => 'ISO-8859-7'
 'en' => 'ISO-8859-1'
 'es' => 'ISO-8859-1'
 'fi' => 'ISO-8859-1'
 'fr' => 'ISO-8859-1'
 'hu' => 'ISO-8859-2'
 'it' => 'ISO-8859-1'
 'nl' => 'ISO-8859-1'
 'no' => 'ISO-8859-1'
 'pl' => 'ISO-8859-2'
 'pt' => 'ISO-8859-1'
 'ro' => 'ISO-8859-2'
 'se' => 'ISO-8859-1'
 'si' => 'ISO-8859-2'
 'sk' => 'ISO-8859-2'
 'sr' => 'ISO-8859-2'

# additional translations for mesonet-map
langlookup|Mesonet Map|Mesonet Map|
langlookup|Regional Mesonets|Regional Mesonets|
langlookup|Home Site|Home Site|
langlookup|Stations in|Stations in|
langlookup|Africa|Africa|
langlookup|Namibia Weather Network|Namibia Weather Network|
langlookup|Namibia|Namibia|
langlookup|Canada|Canada|
langlookup|Canadian Atlantic Weather Network|Canadian Atlantic Weather Network|
langlookup|E-QC,NB,NS,PE,NL|E-QC,NB,NS,PE,NL|
langlookup|Manitoba Weather Network|Manitoba Weather Network|
langlookup|MB|MB|
langlookup|Ontario Weather Network|Ontario Weather Network|
langlookup|ON,W-QC|ON,W-QC|
langlookup|Quebec Weather Network|Quebec Weather Network|
langlookup|E-ON,QC,NB,NS|E-ON,QC,NB,NS|
langlookup|Saskatchewan Weather Network|Saskatchewan Weather Network|
langlookup|SK|SK|
langlookup|Western Canada Weather Network|Western Canada Weather Network|
langlookup|BC,AL|BC,AL|
langlookup|Europe|Europe|
langlookup|Austria Weather Network|Austria Weather Network|
langlookup|Austria|Austria|
langlookup|Benelux Weather Network|Benelux Weather Network|
langlookup|Belgium, Netherlands, Luxembourg|Belgium, Netherlands, Luxembourg|
langlookup|Bosnia and Herzegovina Weather Network|Bosnia and Herzegovina Weather Network|
langlookup|Bosnia and Herzegovina|Bosnia and Herzegovina|
langlookup|Bulgarian Weather Network|Bulgarian Weather Network|
langlookup|Bulgaria|Bulgaria|
langlookup|Czech Republic Weather Weather Network|Czech Republic Weather Weather Network|
langlookup|Czech Republic|Czech Republic|
langlookup|European Weather Network|European Weather Network|
langlookup|French Weather Network|French Weather Network|
langlookup|France|France|
langlookup|Germany Weather Network|Germany Weather Network|
langlookup|Germany|Germany|
langlookup|Hellas Meteo Network|Hellas Meteo Network|
langlookup|Greece|Greece|
langlookup|Hungarian Weather Network|Hungarian Weather Network|
langlookup|Hungary|Hungary|
langlookup|Iberian Peninsula Weather Network|Iberian Peninsula Weather Network|
langlookup|Spain, Portugal, Andorra|Spain, Portugal, Andorra|
langlookup|Poland Weather Network|Poland Weather Network|
langlookup|Poland|Poland|
langlookup|Romanian Weather Network|Romanian Weather Network|
langlookup|Romania|Romania|
langlookup|Scottish Weather Network|Scottish Weather Network|
langlookup|Scotland|Scotland|
langlookup|Serbian Weather Network|Serbian Weather Network|
langlookup|Serbia, Kosovo, Macedonia|Serbia, Kosovo, Macedonia|
langlookup|Slovakia Weather Network|Slovakia Weather Network|
langlookup|Slovakia|Slovakia|
langlookup|Slovenia Weather Network|Slovenia Weather Network|
langlookup|Slovenia|Slovenia|
langlookup|United Kingdom Weather Network|United Kingdom Weather Network|
langlookup|England, Wales, Scotland, N.I.|England, Wales, Scotland, N.I.|
langlookup|Pacific|Pacific|
langlookup|Australia Weather Network|Australia Weather Network|
langlookup|ACT, NSW, NT, QLD, SA, TAS, VIC, WA|ACT, NSW, NT, QLD, SA, TAS, VIC, WA|
langlookup|New Zealand Local Weather Network|New Zealand Local Weather Network|
langlookup|North Island, South Island|North Island, South Island|
langlookup|South America|South America|
langlookup|Argentina Weather Network|Argentina Weather Network|
langlookup|Argentina|Argentina|
langlookup|USA|USA|
langlookup|Alaskan Weather Network|Alaskan Weather Network|
langlookup|AK|AK|
langlookup|Mid-Atlantic Weather Network|Mid-Atlantic Weather Network|
langlookup|PA, NJ, WV, VA, DE, MD, DC|PA, NJ, WV, VA, DE, MD, DC|
langlookup|Mid-South Weather Network|Mid-South Weather Network|
langlookup|TX, OK, AR, LA|TX, OK, AR, LA|
langlookup|Midwestern Weather Network|Midwestern Weather Network|
langlookup|MN, WI, MI, IA, IL, IN, OH, MO, KY|MN, WI, MI, IA, IL, IN, OH, MO, KY|
langlookup|Northeastern Weather Network|Northeastern Weather Network|
langlookup|PA, NJ, NY,CT, RI, MA, VT, NH, ME|PA, NJ, NY,CT, RI, MA, VT, NH, ME|
langlookup|Northwest Weather Network|Northwest Weather Network|
langlookup|WA, OR, ID, MT|WA, OR, ID, MT|
langlookup|Plains Weather Network|Plains Weather Network|
langlookup|OK, KS, ND, NE, SD|OK, KS, ND, NE, SD|
langlookup|Rocky Mountain Weather Network|Rocky Mountain Weather Network|
langlookup|WY, CO, NM|WY, CO, NM|
langlookup|Southeastern Weather Network|Southeastern Weather Network|
langlookup|TN, NC, SC, MS, AL, GA, FL|TN, NC, SC, MS, AL, GA, FL|
langlookup|Southwestern Weather Network|Southwestern Weather Network|
langlookup|AZ, CA, HI, NV, UT|AZ, CA, HI, NV, UT|
# end of mesonet-map translations

---------------------------------------------------------------------------------------  
Appendix IV - Regional Mesonet Architecture and changes...

The V0.x version of the SWN mesonet map used a map and each station in the network
published a bit of HTML with conditions that were rotated by a common JavaScript.
The system became unmanagable as more stations entered the network, and the conditions
rotated on the map in an unsyncronized manner.  Not very satisfying.

The V1.x version used a PHP script to run on each member website (when the page was viewed)
that did a quick pull of conditions from each member station, then displayed the result
on a dynamically created static map with rotating conditions displayed for each station.
As the network grew to above 20 stations, this proved unworkable as the fetch-time for conditions
from each station was added to the page-load time and times of 40+ seconds were observed
on a good day.  So.. back to the drawing board.

The V2.x version used the map/data display of the 1.x version, but a new 'Regional Hub' website was
created and a cron job run to collect the data from each station and save it in a text file.
The member stations would read that text file (fast) and produce the map/data table quickly now
that the fetch-time had been 'offloaded' to the server.  The member station would only fall-back
to collecting data individually from each site if the 'Regional Hub' site was unavailable, or the data
more than one hour old.

The V2.x version also added in multi-language support for displays.  A separate language translation
mechanism was used so the resulting mesonet map could run in any PHP-based website.

Now V3.x version has some fundamental changes from the prior two versions:

1) The map display is based on Google Maps, JavaScript and the Latitude/Longitude of the member
  station to place the pin on the map.  This is a both good and bad news.  The good news is
  that Regional Networks that adopt the V3.x software do not have to fuss about with manually
  placing a pin on an image to add or remove a station.  The bad news is that without JavaScript,
  no map will be displayed (the V2.x version would display a map and temperature without JavaScript).
  But.. the web seems bent on doing mostly everything using JavaScript, so here we go.
  
2) The code to fetch data directly from member stations has been removed.  The V3.x map relies
  on having the Regional Hub site for availability.  It does cache the configuration and data files
  every 5 minutes and will display what it last received.  If the Regional Hub is unavailable
  for an extended period, the member maps will show stale data until the Regional Hub is working
  again.
  
3) The regional networks definition file is also downloaded routinely, so as networks come/go/change,
  the mesonet-map will adapt.  If an existing network is removed from the Global nets, then an
  error message will be displayed instead of the map, and a new network can be picked from
  the cache/mesonet-map-networks.txt file.  We've only had 3 networks drop out in 8+ years.

4) Yes, I know the code is a bit of a mess.. it has been growing 'organically' for 8 years.
  I did some refactoring with variable names and separation of functions from merging two
  sets of code: the [netID]-mesomap.php and the global-map.php code based.  I figure I've
  been fiddling with this over two years in low priority, so it's time to get it done and
  declare V3.0 released.  I hope you enjoy!
	
Version 4.00 did an even more fundamental change -- the Google maps were replaced with the open-source
Leaflet/OpenStreetMaps script set and the JavaScript to create the map was rewritten and tuned.
The minified Leaflet scripts are now included in mesonet-map.js (so no dependency on a CDN for version tweaks) -- 
Google V3.31->V3.32 API change had broken the V3.x version of the mesonet-map and was mostly
unfixable.  Sigh.  Now with the code included, we're protected from capricious version changes in Leaflet
code.

New features added with V4.00:  
 a right-click context menu for the map that shows useful info for your customization,
 and no rotating legends displayed on 'Offline' stations.  Also, the ability to change the clustering 
 algorithm for better display is included.
 
Note that the mesonet-map-lang-ct.txt and mesonet-map-lang-fi.txt were updated. The remaining language
files are unchanged from version 3.  Also, four images were added to MESO-images/ directory:
  layers.png, layers-2x.png, zoom-in.png and zoom-out.png 
be sure to upload those when you are upgrading from V3.x
  
Ken True - webmaster@saratoga-weather.org - 23-May-2018 
 
