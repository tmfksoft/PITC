<?php
$api->addCommand("slap","slap_funct");

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
?>