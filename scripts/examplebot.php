<?php
// Simple bot that replies to @ping with the text "PONG!"
// as well as other features!
// Bot by Fudgie for use with PITC.

// The Api is precalled into $api!

// Anything we wish to do on load is merely left outside of any functions.
$api->log(" = Pingbot v1 loaded! Type @ping in the channel for a reply! =");

// Add a command.
$api->addCommand("pingbot","pingbot_mycommand"); // We want /pingbot to run the function pingbot_mycommand
$api->addCommand("myquit","pingbot_mycommand"); // We can use the function twice!

// Add a text handler.
$api->addTextHandler("pingbot_text");

// Hook into an action
$api->addActionHandler("pingbot_action");

// Add a join handler.
$api->addJoinHandler("pingbot_join");

function pingbot_mycommand($args) {
	global $api;
	//	$args will contain an array of arguments the first one '0' will be the command called.
	// This system allows you to handle mutliple commands with one function to reduce risk of collision
	// and to keep code small and manageable.
	if ($args[0] == "pingbot") {
		$api->pecho("I'm your command! :D");
	}
	else if ($args[0] == "myquit") {
		$eightball = array("Signs point to yes.",
		"Yes.",
		"Reply hazy, try again.",
		"Without a doubt.",
		"My sources say no.",
		"As I see it, yes.",
		"You may rely on it.",
		"Concentrate and ask again.",
		"Outlook not so good.",
		"It is decidedly so.",
		"Better not tell you now.",
		"Very doubtful.",
		"Yes - definitely.",
		"It is certain.",
		"Cannot predict now.",
		"Most likely.",
		"Ask again later.",
		"My reply is no.",
		"Outlook good.",
		"Don't count on it.");
		$api->quit($eightball[array_rand($eightball)]);
	}
}
function pingbot_text($args) {
	global $api;
	$chan = $args[1];
	$message = explode(" ",$args[2]);
	if (strtolower($message[0]) == "@ping") {
		$api->msg($chan,"PONG!");
	}
	else if (strtolower($message[0]) == "@md5") {
		$api->msg($chan,"md5: ".md5($args[2]));
	}
	else if (strtolower($message[0]) == "@slap") {
		if (isset($message[1])) {
			$api->action($chan,"slaps ".$message[1]);
		}
		else {
			$api->msg($chan,"Usage: @slap NICK");
		}
	}
}
function pingbot_action($args) {
	global $api;
	$chan = $args[1];
	$message = explode(" ",$args[2]);
	if (strtolower($message[0]) == "slaps") {
		$api->action($chan,"joins in and slaps ".$message[1]." too");
	}
}
function pingbot_join($irc) {
	global $api;
	$api->msg($irc['chan'],"Welcome to ".$irc['chan'].", ".$irc['nick']."!");
	$api->msg($irc['chan'],$irc['nick']." did you know your host is ".$irc['host']."?");
}
?>