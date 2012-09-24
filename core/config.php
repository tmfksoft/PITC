<?php
function run_config() {
	global $windows,$scrollback,$active;
	// Load Config script.
	$windows[] = "PITC Config";
	$scrollback['1'][] = "PITC Configuration.";
	drawwindow(1);

	$scrollback['1'][] = "Enter an IRC Nickame and press ENTER.";
	drawwindow(1);
	$config[] = "nick=".urlencode(trim(fgets(STDIN)));

	$scrollback['1'][] = "Enter an Alternate IRC Nickame and press ENTER.";
	drawwindow(1);
	$config[] = "altnick=".urlencode(trim(fgets(STDIN)));

	$scrollback['1'][] = "Enter your E-Mail and press ENTER.";
	drawwindow(1);
	$config[] = "username=".urlencode(trim(fgets(STDIN)));

	$scrollback['1'][] = "Enter a Realname and press ENTER.";
	drawwindow(1);
	$config[] = "realname=".urlencode(trim(fgets(STDIN)));

	$scrollback['1'][] = "Enter the default IRC server then press ENTER:";
	drawwindow(1);
	$config[] = "address=".urlencode(trim(fgets(STDIN)));
	
	$scrollback['1'][] = "Enter a default QUIT message, leave blank for PITC default:";
	drawwindow(1);
	$config[] = "quit=".urlencode(trim(fgets(STDIN)));

	$scrollback['1'][] = "Enter a password for auto identification when connecting then press ENTER.";
	$scrollback['1'][] = "Leave blank for no password.";
	drawwindow(1);
	$in = trim(fgets(STDIN));
	if ($in != "") {
		$config[] = "password=".$in;
	}

	$scrollback['1'][] = "Please wait while your configuration is saved...";
	drawwindow(1);

	file_put_contents("config.cfg",implode("\n",$config));

	$scrollback['1'][] = "Done!";
	sleep(1);
	unset($windows['1'],$scrollback['1']);
	$active = 0;
	drawwindow(0);
}
function load_config() {
	global $cnick;
	if (file_exists("config.cfg")) {
	$_CONFIG = explode("\n",file_get_contents("config.cfg"));
	$x = 0;
	while($x != count($_CONFIG)) {
		$data = explode("=",$_CONFIG[$x]);
		$_CONFIG[$data[0]] = urldecode($data[1]);
		unset($_CONFIG[$x]);
		$x++;
	}
	$cnick = $_CONFIG['nick'];
	}
	return $_CONFIG;
}
?>