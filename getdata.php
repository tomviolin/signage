<?php
error_reporting(0);
$nowarg="";
if (isset($argv[1])) {
	$nowarg = $argv[1];
} elseif (isset($_GET['now'])) {
	$nowarg = $_GET['now'];
}
if ($nowarg != "") {
	if (is_numeric($nowarg)) {
		$now = $nowarg;
	} else {
		$now = strtotime($nowarg);
	}
} else {
	$now = time();
}

$limitevents=5; if (isset($_GET['limitevents'])) $limitevents=$_GET['limitevents'] + 0;


require "ics-parser/class.iCalReader.php";

// establish in-memory SQLite database
$db = new SQLite3(":memory:");

$db->query('create table events (
		dtstart bigint,
		dtend   bigint,
		subject varchar(100),
		body	varchar(1024),
		location varchar(128),
		attachurl varchar(128),
		categories varchar(256),
		source varchar(256),
		url varchar(512)
		);');


function fixicstext($icstext) {
	$fixed = preg_replace('/\\\n\\\n/',"<div class='brtag'></div>",$icstext);
	$fixed = preg_replace('/\\\n/',"<br>",$fixed);
	$fixed = preg_replace('/\\\,/',',',$fixed);
	return ($fixed);
}

function insertEvents($icsFile, $psource='') {
	$source = $icsFile;
	if ($psource != '') {
		$source = $psource;
	}
	global $db;
	try {
		$ical   = @new ICal($icsFile);
	} catch (Exception $e) {
		return;
	}
	//unlink($srcFilename);
	$events = $ical->events();

	$date=0;
	$end=0;
	$title="";
	$body="";
	$location="";
	$attachurl="";
	$categories="";
	$url="";


	$st = $db->prepare('insert into events (dtstart,dtend,subject,body,location,attachurl,categories,source, url) values (?,?,?,?,?,?,?,?,?);');
	$st->bindParam(1, $date, SQLITE3_INTEGER);
	$st->bindParam(2, $end,  SQLITE3_INTEGER);
	$st->bindParam(3, $title, SQLITE3_TEXT);
	$st->bindParam(4, $body, SQLITE3_TEXT);
	$st->bindParam(5, $location, SQLITE3_TEXT);
	$st->bindParam(6, $attachurl, SQLITE3_TEXT);
	$st->bindParam(7, $categories, SQLITE3_TEXT);
	$st->bindParam(8, $source, SQLITE3_TEXT);
	$st->bindParam(9, $url, SQLITE3_TEXT);

	if (is_array($events))
	foreach ($events as $event) {
		//print_r($event);
		$date = strtotime($event['DTSTART']);
		$datepr = date('Y-m-d H:i:s',$date);
		$end  = strtotime($event['DTEND']);
		$endpr = date('Y-m-d H:i:s',$end);
		$title = fixicstext($event['SUMMARY']);
		$body = @fixicstext($event['DESCRIPTION']);
		if (isset($event['LOCATION'])) {
			$location=fixicstext($event['LOCATION']);
		} else {
			$location="";
		}
		$categories=""; if (isset($event['CATEGORIES'])) $categories=$event['CATEGORIES'];
		$attachurl = "";
		foreach ($event as $index => $content) {
			if (substr($index,0,7) == "ATTACH;") {
				$attachurl=$content;
			}
		}
		$url="";
		if (isset($event['URL'])) $url = $event['URL'];
		if ($source == "roar" && $title[0] != '[') {
			$glrfloc = $location;
			if (substr($glrfloc,0,5) == "GLRF ") {
				$glrfloc = substr($glrfloc,5);
			}
			$title = "[$glrfloc] $title";
		}
		$result = $st->execute();
	}
}
insertEvents("http://localhost/signage/sfscal.php", 'http://uwm.edu/freshwater/events/?ical=1');
//insertEvents('/calendars/GLRF_ALL.ics','roar');
insertEvents('https://25livepub.collegenet.com/calendars/uwm-sfs-all-events.ics','roar');
insertEvents('https://calendar.google.com/calendar/ical/omohit5llfij2fnafsadkeihlc%40group.calendar.google.com/private-b83faceb660da1fd7e5793f57e96bdfc/basic.ics');
insertEvents('https://calendar.google.com/calendar/ical/sfs.neeskay%40gmail.com/public/basic.ics');


