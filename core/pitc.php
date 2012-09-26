<?php
/*
	#############################
	# PITC IRC TERMINAL CLIENT  #
	#    By Thomas Edwards      #
	#  COPYRIGHT TMFKSOFT 2012  #
	#############################
 */


echo "Loading...\n";
declare(ticks = 1);
stream_set_blocking(STDIN, 0);
stream_set_blocking(STDOUT, 0);
set_error_handler("pitcError");
$start_stamp = time();

if ($argv[1] == "-a") {
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

	pcntl_signal(SIGTERM, "signal_handler");
	pcntl_signal(SIGINT, "signal_handler");
	pcntl_signal(SIGHUP, "signal_handler");
	pcntl_signal(SIGUSR1, "signal_handler");
}

if (strtoupper(substr(PHP_OS, 0, 3)) != 'WIN') {
	$shell_cols = exec('tput cols');
	$shell_rows = exec('tput lines');
}
else {
	$shell_cols = "80";
	$shell_rows = "24";
}

// Init some Variables.
$version = "1.0"; // Do not change this!

// Custom CTCP's - It is advisable that you use a script to add your own!
$ctcps = array(); // LEAVE THIS LINE!
$ctcps['DONK'] = "PUT A DONK ON IT!"; //Edit if you wish.
$ctcps['UPTIME'] = "Start time: ".$start_stamp;

// Variable Inits - LEAVE THEM ALONE!
$active = "0"; // Current window being viewed.
$windows = array("Status");
$scrollback['0'] = array(" = Status window. =");
$text = "";

if (file_exists($_SERVER['PWD']."/core/config.php")) {
	include($_SERVER['PWD']."/core/config.php");
}
else {
	die("Missing core file config.php, Did you extract ALL files?\n");
}
if (file_exists($_SERVER['PWD']."/core/api.php")) {
	include($_SERVER['PWD']."/core/api.php");
}
else {
	die("Missing core file api.php, Did you extract ALL files?\n");
}

if (!file_exists($_SERVER['PWD']."/core/config.cfg")) {
	stream_set_blocking(STDIN, 1);
	run_config();
	sleep(1);
	stream_set_blocking(STDIN, 0);
}

// Load the config..
$_CONFIG = load_config();

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
$_PITC = array();

$_PITC['nick'] = $cnick;
$_PITC['altnick'] = $_CONFIG['altnick'];
$_PITC['network'] = false;
$_PITC['server'] = false;
$_PITC['address'] = false;

$scrollback['0'][] = " = Checking latest version. =";
drawWindow(0,false);
sleep(1);
$latest = file_get_contents("http://s1.ilkotech.co.uk/pitc/latest");
if ($latest > $version) {
	$scrollback['0'][] = " = There is a newer version of PITC! =";
	drawWindow(0,false);
	$scrollback['0'][] = " = Run the update script to update!";
	drawWindow(0);
	sleep(1);
}
	// Init our API
	$api = new pitcapi();
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
				}
				else {
					$scrollback[0][] = " = ERROR Automagically loading '{$scripts[$x]}' no such file! =";
				}
				drawWindow($active);
			}
		}
		//unset($scripts);
	}
	drawWindow($active);
	if ($_SERVER['TERM'] == "screen") {
		$scrollback[0][] = " = You are running on SCREEN! Just thought you may want to know that! =";
		drawWindow($active);
	}
