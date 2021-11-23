<?php

function testme($a, $b='') {
	echo "a=$a, b=$b\n";
}

testme("one");

testme("one", "two");

?>
