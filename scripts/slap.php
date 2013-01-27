<?php
$api->addCommand("slap","slap_funct");
$api->addCommand("wtop","topic_funct");

function slap_funct($irc) {
	global $api;
	if (isset($irc['1'])) {
		if ($irc['active'] != "Status") {
			$api->action($irc['active'],"slaps ".$irc['1']." with a PITC shaped trout");
		}
	}
	else {
		$api->pecho(" = Usage: /slap NICK =",$irc['active']);
	}
}
function topic_funct($args) {
	global $api,$chan_api;
	$arr = $args['text_array'];
	$cn = $arr[1];
	$topic = $chan_api->topic($cn);
	$modes = $chan_api->modes($cn);
	$count = count($chan_api->users($cn));
	$api->msg($cn,"Topic in {$cn} is: ".$topic);
	$api->msg($cn,"Modes in {$cn} is: ".$modes);
	$api->msg($cn,"Usercount in {$cn} is: ".$count);
}
?>