while (1) {
	if (isset($windows[$active])) {
		if (!isset($scroll)) { $scroll = $scrollback; }
		if ($scrollback != $scroll) {
			drawWindow($active);
		}
		$scroll = $scrollback;
	}
	$x = 0;
	while ($x != count($api_tick)) {
		$args = array(); // Empty for now
		call_user_func($api_tick[$x],$args);
		$x++;
	}
	if ($_SERVER['TERM'] == "screen") {
		$screen_d = shell_exec("screen -ls");
		$screen_d = explode("\n",$screen_d);
		$x = 0;
		while ($x != count($screen_d)) {
			$data = explode(" ",$screen_d[$x]);
			if ($data[0] == $_SERVER['STY']) {
				if ($data[2] == "(Detached)") {
					if (isset($_CONFIG['screen_away']) && $_CONFIG['screen_away'] == "true") {
						if (isset($sid)) {
							fputs($sid,"NICK :".$cnick."[Away]\n");
							fputs($sid,"AWAY :I'm away. Auto away due to SCREEN being detatched.\n");
							$scrollback[0][] = " = Screen disconnection detected! =";
						}
					}
				}
			}
			$x++;
		}
	}
	/*
	if ($shell_cols != exec('tput cols') || $shell_rows != exec('tput lines')) {
		drawWindow($active);
	}
	*/
	$text = explode(" ",trim(fgets(STDIN)));

	$cmd = strtolower($text[0]);
	
	// Command Checking
	if ($cmd == "/quit") {
		if (isset($sid)) {
			$scrollback[$active][] = "Quitting!";
			if (isset($text[1])) {
				$qmsg = array_slice($text,1);
				$qmsg = implode(" ",$qmsg);
				fputs($sid,"QUIT :".$qmsg."\n");
			}
			else {
				if (isset($_CONFIG['quit'])) {
					fputs($sid,"QUIT :{$_CONFIG['quit']}\n");
				}
				else {
					fputs($sid,"QUIT :Goodbye! For now!\n");
				}
			}
			$scrollback = array($scrollback['0']);
			$windows = array("Status");
			$scrollback['0'][] = " = Disconnected use /connect to reconnect to ".$_PITC['address']." =";
			$cnick = $_CONFIG['nick'];
			$active = 0;
			fclose($sid);
			unset($sid);
			$_PITC['address'] = false;
		}
		else {
			$scrollback['0'][] = " = You're not connected to IRC! Use /exit to close the client! =";
		}
	}
	else if ($cmd == "/exit") {
		if (isset($sid)) {
			if (isset($_CONFIG['quit'])) {
				fputs($sid,"QUIT :{$_CONFIG['quit']}\n");
			}
			else {
				fputs($sid,"QUIT :Goodbye! For now!\n");
			}
		}
		die();
	}
	else if ($cmd == "^[^[[C") {
		$scrollback[$active][] = "No open channels.";
	}
	else if ($cmd == "/nick") {
		if (isset($text[1])) {
			if (!isset($sid)) {
				$_CONFIG['nick'] = $text[1];
				$cnick = $_CONFIG['nick'];
				$scrollback['0'][] = " = Nick changed to ".$text[1]." =";
			}
			else {
				fputs($sid,"NICK :".$text[1]."\n");
			}
		}
		else {
			$scrollback[$active][] = "Usage: /nick NICK";
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
				// We trust the script will do a log to say its loaded.
				drawWindow($active);
			}
			else {
				$scrollback[$active][] = " ERROR! Script file not found!";
			}
		}
		else {
			$scrollback[$active][] = " Usage: /load file";
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
	else if ($cmd == "/dumpmem") {
		$fname = time()."_dump";
		$scrollback[$active][] = "Dumping variable memory to ".$fname.".";
		$vars = print_r(get_defined_vars(),true);
		file_put_contents($fname,$vars);
		if (file_exists($fname)) {
			$scrollback[$active][] = "Dumped all variables to ".$fname.".";
		}
		else {
			$scrollback[$active][] = "Error dumping variable memory to ".$fname.".";
		}
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
					$scrollback[$active][] = $colors->getColoredString("You are already viewing ".$windows[$id], "red");
				}
				else {
					$active = $id;
					$scrollback[$active][] = $colors->getColoredString("Viewing ".$windows[$id], "red");
				}
			}
			else {
				$scrollback[$active][] = $colors->getColoredString("No such window!", "red");
			}
		}
		else {
			$scrollback[$active][] = $colors->getColoredString("Usage: /view ID/Name", "red");
		}
	}
	// End /view
	else if ($cmd == "/windows") {
		end($windows);
		$amount = key($windows);
		$amount++;
		//$amount = count($windows);
		$scrollback[$active][] = "There are ".$amount." open windows.";
		$x = 0;
		while ($x != $amount) {
			if (isset($windows[$x])) {
				$scrollback[$active][] = "\t".$x." - ".$windows[$x];
			}
			$x ++;
		}
		$scrollback[$active][] = "The currently active window is ".$active.":".$windows[$active];
	}
	else if ($cmd == "/config") {
		if (isset($sid)) {
			$scrollback[$active][] = " = You cannot run Configuration while connected. Disconnect via /quit =";
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
				fputs($sid,"PART ".$windowname." :Parting!\n");
			}
			unset($windows[$win], $scrollback[$win],$userlist[$win]);
			array_values($windows);
			$active = count($windows)-1;
		}
		else {
			$scrollback[$active][] = "Cannot close status window!";
			$scrollback[$active][] = "Use /quit to exit PITC";
		}
		
	}
	else if ($cmd == "/connect" || $autoconnect == true) {
		if ($autoconnect) {
			$scrollback[$active][] = " = Auto connecting to IRC! =";
			$autoconnect = false;
		}
		if (!isset($text[1])) {
			$scrollback[$active][] = " = Connecting to default server (".$_CONFIG['address'].") =";
			$address = $_CONFIG['address'];
		}
		else {
			$scrollback[$active][] = " = Connecting to ".$text[1];
			$address = $text[1];
		}
		$_PITC['address'] = $address;
		$address = explode(":",$address);
		if (isset($address[1]) && is_numeric($address[1])) { $port = $address[1]; }
		else { $port = 6667; }
		if (isset($text[2])) { $password = $text[2]; } else { if (isset($_CONFIG['password'])) { $password = $_CONFIG['password']; } else { $password = false; } }
		$ssl = false;
		if ($port[0] == "+") { $ssl = true; }
		$sid = connect($_CONFIG['nick'],$address[0],$port,$ssl,$password);
		stream_set_blocking($sid, 0);
		if (!$sid) {
			$scrollback[$active][] = "Error connecting to IRC Server.";
			unset($sid);
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
						$scrollback[$active][] = $colors->getColoredString(" You cannot talk in the Status window!", "red");
					}
					else {
						$action = $text;
						unset($action[0]);
						$action = implode(" ",$action);
						fputs($sid,"PRIVMSG ".$windows[$active]." :ACTION ".$action."\n");
						$scrollback[$active][] = $colors->getColoredString("* ".$cnick." ".$action,"purple");
					}
				}
				else if ($command == "join") {
					$chans = implode(" ",$params);
					fputs($sid,"JOIN :".$chans."\n");
				}
				else if ($command == "raw" || $command == "/raw") {
					$message = array_slice($text, 2);
					$message = implode(" ",$message);
					fputs($sid,$message."\n");
				}
				else if ($command == "ctcp") {
					if (!isset($params[1]) || !isset($params[2])) {
						$scrollback[$active][] = " Usage: /ctcp nick ctcp";
					}
					else {
						$scrollback[0][] = $colors->getColoredString(" -> [".$params[1]."] ".strtoupper($params[2]), "light_red");
						ctcp($params[1],strtoupper($params[2]));
					}
				}
				else if ($command == "msg") {
					// Send a message!
					if (!isset($text[1]) || !isset($text[2])) {
						$scrollback[$active][] = " Usage: /msg Nick/#Channel Message";
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
						$scrollback[$active][] = " Usage: /amsg Message";
					}
					else {
						$message = array_slice($text, 1);
						$message = implode(" ",$message);
						$x = 0;
						while ($x != key($windows)) {
							if (isset($windows[$x])) {
								if ($windows[$x][0] == "#") {									
									fputs($sid,"PRIVMSG ".$windows[$x]." :".$message."\n");
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
						$scrollback[$active][] = "Usage: /notice Nick/#Channel Message";
					}
					else {
						$target = $text[1];
						$message = array_slice($text, 2);
						$message = implode(" ",$message);
						fputs($sid,"NOTICE ".$target." :".$message."\n");
						$scrollback[$active][] = " -".$target."- -> ".$message;
					}
				}
				else if ($command == "query") {
					if (!isset($text[1])) {
						$scrollback[$active][] = "Usage /query nick";
					}
					else {
						if ($text[1][0] == "#") {
							$scrollback[$active][] = "To talk in a channel /join it and talk!";
						}
						else {
							$wid = getWid($text[1]);
							if (!$wid) {
								// Open a new window.
								$windowid = count($windows);
								$windows[] = $text[1];
								$scrollback[$windowid] = array(" = Opened query to ".$text[1]);
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
						$fnct = $api_commands[strtolower($command)];
						$args = explode(" ",$tentered);
						$args['active'] = $windows[$active];
						call_user_func($fnct,$args);
					}
					else {
						fputs($sid,$tentered."\n");
					}
				}
			}
			else {
				if ($active == "0") {
					$scrollback[$active][] = $colors->getColoredString("You cannot talk in the Status window!", "red");
				}
				else {
					//$scrollback[$active][] = "Sending message to ".$active.":".$windows['0']; // DEBUG
					fputs($sid,"PRIVMSG ".$windows[$active]." :".$entered."\n");
					$scrollback[$active][] = " <".$cnick."> ".$entered;
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
				$scrollback[$active][] = "Unknown Command!";
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
				$x = 0;
				while ($x != count($api_connect)) {
					$args = array(); // Empty for now
					call_user_func($api_connect[$x],$args);
					$x++;
				}
				$_PITC['network'] = $irc_data[1];
			}
			else if ($irc_data[1] == "353") {
				// User list :3
				$users = array_slice($irc_data,5);
				$chan = $irc_data[4];
				$users[0] = substr($users[0],1);
				$scrollback[getWid($channel)][] = $colors->getColoredString(" [ ".implode(" ",uListSort($users))." ]","cyan");
				$userlist[getWid($channel)][] = uListSort($users);
				array_values($userlist[getWid($channel)]);
			}
			else if ($irc_data[1] == "PRIVMSG") {
				$ex = explode("!",$irc_data[0]);
				$source = substr($ex[0],1);
				$target = $irc_data[2];
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
					$scrollback[$active][] = $colors->getColoredString(" = Message in Window [".$wid.":".$win."] from ".$source." = ","cyan");
					// Get the new id.
				}

				$words = explode(" ",$message);
				// Last Char
				$sc = implode(" ",$words);
				$length = strlen($sc);
				$lchar = $sc[$length-1];
				// Figure out if its an action or not. -.-
				// 
				if ($words[0] == "ACTION" && $lchar == "" && !$isctcp) {
					// ACTION!
					unset($words[0]);
					$words_string = trim(implode(" ",$words),"");
					// Check for Highlight!
					if (isHighlight($words_string,$cnick)) {
						// Highlight!
						$scrollback[$wid][] = $colors->getColoredString("* ".$source." ".$words_string,"yellow");
						if ($active != $wid) {
							$scrollback[$active][] = $colors->getColoredString(" = ".$source." highlighted you in ".$win." = ","cyan");
							ringBell();
						}
					}
					else {
						$scrollback[$wid][] = $colors->getColoredString("* ".$source." ".$words_string,"purple");
					}
					// API TIME!
					$args = array();
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
						// Message!
						// Check for highlight!
						//$scrollback[$wid][] = $cnick." ".$_CONFIG['nick']." ".stripos($message,$cnick)." ".stripos($message,$_CONFIG['nick']); // H/L Debug
						if (isHighlight($message,$cnick)) {
							// Highlight!
							$scrollback[$wid][] = $colors->getColoredString(" <".$source."> ".$message,"yellow");
							if ($active != $wid) {
								$scrollback[$active][] = $colors->getColoredString(" = ".$source." highlighted you in ".$win." = ","cyan");
								ringBell();
							}
						}
						else {
							$scrollback[$wid][] = " <".$source."> ".$message;
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
					$string = $colors->getColoredString("  * ".$nick." is now known as ".$nnick, "green");
				}
				else {
					$string = $colors->getColoredString("  * You are now known as ".$nnick, "green");
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
						$scrollback[$x][] = $colors->getColoredString(" = Disconnected from IRC! Use /connect ".$_PITC['address']." to reconnect. =","blue");
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
				$scrollback[$wid][] = $colors->getColoredString("  * ".$nick." changes to topic to '".$message."'", "green");
			}
			else if ($irc_data[1] == "332") {
				// Topic.
				$chan = $irc_data[3];
				$wid = getWid($chan);
				$message = array_slice($irc_data, 4);
				$message = substr(implode(" ",$message),1);
				$scrollback[$wid][] = $colors->getColoredString("  * Topic is '".$message."'","green");
			}
			else if ($irc_data[1] == "333") {
				$chan = $irc_data[3];
				$wid = getWid($chan);
				$ex = explode("!",$irc_data[4]);
				$nick = $ex[0];
				$date = date(DATE_RFC822,$irc_data[5]);
				$scrollback[$wid][] = $colors->getColoredString("  * Set by ".$nick." ".$date,"green");
			}
			else if ($irc_data[1] == "MODE") {
				$chan = $irc_data[2];
				$wid = getWid($chan);
				$ex = explode("!",$irc_data[0]);
				$nick = substr($ex[0],1);
				$message = array_slice($irc_data,3);
				$message = implode(" ",$message);
				if ($message[0] == ":") { $message = substr($message,1); }
				$scrollback[$wid][] = $colors->getColoredString("  * ".$nick." sets mode: ".$message,"green");
			}
			else if ($irc_data[1] == "JOIN") {
				// Joined to a channel.
				// Add a new window.
				$channel = substr($irc_data[2],1);
				$ex = explode("!",$irc_data[0]);
				$nick = substr($ex[0],1);
				
				// Did I join or did someone else?
				if ($nick == $cnick) {
					// I joined, Make a window.
					$wid = count($windows); // Our new ID.
					$windows[$wid] = $channel;
					$userlist[$wid] = array();
					$scrollback[$wid] = array($colors->getColoredString("  * Now chatting in ".$channel,"green"));
					$active = $wid;
				}
				else {
					// Someone else did.
					$wid = getWid($channel);
					$scrollback[$wid][] = $colors->getColoredString("  * ".$nick." (".$ex[1].") has joined ".$channel,"green");
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
						$scrollback[$wid][] = $colors->getColoredString("  * ".$nick." (".$ex[1].") has parted ".$channel." (".$message.")","green");
					}
					else {
						$scrollback[$wid][] = $colors->getColoredString("  * ".$nick." (".$ex[1].") has parted ".$channel,"green");
					}
				}
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
						$scrollback[$wid][] = $colors->getColoredString("  * ".$kicked." has been kicked by ".$kicker." (".$message.")","green");
					}
					else {
						// %5 chance of this ever been used. but hey still could be!
						$scrollback[$wid][] = $colors->getColoredString("  * ".$kicked." has been kicked by ".$kicker,"green");
					}
				}
				else {
					// I've been kicked.
					if (isset($irc_data[4])) {
						$message = array_slice($irc_data, 4);
						$message = substr(implode(" ",$message),1);
						$scrollback['0'][] = $colors->getColoredString("  * ".$kicker." kicked you from ".$channel." (".$message.")","green");
					}
					else {
						$scrollback['0'][] = $colors->getColoredString("  * ".$kicked." kicked you from ".$channel,"green");
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
					$scrollback[$active][] = $colors->getColoredString("  * ".$nick." (".$ex[1].") quit (".$message.")","blue");
				}
			}
			else {
				$message = array_slice($irc_data, 3);
				$message = substr(implode(" ",$message),1);
				$scrollback['0'][] = $message;
			}
		}
	}
	usleep(25000);
}

function drawWindow($window,$input = true) {
	// Lets draw the contents of the window... Fun
	global $windows,$scrollback,$text,$colors,$sid,$_CONFIG,$cnick;
	
	if (!isset($windows[$window])) {
		var_dump($windows);
		die("Script supplied invalid window ID\n");
	}
	if (!isset($scrollback[$window])) {
		var_dump($scrollback);
		die("Script supplied invalid (scrollback) for window ".$window."\n");
	}
	
	$data = "";
	if (strtoupper(substr(PHP_OS, 0, 3)) != 'WIN') {
		$shell_cols = exec('tput cols');
		$shell_rows = exec('tput lines');
	}
	else {
		$shell_cols = "80";
		$shell_rows = "24";
	}
	// Cater for overspill!
	$x = 0;
	$spill = 0;
	while ($x != count($scrollback[$window])) {
		if (strlen($scrollback[$window][$x]) > $shell_cols-1) {
			$spill++;
		}
		$x++;
	}
	
	// Top Bar.
	$data .= "\n= PITC - ".$windows[$window]." ";
	$data .= str_repeat("=",$shell_cols-strlen("= pIRC - ".$windows[$window]." "));
	$empty = $shell_rows-3; // Amount of lines to fill with Scrollback or \n
	$scroll = count($scrollback[$window]); // Amount of lines in scrollback.
	
	if ($scroll < $empty) {
		$ns = $empty-$scroll+1; // Amount of \n's to show.
		$data .= str_repeat("\n",$ns);
	}
	if ($input == false) {
		$data .= "\n";
	}
	// Now to show the amount of SCROLLBACK lines we need.
	// First determine how many CAN be shown.
	if ($scroll < $empty) {
		$showable = $empty-($empty-$scroll); // If I do my maths right thats how many are left.
	}
	else {
		$showable = $empty; // Fill all avaliable lines.
	}
	$showable - $spill;
	$x = 0;
	$text = $scrollback[$window];
	while ($x != $showable) {
		$line = $scroll - $showable + $x;
		$line = $text[$line];
		$a = 0;
		$a_text = "";
		while ($a < strlen($line)) {
			$a_text .= $line[$a];
			$a++;
		}
		$data .= $a_text."\n";
		$x++;
	}
	if ($input == true) {
		$extra = "= [".$window.":".$windows[$window]."] ";
		$data .= $extra;
		$data .= str_repeat("=",$shell_cols-strlen($extra));
		if (isset($sid)) {
			$data .= "(".$cnick."): ";
		}
		else {
			$data .= "> ";
		}
	}
	else {
		// We don't care about input.
		$data .= str_repeat("=",$shell_cols);
	}
	echo $data;
}

function signal_handler($signal) {
	global $sid;
	if (isset($sid)) {
		fputs($sid,"QUIT :Leaving... (".$signal.")\n");
		fclose($sid);
	}
	die();
}

function connect($nick,$address,$port,$ssl = false,$password = false) {
	global $_CONFIG,$domain;
	if ($ssl) { $address = "ssl://".$address; }
	$fp = fsockopen($address,$port, $errno, $errstr, 30);
	if ($fp) {
		if (!fputs($fp,"NICK ".$nick."\n")) { die("ERROR WRITING NICK"); }
		$ed = explode("@",$_CONFIG['email']);
        if (!fputs($fp,'USER '.$ed[0].' "'.$ed[1].'" "'.$address.'" :'.$_CONFIG['realname'].chr(10))) { die("ERROR WRITING USER"); }
		if ($password) { if (!fputs($fp,"PASS :".$password."\n")) { die("ERROR WRITING PASS"); } }
		return $fp;
	}
	else {
		return false;
	}
}
function parse($rid) {
	global $scrollback,$active,$_CONFIG,$cnick;
	//echo "Handling bot with RID ".$rid."\n";
	if ($data = fgets($rid)) {
		$data = trim($data);
		flush();
		$ex = explode(' ', $data);
		if ($ex[0] == "PING") {
			fputs($rid,"PONG ".$ex[1]."\n");
		}
		else if ($ex[1] == "001") {
			$scrollback['0'][] = " = Connected to IRC! =";
		}
		else if ($ex[1] == "433") {
			// Nick in use.
			$scrollback['0'][] = "Nick in use. Changing to alternate nick.";
			$cnick = $_CONFIG['altnick'];
			fputs($rid,"NICK :".$cnick."\n");
		}
	}
	return $data;
}

function load_script($file) {
	global $scrollback;
	if (file_exists($file)) {
		$res = include($file);
		if ($res) {
			$scrollback['0'][] = " = Loaded script '".$file."' = ";
		}
		else {
			$scrollback['0'][] = " = Error loading script '".$file."' = - ".$res;
		}
	}
	else {
		$scrollback['0'][] = " = Error loading script '".$file."' = - File does not exist.";
	}
}
function getWid($name) {
	global $windows;
	$wins = array_map('strtolower', $windows);
	$id = array_search(strtolower($name), $wins);
	return $id;
}
function pitcError($errno, $errstr, $errfile, $errline) {
	global $active,$scrollback;
	$scrollback[$active][] = "PITC PHP Error: (".$errline.") [$errno] $errstr";
}
function ctcpReply($nick,$ctcp,$text) {
	global $sid;
	fputs($sid,"NOTICE ".$nick." :".$ctcp." ".$text."\n");
}
function ctcp($nick,$ctcp) {
	global $sid;
	fputs($sid,"PRIVMSG ".$nick." :".$ctcp."\n");
}
function getCtcp($ctcp) {
	global $ctcps,$version;
	$ctcps['VERSION'] = "PITC v".$version." by Thomas Edwards";
	$ctcp = strtoupper($ctcp);
	if (isset($ctcps[$ctcp])) {
		return $ctcps[$ctcp];
	}
	else {
		return false;
	}
}
function ringBell() {
	echo chr(7);
}
function isHighlight($text,$nick) {
	if (is_array($text)) { $text = implode(" ",$text); }
	$nick = preg_quote($nick);
	return preg_match("/".$nick."/i", $text);
}
function pitcEval($text) {
	return $text;
}
function uListSort($users) {
	// Sorts all users depending on their Symbol.
	if (!is_array($users)) $users = explode(" ",$users);
	$owners = array();
	$owners_other = array(); // For '!' founders.
	$admins = array();
	$ops = array();
	$hops = array();
	$voices = array();
	$none = array();
	$x = 0;
	while ($x != count($users)) {
		$n = $users[$x];
		if ($n[0] == "~") {
			// Owner.
			$owners[] = $n;
		}
		else if ($n[0] == "!") {
			// Owner v2
			$owners_other[] = $n;
		}
		else if ($n[0] == "&") {
			// Admin
			$admins[] = $n;
		}
		else if ($n[0] == "@") {
			// Op
			$ops[] = $n;
		}
		else if ($n[0] == "%") {
			// Halfop
			$hops[] = $n;
		}
		else if ($n[0] == "+") {
			// Voice
			$voices[] = $n;
		}
		else {
			// None.
			$none[] = $n;
		}
		$x++;
	}
	natcasesort($owners);
	natcasesort($owners_other);
	natcasesort($admins);
	natcasesort($ops);
	natcasesort($hops);
	natcasesort($voices);
	natcasesort($none);
	$ulist = array_merge($owners,$owners_other,$admins,$ops,$hops,$voices,$none);
	$ulist = array_values($ulist);
	return $ulist;
}
// Userlist system.
?>