<?php
/*
	#############################
	# PITC IRC TERMINAL CLIENT  #
	#    By Thomas Edwards      #
	#  COPYRIGHT TMFKSOFT 2012  #
	#############################
 */
 
// One line you may want to tweak.
$refresh = "5000";

/*
 * You can tweak the refresh from within PITC to find a suitable speed.
 * Use '/refresh' to return the current speed.
 * Use '/refresh value' to set the refresh speed.
 */
 
 // DO NOT EDIT ANY CODE IN THIS FILE, You not longer need to.
 
 // DEBUG
	$keybuff_arr = array();
 // END DEBUG
 
echo "Loading...\n";
declare(ticks = 1);
@ini_set("memory_limit","8M"); // Ask for more memory
stream_set_blocking(STDIN, 0);
stream_set_blocking(STDOUT, 0);
set_error_handler("pitcError");
$start_stamp = time();
$buffer = "";
$buffpos = 0;
$curshow = 0;
$cmd = "";
$text = "";
$previous = "";
$rawlog = array();
$ctcps = array();
$error_log = array();

$_DEBUG = array(); // Used to set global vars for /dump from within functions.
$loaded = array(); // Loaded scripts.

if (isset($argv[1]) && $argv[1] == "-a") {
	$autoconnect = true;
}
else {
	$autoconnect = false;
}
/* Handle being terminated */
if (function_exists('pcntl_signal')) {
	/*
	 * Mac OS X (darwin) doesn't be default come with the pcntl module bundled
	 * with it's PHP install.
	 * Load it to take advantage of Signal Features.
	*/
	
	/* Currently broken
	pcntl_signal(SIGTERM, "signal_handler");
	pcntl_signal(SIGINT, "signal_handler");
	pcntl_signal(SIGHUP, "signal_handler");
	pcntl_signal(SIGUSR1, "signal_handler");
	*/
}

if (strtoupper(substr(PHP_OS, 0, 3)) != 'WIN') {
	system("stty -icanon"); // Only Linux can do this :D
	$shell_cols = exec('tput cols');
	$shell_rows = exec('tput lines');
}
else {
	$shell_cols = "80";
	$shell_rows = "24";
}

// Init some Variables.
$version = "1.2"; // Do not change this!

if (file_exists($_SERVER['PWD']."/core/functions.php")) {
	include($_SERVER['PWD']."/core/functions.php");
}
else {
	die("Missing Functions.php! PITC CANNOT Function without this.");
}

if (file_exists($_SERVER['PWD']."/core/config.php")) {
	include($_SERVER['PWD']."/core/config.php");
}
else {
	shutdown("ERROR Loading Config.php!\n");
}

if (!file_exists($_SERVER['PWD']."/core/config.cfg")) {
	stream_set_blocking(STDIN, 1);
	run_config();
	sleep(1);
	stream_set_blocking(STDIN, 0);
	drawwindow(0);
}

// Load the config and language pack.
$_CONFIG = load_config();
if (isset($_CONFIG['lang'])) {
	$language = $_CONFIG['lang'];
}
else {
	$language = "en";
}
$lng = array();
if (file_exists("langs/".$language.".lng")) {
	eval(file_get_contents("langs/".$language.".lng"));
}
else {
	if (file_exists("langs/en.lng")) {
		eval(file_get_contents("langs/en.lng"));
	}
	else {
		shutdown("Unable to load Specified Language or English Language!\n");
	}
}


// Variable Inits - LEAVE THEM ALONE!
$active = "strt"; // Current window being viewed.
$windows = array("strt"=>"PITC starting up",0=>$lng['STATUS']);
$scrollback['strt'] = array();
$scrollback['0'] = array(" = {$lng['STATUS']} {$lng['WINDOW']}. =");
$text = "";
$chan_modes = array();
$chan_topic = array();

if (file_exists($_SERVER['PWD']."/core/api.php")) {
	include($_SERVER['PWD']."/core/api.php");
}
else {
	shutdown("{$lng['MSNG_API']}\n");
}

// ASCII Feature?!
if (file_exists($_SERVER['PWD']."/core/ascii.php")) {
	include($_SERVER['PWD']."/core/ascii.php");
}
// DNS issue. quick fix for now.
if (!is_connected()) {
	$text = ascii_read_file($_SERVER['PWD']."/core/pitc_ascii");
	$error_log[] = "[ASCII] No internet connection, loading local file.";
}
else {
	$text = ascii_read_file("http://pitc.x10.mx/ascii/");
	$error_log[] = "[ASCII] Internet, Loading remote data.";
}
ascii_display($text,"strt");

$textlns = count($text);
$void = ($shell_rows - $textlns) / 2;

for ($i=0;$i<$void;$i++) { $scrollback['strt'][] = ""; }


drawWindow($active,false);
sleep(2);
unset($scrollback['strt'],$windows['strt']);
$active = "0";

// Scripting interface/api
$api_commands = array();
$api_messages = array();
$api_actions = array();
$api_ctcps = array();
$api_joins = array();
$api_parts = array();
$api_connect = array();
$api_tick = array();
$api_raw = array();
$api_start = array();
$_PITC = array();

// PITC Variables
$_PITC['nick'] = $_CONFIG['nick'];
$_PITC['altnick'] = $_CONFIG['altnick'];
$_PITC['network'] = false;
$_PITC['server'] = false;
$_PITC['address'] = false;

// START Handler/Hook
$x = 0;
while ($x != count($api_start)) {
	$args = array(); // Empty for now
	call_user_func($api_start[$x],$args);
	$x++;
}

