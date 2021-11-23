<?php
if (isset($argv[1])) {
	$timeoffset = strtotime($argv[1]) - time();
} elseif (isset($_GET['now'])) {
	$timeoffset = strtotime($_GET['now']) - time();
} else {
	$timeoffset = 0;
}
//$videoID=file_get_contents("http://waterbase.uwm.edu/lastvideo/lastvidid.txt");

$MAPWIDTH=977;
$MAPHEIGHT=550;
$MAPMARGIN=10;
?>
<!doctype html>
<html>
<head>
<meta http-equiv="Refresh" content="1200">
<meta charset="utf-8">
	<meta content="width=device-width, minimum-scale=0.9, maximum-scale=0.9" name="viewport">

	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="mobile-web-app-capable" content="yes">



	<link rel="stylesheet" href="themes/sfs-signage.min.css" />
	<link rel="stylesheet" href="themes/jquery.mobile.icons.min.css" />
	<link rel="stylesheet" href="http://code.jquery.com/mobile/1.4.5/jquery.mobile.structure-1.4.5.min.css" />
	<script src="http://code.jquery.com/jquery-1.11.1.min.js"></script>
	<script src="http://code.jquery.com/mobile/1.4.5/jquery.mobile-1.4.5.min.js"></script>



<!-- (Start) Add jQuery UI Touch Punch -->
  <script src="jquery.ui.touch-punch.min.js"></script>
<!-- (End) Add jQuery UI Touch Punch -->

<script src="jquery.color-animation.js"></script>

<script src="https://ajax.googleapis.com/ajax/libs/jquerymobile/1.4.5/jquery.mobile.min.js"></script>
<script src="jquery.sprintf.js"></script>

<script src="howler.js"></script>
<script src="jquery.sparkline.js"></script>
<script src="node_modules/chart.js/dist/Chart.bundle.js"></script>
<script src="moment-with-locales.js"></script>
<script src="unslider/dist/js/unslider.js"></script>
<script src="jquery.marquee.min.js"></script>
<style>
	#content {height:96%;}
	#mainhead {
		font-size: 200%;
	}
	#announcements, #sliders, #roomlist, #featured {
		margin-top: 0px;
	}
	#roomlist { opacity: 0.7; }
	#announcements>li {
		padding-top: 0em;
		padding-bottom:0em;
	}
	#announcements>li>h1 {
		margin-top:0.1em;
		margin-bottom:0.1em;
	}
	#neeskay {
		background-color: #9999ff;
		width: 50% !important;
		background-size: 180px 110px;
		background-repeat: no-repeat;
		background-position: 80% 80%;
		xbackground-image: url('https://uwm.edu/freshwater/wp-content/uploads/sites/48/2014/10/neeskay-2.jpg');
	}
	#neeskay li.neeskay {
		background-color: #000066;
	}
	.bottomscroller {
		overflow:hidden;
		width: 1000px;
		color :#FF8888;
		letter-spacing: 0.1em;
	}
	#mainfooter {
		display:none;
	}
	#mainfooter>h1 {
		margin-left:0;
		margin-right:0;
		padding-top:0.1em;
		padding-bottom:0;
		font-size: 150%;
	}
	div.ui-content {
	}
	div.desc div.brtag {
		height: 0.3em;
	}
	.ui-listview>.ui-li-static.ui-li-has-icon {
		padding-left: 1.8em;
	}
	.ui-listview>.ui-li-has-icon>img:first-child {
		max-height: 1.3em;
		left: .5em;
		top: .6em;
	}
	.ui-header h1.ui-title {
		margin-right:5%;
		margin-left: 5%;
		padding-top: 0.2em;
		padding-bottom: 0.2em;
	}
#page { top:0;bottom:0;left:0;right:0;margin:0;paddng:0;}
	
#rotator ul {
	width:<?= $MAPWIDTH+$MAPMARGIN*2 ?>px;
}

#rotator li.imgrotator {
height:<?= $MAPHEIGHT+$MAPMARGIN*2 ?>px; 
}

#rotator li.imgrotator img {
	position: absolute;
	top:0;
	left:0;
	max-width: <?= $MAPWIDTH ?>px !important;
	max-height:<?= $MAPHEIGHT ?>px !important;
}
#rotator .static li {
	white-space: inherit;
}
#room_maps .ui-listview li.imgrotator img {
margin:  <?= $MAPMARGIN ?>px;
}
</style>
<script>
var roomMaps=null;
var latestRoomData=null;
var needShort = false;


