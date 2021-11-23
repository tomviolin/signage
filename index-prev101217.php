<?php
if (isset($argv[1])) {
	$now = strtotime($argv[1]);
} elseif (isset($_GET['now'])) {
	$now = strtotime($_GET['now']);
} else {
	$now = -1;
}
?>
<!doctype html>
<html>
<head>
<meta http-equiv="Refresh" content="600">
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
	#mainhead {
		font-size: 200%;
	}
	#announcements, #sliders, #roomlist, #featured {
		margin-top: 0px;
	}
	#announcements>li {
		padding-top: 0em;
		padding-bottom:0em;
	}
	#announcements>li>h1 {
		margin-top:0.1em;
		margin-bottom:0.1em;
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
</style>
<script>
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
		html += '<li data-role="list-divider" style="background: '+sectionColor+'; color:'+sectionFontColor+'">'+sectionName+'</li>\n';
		for (i = 0; i < roomarray.length; ++i) {
			x = roomarray[i];
			html += '<li><img src="'+sectionIcon+'" class="ui-li-icon" height=24><div style="display: inline-block; width: 8.4em; text-align:center;color: #887700">' + moment(x.dtstart*1000).format('h:mmA-') + moment(x.dtend*1000).format('h:mmA')+'</div> '+x.subject+'</li>\n';
		}
	}
	return (html);
}

var latestData;

var forcedNow = <?=$now?>;

var numevents=2;
var marqueed = false;
var marqueetext = "";
var bgidx=1;

function flipem() {
	$('#bg'+bgidx).css('z-index', bgidx);
	$('#bg'+(1-bgidx)).css('z-index', 1-bgidx);
	bgidx=1-bgidx;
}

function updaterooms(everything) {
	numevents=8;
	var thisNow = new Date().getTime()/1000;
	if (forcedNow == -1) {
		thisNow = new Date().getTime()/1000;
	} else {
		thisNow = forcedNow;
	}
	//$('#bg'+(1-bgidx)).css('background-image','url(http://www.glwi.freshwater.uwm.edu/~tomh/iphonepics/currentlink.jpg?time='+thisNow+')');
	$('#clock').html(moment(thisNow*1000).format('MMMM D Y h:mm:ss A'));
	if (!everything) return;
	$.getJSON("getdata.php?now="+thisNow+"&limitevents="+numevents, function(data) {
		latestData=data;
		// build room lists
		var html = "";
		html += outputRooms(data.imminentrooms, 'Classes/Meetings Starting Soon','images/doorflash.gif', '#f6c6c6','#000000');
		html += outputRooms(data.juststartedrooms, 'Classes/Meetings Just Started','images/doorclosedflash.gif','#f6c6c6','#000000');
		html += outputRooms(data.currentrooms, 'Classes/Meetings In Progress', 'images/doorsclosed.png', '#f6f6f6','#000000');
		html += outputRooms(data.roomstoday, 'Coming Up Today', 'images/doorsopen.png');
		html += outputRooms(data.roomstomorrow, 'Classes/Meetings Tomorrow, '+moment((thisNow+60*60*24)*1000).format('MMMM D Y'), 'images/doorsclosed.png', '#ffbd00','#000000');

		featuredml='';
		for (var i = 0; i < data.featured.length; ++i) {
			x=data.featured[i];
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
				"<div class='qrbox'><img src='phpqrcode/makeone.php?url="+escape(x.url)+"&size=2'></div>" +
				"</li>";
		}
		if (data.featured.length >0 ) {
			if (data.featured.length > 1) plural="s"; else plural="";
			featuredml='<li data-role="list-divider" data-theme="b">Featured Event'+plural+'</li>'+featuredml;
		}
		eventml='<li data-role="list-divider" data-theme="b">Upcoming Events and Announcements</li>';
		for (var i = 0; i < data.events.length; ++i) {
			console.log(data.events[i]);
			x=data.events[i];
			console.log(eventml);
			var datemonth = moment(x.dtstart*1000).format('MMM');
			var dateday   = moment(x.dtstart*1000).format('D');
			var dateyear = moment(x.dtstart*1000).format('YYYY');
			var datedow = moment(x.dtstart*1000).format('dddd');
			var datetime = moment(x.dtstart*1000).format('h:mm A');
			var dateend = moment(x.dtend*1000).format('h:mm A');
			eventml += "<li style='border-color:black;'>\n";
			eventml += 
				"<h1 style='margin-bottom: -0.1em; margin-top:0.2em'>" +(x.subject) + "</h1>" +
				"<div class='location' style='margin-bottom: 0.3em;'>"+x.location+"</div>"+
				"<div class='desc'>" + (x.body) + "</div>" +
				"<div class='datebox'><div class='thedate'><b><font style='font-size: 1.1em'>"+datedow+"<br>"+datemonth+" "+dateday+"</font></b><br><b>"+datetime+"<br>-"+dateend+"</b></div></div>" +
				"<div class='qrbox'><img src='phpqrcode/makeone.php?url="+escape(x.url)+"'></div>" +
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
		$('#roomlist').html(html);
		$('#roomlist').listview('refresh');
		$('#featured').html(featuredml);
		$('#sliders').html(eventml);
		$('#featured').listview('refresh');
//		$('#sliders').listview('refresh');
		$('div.ui-loader').hide();

		x=$('#sliders');
		while(false&&true) {
			m=	x.parent().parent().parent().position().top +
				x.parent().parent().position().top +
				x.parent().position().top +
				x.position().top +
				x.height();
			n=x.parent().parent().parent().height();
			if (m>n) {	
				$('#sliders li:last-child').remove();
				$('#sliders').listview('refresh');
			} else {
				break;
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

	});
}
$(document).on("pagebeforeshow", function() {
	//$('#bg0').on('load',flipem);
	//$('#bg1').on('load',flipem);
	updaterooms(true);
	if (forcedNow == -1) {
		window.setInterval(updaterooms,2000, true);
		window.setInterval(updaterooms, 500, false);
	}
});
</script>
<style>
ul.events li h1 {
	font-size:130%;
}
ul.events.ui-listview {
	background: rgba(255,255,255,.8);
}
#featured.ui-listview {
	background: rgba(200,200,255,.7);
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
<body style="height:100%; background-image: url(images/sfsbackground.jpg) !important">
<!-- <div id="bg0" style="position: absolute; top:0; bottom:0; left:0; right:0; background: black; z-index:0"></div>
<div id="bg1" style="position: absolute; top:0; bottom:0; left:0; right:0; background: black; z-index:1"></div>  -->
<div data-role='page' style="background: rgba(0,0,0,0);">
	<div data-role="header" xclass="ui-bar" data-theme="c" data-position="fixed" id="mainhead">
		<h1>Welcome to the School of Freshwater Sciences<span style="display: inline-block; width: 45%" id="clock"></span></h1>
	</div>
	<div data-role="content" style="background: rgba(0,0,0,0) !important;">
		<!-- container for room reservations -->
		<ul data-role="listview" data-inset="true" id="roomlist" style="width: 30%; float: right; ">
		</ul>
		<div>
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
		</div>


	</div>
	<div data-role="footer" data-theme="b" data-position="fixed" id="mainfooter">
		<h1><div class="bottomscroller"></div></h1>
	</div>

</div>
</body>
</html>
