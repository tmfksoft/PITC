<?php
/*	PITC Screenshots
	Author: Thomas Edwards
	Description: Adds /screenshot to PITC.
	Script Version: v0.1
	PITC Version: v1.1 - Display function has return option.
	Requires: php5-gd
*/

if (function_exists("gd_info") && $version >= "1.1") {
	$api->log(" = PITC Screenshots Loaded! =",0);
	// If GD is installed we will work.
	if (!file_exists("screenshots")) {
		mkdir("screenshots");
	}
	$api->addCommand("screenshot","do_screenshot");
	$api->addCommand("ss","do_screenshot"); // Comment out to disable /ss
}
else {
	if ($version < "1.1") {
		$api->log(" = PITC Screenshots not loaded, PITC v1.1 or above only! =",0);
	} else {
		$api->log(" = PITC Screenshots not loaded, GD is not installed! =",0);
	}
}
function do_screenshot() {
	global $api, $active, $scrollback, $shell_cols, $shell_rows;
	
	$fname = date("d-m-Y_h-i-s").".png";
	
	// Now we make the screenie.
	$data = explode("\n",drawWindow($active,true,true));
	$data[] = "\n";
	
	// Do our GD Magic!
	$width = $shell_cols*5.15;
	$height = $shell_rows*10+10;
	$im = imagecreate($width,$height);
	$background_color = imagecolorallocate($im, 0, 0, 0);
	$text_color = imagecolorallocate($im, 255, 255, 255);
	foreach ($data as $x => $string) {
		imagestring($im, 1, 10, (10*$x),  $string, $text_color);
	}
	$im_r = imagecreatetruecolor($width*2, $height*2);
	imagecopyresized($im_r, $im, 0, 0, 0, 0, $width*2, $height*2, $width, $height);
	imagepng($im_r,"screenshots/".$fname);
	imagedestroy($im);
	
	$api->log("Screenshot saved to: ".$fname,$active);
	
	if (function_exists("ssu_do")) {
		// Check for the extension snippet.
		$ssu_url = ssu_do($fname);
	}
}
?>