<?php
	$size=1;
	if (isset($_GET['size'])) $size=2;
	$pid = getmypid();
	header('Content-type: image/png');

	include 'qrlib.php';

	
	QRcode::png($_GET['url'], "/dev/shm/qr$pid.png", 'L', $size, 2);

	echo file_get_contents("/dev/shm/qr$pid.png");
	unlink("/dev/shm/qr$pid.png");

?>
