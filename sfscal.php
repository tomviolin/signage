<?php
if (!file_exists("/tmp/events.ical") || time() - filemtime("/tmp/events.ical") > 60*10) {
	system("wget -qq -O /tmp/events.ical 'http://uwm.edu/freshwater/events/?ical=1' >/dev/null");
}
header("Content-type: text/calendar");
header('Content-Disposition: attachment; filename="school-of-freshwater-sciences-56105e7e859.ics"');
header("Content-length: ".filesize("/tmp/events.ical"));
header("Connection: close");

readfile("/tmp/events.ical");
exit(0);

//readfile("http://uwm.edu/freshwater/events/?ical=1");
