<?php
class pitcapi {
	public function log($text = false) {
		global $scrollback,$cserver;
		if (!$text) {
			die("Error. Missing TEXT in function LOG");
		}
		else {
			$scrollback[$cserver]['0'][] = $text;
		}
	}
	public function addCommand($command = false,$function = false) {
		global $api_commands, $scrollback,$active;
		if (!$command) {
			$scrollback['0'][] = " ERROR. Missing COMMAND in function ADDCOMMAND";
		}
		else if (!$function) {
			$scrollback['0'][] = " ERROR. Missing FUNCTION in function ADDCOMMAND";
		}
		else {
			$command = strtolower($command);
			$api_commands[$command] = strtolower($function);
		}
	}
	public function addTextHandler($function = false) {
		global $api_messages,$scrollback,$active;
		if (!$function) {
			$scrollback['0'][] = " ERROR. Missing FUNCTION in function ADDTEXTHANDLER";
		}
		else {
			$api_messages[] = strtolower($function);
		}
	}
	public function addConnectHandler($function = false) {
		global $api_connect,$scrollback,$active;
		if (!$function) {
			$scrollback['0'][] = " ERROR. Missing FUNCTION in function ADDCONNECTHANDLER";
		}
		else {
			$api_connect[] = strtolower($function);
		}
	}
	public function addActionHandler($function = false) {
		global $api_actions,$scrollback,$active;
		if (!$function) {
			$scrollback['0'][] = " ERROR. Missing FUNCTION in function ADDACTIONHANDLER";
		}
		else {
			$api_actions[] = strtolower($function);
		}
	}
	public function addStartHandler($function = false) {
		global $api_start,$scrollback,$active;
		if (!$function) {
			$scrollback['0'][] = " ERROR. Missing FUNCTION in function ADDSTARTHANDLER";
		}
		else {
			$api_start[] = strtolower($function);
		}
	}
	public function addJoinHandler($function = false) {
		global $api_joins,$scrollback,$active;
		if (!$function) {
			$scrollback['0'][] = " ERROR. Missing FUNCTION in function ADDJOINHANDLER";
		}
		else {
			$api_joins[] = strtolower($function);
		}
	}
	public function addPartHandler($function = false) {
		global $api_parts,$scrollback,$active;
		if (!$function) {
			$scrollback['0'][] = " ERROR. Missing FUNCTION in function ADDPARTHANDLER";
		}
		else {
			$api_parts[] = strtolower($function);
		}
	}
	public function addTickHandler($function = false) {
		global $api_tick,$scrollback,$active;
		if (!$function) {
			$scrollback['0'][] = " ERROR. Missing FUNCTION in function ADDTICKHANDLER";
		}
		else {
			$api_tick[] = strtolower($function);
		}
	}
	public function addRawHandler($function = false) {
		global $api_raw,$scrollback,$active;
		if (!$function) {
			$scrollback['0'][] = " ERROR. Missing FUNCTION in function ADDRAWHANDLER";
		}
		else {
			$api_raw[] = strtolower($function);
		}
	}
	// Now we add the commands.
	public function pecho($text = false,$window = false,$server = 0) {
		global $scrollback,$active;
		if (!$text) {
			$scrollback[$server]['0'][] = " ERROR. Missing TEXT in function PECHO";
		}
		else {
			if (!$window) {
				$scrollback[$server][$active][] = $text;
			}
			else {
				if (!is_numeric($window)) {
					$window = getWid($server,$window);
				}
				$scrollback[$server][$window][] = $text;
			}
		}
	}
	public function msg($channel = false,$text = false) {
		global $scrollback, $sid, $cnick,$cserver;
		if (!$channel) {
			$scrollback[$cserver]['0'][] = " ERROR. Missing TEXT in function MSG";
		}
		else if (!$text) {
			$scrollback[$cserver]['0'][] = " ERROR. Missing TEXT in function MSG";
		}
		else {
			if ($sid) {
				fputs($sid,"PRIVMSG ".$channel." :".$text."\n");
				$scrollback[$cserver][getWid($cserver,$channel)][] = " <".$cnick."> ".$text;
			}
			else {
				$scrollback[$cserver]['0'][] = " = You are not connected to IRC! =";
			}
		}
	}
	public function notice($channel = false,$text = false) {
		global $scrollback, $sid, $cnick;
		if (!$channel) {
			$scrollback['0'][] = " ERROR. Missing TEXT in function NOTICE";
		}
		else if (!$text) {
			$scrollback['0'][] = " ERROR. Missing TEXT in function NOTICE";
		}
		else {
			if ($sid) {
				fputs($sid,"NOTICE ".$channel." :".$text."\n");
				$scrollback[getWid($channel)][] = " -".$cnick."- -> ".$text;
			}
			else {
				$scrollback['0'][] = " = You are not connected to IRC! =";
			}
		}
	}
	public function action($channel = false,$text = false) {
		global $scrollback, $colors, $sid, $cnick;
		if (!$channel) {
			$scrollback['0'][] = " ERROR. Missing TEXT in function ACTION";
		}
		else if (!$text) {
			$scrollback['0'][] = " ERROR. Missing TEXT in function ACTION";
		}
		else {
			if ($sid) {
				fputs($sid,"PRIVMSG ".$channel." :ACTION ".$text."\n");
				$scrollback[getWid($channel)][] = $colors->getColoredString("* ".$cnick." ".$text,"purple");
			}
			else {
				$scrollback['0'][] = " = You are not connected to IRC! =";
			}
		}
	}
	public function quit($message = "Goodbye! For now!") {
		global $sid;
		if ($sid) {
			fputs($sid,"QUIT :".$message."\n");
			fclose($sid);
		}
		die();
	}
	public function part($channel = false,$message = "Parting!") {
		global $sid,$scrollback;
		if ($sid) {
			fputs($sid,"PART ".$channel." :".$message."\n");
		}
		else {
			$scrollback['0'][] = " = You are not connected to IRC! =";
		}
	}
	public function join($channel = false) {
		global $sid,$scrollback;
		if ($sid) {
			fputs($sid,"JOIN ".$channel."\n");
		}
		else {
			$scrollback['0'][] = " = You are not connected to IRC! =";
		}
	}
	public function nick($nick = false) {
		global $sid,$scrollback;
		if ($nick == false) {
			$scrollback['0'][] = " ERROR. Missing NICK in function NICK";
		}
		else {
			if ($sid) {
				fputs($sid,"NICK :".$nick."\n");
			}
			$_CONFIG['nick'] = $nick;
		}
	}
	public function raw($text = false) {
		global $sid,$scrollback;
		if ($sid) {
			fputs($sid,$text."\n");
		}
		else {
			$scrollback['0'][] = " = You are not connected to IRC! =";
		}
	}
	public function mode($chan = false,$mode = false) {
		global $sid,$scrollback;
		if (!$chan) {
			$scrollback['0'][] = " ERROR. Missing CHANNEL in function MODE";
		}
		else if (!$mode) {
			$scrollback['0'][] = " ERROR. Missing MODE(S) in function MODE";
		}
		else {
			if ($chan[0] == "#") {
				if ($sid) {
					fputs($sid,"MODE {$chan} {$mode}\n");
				}
				else {
					$scrollback['0'][] = " = You are not connected to IRC! =";
				}
			}
			else {
				$scrollback['0'][] = " ERROR. Invalid CHANNEL in function MODE";
			}
		}
	}
	public function ctcp($nick = false,$ctcp = false) {
		global $sid,$scrollback;
		if (!$nick) {
			$scrollback['0'][] = " ERROR. Missing NICK in function CTCP";
		}
		else if (!$ctcp) {
			$scrollback['0'][] = " ERROR. Missing CTCP in function CTCP";
		}
		else {
			if ($sid) {
				ctcp($nick,$ctcp);
			}
			else {
				$scrollback['0'][] = " = You are not connected to IRC! =";
			}
		}
	}
	public function topic($chan = false,$text = false) {
		global $sid,$scrollback;
		if (!$chan) {
			$scrollback['0'][] = " ERROR. Missing CHANNEL in function TOPIC";
		}
		else if (!$ctcp) {
			$scrollback['0'][] = " ERROR. Missing CHANNEL in function TOPIC";
		}
		else {
			if ($sid) {
				fputs($sid,"TOPIC {$chan} :{$text}\n");
			}
			else {
				$scrollback['0'][] = " = You are not connected to IRC! =";
			}
		}
	}
	public function ctcpreply($nick = false,$ctcp = false,$text = false) {
		global $sid,$scrollback;
		if (!$nick) {
			$scrollback['0'][] = " ERROR. Missing NICK in function CTCPREPLY";
		}
		else if (!$ctcp) {
			$scrollback['0'][] = " ERROR. Missing CTCP in function CTCPREPLY";
		}
		else if (!$text) {
			$scrollback['0'][] = " ERROR. Missing TEXT in function CTCPREPLY";
		}
		else {
			if ($sid) {
				ctcpreply($nick,$ctcp,$text);
			}
			else {
				$scrollback['0'][] = " = You are not connected to IRC! =";
			}
		}
	}
	// Window Control
	public function addWindow($name) {
		global $windows,$userlist,$scrollback,$active;
		if (!getWid($name)) {
			$wid = count($windows); // Our new ID.
			$windows[$wid] = $name;
			$userlist[$wid] = array();
			$scrollback[$wid] = array();
			$active = $wid;
			return $wid;
		}
		else {
			return false;
		}
	}
	public function delWindow($name) {
		global $windows,$userlist,$scrollback,$active;
		if ($name != "0") {
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
			unset($windows[$win], $scrollback[$win],$userlist[$win]);
			array_values($windows);
			$active = count($windows)-1;
			return true;
		}
		else {
			return false;
		}
	}
	public function checkWindow($id) {
		global $windows;
		if (!is_numeric($id)) {
			$id = getWid($id);
		}
		if (isset($windows[$id])) {
			return true;
		}
		else {
			return false;
		}
	}
	public function color($col,$text) {
		return "".$col.$text."";
	}
	public function colour($col,$text) {
		return "".$col.$text."";
	}
	public function bold($text) {
		return "".$text."";
	}
	public function italic($text) {
		return "".$text."";
	}
}
?>