function htmlDecode(input){
  var e = document.createElement('div');
  e.innerHTML = input;
  return e.childNodes[0].nodeValue;
}

function isNumeric(x){
	var xx=$.trim(x);
	if (xx != "" && isFinite(xx)) return true;
	return false;
}

function outputRooms(roomarray, sectionName, sectionIcon,sectionColor,sectionFontColor) {
	html='';
	if (roomarray.length>0) {
		html += '<li data-role="list-divider" style="background: '+sectionColor+'; color:'+sectionFontColor+'; font-size:120%">'+sectionName+'</li>\n';
		for (i = 0; i < roomarray.length; ++i) {
			x = roomarray[i];
			startdt = moment(x.dtstart*1000).format('h:mm');
			startap = moment(x.dtstart*1000).format('a');
			enddt =   moment(x.dtend*1000).format('h:mm');
			endap =   moment(x.dtend*1000).format('a');
			if (startap == endap) startap = '';
			html += '<li><img src="'+sectionIcon+'" class="ui-li-icon" height=24><div style="display: inline-block; width: 7.5em; text-align:center;color: #887700">' + 
				startdt + startap + "-"+enddt+endap+'</div> '+x.subject+'</li>\n';
		}
	}
	return (html);
}


function outputMap(room) {
	roomid = room.location.substr(5);
	if (roomMaps[roomid] == undefined) {
		return('');
	}
	sectionIcon = '';
	mapml = '<div class="rotating map" style="opacity:0; position:absolute; top:0; left:0;"><ul data-role="listview" data-inset="true" style="width:<?= $MAPWIDTH+$MAPMARGIN*2 ?>px; float:left; margin-right: 0.5%; background: white">\n';
	mapml += '<li data-role="list-divider" data-theme="b" style="font-size: 200%"><img src="'+sectionIcon+'" class="ui-li-icon" height=24><div style="display: inline-block; width: 8.4em; text-align:center;color: #FFDD00">' + moment(room.dtstart*1000).format('h:mmA-') + moment(room.dtend*1000).format('h:mmA')+'</div> '+room.subject+'</li>\n';
	mapml += '<li class="imgrotator">\n';
	for (i = 0; i < roomMaps[roomid].length; ++i) {
		mapml += '  <img src="maps/'+roomMaps[roomid][i]+'" width="<?= $MAPWIDTH ?>" height="<?= $MAPHEIGHT ?>" style="position:absolute; top:0; left:0;">\n';
	}
	mapml += "</li></ul></div>\n";
	//console.log(mapml);
	return mapml;
}


function outputFloor(floorpic, name) {
	mapml = '<div class="rotating optional" style="opacity:0; position:absolute; top:0; left:0;"><ul data-role="listview" data-inset="true" class="maps" style="width:<?= $MAPWIDTH+$MAPMARGIN*2 ?>px; float:left; margin-right: 0.5%;">\n'
	      + '<li data-role="list-divider" data-theme="b" style="font-size:120%">'+name+'</li>\n<li class="imgrotator">\n';
	mapml += '  <img src="maps/'+floorpic+'" width="<?= $MAPWIDTH ?>" height="<?= $MAPHEIGHT ?>">\n';
	mapml += "</li></ul></div>\n";
	//console.log(mapml);
	return mapml;
}




var timeOffset = <?=$timeoffset?>;

var numevents=2;
var marqueed = false;
var marqueetext = "";
var bgidx=1;

function flipem() {
	$('#bg'+bgidx).css('z-index', bgidx);
	$('#bg'+(1-bgidx)).css('z-index', 1-bgidx);
	bgidx=1-bgidx;
}