$lastmidnight = strtotime(date("Y-m-d 00:00:00",$now));

// immediate past room bookings
$justmissed=[];
$query = "select * from events where dtstart > $lastmidnight and dtstart < $now and dtend between $now-30*60 and $now and source like '%roar%' order by dtstart, dtend";
$result = $db->query($query);
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
	$justmissed[]= $row;
}

// current room bookings less than 20 minutes in
$juststartedrooms=[];
$query = "select * from events where dtstart > $lastmidnight and dtstart <= $now and dtstart between $now-20*60 and $now and dtend >= $now and source like '%roar%' order by dtstart,dtend";
$result = $db->query($query);
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
	$juststartedrooms[]= $row;
}

// current room bookings
$currentrooms=[];
$query = "select * from events where dtstart > $lastmidnight and dtstart <= $now and dtstart < $now-20*60 and dtend >= $now and source like '%roar%' order by dtstart,dtend";
$result = $db->query($query);
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
	$currentrooms[]= $row;
}

// immediate future room bookings
$imminentrooms = [];
$query = "select * from events where dtstart between $now and $now+60*15 and source like '%roar%' order by dtstart,dtend";
$result = $db->query($query);
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
	$imminentrooms[]= $row;
}


// today's future room bookings
$roomstoday=[];
$thismidnight = $lastmidnight + 60*60*24;
$query = "select * from events where dtstart > $now+60*15 and dtstart <= $thismidnight and source like '%roar%' order by dtstart,dtend";
$result = $db->query($query);
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
	$roomstoday[] = $row;
}

// today's future room bookings
$roomstomorrow=[];
$thismidnight = $lastmidnight + 60*60*24;
$nextmidnight = $thismidnight + 60*60*24;
$query = "select * from events where dtstart > $thismidnight and dtstart < $nextmidnight and source like '%roar%' order by dtstart,dtend";
$result = $db->query($query);
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
	$roomstomorrow[] = $row;
}


$featured =[];
$query="select * from events where dtstart between $now and $now+24*60*60 and dtend >= $now and (source like '%freshwater/events%' or (source like '%google%' and location != 'scroller')) order by dtstart, dtend limit ($limitevents)+0";
$result = $db->query($query);
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
	$featured[] = $row;
}


$events =[];
$query="select * from events where dtstart > $now+24*60*60 and dtend >= $now and source like '%freshwater/events%' order by dtstart, dtend limit ($limitevents)+0";
$result = $db->query($query);
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
	$events[] = $row;
}

$building_announcements =[];
$query="select * from events where dtstart <= $now and dtend >= $now and source like 'https://calendar.google.com/%' and source not like '%neeskay%' and location != 'scroller' order by dtstart, dtend";
$result = $db->query($query);
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
	$building_announcements[] = $row;
}

$scroller =[];
$query="select * from events where dtstart <= $now and dtend >= $now and source like 'https://calendar.google.com/%' and location = 'scroller' order by dtstart, dtend";
$result = $db->query($query);
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
	$scroller[] = $row;
}



$neeskay =[];
$query="select * from events where dtstart > $now+24*60*60-24*60*60*365 and dtend >= $now and source like '%neeskay%' order by dtstart, dtend limit (2)+0";
$result = $db->query($query);
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
	$neeskay[] = $row;
}


$returnstruct = array(
			"now"		=> $now,
			"justmissed"     => $justmissed,
			"currentrooms"  => $currentrooms,
			"juststartedrooms" => $juststartedrooms,
			"imminentrooms"   => $imminentrooms,
			"roomstoday"    => $roomstoday,
			"roomstomorrow" => $roomstomorrow,
			"featured"	=> $featured,
			"events"	=> $events,
			"announcements" => $building_announcements,
			"scroller"	=> $scroller,
			"neeskay"	=> $neeskay
		);

