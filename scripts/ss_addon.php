<?php

if (function_exists("do_screenshot")) {
	$api->addCommand("ssa","ssu_do");
	$api->log(" = PrintScreen.it Addon Loaded =",0);
	// Adds Clipboard! :D
	$api->addCommand("paste","clip_paste");
	$clipboard = "";
}
else {
	$api->log(" = Screenshot script by Thomas Edwards has not been loaded! =");
}
function clip_set($data) {
	global $clipboard;
	$clipboard = $data;
	return true;
}
function clip_get() {
	global $clipboard;
	return $clipboard;
}
function clip_paste($args) {
	global $api;
	$api->msg($args['active'],clip_get());
}
function ssu_do($fname) {
	global $api,$_DEBUG;
	$img = "screenshots/".$fname;
	$base = base64_encode(file_get_contents($img));
	
	$data_in = base64_encode(json_encode(array("user"=>"tom_username","pass"=>"tom_pass","image"=>$base)));
	
	$ch = curl_init();
	curl_setopt($ch,CURLOPT_URL, "http://pitc.printscreen.it/api.php");
	curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch,CURLOPT_POST, "1");
	curl_setopt($ch,CURLOPT_POSTFIELDS, "data={$data_in}");
	$raw = curl_exec($ch);
	curl_close($ch);

	$data = json_decode(base64_decode($raw));
	
	$_DEBUG['ss_dat'] = $data;
	$_DEBUG['ss_raw'] = $raw;
	
	$message = $data->msg;
	if ($data->ret == "0") {
		$api->pecho(" = SSA: Error uploading image. =");
	}
	else {
		$api->pecho(" = SSA: Uploaded image, Url: ".$data->msg." =");
		clip_set($data->msg);
		$api->pecho(" = Added to ClipBoard! Use /paste to paste to the current channel. =");
	}
}

?>