function updaterooms(everything, nextMove) {
	numevents=8;
	var thisNow = new Date().getTime()/1000 + timeOffset;
	//$('#bg'+(1-bgidx)).css('background-image','url(http://www.glwi.freshwater.uwm.edu/~tomh/iphonepics/currentlink.jpg?time='+thisNow+')');
	$('#clock').html(moment(thisNow*1000).format('MMMM D Y h:mm:ss A'));
	if (!everything) return;
	$.getJSON("getdata.php?now="+thisNow+"&limitevents="+numevents, function(data) {
		latestRoomData = data;
		// build room lists
		var rlistml = "";
		rlistml += outputRooms(data.imminentrooms, 'Classes/Meetings Starting Soon','images/doorflash.gif', '#ddddff','#000000');
		rlistml += outputRooms(data.juststartedrooms, 'Classes/Meetings Just Started','images/doorclosedflash.gif','#ddddff','#000000');
		rlistml += outputRooms(data.currentrooms, 'Classes/Meetings In Progress', 'images/doorsclosed.png', '#ddddff','#000000');
		rlistml += outputRooms(data.roomstoday, 'Classes/Meetings Coming Up Today', 'images/doorsopen.png', '#ddddff','#000000');
		rlistml += outputRooms(data.roomstomorrow, 'Classes/Meetings Tomorrow, '+moment((thisNow+60*60*24)*1000).format('MMMM D Y'), 'images/doorsclosed.png', '#ddddff','#000000');

		// maps 
		mapml = "";
<?php if (isset($_GET['maps'])) { ?>
		$.each(data.juststartedrooms, function(index) {
			mapml += outputMap(this);
		});
		$.each(data.imminentrooms, function(index) {
			mapml += outputMap(this);
		});



		if (mapml != ""){
			needShort = true;
		} else {
			needShort = false;
			// floors
			mapml += outputFloor("floor1.png", "SFS Floor 1 Map");
			mapml += outputFloor("floor3.png", "SFS Floor 3 Map");
		}
		$('#room_maps').html(mapml);
		$("#room_maps ul").listview();
<?php	} ?>



		featuredml='';
		for (var i = 0; i < data.featured.length; ++i) {
			x=data.featured[i];
			console.log(x.body);
			p=x.body.indexOf('<br>');
			if (p>0) {
				x.body=x.body.substring(0,p);
			}
			var datemonth = moment(x.dtstart*1000).format('MMM');
			var dateday   = moment(x.dtstart*1000).format('D');
			var dateyear = moment(x.dtstart*1000).format('YYYY');
			var datedow = moment(x.dtstart*1000).format('dddd');
			var datetime = moment(x.dtstart*1000).format('h:mm A');
			var dateend = moment(x.dtend*1000).format('h:mm A');
			if (!x.url || x.url=='') x.url="http://uwm.edu/freshwater";
			featuredml += "<li style='border-color:black;'>\n";
			featuredml += 
				"<h1 style='margin-bottom: -0.1em; margin-top:0.2em'>" +(x.subject) + "</h1>" +
				"<div class='location' style='margin-bottom: 0.3em;'>"+x.location+"</div>"+
				"<div class='desc'>" + (x.body) + "</div>" +
				"<div class='datebox'><div class='thedate'><b><font style='font-size: 1.1em'>"+datedow+"<br>"+datemonth+" "+dateday+"</font></b><br><b>"+datetime+"<br>-"+dateend+"</b></div></div>" +
				"<div class='qrbox'><img src='phpqrcode/makeone.php?url="+escape(x.url)+"&size=4'></div>" +
				"</li>";
		}
		if (data.featured.length >0 ) {
			if (data.featured.length > 1) plural="s"; else plural="";
			featuredml='<li data-role="list-divider" data-theme="b">Featured Event'+plural+'</li>'+featuredml;
		}


		neeskayml='';
		for (var i = 0; i < data.neeskay.length; ++i) {
			x=data.neeskay[i];
			console.log(x.body);
			p=x.body.indexOf('<br>');
			if (p>0) {
				x.body=x.body.substring(0,p);
			}
			var datemonth = moment(x.dtstart*1000).format('MMM');
			var dateday   = moment(x.dtstart*1000).format('D');
			var dateyear = moment(x.dtstart*1000).format('YYYY');
			var datedow = moment(x.dtstart*1000).format('dddd');
			var datetime = moment(x.dtstart*1000).format('h:mm A');
			var dateend = moment(x.dtend*1000).format('h:mm A');
			if (!x.url || x.url=='') x.url="http://uwm.edu/freshwater";
			neeskayml += "<li style='border-color:black;'>\n";
			neeskayml += 
				"<h1 style='margin-bottom: -0.1em; margin-top:0.2em'>" +(x.subject) + "</h1>" +
				"<div class='location' style='margin-bottom: 0.3em;'>"+x.location+"</div>"+
				"<div class='desc'>" + (x.body) + "</div>" +
				"<div class='datebox'><div class='thedate'><b><font style='font-size: 1.1em'>"+datedow+"<br>"+datemonth+" "+dateday+"</font></b><br><b>"+datetime+"<br>-"+dateend+"</b></div></div>" +
				"<div class='qrbox'><img src='phpqrcode/makeone.php?url="+escape(x.url)+"&size=4'></div>" +
				"</li>";
		}
		if (data.neeskay.length >0 ) {
			if (data.neeskay.length > 1) plural="s"; else plural="";
			neeskayml='<li data-role="list-divider" data-theme="b" class="neeskay">Neeskay Cruise'+plural+'</li>'+neeskayml;
		}

		eventml='<li data-role="list-divider" data-theme="b" style="font-size:150%">Upcoming Events and Announcements</li>';
		eventml += "<li style='border-color:black;background-color:#ffbd00; display:flex;' data-theme='b'>\n";
		eventml += "<span style='font-size:200%;color:#000000; background-color: #ffbd00;'>Visit our <a href=\"https://uwm.edu/coronavirus/\">COVID-19 website</a> for information about UWM’s response to the pandemic.</span><br><img width=75 src=\"uwm_corona.png\"></li>";
		for (var i = 0; i < data.events.length; ++i) {
			x=data.events[i];
			var datemonth = moment(x.dtstart*1000).format('MMM');
			var dateday   = moment(x.dtstart*1000).format('D');
			var dateyear = moment(x.dtstart*1000).format('YYYY');
			var datedow = moment(x.dtstart*1000).format('dddd');
			var datetime = moment(x.dtstart*1000).format('h:mm A');
			var dateend = moment(x.dtend*1000).format('h:mm A');
			console.log(x.body);
			p=x.body.indexOf('<br>');
			if (p>0) {
				x.body=x.body.substring(0,p);
			}
			eventml += "<li style='border-color:black;'>\n";
			eventml += 
				"<h1 style='margin-bottom: -0.1em; margin-top:0.2em'>" +(x.subject) + "</h1>" +
				"<div class='location' style='margin-bottom: 0.3em;'>"+x.location+"</div>"+
				"<div class='desc'>" + (x.body) + "</div>" +
				"<div class='datebox'><div class='thedate'><b><font style='font-size: 1.1em'>"+datedow+"<br>"+datemonth+" "+dateday+"</font></b><br><b>"+datetime+"<br>-"+dateend+"</b></div></div>" +
				"<div class='qrbox'><img src='phpqrcode/makeone.php?url="+escape(x.url)+"&size=3'></div>" +
				"</li>";
		}

		var bldg = data.announcements;
		var bldghtml = "";
		if (bldg.length >= 1) {
			//bldghtml = '<li data-role="list-divider" data-theme="b">General SFS Announcements</li>';
			bldghtml += '<li><h1>'+bldg[0].subject+'</h1><div style="desc">'+bldg[0].body+'</div></li>';
			$('#announcements').html(bldghtml);
			$('#announcements').show();
		} else {
			$('#announcements').hide();
		}

		var scroller = data.scroller;
		var scrollerhtml = "";
		if (scroller.length >= 1) {
			scrollerhtml += scroller[0].subject;

			// no marquee => marquee
			// marquee => marquee with change
			// marquee => marquee without change
			// marquee => no marquee
			// no marquee => no marquee
			if (marqueed) {   // marquee active
				if (scrollerhtml.localeCompare(marqueetext) != 0) {
					// marquee has changed: must destroy and recreate
					$('.bottomscroller').marquee('destroy');
					$('.bottomscroller').html(scrollerhtml);
					$('.bottomscroller').marquee({duration: 10000});
					marqueetext = scrollerhtml;
				} else {
					// marquee has not changed: do nothing
				}
			} else {
				// no marquee: must create
				$('#mainfooter').show();
				$('.bottomscroller').html(scrollerhtml);
				$('.bottomscroller').css('width', $('#mainfooter').width());
				$('.bottomscroller').marquee({duration: 10000});
				marqueed = true;
				marqueetext = scrollerhtml;
			}
		} else {
			if (marqueed) {
				$('.bottomscroller').marquee('destroy');
				$('#mainfooter').hide();
				marqueed = false;
				marqueetext = "";
			}
		}

		// leave actual screen updates to the end
		$('#announcements').listview('refresh');
		$('#roomlist').html(rlistml);
		$('#roomlist').listview('refresh');
		if (featuredml=='') {
			$('#featured').css({ display: "none"});
		} else {
			$('#featured').css({ display: "block"});
			$('#featured').html(featuredml);
			$('#featured').listview('refresh');
		}
		$('#sliders').html(eventml);
		$('#sliders').listview('refresh');
		$('#neeskay').html(neeskayml);
		$('#neeskay').listview('refresh');

		// TRIM based on screen size
		x=$('#sliders');
		y=x;
		/* -- tracing partent heights -- might come in handy again
		console.log("sliders:");
		console.log($(y).height()+": "+$(y).attr('id'))
		y=y.parent();
		console.log($(y).height()+": "+$(y).attr('id'))
		y=y.parent();
		console.log($(y).height()+": "+$(y).attr('id'))
		y=y.parent();
		console.log($(y).height()+": "+$(y).attr('id'))
		y=y.parent();
		console.log($(y).height()+": "+$(y).attr('id'))
		*/
		if (x && x != undefined || x.parent() != undefined){
			while(true) {
				m=x.parent().height();
				n=x.parent().parent().parent().height();
				if (m>n) {	
					$('#sliders li:last-child').remove();
					$('#sliders').listview('refresh');
				} else {
					break;
				}
			}
		}
		x=$('#roomlist');
		while(true) {
			m=	x.parent().parent().parent().position().top +
				x.parent().parent().position().top +
				x.parent().position().top +
				x.position().top +
				x.height();
			n=x.parent().parent().parent().height();
			if (m>n) {	
				$('#roomlist li:last-child').remove();
				$('#roomlist').listview('refresh');
			} else {
				break;
			}
		}
		if (nextMove) {
			window.setTimeout(nextMove,200);
		}
		$("ul.listview").listview("refresh");

		$('div.ui-loader').hide();

	});
}