$scrollback['0'][] = " = {$lng['CHECKING_LATEST']} =";
drawWindow(0,false);
sleep(1);
if (is_connected()) {
	$latest = @file_get_contents("http://pitc.x10.mx/update/?action=latest");
}
else {
	$latest = false;
	$scrollback['0'][] = "PITC requires an internet connection to check for updates. Aborting check.";
}
if ($latest != false && $latest > $version) {
	$scrollback['0'][] = " = {$lng['NEWER']} =";
	drawWindow(0,false);
	$scrollback['0'][] = " = {$lng['RUNUPDATE']}";
	drawWindow(0);
	sleep(1);
}
	// Init our API's
	$api = new pitcapi();
	$chan_api = new channel();
	// Load any core scripts.
	include("colours.php");
	$colors = new Colors(); // Part of Colours Script
	// Load auto scripts.
	if (file_exists($_SERVER['PWD']."/scripts/autoload")) {
		$scripts = explode("\n",file_get_contents($_SERVER['PWD']."/scripts/autoload"));
		for ($x=0;$x != count($scripts);$x++) {
			if ($scripts[$x][0] != ";") {
				$script = $_SERVER['PWD']."/scripts/".trim($scripts[$x]);
				if (file_exists($script)) {
					include_once($script);
					$loaded[] = $script;
				}
				else {
					$scrollback[0][] = " = {$lng['AUTO_ERROR']} '{$scripts[$x]}' {$lng['NOSUCHFILE']} =";
				}
				drawWindow($active);
			}
		}
		//unset($scripts);
	}
	drawWindow($active);
	if ($_SERVER['TERM'] == "screen") {
		$scrollback[0][] = " = {$lng['SCREEN']} =";
		drawWindow($active);
	}
	$ann = data_get("http://announcements.pitc.x10.mx/");
	if ($ann->message != "none") { $scrollback[0][] = " ".$ann->message; }
