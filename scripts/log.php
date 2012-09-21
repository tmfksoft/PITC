<?php
if (!file_exists("logs")) {
	mkdir("logs");
}
$api->addCommand("log","save_log");
$api->addTickHandler("log_tick");
$api->log(" = Scrolback saving script loaded! =");
function save_log($irc) {
	global $api,$windows,$scrollback;
	if (isset($irc['1'])) {
		if (is_numeric($irc['1'])) {
			if (isset($windows[$irc['1']])) {
				$cname = $windows[$irc['1']];
				$id = $irc['1'];
			}
			else {
				$api->pecho(" = No such window! =");
			}
		}
		else {
			$id = getWid($irc['1']);
			if (isset($windows[$id])) {
				$cname = $irc['1'];
			}
			else {
				$api->pecho(" = No such window! =");
			}
		}
		if (isset($id)) {
			$api->pecho("Saving log of {$cname} to logs/".$cname.".log");
			$log = implode("\n",$scrollback[$id]);
			file_put_contents("logs/".$cname.".log",$log);
			if (file_exists("logs/".$cname.".log")) {
				$api->pecho("Saved log of {$cname} to logs/".$cname.".log");
			}
			else {
				$api->pecho("Error Saving log of {$cname} to logs/".$cname.".log!");
			}
		}
	}
	else {
		$api->pecho("Usage: /log ID/Name");
	}
}
function log_tick() {
	global $api,$windows,$scrollback;
	$x = 0;
	while ($x != key($windows)) {
		if (isset($windows[$x])) {
			$log = implode("\n",$scrollback[$x]);
			file_put_contents("logs/".$windows[$x].".log",$log);
		}
		$x++;
	}
}
?>