var rotateSound;

var thisRotator = 1;

function initRotator() {
	thisRotator = 1;
	rotators = $("#rotator .rotating").css("opacity","0.0");
	$($("#rotator .rotating")[1]).css("opacity","1.0");
	//$.each(rotators, function(i) {
		//if (i == 0) {
		//	$(this).css({opacity: 1});
		//} else {
		//	$(this).css({opacity: 0});
		//}
	//	$(this).css({"z-index": i*100+1000});
	//});
	window.setInterval("rotateNext()", 60*1000); // every 1 minutes
}

function rotateNext() {
	updaterooms(true);
	return;
	rotators = $("#rotator .rotating");
	newRotator = (thisRotator + 1) % (rotators.length);
	if (needShort) {
		while ($(rotators[newRotator]).hasClass("optional")) {
			newRotator = (newRotator + 1) % (rotators.length);
		}
	}

	if (needShort && $(rotators[newRotator]).hasClass("map")) rotateSound.play();
	if ($(rotators[thisRotator]).hasClass("map") &&
	    $(rotators[newRotator]).hasClass("map") ) {
		$(rotators[thisRotator]).animate({opacity: 1},400);
	}

	if (thisRotator > -1) {
		$(rotators[thisRotator]).animate({opacity: 0},400);
		$(rotators[newRotator]).animate({opacity: 1},400);
	}
	thisRotator = newRotator;
	if (thisRotator==0) {
		window.setTimeout("updaterooms(true)",200);
	}
	if (needShort && $(rotators[thisRotator]).hasClass("short")) {
		window.setTimeout("rotateNext()", 2000);
	} else if (needShort && $(rotators[thisRotator]).hasClass("map"))	{
		window.setTimeout("rotateNext()", 7000);
	} else {
		window.setTimeout("rotateNext()", 8000);
	}
}