while (1) {

	if (strtoupper(substr(PHP_OS, 0, 3)) != 'WIN') {
		// Update screensize.
		$shell_cols = exec('tput cols');
		$shell_rows = exec('tput lines');
	}
	if (isset($windows[$active])) {
		drawWindow($active);
	}
	$x = 0;
	while ($x != count($api_tick)) {
		$args = array(); // Empty for now
		call_user_func($api_tick[$x],$args);
		$x++;
	}
	if ($_SERVER['TERM'] == "screen" && isset($_SERVER['STY'])) {
		$screen_d = shell_exec("screen -ls");
		$screen_d = explode("\n",$screen_d);
		$x = 0;
		while ($x != count($screen_d)) {
			$data = explode(" ",$screen_d[$x]);
			if ($data[0] == $_SERVER['STY']) {
				if ($data[2] == "(Detached)") {
					if (isset($_CONFIG['screen_away']) && $_CONFIG['screen_away'] == "true") {
						if (isset($sid)) {
							pitc_raw("NICK :".$cnick."[Away]");
							pitc_raw("AWAY :{$lng['SCREEN_D_1']}");
							$scrollback[0][] = " = {$lngp['SCREEN_D_2']} =";
						}
					}
				}
			}
			$x++;
		}
	}
	$in = fgets(STDIN);

	if ($in != "" && ord($in) > 31 && ord($in) != 127) {
		$text = "";
		$cmd = "";
		
		$left = substr($buffer, 0, $buffpos);
		$right = substr($buffer, $buffpos);
		$buffpos++;
		$buffer = $left.$in[0].$right;
	}
	else if (ord($in) == 27) {
		if ($in[2] == "D") {
			// Pressed Left.
			if ($buffpos > 0) { $buffpos--; }
		}
		else if ($in[2] == "B") {
			// Pressed Down
			$buffer = "";
			$buffpos = 0;
		}
		else if ($in[2] == "C") {
			// Pressed Right.
			if ($buffpos < strlen($buffer)) { $buffpos++; }
		}
		else if ($in[2] == "A") {
			// Pressed Up.
			$buffer = $previous;
			$buffpos = strlen($buffer);
		}
		else if ($in[2] == 5) {
			// Page Up.
			$scrollback[$active][] = " = Page UP =";
		}
		else if ($in[2] == 6) {
			// Page down
			$scrollback[$active][] = " = Page Down =";
		}
	}
	else if (ord($in) == 9) {
		// Check if we're tab completing or not.
		if (substr($buffer,0,-1) != " ") {
			// Tab complete
			if ($active != "0") {
				// Tab :3
				// Check if we should be checking.
				if ($buffer != "" && $buffer != " ") {
					$full = nick_tab($userlist[$active],$buffer);
				}
				else {
					$full = FALSE; // Aborts it anyway.
				}
				if ($full != FALSE) {
					$buff = explode(" ",$buffer);
					$items = count($buff);
					$scrollback[$active][] = " I found {$items} words.";
					if ($items > 1) {
						unset($buff[count($buff)-1]);
						$buff[] = $full;
						$buffer = implode(" ",$buff);
					}
					else {
						$buffer = $full;
					}
					$buffpos = strlen($buffer);
				}
			}
			else { $buffer .= "	"; }
		}
		else {
			$buffer .= "	";
		}
	}
	else if (ord($in) == 127) {
		// Backspace.
		$left = substr($buffer, 0, $buffpos);
		$right = substr($buffer, $buffpos);
		$buffer = substr($left,0,-1).$right;
		$buffpos--;
	}
	else if (ord($in) == 10) {
		$text = explode(" ",$buffer);
		$cmd = strtolower($text[0]);
		$previous = $buffer;
		$buffer = "";
		$buffpos = 0;
	}
	// anything not recognised.
	else if (ord($in) < 31 && ord($in) != 0) {
		$buffer .= ord($in);
	}
	else {
		$text = "";
		$cmd = "";
	}
	if (ord($in) != 0) {
		$keybuff_arr[] = ord($in);
	}
	// Command Checking
	if ($cmd == "/quit") {
		if (isset($sid)) {
			$scrollback[$active][] = "Quitting!";
			if (isset($text[1])) {
				$qmsg = array_slice($text,1);
				$qmsg = implode(" ",$qmsg);
				pitc_raw("QUIT :".$qmsg);
			}
			else {
				if (isset($_CONFIG['quit'])) {
					pitc_raw("QUIT :{$_CONFIG['quit']}");
				}
				else {
					pitc_raw("QUIT :{$lng['DEF_QUIT']}");
				}
			}
			$scrollback = array($scrollback['0']);
			$windows = array("Status");
			$scrollback['0'][] = " = {$lng['DISCONNECTED']} {$lng['RECONNECT_TO']} ".$_PITC['address']." =";
			$cnick = $_CONFIG['nick'];
			$active = 0;
			fclose($sid);
			unset($sid);
			$_PITC['address'] = false;
		}
		else {
			$scrollback['0'][] = " = {$lng['NOT_CONN']} =";
		}
	}
	else if ($cmd == "/version") {
		$scrollback['0'][] = " = You are running PITC v".$version." =";
	}
	else if ($cmd == "/refresh") {
		if (isset($text[1])) {
			if (is_numeric($text[1])) {
				$refresh = $text[1];
				$scrollback['0'][] = " = Refresh speed altered to {$refresh}ms =";
			}
			else {
				$scrollback['0'][] = " = The refresh speed CANNOT be set to a letter! =";
			}
		}
		else {
				$scrollback['0'][] = " = Refresh speed is currently {$refresh}ms =";
		}
	}
	else if ($cmd == "/update") {
		if ($sid) {
			$scrollback[0][] = " = You are connected to IRC. Disconnect first! =";
		}
		else {
			update(true);
			shutdown("Please start PITC.");
		}
	}
	else if ($cmd == "/exit") {
		if (isset($sid)) {
			if (isset($_CONFIG['quit'])) {
				pitc_raw("QUIT :{$_CONFIG['quit']}");
			}
			else {
				pitc_raw("QUIT :{$lng['DEF_QUIT']}");
			}
		}
		shutdown("\nThanks for using PITC!\n");
	}
	else if ($cmd == "^[^[[C") {
		$scrollback[$active][] = $lng['NO_OPEN'];
	}
	else if ($cmd == "^[[A") {
		$scrollback[$active][] = "Last command";
	}
	else if ($cmd == "/settings") {
		$scrollback[$active][] = " Your current configuration is as follows:";
		foreach ($_CONFIG as $directive => $setting) {
			$scrollback[$active][] = "    {$directive} = {$setting}";
		}
	}
	else if ($cmd == "/nick") {
		if (isset($text[1])) {
			if (!isset($sid)) {
				$_CONFIG['nick'] = $text[1];
				$cnick = $_CONFIG['nick'];
				$scrollback['0'][] = " = Nick {$lng['CHANGED']} ".$text[1]." =";
			}
			else {
				pitc_raw("NICK :".$text[1]);
			}
		}
		else {
			$scrollback[$active][] = "{$lng['USAGE']}: /nick NICK";
		}
	}
	else if ($cmd == "/clear") {
		// Clear Active Scrollback
		$scrollback[$active] = array();
	}
	else if ($cmd == "/load") {
		// Clear Active Scrollback
		if (isset($text[1])) {
			// Check for file
			if (file_exists($_SERVER['PWD']."/".$text[1])) {
				include_once($_SERVER['PWD']."/".$text[1]);
				$loaded[] = $_SERVER['PWD']."/".$text[1];
				// We trust the script will do a log to say its loaded.
				drawWindow($active);
			}
			else {
				$scrollback[$active][] = " {$lng['ERROR_SCRIPT']}";
			}
		}
		else {
			$scrollback[$active][] = " {$lng['USAGE']}: /load file";
		}
	}
	else if ($cmd == "/loaded") {
		$scrollback[$active][] = " - Currently loaded scripts:";
		foreach ($loaded as $script) {
			$scrollback[$active][] = "   - ".$script;
		}
	}
	else if ($cmd == "/bell") {
		// Clear Active Scrollback
		ringBell();
		drawWindow($active);
	}
	else if ($cmd == "/donk") {
		$scrollback[$active][] = " = DONK! = ";
	}
	else if ($cmd == "/eval") {
		$code = implode(" ",array_slice($text, 1));
		ob_start();
		@eval("?>".$code."<?");
		$ret = ob_get_clean();
		$ret = explode("\n",$ret);
		foreach ($ret as $message) {
			$scrollback[$active][] = " [PHP] ".$message;
		}
		unset($code);
		unset($ret);
		unset($eval);
	}
	else if ($cmd == "/lang") {
		$language = strtolower($text[1]);
		if (file_exists("langs/".$language.".lng")) {
			$lng = array();
			eval(file_get_contents("langs/".$language.".lng"));
			$scrollback[$active][] = "Loaded ".strtoupper($language)." - Prelogged data CANNOT be changed!";
			drawWindow($active);
		}
		else {
			$scrollback[$active][] = "ERROR Loading Language File!";
		}
	}
	else if ($cmd == "/dump") {
		$fname = "dumps/".time()."_dump";
		$scrollback[$active][] = " # {$lng['MEM_DMPG']} ".$fname.". #";
		$vars = print_r(get_defined_vars(),true);
		if (!file_exists("dumps") && !is_dir("dumps")) {
			mkdir("dumps");
		}
		file_put_contents($fname,$vars);
		if (file_exists($fname)) {
			$scrollback[$active][] = " # {$lng['MEM_DUMPED']} ".$fname.". #";
		}
		else {
			$scrollback[$active][] = " # {$lng['MEM_ERROR']} ".$fname.". #";
		}
	}
	else if ($cmd == "/bugreport") {
		// Mails a memory dump to bugs@pitc.x10.mx
		$data = "CORE VARS: \n\n".base64_encode(print_r(get_defined_vars(),true));
		foreach ($loaded as $script) {
			$data .= "\n $script\n\n".base64_encode(file_get_contents($script));
		}
		$username = $_SERVER['user'];
		$host = gethostname();
		mail('bugs@pitc.x10.mx', 'Automated MEMDUMP '.time(), $data,"From: {$username}@{$host}");
		unset($data);
		$scrollback[$active][] = " = Performed bug report =";
	}
	else if ($cmd == "/view") {
		if (isset($text[1])) {
			// String or number?
			if (is_numeric($text[1])) {
				$id = $text[1];
					}
			else {
				$id = getWid($text[1]);
			}
			if (isset($windows[$id])) {
				// View window.
				if ($id == $active) {
					$scrollback[$active][] = $colors->getColoredString("{$lng['VIEW_ALREADY']} ".$windows[$id], "red");
				}
				else {
					$active = $id;
					$scrollback[$active][] = $colors->getColoredString("{$lng['VIEWING']} ".$windows[$id], "red");
				}
			}
			else {
				$scrollback[$active][] = $colors->getColoredString("{$lng['VIEW_NO']}", "red");
			}
		}
		else {
			$scrollback[$active][] = $colors->getColoredString("{$lng['USAGE']}: /view ID/Name", "red");
		}
	}
	// End /view
	else if ($cmd == "/windows") {
		end($windows);
		$amount = key($windows);
		$amount++;
		//$amount = count($windows);
		$scrollback[$active][] = "{$lng['WINDOWS_1']} ".$amount." {$lng['WINDOWS_2']}";
		$x = 0;
		while ($x != $amount) {
			if (isset($windows[$x])) {
				if ($x == $active) {
					$scrollback[$active][] = "\t".$x." - ".$windows[$x]." ({$lng['VIEWING']}) ";
				}
				else {
					$scrollback[$active][] = "\t".$x." - ".$windows[$x];
				}
			}
			$x ++;
		}
		$scrollback[$active][] = "{$lng['WINDOWS_3']} ".$active.":".$windows[$active];
	}
	else if ($cmd == "/config") {
		if (isset($sid)) {
			$scrollback[$active][] = " = {$lng['NO_CONFIG']} =";
		}
		else {
			stream_set_blocking(STDIN,1);
			run_config();
			unset($scrollback[1],$windows[1]);
			$active = 0;
			stream_set_blocking(STDIN,0);
			$_CONFIG = load_config();
		}
	}
	else if ($cmd == "/ulist") {
		$users = $userlist[$active];
		$scrollback[$active][] = " = There are ".count($users)." users in this channel. =";
		foreach ($users as $user) {
			$scrollback[$active][] = "\t{$user}";
		}
		$scrollback[$active][] = " = End /ulist =";
	}
	else if ($cmd == "/close" || $cmd == "/part") {
		// Close active window
		if ($active != "0") {
			// Close window
			if (isset($text[1])) {
				if (is_numeric($text[1])) {
					$win = $text[1];
				}
				else {
					$win = getWid($text[1]);
				}
			}
			else {
				$win = $active;
			}
			$windowname = $windows[$win];
			if ($windowname[0] == "#") {
				// Tell the IRCD we're parting.
				pitc_raw("PART ".$windowname." :{$lng['PARTING']}!");
			}
			unset($windows[$win], $scrollback[$win],$userlist[$win]);
			array_values($windows);
			$active = count($windows)-1;
		}
		else {
			$scrollback[$active][] = "{$lng['STATUS_NO']}";
			$scrollback[$active][] = "{$lngp['USE_EXIT']}";
		}
		
	}
	else if ($cmd == "/connect" || $autoconnect == true) {
		if ($autoconnect) {
			$scrollback[$active][] = " = {$lng['AUTO_CONN']} =";
			$autoconnect = false;
		}
		if (!isset($text[1])) {
			$scrollback[$active][] = " = {$lng['CONN_DEF']} (".$_CONFIG['address'].") =";
			$address = $_CONFIG['address'];
		}
		else {
			$scrollback[$active][] = " = {$lng['CONN_TO']} ".$text[1];
			$address = $text[1];
		}
		drawWindow(0,false);
		$_PITC['address'] = $address;
		$address = explode(":",$address);
		if (isset($address[1]) && is_numeric($address[1])) { $port = $address[1]; }
		else { $port = 6667; }
		if (isset($text[2])) { $password = $text[2]; } else { if (isset($_CONFIG['password'])) { $password = $_CONFIG['password']; } else { $password = false; } }
		$ssl = false;
		if ($port[0] == "+") { $ssl = true; }
		$sid = connect($_CONFIG['nick'],$address[0],$port,$ssl,$password);
		if (!$sid) {
			$scrollback[$active][] = " = {$lng['CONN_ERROR']} =";
			unset($sid);
		}
		else {
			stream_set_blocking($sid, 0);
		}
	}
	else if ($cmd == "") {
	// Do nothing
	}
	else {
		if (isset($sid)) {
			$entered = implode(" ",$text);
			$params = $text;
			unset($params[0]);
			if ($cmd[0] == "/") {
				// PERFORMABLE COMMANDS WHILE CONNECTED!
				$tentered = substr($entered,1);
				// Entered a command :D
				$command = substr($cmd,1);
				if ($command == "me") {
					if ($active == "0") {
						$scrollback[$active][] = $colors->getColoredString(" {$lng['STATUS_TLK']}", "red");
					}
					else {
						$action = $text;
						unset($action[0]);
						$action = implode(" ",$action);
						pitc_raw("PRIVMSG ".$windows[$active]." :ACTION ".$action."");
						if ($windows[$active][0] == "#") {
							$mynick = get_prefix($cnick,$userlist[getWid($active)]);
						}
						else {
							$mynick = $cnick;
						}
						$scrollback[$active][] = $colors->getColoredString("* ".$mynick." ".$action,"purple");
					}
				}
				else if ($command == "join") {
					$chans = implode(" ",$params);
					pitc_raw("JOIN :".$chans);
				}
				else if ($command == "raw" || $command == "/raw") {
					$message = array_slice($text, 2);
					$message = implode(" ",$message);
					fputs($sid,$message."\n");
				}
				else if ($command == "ctcp") {
					if (!isset($params[1]) || !isset($params[2])) {
						$scrollback[$active][] = " {$lng['USAGE']}: /ctcp nick ctcp";
					}
					else {
						$scrollback[0][] = $colors->getColoredString(" -> [".$params[1]."] ".strtoupper($params[2]), "light_red");
						ctcp($params[1],strtoupper($params[2]));
					}
				}
				else if ($command == "msg") {
					// Send a message!
					if (!isset($text[1]) || !isset($text[2])) {
						$scrollback[$active][] = " {$lng['USAGE']}: /msg Nick/#Channel Message";
					}
					else {
						$target = $text[1];
						$message = array_slice($text, 2);
						$message = implode(" ",$message);
						fputs($sid,"PRIVMSG ".$target." :".$message."\n");
						$scrollback[$active][] = $target." -> ".$message;
					}
				}
				else if ($command == "amsg") {
					// Send a message!
					if (!isset($text[1])) {
						$scrollback[$active][] = " {$lng['USAGE']}: /amsg Message";
					}
					else {
						$message = array_slice($text, 1);
						$message = implode(" ",$message);
						$x = 0;
						while ($x != key($windows)) {
							if (isset($windows[$x])) {
								if ($windows[$x][0] == "#") {									
									pitc_raw("PRIVMSG ".$windows[$x]." :".$message);
									$scrollback[$x][] = " <.".$cnick."> ".$message;
								}
							}
							$x++;
						}
					}
				}
				else if ($command == "notice") {
					// Send a notice!
					if (!isset($text[1]) || !isset($text[2])) {
						$scrollback[$active][] = "{$lng['USAGE']}: /notice Nick/#Channel Message";
					}
					else {
						$target = $text[1];
						$message = array_slice($text, 2);
						$message = implode(" ",$message);
						pitc_raw("NOTICE ".$target." :".$message);
						$scrollback[$active][] = " -".$target."- -> ".$message;
					}
				}
				else if ($command == "query") {
					if (!isset($text[1])) {
						$scrollback[$active][] = "{$lng['USAGE']} /query nick";
					}
					else {
						if ($text[1][0] == "#") {
							$scrollback[$active][] = $lng['QUERY_CHAN'];
						}
						else {
							$wid = getWid($text[1]);
							if (!$wid) {
								// Open a new window.
								$windowid = count($windows);
								$windows[] = $text[1];
								$scrollback[$windowid] = array(" = {$lng['QUERY_OPEN']} ".$text[1]);
								$userlist[$windowid] = array($cnick,$text[1]);
								$active = $windowid;
							}
							else {
								// Bring the window to focus.
								$active = $wid;
							}
						}
					}
				}
				else {
					// Forward to Server
					if (isset($api_commands[$command])) {
						// Command exists in the api. Call its function
						$args = array();
						$args['active'] = $windows[$active];
						$args['text'] = $tentered;
						$args['text_array'] = explode(" ",$tentered);
						$fnct = $api_commands[strtolower($command)];
						call_user_func($fnct,$args);
					}
					else {
						pitc_raw($tentered);
					}
				}
			}
			else {
				if ($active == "0") {
					$scrollback[$active][] = $colors->getColoredString($lng['STATUS_TLK'], "red");
				}
				else {
					//$scrollback[$active][] = "Sending message to ".$active.":".$windows['0']; // DEBUG
					fputs($sid,"PRIVMSG ".$windows[$active]." :".$entered."\n");
					if ($windows[$active][0] == "#") {
						$mynick = get_prefix($cnick,$userlist[$active]);
					}
					else {
						$mynick = $cnick;
					}
					$scrollback[$active][] = " <".$mynick."> ".$entered;
				}
			}
		}
		else {
			if (isset($api_commands[substr($text[0],1)])) {
				// Command exists in the api. Call its function
				$cmd = $text[0];
				$fnct = $api_commands[substr($text[0],1)];
				$args = $text;
				$args[0] = substr($args[0],1);
				call_user_func($fnct,$args);
			}
			else {
				$scrollback[$active][] = $lng['CMD_UK'];
			}
		}
	}
	// Handle Connection
	if (isset($sid)) {
		$irc = parse($sid);
		if ($irc) {
			// Handle IRC.
			
			$irc_data = explode(" ",$irc);
			// Raw Handler.
			$x = 0;
			while ($x != count($api_raw)) {
				call_user_func($api_raw[$x],$irc_data);
				$x++;
			}
			if ($irc_data[1] == "001") {
				$cnick = $irc_data[2];
				$x = 0;
				while ($x != count($api_connect)) {
					$args = array(); // Empty for now
					call_user_func($api_connect[$x],$args);
					$x++;
				}
				$_PITC['network'] = $irc_data[1];
			}
			else if ($irc_data[1] == "CAP" && $irc_data[4] == ":sasl") {
				// SASL Time.
				if (isset($_CONFIG['sasl']) && strtolower($_CONFIG['sasl']) == "y") {
					$scrollback[0][] = " = IRC Network supports SASL, Using SASL! =";
					pitc_raw("AUTHENTICATE PLAIN");
				}
			}
			else if ($irc_data[0] == "AUTHENTICATE" && $irc_data[1] == "+") {
				if (isset($_CONFIG['sasl']) && strtolower($_CONFIG['sasl']) == "y") {
					$enc = base64_encode(chr(0).$_CONFIG['sasluser'].chr(0).$_CONFIG['saslpass']);
					pitc_raw("AUTHENTICATE {$enc}");
				}
			}
			else if ($irc_data[0] == "AUTHENTICATE" && $irc_data[1] == "A") {
				// IRCD Aborted SASL.
				$scrollback[0][] = " = Server aborted SASL conversation! =";
				pitc_raw("CAP END");
			}
			else if ($irc_data[0] == "AUTHENTICATE" && $irc_data[1] == "F") {
				// Some form of Failiure, Not sure which. InspIRCD seems to send it.
				pitc_raw("CAP END");
			}
			else if ($irc_data[1] == "900") {
				$scrollback[0][] = " = You are logged in via SASL! =";
				pitc_raw("CAP END");
			}
			else if ($irc_data[1] == "904" || $irc_data[1] == "905") {
				$scrollback[0][] = " = SASL Auth failed. Incorrect details =";
				pitc_raw("CAP END");
			}
			else if ($irc_data[1] == "906") {
				// IRCD Aborted SASL.
				$scrollback[0][] = " = Server aborted SASL conversation! =";
				pitc_raw("CAP END");
			}
			else if ($irc_data[1] == "903") {
				pitc_raw("CAP END");
			}
			else if ($irc_data[1] == "353") {
				// Userlist :3
				// 2013 - Fixed in regards to a bug causing issues. :D
				// [WIP] Userlist shows every time a mode changes etc, needs to be sorted.
				$users = array_slice($irc_data,5);
				$chan = $irc_data[4];
				$users[0] = substr($users[0],1);
				$scrollback[getWid($chan)][] = $colors->getColoredString(" [ ".implode(" ",uListSort($users))." ]","cyan");
				$userlist[getWid($chan)] = array_merge($userlist[getWid($chan)],$users);
				$userlist[getWid($chan)] = uListSort($userlist[getWid($chan)]);
				array_values($userlist[getWid($chan)]);
			}
			else if ($irc_data[1] == "324") {
				$mode = $irc_data[4];
				$chan = $irc_data[3];
				$id = getWid($chan);
				$mode = str_split($mode);
				sort($mode);
				$mode = implode("",$mode);
				$chan_modes[$id] = $mode;
			}
			else if ($irc_data[1] == "311") {
				// WHOIS.
				$scrollback[$active][] = " = WHOIS for {$irc_data[3]} =";
				$scrollback[$active][] = " * {$irc_data[3]} is ".implode(" ",array_slice($irc_data,4));
			}
			else if ($irc_data[1] == "379" || $irc_data[1] == "378") {
				$scrollback[$active][] = " * Whois data";
			}
			else if ($irc_data[1] == "PRIVMSG") {
				$ex = explode("!",$irc_data[0]);
				$source = substr($ex[0],1);
				$target = $irc_data[2];
				if ($target[0] == "#") {
					// Ulist Check!
					$source = get_prefix($source,$userlist[getWid($target)]);
				}
				$message = array_slice($irc_data, 3);
				$message = substr(implode(" ",$message),1);
				$isctcp = false;
				if ($target == $cnick) {
					// Check for CTCP!
					$msg_d = explode(" ",$message); // Reversing the previous, I know.
					$msg_d_lchar = strlen($msg_d[0][0])-1;
					$msg_d_lchar = $msg_d[0][$msg_d_lchar];
					if ($msg_d[0][0] == "" && $msg_d_lchar == "") {
						// CTCP!
						$ctcp = trim($msg_d[0],"");
						$ctcp_data = getCtcp($ctcp);
						$scrollback[0][] = $colors->getColoredString("[".$source." ".$ctcp."]","light_red");
						if ($ctcp == "PING") {
							ctcpReply($source,$ctcp,trim($msg_d[1],""));
						}
						if ($ctcp_data) {
							ctcpReply($source,$ctcp,$ctcp_data);
						}
						$isctcp = true;
						// CTCP API
						$args = array();
						$args[] = strtolower($source);
						$args[] = $ctcp;
						$x = 0;
						while ($x != count($api_ctcps)) {
							call_user_func($api_ctcps[$x],$args);
							$x++;
						}
					}
					// Message to me.
					$wid = getWid($source);
					$win = $source;
				}
				else {
					// Message to a channel.
					$wid = getWid($target);
					$win = $target;
				}
				if (!$wid && !$isctcp) {
					// No such channel. Create it.
					$windows[] = $win;
					$wid = getWid($win);
					$scrollback[$active][] = $colors->getColoredString(" = {$lng['MSG_IN']} [".$wid.":".$win."] {$lng['FROM']} ".$source." = ","cyan");
					// Get the new id.
				}

				$words = explode(" ",$message);
				// Last Char
				$sc = implode(" ",$words);
				$length = strlen($sc);
				$lchar = $sc[$length-1];
				// Figure out if its an action or not. -.-
				if ($words[0] == "ACTION" && $lchar == "" && !$isctcp) {
					// ACTION!
					unset($words[0]);
					$words_string = trim(implode(" ",$words),"");
					// Check for Highlight!
					if (isHighlight($words_string,$cnick)) {
						// Highlight!
						$scrollback[$wid][] = $colors->getColoredString("* ".$source." ".$words_string,"yellow");
						if ($active != $wid) {
							$scrollback[$active][] = $colors->getColoredString(" = ".$source." {$lng['HIGHLIGHT']} ".$win." = ","cyan");
							ringBell();
						}
					}
					else {
						$scrollback[$wid][] = $colors->getColoredString("* ".$source." ".$words_string,"purple");
					}
					// API TIME!
					$args = array();
					$args['active'] = $active;
					$args['nick'] = $source;
					$args['channel'] = strtolower($win);
					$args['text'] = $words_string;
					$args['text_array'] = explode(" ",$words_string);
					$x = 0;
					while ($x != count($api_actions)) {
						call_user_func($api_actions[$x],$args);
						$x++;
					}
				}
				else {
					if (!$isctcp) {
						// Message! - Check for highlight!
						//$scrollback[$wid][] = $cnick." ".$_CONFIG['nick']." ".stripos($message,$cnick)." ".stripos($message,$_CONFIG['nick']); // H/L Debug
						if (isHighlight($message,$cnick)) {
							// Highlight!
							$scrollback[$wid][] = $colors->getColoredString(" <".$source."> ".$message,"yellow");
							if ($active != $wid) {
								$scrollback[$active][] = $colors->getColoredString(" = ".$source." {$lng['HIGHLIGHT']} ".$win." = ","cyan");
								ringBell();
							}
						}
						else {
							$scrollback[$wid][] = " <".$source."> ".format_text($message);
						}
						// API TIME!
						$args = array();
						$args['nick'] = $source;
						$args['channel'] = strtolower($win);
						$args['text'] = $message;
						$args['text_array'] = explode(" ",$message);
						$x = 0;
						while ($x != count($api_messages)) {
							call_user_func($api_messages[$x],$args);
							$x++;
						}
						// Done
					}
				}
			}
			else if ($irc_data[1] == "NICK") {
				$ex = explode("!",$irc_data[0]);
				$nick = substr($ex[0],1);
				if ($irc_data[2][0] == ":") {
					$nnick = substr($irc_data[2],1);
				}
				else {
					$nnick = $irc_data[2];
				}
				if ($nick != $cnick) {
					$string = $colors->getColoredString("  * ".$nick." {$lng['NICK_OTHER']} ".$nnick, "green");
				}
				else {
					$string = $colors->getColoredString("  * {$lng['NICK_SELF']} ".$nnick, "green");
					$cnick = $nnick;
				}
				$scrollback[$active][] = $string;
			}
			else if ($irc_data[0] == "PING") {
				// Do nothing.
			}
			else if ($irc_data[0] == "ERROR") {
				// Lost connection!
				$message = array_slice($irc_data,1);
				$message = substr(implode(" ",$message),1);
				$scrollback[0][] = $colors->getColoredString(" = ".$message." =","blue");
				$x = 0;
				while ($x != key($scrollback)) {
					if (isset($scrollback[$x])) {
						$scrollback[$x][] = $colors->getColoredString(" = {$lng['DISCONNECTED']} ".$_PITC['address']." {$lng['RECONNECT']} =","blue");
					}
					$x++;
				}
				unset($sid);
			}
			else if ($irc_data[1] == "NOTICE") {
				// Got Notice!
				$dest = $irc_data[2];
				$ex = explode("!",$irc_data[0]);
				$nick = substr($ex[0],1);
				$message = array_slice($irc_data, 3);
				$message = substr(implode(" ",$message),1);
				
				// CTCP Stuff.
				$msg_d = explode(" ",$message); // Reversing the previous, I know.
				$msg_d_lchar = strlen($msg_d[count($msg_d)-1][0])-1;
				$ctcp = trim($msg_d[0],"");
				$ctcp_data = trim(implode(" ",array_slice($msg_d, 1)),"");
				$msg_d_lchar = $msg_d[0][$msg_d_lchar];
				
				if ($dest[0] == "#") {
					// Channel notice.
					$wid = getWid($dest);
					$scrollback[$wid][] = $colors->getColoredString(" -".$nick.":".$dest."- ".$message, "red");
				}
				else {
					// Private notice. Forward to Status window
					if ($msg_d[0][0] == "" && $msg_d_lchar == "") {
						$scrollback['0'][] = $colors->getColoredString(" <- [".$nick." ".$ctcp." reply]: ".$ctcp_data, "light_red");
					}
					else {
						$scrollback['0'][] = $colors->getColoredString(" -".$nick."- ".$message, "red");
					}
				}
			}
			else if ($irc_data[1] == "421") {
				// IRCD Threw an error regarding a command :o
				$message = array_slice($irc_data, 4);
				$message = substr(implode(" ",$message),1);
				$scrollback[0][] = strtoupper($irc_data[3])." ".$message;
			}
			else if ($irc_data[1] == "404") {
				// 3 - chan
				$message = array_slice($irc_data, 4);
				$message = substr(implode(" ",$message),1);
				$scrollback[getWid($irc_data['3'])][] = $colors->getColoredString(" = ".$message." =","light_red");
			}
			else if ($irc_data[1] == "TOPIC") {
				$chan = $irc_data[2];
				$wid = getWid($chan);
				$ex = explode("!",$irc_data[0]);
				$nick = substr($ex[0],1);
				$message = array_slice($irc_data, 3);
				$message = substr(implode(" ",$message),1);
				$chan_topic[$wid] = $message;
				$scrollback[$wid][] = $colors->getColoredString("  * ".$nick." {$lng['TOPIC_CHANGE']} '".$message."'", "green");
			}
			else if ($irc_data[1] == "332") {
				// Topic.
				$chan = $irc_data[3];
				$wid = getWid($chan);
				$message = array_slice($irc_data, 4);
				$message = substr(implode(" ",$message),1);
				$chan_topic[$wid] = $message;
				$scrollback[$wid][] = $colors->getColoredString("  * {$lng['TOPIC_IS']} '".format_text($message)."'","green");
			}
			else if ($irc_data[1] == "333") {
				$chan = $irc_data[3];
				$wid = getWid($chan);
				$ex = explode("!",$irc_data[4]);
				$nick = $ex[0];
				$date = date(DATE_RFC822,$irc_data[5]);
				$scrollback[$wid][] = $colors->getColoredString("  * {$lng['TOPIC_BY']} ".$nick." ".$date,"green");
			}
			else if ($irc_data[1] == "MODE") {
				$chan = $irc_data[2];
				if ($chan[0] == "#") {
					$wid = getWid($chan);
				}
				else {
					$wid = "0";
				}
				$ex = explode("!",$irc_data[0]);
				$nick = substr($ex[0],1);
				$message = array_slice($irc_data,3);
				$message = implode(" ",$message);
				if ($message[0] == ":") { $message = substr($message,1); }
				if ($chan[0] == "#") {
					$nick = get_prefix($nick,$userlist[$wid]);
					// Recapture the userlist.
					$userlist[$wid] = array();
					pitc_raw("NAMES ".$chan);
					pitc_raw("MODE {$chan}");
				}
				$scrollback[$wid][] = $colors->getColoredString("  * ".$nick." {$lng['SETS_MODE']}: ".$message,"green");
			}
			else if ($irc_data[1] == "JOIN") {
				// Joined to a channel.
				// Add a new window.
				if ($irc_data[2][0] == ":") {
					$channel = substr($irc_data[2],1);
				}
				else {
					$channel = $irc_data[2];
				}
				$ex = explode("!",$irc_data[0]);
				$nick = substr($ex[0],1);
				
				// Did I join or did someone else?
				if ($nick == $cnick) {
					// I joined, Make a window.
					$wid = count($windows); // Our new ID.
					$windows[$wid] = $channel;
					pitc_raw("MODE {$channel}");
					$userlist[$wid] = array();
					$scrollback[$wid] = array($colors->getColoredString("  * {$lng['JOIN_SELF']} ".$channel,"green"));
					$active = $wid;
				}
				else {
					// Someone else did.
					$wid = getWid($channel);
					$scrollback[$wid][] = $colors->getColoredString("  * ".$nick." (".$ex[1].") {$lng['JOIN_OTHER']} ".$channel,"green");
					// Recapture the userlist.
					$userlist[$wid] = array();
					pitc_raw("NAMES ".$channel);
				}
				// API TIME!
				$args = array();
				$args['nick'] = $nick;
				$args['channel'] = strtolower($channel);
				$args['host'] = $ex[1];
				$x = 0;
				while ($x != count($api_joins)) {
					call_user_func($api_joins[$x],$args);
					$x++;
				}
			}
			else if ($irc_data[1] == "PART") {
				$channel = $irc_data[2];
				$ex = explode("!",$irc_data[0]);
				$nick = substr($ex[0],1);
				$wid = getWid($channel);
				if ($nick != $cnick) {
					if (isset($irc_data[3])) {
						$message = array_slice($irc_data, 3);
						$message = substr(implode(" ",$message),1);
						$scrollback[$wid][] = $colors->getColoredString("  * ".$nick." (".$ex[1].") {$lng['PARTED']} ".$channel." (".$message.")","green");
					}
					else {
						$scrollback[$wid][] = $colors->getColoredString("  * ".$nick." (".$ex[1].") {$lng['PARTED']} ".$channel,"green");
					}
				}
				// Repopulate the Userlist.
				$userlist[$wid] = array();
				pitc_raw("NAMES ".$channel);
				// API TIME!
				$args = array();
				$args['nick'] = $nick;
				$args['channel'] = strtolower($channel);
				$args['host'] = $ex[1];
				$args['text'] = $message;
				$args['text_array'] = explode(" ",$message);
				$x = 0;
				while ($x != count($api_joins)) {
					call_user_func($api_joins[$x],$args);
					$x++;
				}
			}
			else if ($irc_data[1] == "KICK") {
				$channel = $irc_data[2];
				$ex = explode("!",$irc_data[0]);
				$kicker = substr($ex[0],1);
				$wid = getWid($channel);
				$kicked = $irc_data[3];
				if ($kicked != $cnick) {
					if (isset($irc_data[4])) {
						$message = array_slice($irc_data, 4);
						$message = substr(implode(" ",$message),1);
						$scrollback[$wid][] = $colors->getColoredString("  * ".$kicked." {$lng['KICK_OTHER']} ".$kicker." (".$message.")","green");
					}
					else { // %5 chance of this ever been used. but hey still could be!
						$scrollback[$wid][] = $colors->getColoredString("  * ".$kicked." {$lng['KICK_OTHER']} ".$kicker,"green");
					}
				}
				else {
					// I've been kicked.
					if (isset($irc_data[4])) {
						$message = array_slice($irc_data, 4);
						$message = substr(implode(" ",$message),1);
						$scrollback['0'][] = $colors->getColoredString("  * ".$kicker." {$lng['KICK_SELF']} ".$channel." (".$message.")","green");
					}
					else {
						$scrollback['0'][] = $colors->getColoredString("  * ".$kicked." {$lng['KICK_SELF']} ".$channel,"green");
					}
					$active = 0;
					unset($windows[$wid], $scrollback[$wid],$userlist[$wid]);
					array_values($windows);
				}
			}
			else if ($irc_data[1] == "QUIT") {
				$ex = explode("!",$irc_data[0]);
				$nick = substr($ex[0],1);
				if ($nick != $cnick) {
					// Not me.
					$message = array_slice($irc_data, 2);
					$message = substr(implode(" ",$message),1);
					$scrollback[$active][] = $colors->getColoredString("  * ".$nick." (".$ex[1].") {$lng['QUIT']} (".$message.")","blue");
				}
			}
			else {
				$message = array_slice($irc_data, 3);
				$message = substr(implode(" ",$message),1);
				$scrollback['0'][] = $message;
			}
		}
	}
	usleep($refresh);
}
function pitcError($errno, $errstr, $errfile, $errline) {
	global $active,$scrollback;
	// Dirty fix to supress connection issues for now.
	if ($errline != 171) {
		$scrollback[$active][] = "PITC PHP Error: (Line ".$errline." called at ".__LINE__.") [$errno] $errstr in $errfile";
	}
}
?>