echo json_encode($returnstruct);


/*


Array
(
    [0] => Array
        (
            [DTSTART] => 20170223T170000
            [DTEND] => 20170223T183000
            [DTSTAMP] => 20170216T213255
            [CREATED] => 20170120T210232Z
            [LAST-MODIFIED] => 20170120T210232Z
            [UID] => 9994-1487869200-1487874600@uwm.edu
            [SUMMARY] => Environmental Science Exchange
            [DESCRIPTION] => Presentation about global climate change by Mark Borucki\, Lecturer in Geosciences.
            [URL] => http://uwm.edu/freshwater/event/environmental-science-exchange-2/
        )

    [1] => Array
        (
            [DTSTART] => 20170228T163000
            [DTEND] => 20170228T173000
            [DTSTAMP] => 20170216T213255
            [CREATED] => 20170109T182233Z
            [LAST-MODIFIED] => 20170113T182006Z
            [UID] => 9940-1488299400-1488303000@uwm.edu
            [SUMMARY] => Lecture: Regulations\, Permits\, and Planning the Building of the Deep Tunnel
            [DESCRIPTION] => Part of the Sewer School Lecture Series\, open to all.\n\nFeatured speakers:\n\nPat Marchese\, retired PE\, Environmental Engineer and former Executive Director for Milwaukee Metropolitan Sewerage District and for Southeastern Wisconsin Watersheds Trust
            [URL] => http://uwm.edu/freshwater/event/regulations-permits-and-planning-the-building-of-the-deep-tunnel/
        )

    [2] => Array
        (
            [DTSTART] => 20170302T120000
            [DTEND] => 20170302T130000
            [DTSTAMP] => 20170216T213255
            [CREATED] => 20170201T190541Z
            [LAST-MODIFIED] => 20170201T190541Z
            [UID] => 10061-1488456000-1488459600@uwm.edu
            [SUMMARY] => School of Freshwater Sciences Colloquium
            [DESCRIPTION] => Chris Suchocki\, Graduate Student\, School of Freshwater Sciences.\n\nCulturing sustainability: Current research in yellow perch aquaculture.
            [URL] => http://uwm.edu/freshwater/event/school-of-freshwater-sciences-colloquium-3/
        )

    [3] => Array
        (
            [DTSTART] => 20170307T163000
            [DTEND] => 20170307T173000
            [DTSTAMP] => 20170216T213255
            [CREATED] => 20170109T182443Z
            [LAST-MODIFIED] => 20170113T182033Z
            [UID] => 9942-1488904200-1488907800@uwm.edu
            [SUMMARY] => Lecture: Water Legislation in Washington DC
            [DESCRIPTION] => Part of the Sewer School Lecture Series\, open to all.\n\nFeatured speaker:\n\nKristina Surfus\, MS\, Manager\, Legislative Affairs\, National Association of Clean Water Agencies; former Knauss Fellow to Senator Tammy Baldwin (Wisconsin)\, and SFS alumnus
            [URL] => http://uwm.edu/freshwater/event/water-legislation-in-washington-dc/
        )

    [4] => Array
        (
            [DTSTART] => 20170310T070000
            [DTEND] => 20170312T170000
            [DTSTAMP] => 20170216T213255
            [CREATED] => 20170126T164248Z
            [LAST-MODIFIED] => 20170126T164308Z
            [UID] => 10023-1489129200-1489338000@uwm.edu
            [SUMMARY] => 2017 Aquaponics Workshop Series "Food from Fresh Water"
            [DESCRIPTION] => 
            [URL] => http://uwm.edu/freshwater/event/2017-aquaponics-workshop-series-food-from-fresh-water/2017-03-10/
            [LOCATION] => 600 E Greenfield Avenue\, Milwaukee\, WI\, 53029\, United States
            [GEO] => 43.0174858;-87.904112
            [X-APPLE-STRUCTURED-LOCATION;VALUE=URI;X-ADDRESS=600 E Greenfield Avenue Milwaukee WI 53029 United States;X-APPLE-RADIUS=500;X-TITLE=600 E Greenfield Avenue] => geo:-87.904112,43.0174858
            [ORGANIZER;CN="Growing%20Power"] => MAILTO:staff@growingpower.org
        )

    [5] => Array
        (
            [DTSTART] => 20170310T153000
            [DTEND] => 20170310T170000
            [DTSTAMP] => 20170216T213255
            [CREATED] => 20170113T184115Z
            [LAST-MODIFIED] => 20170113T184236Z
            [UID] => 9983-1489159800-1489165200@uwm.edu
            [SUMMARY] => Info Session and Tour
            [DESCRIPTION] => Looking for a new career direction or more opportunities in your current job? Interested in pursuing graduate study in a growing field with job opportunities? Learn about our Master’s and PhD programs at an Info Session. Register here.
            [URL] => http://uwm.edu/freshwater/event/info-session-and-tour-2/
            [LOCATION] => 600 E Greenfield Avenue\, Milwaukee\, WI\, 53029\, United States
            [GEO] => 43.0174858;-87.904112
            [X-APPLE-STRUCTURED-LOCATION;VALUE=URI;X-ADDRESS=600 E Greenfield Avenue Milwaukee WI 53029 United States;X-APPLE-RADIUS=500;X-TITLE=600 E Greenfield Avenue] => geo:-87.904112,43.0174858
        )

    [6] => Array
        (
            [DTSTART] => 20170314T090000
            [DTEND] => 20170314T120000
            [DTSTAMP] => 20170216T213255
            [CREATED] => 20160915T161031Z
            [LAST-MODIFIED] => 20160915T161031Z
            [UID] => 9424-1489482000-1489492800@uwm.edu
            [SUMMARY] => Cooperative Institute for Urban Agriculture and Nutrition Board of Directors Meeting
            [DESCRIPTION] => This is the Board of Directors for the Cooperative Institute for Urban Agriculture and Nutrition. They meet the second Tuesday of every month.
            [URL] => http://uwm.edu/freshwater/event/cooperative-institute-for-urban-agriculture-and-nutrition-board-of-directors-meeting/2017-03-14/
        )

    [7] => Array
        (
            [DTSTART] => 20170314T163000
            [DTEND] => 20170314T173000
            [DTSTAMP] => 20170216T213255
            [CREATED] => 20170109T182642Z
            [LAST-MODIFIED] => 20170113T182106Z
            [UID] => 9945-1489509000-1489512600@uwm.edu
            [SUMMARY] => Lecture: Milwaukee Riverkeeper: Monitoring and Mapping Our Water Quality
            [DESCRIPTION] => Part of the Sewer School Lecture Series\, open to all.\n\nFeatured speaker:\n\nCheryl Nenn\, Riverkeeper and Zac Driscol\, Water Quality Specialist
            [URL] => http://uwm.edu/freshwater/event/milwaukee-riverkeeper-monitoring-and-mapping-our-water-quality/
        )

    [8] => Array
        (
            [DTSTART] => 20170330T120000
            [DTEND] => 20170330T130000
            [DTSTAMP] => 20170216T213255
            [CREATED] => 20170201T190655Z
            [LAST-MODIFIED] => 20170201T190655Z
            [UID] => 10063-1490875200-1490878800@uwm.edu
            [SUMMARY] => School of Freshwater Sciences Colloquium
            [DESCRIPTION] => Ben Turschak\, Graduate Student\, School of Freshwater Sciences.\n\nEffects of Ecology and Biogeochemistry on the Stable Isotopes of Nearshore Fishes in Lake Michigan.
            [URL] => http://uwm.edu/freshwater/event/school-of-freshwater-sciences-colloquium-4/
        )

    [9] => Array
        (
            [DTSTART] => 20170402T080000
            [DTEND] => 20170402T170000
            [DTSTAMP] => 20170216T213255
            [CREATED] => 20170112T203915Z
            [LAST-MODIFIED] => 20170112T203915Z
            [UID] => 9959-1491120000-1491152400@uwm.edu
            [SUMMARY] => 2017 Wisconsin Regional ROV Competition
            [DESCRIPTION] => Teams from area schools design underwater robots and compete in challenges that promote technical\, communication\, teamwork\, problem-solving and critical thinking skills. Teams design an ROV—a tethered\, underwater\, unmanned submarine—using CAD\, produce a technical report of all engineering work undertaken during the project\, develop a poster\, present an engineering oral report\, and participate in a real-time\, judged ROV simulation.The winner of the Wisconsin regional will continue to the international Marine Advanced Technology Education (MATE) Center competition and go up against teams from around the world.
            [URL] => http://uwm.edu/freshwater/event/2017-wisconsin-regional-rov-competition/
            [LOCATION] => 600 E Greenfield Avenue\, Milwaukee\, WI\, 53029\, United States
            [GEO] => 43.0174858;-87.904112
            [X-APPLE-STRUCTURED-LOCATION;VALUE=URI;X-ADDRESS=600 E Greenfield Avenue Milwaukee WI 53029 United States;X-APPLE-RADIUS=500;X-TITLE=600 E Greenfield Avenue] => geo:-87.904112,43.0174858
        )

    [10] => Array
        (
            [DTSTART] => 20170404T163000
            [DTEND] => 20170404T173000
            [DTSTAMP] => 20170216T213255
            [CREATED] => 20170109T182825Z
            [LAST-MODIFIED] => 20170113T182135Z
            [UID] => 9947-1491323400-1491327000@uwm.edu
            [SUMMARY] => Lecture: Stormwater Management and Watershed Based Permitting
            [DESCRIPTION] => Part of the Sewer School Lecture Series\, open to all.\n\nFeatured speaker:\n\nBenjamin Benninghoff\, Basin Supervisor\, Wisconsin Department of Natural Resources
            [URL] => http://uwm.edu/freshwater/event/stormwater-management-and-watershed-based-permitting/
        )

    [11] => Array
        (
            [DTSTART] => 20170406T140000
            [DTEND] => 20170406T150000
            [DTSTAMP] => 20170216T213255
            [CREATED] => 20170201T190917Z
            [LAST-MODIFIED] => 20170201T190917Z
            [UID] => 10065-1491487200-1491490800@uwm.edu
            [SUMMARY] => School of Freshwater Sciences Colloquium
            [DESCRIPTION] => Laurie Fowler\, Executive Director for Public Services and External Affairs\, Odum School of Ecology.
            [URL] => http://uwm.edu/freshwater/event/school-of-freshwater-sciences-colloquium-5/
        )

    [12] => Array
        (
            [DTSTART] => 20170407T070000
            [DTEND] => 20170409T170000
            [DTSTAMP] => 20170216T213255
            [CREATED] => 20170126T164248Z
            [LAST-MODIFIED] => 20170126T164310Z
            [UID] => 10024-1491548400-1491757200@uwm.edu
            [SUMMARY] => 2017 Aquaponics Workshop Series "Food from Fresh Water"
            [DESCRIPTION] => 
            [URL] => http://uwm.edu/freshwater/event/2017-aquaponics-workshop-series-food-from-fresh-water/2017-04-07/
            [LOCATION] => 600 E Greenfield Avenue\, Milwaukee\, WI\, 53029\, United States
            [GEO] => 43.0174858;-87.904112
            [X-APPLE-STRUCTURED-LOCATION;VALUE=URI;X-ADDRESS=600 E Greenfield Avenue Milwaukee WI 53029 United States;X-APPLE-RADIUS=500;X-TITLE=600 E Greenfield Avenue] => geo:-87.904112,43.0174858
            [ORGANIZER;CN="Growing%20Power"] => MAILTO:staff@growingpower.org
        )

    [13] => Array
        (
            [DTSTART] => 20170411T090000
            [DTEND] => 20170411T120000
            [DTSTAMP] => 20170216T213255
            [CREATED] => 20160915T161031Z
            [LAST-MODIFIED] => 20160915T161031Z
            [UID] => 9425-1491901200-1491912000@uwm.edu
            [SUMMARY] => Cooperative Institute for Urban Agriculture and Nutrition Board of Directors Meeting
            [DESCRIPTION] => This is the Board of Directors for the Cooperative Institute for Urban Agriculture and Nutrition. They meet the second Tuesday of every month.
            [URL] => http://uwm.edu/freshwater/event/cooperative-institute-for-urban-agriculture-and-nutrition-board-of-directors-meeting/2017-04-11/
        )

    [14] => Array
        (
            [DTSTART] => 20170411T163000
            [DTEND] => 20170411T173000
            [DTSTAMP] => 20170216T213255
            [CREATED] => 20170109T183013Z
            [LAST-MODIFIED] => 20170113T182208Z
            [UID] => 9949-1491928200-1491931800@uwm.edu
            [SUMMARY] => Lecture: Creating TMDLs for Milwaukee’s Waterways
            [DESCRIPTION] => Part of the Sewer School Lecture Series\, open to all.\n\nFeatured speaker:\n\nKim Siemens\, PE\, Water Resource Engineer\, CDM Smith
            [URL] => http://uwm.edu/freshwater/event/creating-tmdls-for-milwaukees-waterways/
        )

    [15] => Array
        (
            [DTSTART] => 20170412T123000
            [DTEND] => 20170412T140000
            [DTSTAMP] => 20170216T213255
            [CREATED] => 20170113T184200Z
            [LAST-MODIFIED] => 20170113T184200Z
            [UID] => 9985-1492000200-1492005600@uwm.edu
            [SUMMARY] => Info Session and Tour
            [DESCRIPTION] => Looking for a new career direction or more opportunities in your current job? Interested in pursuing graduate study in a growing field with job opportunities? Learn about our Master’s and PhD programs at an Info Session. Register here.
            [URL] => http://uwm.edu/freshwater/event/info-session-and-tour-3/
            [LOCATION] => 600 E Greenfield Avenue\, Milwaukee\, WI\, 53029\, United States
            [GEO] => 43.0174858;-87.904112
            [X-APPLE-STRUCTURED-LOCATION;VALUE=URI;X-ADDRESS=600 E Greenfield Avenue Milwaukee WI 53029 United States;X-APPLE-RADIUS=500;X-TITLE=600 E Greenfield Avenue] => geo:-87.904112,43.0174858
        )

    [16] => Array
        (
            [DTSTART] => 20170505T070000
            [DTEND] => 20170507T170000
            [DTSTAMP] => 20170216T213255
            [CREATED] => 20170126T164248Z
            [LAST-MODIFIED] => 20170126T164311Z
            [UID] => 10025-1493967600-1494176400@uwm.edu
            [SUMMARY] => 2017 Aquaponics Workshop Series "Food from Fresh Water"
            [DESCRIPTION] => 
            [URL] => http://uwm.edu/freshwater/event/2017-aquaponics-workshop-series-food-from-fresh-water/2017-05-05/
            [LOCATION] => 600 E Greenfield Avenue\, Milwaukee\, WI\, 53029\, United States
            [GEO] => 43.0174858;-87.904112
            [X-APPLE-STRUCTURED-LOCATION;VALUE=URI;X-ADDRESS=600 E Greenfield Avenue Milwaukee WI 53029 United States;X-APPLE-RADIUS=500;X-TITLE=600 E Greenfield Avenue] => geo:-87.904112,43.0174858
            [ORGANIZER;CN="Growing%20Power"] => MAILTO:staff@growingpower.org
        )

    [17] => Array
        (
            [DTSTART] => 20170509T090000
            [DTEND] => 20170509T120000
            [DTSTAMP] => 20170216T213255
            [CREATED] => 20160915T161031Z
            [LAST-MODIFIED] => 20160915T161031Z
            [UID] => 9426-1494320400-1494331200@uwm.edu
            [SUMMARY] => Cooperative Institute for Urban Agriculture and Nutrition Board of Directors Meeting
            [DESCRIPTION] => This is the Board of Directors for the Cooperative Institute for Urban Agriculture and Nutrition. They meet the second Tuesday of every month.
            [URL] => http://uwm.edu/freshwater/event/cooperative-institute-for-urban-agriculture-and-nutrition-board-of-directors-meeting/2017-05-09/
        )

    [18] => Array
        (
            [DTSTART] => 20170512T153000
            [DTEND] => 20170512T170000
            [DTSTAMP] => 20170216T213255
            [CREATED] => 20170113T184312Z
            [LAST-MODIFIED] => 20170113T184312Z
            [UID] => 9987-1494603000-1494608400@uwm.edu
            [SUMMARY] => Info Session and Tour
            [DESCRIPTION] => Looking for a new career direction or more opportunities in your current job? Interested in pursuing graduate study in a growing field with job opportunities? Learn about our Master’s and PhD programs at an Info Session. Register here.
            [URL] => http://uwm.edu/freshwater/event/info-session-and-tour-4/
            [LOCATION] => 600 E Greenfield Avenue\, Milwaukee\, WI\, 53029\, United States
            [GEO] => 43.0174858;-87.904112
            [X-APPLE-STRUCTURED-LOCATION;VALUE=URI;X-ADDRESS=600 E Greenfield Avenue Milwaukee WI 53029 United States;X-APPLE-RADIUS=500;X-TITLE=600 E Greenfield Avenue] => geo:-87.904112,43.0174858
        )

    [19] => Array
        (
            [DTSTART] => 20170613T090000
            [DTEND] => 20170613T120000
            [DTSTAMP] => 20170216T213255
            [CREATED] => 20160915T161031Z
            [LAST-MODIFIED] => 20160915T161031Z
            [UID] => 9427-1497344400-1497355200@uwm.edu
            [SUMMARY] => Cooperative Institute for Urban Agriculture and Nutrition Board of Directors Meeting
            [DESCRIPTION] => This is the Board of Directors for the Cooperative Institute for Urban Agriculture and Nutrition. They meet the second Tuesday of every month.
            [URL] => http://uwm.edu/freshwater/event/cooperative-institute-for-urban-agriculture-and-nutrition-board-of-directors-meeting/2017-06-13/
        )

    [20] => Array
        (
            [DTSTART] => 20170616T070000
            [DTEND] => 20170618T170000
            [DTSTAMP] => 20170216T213255
            [CREATED] => 20170126T164248Z
            [LAST-MODIFIED] => 20170126T164313Z
            [UID] => 10026-1497596400-1497805200@uwm.edu
            [SUMMARY] => 2017 Aquaponics Workshop Series "Food from Fresh Water"
            [DESCRIPTION] => 
            [URL] => http://uwm.edu/freshwater/event/2017-aquaponics-workshop-series-food-from-fresh-water/2017-06-16/
            [LOCATION] => 600 E Greenfield Avenue\, Milwaukee\, WI\, 53029\, United States
            [GEO] => 43.0174858;-87.904112
            [X-APPLE-STRUCTURED-LOCATION;VALUE=URI;X-ADDRESS=600 E Greenfield Avenue Milwaukee WI 53029 United States;X-APPLE-RADIUS=500;X-TITLE=600 E Greenfield Avenue] => geo:-87.904112,43.0174858
            [ORGANIZER;CN="Growing%20Power"] => MAILTO:staff@growingpower.org
        )

    [21] => Array
        (
            [DTSTART] => 20170711T090000
            [DTEND] => 20170711T120000
            [DTSTAMP] => 20170216T213255
            [CREATED] => 20160915T161031Z
            [LAST-MODIFIED] => 20160915T161031Z
            [UID] => 9428-1499763600-1499774400@uwm.edu
            [SUMMARY] => Cooperative Institute for Urban Agriculture and Nutrition Board of Directors Meeting
            [DESCRIPTION] => This is the Board of Directors for the Cooperative Institute for Urban Agriculture and Nutrition. They meet the second Tuesday of every month.
            [URL] => http://uwm.edu/freshwater/event/cooperative-institute-for-urban-agriculture-and-nutrition-board-of-directors-meeting/2017-07-11/
        )

    [22] => Array
        (
            [DTSTART] => 20170808T090000
            [DTEND] => 20170808T120000
            [DTSTAMP] => 20170216T213255
            [CREATED] => 20160915T161031Z
            [LAST-MODIFIED] => 20160915T161031Z
            [UID] => 9429-1502182800-1502193600@uwm.edu
            [SUMMARY] => Cooperative Institute for Urban Agriculture and Nutrition Board of Directors Meeting
            [DESCRIPTION] => This is the Board of Directors for the Cooperative Institute for Urban Agriculture and Nutrition. They meet the second Tuesday of every month.
            [URL] => http://uwm.edu/freshwater/event/cooperative-institute-for-urban-agriculture-and-nutrition-board-of-directors-meeting/2017-08-08/
        )

    [23] => Array
        (
            [DTSTART] => 20170912T090000
            [DTEND] => 20170912T120000
            [DTSTAMP] => 20170216T213255
            [CREATED] => 20160915T161031Z
            [LAST-MODIFIED] => 20160915T161031Z
            [UID] => 9430-1505206800-1505217600@uwm.edu
            [SUMMARY] => Cooperative Institute for Urban Agriculture and Nutrition Board of Directors Meeting
            [DESCRIPTION] => This is the Board of Directors for the Cooperative Institute for Urban Agriculture and Nutrition. They meet the second Tuesday of every month.
            [URL] => http://uwm.edu/freshwater/event/cooperative-institute-for-urban-agriculture-and-nutrition-board-of-directors-meeting/2017-09-12/
        )

    [24] => Array
        (
            [DTSTART] => 20171010T090000
            [DTEND] => 20171010T120000
            [DTSTAMP] => 20170216T213255
            [CREATED] => 20160915T161031Z
            [LAST-MODIFIED] => 20160915T161127Z
            [UID] => 9419-1507626000-1507636800@uwm.edu
            [SUMMARY] => Cooperative Institute for Urban Agriculture and Nutrition Board of Directors Meeting
            [DESCRIPTION] => This is the Board of Directors for the Cooperative Institute for Urban Agriculture and Nutrition. They meet the second Tuesday of every month.
            [URL] => http://uwm.edu/freshwater/event/cooperative-institute-for-urban-agriculture-and-nutrition-board-of-directors-meeting/2017-10-10/
        )

    [25] => Array
        (
            [DTSTART] => 20171114T090000
            [DTEND] => 20171114T120000
            [DTSTAMP] => 20170216T213255
            [CREATED] => 20160915T161031Z
            [LAST-MODIFIED] => 20160915T161031Z
            [UID] => 9431-1510650000-1510660800@uwm.edu
            [SUMMARY] => Cooperative Institute for Urban Agriculture and Nutrition Board of Directors Meeting
            [DESCRIPTION] => This is the Board of Directors for the Cooperative Institute for Urban Agriculture and Nutrition. They meet the second Tuesday of every month.
            [URL] => http://uwm.edu/freshwater/event/cooperative-institute-for-urban-agriculture-and-nutrition-board-of-directors-meeting/2017-11-14/
        )

    [26] => Array
        (
            [DTSTART] => 20171212T090000
            [DTEND] => 20171212T120000
            [DTSTAMP] => 20170216T213255
            [CREATED] => 20160915T161031Z
            [LAST-MODIFIED] => 20160915T161031Z
            [UID] => 9432-1513069200-1513080000@uwm.edu
            [SUMMARY] => Cooperative Institute for Urban Agriculture and Nutrition Board of Directors Meeting
            [DESCRIPTION] => This is the Board of Directors for the Cooperative Institute for Urban Agriculture and Nutrition. They meet the second Tuesday of every month.
            [URL] => http://uwm.edu/freshwater/event/cooperative-institute-for-urban-agriculture-and-nutrition-board-of-directors-meeting/2017-12-12/
        )

)
*/

?>