function initMaps() {
	$("li.imgrotator").each(function(index) {
		imgs = $(this).children();
		if (imgs.length == undefined || imgs.length == 0 || imgs.length==1) return;
		// set first one to opaque
		$(imgs[0]).css({opacity:1.0, width:'<?= $MAPWIDTH ?>px', height: '<?= $MAPHEIGHT ?>px'});
		$(imgs[0]).attr('width','<?= $MAPWIDTH ?>');
		$(imgs[0]).attr('height','<?= $MAPHEIGHT ?>');
		// set rest to transparent
		for (var i=1; i<imgs.length; ++i) {
			$(imgs[i]).css({opacity: 1.0, height: '<?= $MAPHEIGHT ?>px', width:'<?= $MAPWIDTH?>px'});
			$(imgs[i]).attr('width','<?= $MAPWIDTH ?>');
			$(imgs[i]).attr('height','<?= $MAPHEIGHT ?>');
		}
		for (var i=0; i<imgs.length; ++i) {
			$(imgs[i]).attr('width','<?= $MAPWIDTH ?>');
			$(imgs[i]).attr('height','<?= $MAPHEIGHT ?>');
		}
	});
	//window.setInterval("mapRotator()",1000);
	return 0;
}

function mapRotator() {
	// first image is base image and never goes away.
	// each successive image is phsed in over top of the main map in sequence.
	
	$("li.imgrotator").each(function(index) {
		imgrot = this;
		// list children
		imgs = $(imgrot).children();
		if (imgs.length == undefined || imgs.length < 2) return;

		// we're hardcoding for one, for now.

		$(imgs[0]).css('opacity',1.0);
		$(imgs[1]).css('opacity',1.0);

		// just a 'flicker'
		$(imgs[1]).animate({opacity:0.0},150);
		$(imgs[1]).animate({opacity:1.0},150);
	});
}	



