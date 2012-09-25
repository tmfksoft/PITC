function load() {
	global $api;
	$api->addCommand("slap","slap_funct");
}
function slap_funct($irc) {
	global $api;
	$api->action($irc['active'],"slaps ".$irc['1']." with a PITC shaped trout");
}