$(document).on("pagebeforeshow", function() {
	rotateSound=new Howl({
		urls: ['storedoor.mp3']
	});
	$.getJSON('rooms.json',function(data){
		roomMaps=data;
	});
	initMaps();
	//window.setInterval(updaterooms,2000, true);
	window.setInterval(updaterooms, 50, false);
	// rotator
	updaterooms(true, "initRotator()");
});
</script>
<style>
ul.events li h1 {
	font-size:130%;
}
ul.events.ui-listview {
	background: rgb(255,255,255);
}
#featured.ui-listview {
	background: rgb(200,200,255);
}

ul.events.ui-listview>.ui-li-static {
	white-space: normal;
	padding-left: 8em;
	padding-right: 8em;
	min-height: 5em;
	background: transparent;
}
ul.events.ui-listview>.ui-li-static div.datebox {
	position:absolute; top:0; left:0; height:100%; width: 7em;
	background: #ffbd00; color: #00000; text-align:center; display: flex; justify-content: center;
}
ul.events.ui-listview>.ui-li-static div.datebox .thedate {
	align-self: center;
	
}
ul.events.ui-listview>.ui-li-static div.qrbox {
	position:absolute; top:0; right:0; height:100%; width: 7em;
	background: rgba(255,255,255,.1); color: #00000; text-align:center; display: flex; justify-content: center;
}
ul.events.ui-listview>.ui-li-static div.qrbox img {
	align-self: center;
	
}

ul.events.ui-listview>.ui-li-static div.location {
	font-style: italic;
}

#featured.events {
	font-size: 140%;
}


#announcements li.ui-li-static.ui-body-inherit {
	font-size: 200%;
	background-color: #ffdddd;
}

body {
	overflow-x: hidden;
	overflow-y: hidden;
}

</style>

</head>
<body style="background-color: black;"> 
<div id="player" style="postion:absolute; top:0;left:0;min-width:95%;min-height:99.9999%;padding:0;margin:0; background-image: url('http://waterbase.uwm.edu/lastvideo/current.jpg'); background-size: 120.5%"></div>

<!-- <div id="bg0" style="position: absolute; top:0; bottom:0; left:0; right:0; background: black; z-index:0"></div>
<div id="bg1" style="position: absolute; top:0; bottom:0; left:0; right:0; background: black; z-index:1"></div>  -->
<div data-role='page' id="page" style="background: rgba(0,0,0,0);">
	<div data-role="header" xclass="ui-bar" data-theme="c" data-position="fixed" id="mainhead">
		<h1 style="font-size: 75%">UWM School of Freshwater Sciences Classroom, Meeting & Event Information<span style="display: inline-block; width: 45%" id="clock"></span></h1>
	</div>
	<div id="content" data-role="content" style="background: rgba(0,0,0,0) !important;">
		<!-- container for room reservations -->
		<ul data-role="listview" data-inset="true" id="roomlist" style="width: 30%; float: right; ">
		</ul>
		<!-- container for rotating divs -->
		<div id="rotator" style="position:relative; top:0; left:0">
			<div class="rotating static short" style="position:absolute; top:0;left:0"><ul data-role="listview" data-inset="true" ><li data-role="list-divider" data-theme="b" style="font-size:150%">UWM Freshwater Webcam</li><li>The UWM School of Freshwater Sciences webcam records 24/7 images of the Inner Harbor of Milwaukee.  The background imagery on this display shows the most recent complete hour of images compressed into 30 seconds. 
<!-- <br><br><center><img src="phpqrcode/makeone.php?url=http://yt.vu/+freshwatercam&size=8"</center> -->
</li></ul></div> 
			<div class="rotating announcements optional" style="position: absolute; top:0; left:0; opacity:0">
				<!-- announcements -->
				<ul data-role="listview" data-inset="true" id="announcements" style="display:none">
				</ul>
				<!-- featured event(s) -->
				<ul data-role="listview" data-inset="true" id="featured" class="events" style="width: 68%; float: left; margin-right: 0.5%;">
					<!-- <li data-role="list-divider" data-theme="b">Upcoming Events and Announcements</li>  -->
				</ul>
				<!-- upcoming events -->
				<ul data-role="listview" data-inset="true" id="sliders"  class="events" style="width: 68%; float: left; margin-right: 0.5%;">
				<!-- <li data-role="list-divider" data-theme="b">Upcoming Events and Announcements</li> -->
				</ul>
				<!-- neeskay -->
				<ul data-role="listview" data-inset="true" id="neeskay"  class="events" style="width: 100%; float: left; margin-right: 0.5%;">
				<!-- <li data-role="list-divider" data-theme="b">Upcoming Events and Announcements</li> -->
				</ul>
			</div>

			<div style="top:0; left:0; width: <?= $MAPWIDTH+$MAPMARGIN*2 ?>px;">
				<div class="rotator_container" id="room_maps">
<!--
this worked, just saving it until the automatic filling works

					<li data-role="list-divider" id="room3080">Room 3080</li>
					<li class="imgrotator">
						<img src="maps/floor3.png">
						<img src="maps/floor3a.png">
						<img src="maps/room3080.png">
					</li>
-->
				</div>
			</div>
		</div>
	</div>
	<div data-role="footer" data-theme="b" data-position="fixed" id="mainfooter">
		<h1><div class="bottomscroller"></div></h1>
	</div>

</div>
</body>
<?php /*
    <script>
      // 2. This code loads the IFrame Player API code asynchronously.
      var tag = document.createElement('script');

      tag.src = "https://www.youtube.com/iframe_api";
      var firstScriptTag = document.getElementsByTagName('script')[0];
      firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

      // 3. This function creates an <iframe> (and YouTube player)
      //    after the API code downloads.
      var player;
      function onYouTubeIframeAPIReady() {
        player = new YT.Player('player', {
          height: $(window).height(),
          width: $(window).width(),
	  videoId: '<?=$videoID?>',
          events: {
            'onReady': onPlayerReady,
            'onStateChange': onPlayerStateChange
          }
        });
	$("#player").css({ margin:0, padding:0, "z-order":9});
      }

      // 4. The API will call this function when the video player is ready.
      var vidpos=0;
      function advanceVideo(n) {
	      vidpos += n;
	      if (vidpos >= player.getDuration()) vidpos=0;
	  	player.seekTo(vidpos);
	}
      function onPlayerReady(event) {
        event.target.pauseVideo();
	window.setInterval(function() {
		player.pauseVideo();
		advanceVideo(0.01);
	},10000);

      }

      // 5. The API calls this function when the player's state changes.
      //    The function indicates that when playing a video (state=1),
      //    the player should play for six seconds and then stop.
      var done = false;
      function onPlayerStateChange(event) {
			          if (event.data === YT.PlayerState.ENDED) {
					              player.playVideo(); 
						              }
				      }
      function stopVideo() {
        player.stopVideo();
      }
      console.log('page height: '+$('#page').height());
      $('#content').css({height:  ($('#page').height()-56)});
    </script>
     */ ?>
</html>
