<?php
// ADDED [WIP] in comments above buggy code.
function drawWindow($window,$input = true,$return = false) {
	// Lets draw the contents of the window... Fun
	global $windows,$scrollback,$text,$colors,$sid,$_CONFIG,$cnick,$buffer,$buffpos,$curshow,$userlist,$chan_modes,$chan_topic;
	
	if (!isset($windows[$window])) {
		var_dump($windows);
		shutdown("Script supplied invalid window ID\n");
	}
	if (!isset($scrollback[$window])) {
		var_dump($scrollback);
		shutdown("[drawWindow] Script supplied invalid (scrollback) for window ".$window." @ Line ".__LINE__."\n");
	}
	
	$data = "\n";
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
	if ($windows[$window][0] == "#") {
	
		// Channel.
		$ucount = count($userlist[$window]);
		if (isset($chan_modes[$window])) {
			$modes = $chan_modes[$window];
		}
		else {
			$modes = "(N/A)";
		}
		if (isset($chan_topic[$window])) {
			$topic = format_text($chan_topic[$window]);
		}
		else {
			$topic = " No Topic to Display! ";
		}
		// Sammich, Left and right data. Makes the maths and such easier.
		$l = "= PITC - ".$windows[$window]." [{$ucount}] [{$modes}: ";
		$r = " ] =";
		$max = $shell_cols - (strlen($l)+strlen($r))-3; // MAX Length of the topic string, take 3 for the ...
		if (strlen($topic) > $max) {
			// Topic is tooooooo long, lets chop.
			$topic = substr($topic,0,$max);
			$tbar = $l.$topic."...".$r;
			// Should be correct sized.
		}
		else {
			$tbar = $l.$topic.$r; // Slap it together.
			// Calc if we need to add some padding.
			if (strlen($tbar) < $shell_cols) {
				// We need padding,
				$pad = $shell_cols - strlen($tbar);
				$tbar .= str_repeat("=",$pad);
				// Done
			}
			// All should be done and it should be nice and padded up.
		}
		
	}
	else {
		$tbar= "= PITC - ".$windows[$window]." ";
		$equals = $shell_cols - strlen($tbar);
		$tbar .= str_repeat("=",$equals);
	}
	
	$data .= $tbar; // Add topic bar.
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
		$extra .= str_repeat("=",$shell_cols-strlen($extra));
		$data .= $extra;
		
		$left = substr($buffer, 0, $buffpos);
		$right = substr($buffer, $buffpos);
		
		if (isset($sid)) {
			$data .= "\n(".$cnick."): {$left}|{$right}";
		}
		else {
			$data .= "\n> {$left}|{$right}";
		}
	}
	else {
		// We don't care about input.
		$data .= str_repeat("=",$shell_cols);
	}
	if ($return) {
		return $data;
	}
	else {
		echo $data;
	}
}

function draw_display() {

}

function text_split($in) {
	$len = count($in);
	$a = substr($in,0,$len/2);
	$b = substr($in,0,(0-($len/2)));
	return array($a,$b);
}

function shutdown($message = "Shutdown") {
	global $sid;
	if (isset($sid)) {
		system("stty sane");
		pitc_raw("QUIT :Leaving...");
		fclose($sid);
	}
	die($message);
}

function connect($nick,$address,$port,$ssl = false,$password = false) {
	global $_CONFIG,$domain,$sasl;
	if ($ssl) { $address = "ssl://".$address; }
	$fp = @fsockopen($address,$port, $errno, $errstr, 30) or pitcError(0, "Error connecting.", "PITCCORE", "CORE+121");
	if ($fp) {
		if (isset($_CONFIG['sasl'])) {
			if (strtolower($_CONFIG['sasl']) == "y") { pitc_raw("CAP REQ :sasl",$fp); }
		}
		if ($password) { pitc_raw("PASS :".$password,$fp); }
		pitc_raw("NICK ".$nick,$fp);
		$ed = explode("@",$_CONFIG['email']);
        pitc_raw('USER '.$ed[0].' "'.$ed[1].'" "'.$address.'" :'.$_CONFIG['realname'],$fp);
		return $fp;
	}
	else {
		return false;
	}
}
function parse($rid) {
	global $scrollback,$active,$_CONFIG,$cnick,$rawlog;
	//echo "Handling bot with RID ".$rid."\n";
	if ($data = fgets($rid)) {
		$data = trim($data);
		$rawlog[] = "S: ".$data;
		flush();
		$ex = explode(' ', $data);
		if ($ex[0] == "PING") {
			pitc_raw("PONG ".$ex[1]);
		}
		else if ($ex[1] == "001") {
			$scrollback['0'][] = " = Connected to IRC! =";
			// Ajoin!
			if (isset($_CONFIG['ajoin'])) {
				$chans = explode(" ",$_CONFIG['ajoin']);
				$rawjoin = "JOIN ";
				foreach ($chans as $x => $chan) {
					if ($x != count($chans)-1) {
						$rawjoin .= "{$chan},";
					}
					else {
						$rawjoin .= $chan;
					}
				}
				pitc_raw($rawjoin);
			}
		}
		else if ($ex[1] == "433") {
			// Nick in use.
			$scrollback['0'][] = "Nick in use. Changing to alternate nick.";
			$cnick = $_CONFIG['altnick'];
			pitc_raw("NICK :".$cnick);
		}
	}
	return $data;
}
function pitc_raw($text,$sock = false) {
	global $sid,$rawlog;
	if ($sock) { $fp = $sock; }
	else { $fp = $sid; }
	$rawlog[] = "C: {$text}";
	return fputs($fp,"{$text}\n");
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
function ctcpReply($nick,$ctcp,$text) {
	global $sid;
	fputs($sid,"NOTICE ".$nick." :".$ctcp." ".$text."\n");
}
function ctcp($nick,$ctcp) {
	global $sid;
	fputs($sid,"PRIVMSG ".$nick." :".$ctcp."\n");
}
function getCtcp($ctcp) {
	global $ctcps,$version,$start_stamp;
	$ctcps['VERSION'] = "PITC v".$version." by Thomas Edwards";
	$ctcps['UPTIME'] = string_duration(time(),$start_stamp);
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
function format_text($text) {
	$text = preg_replace('/0(.*)/is', "\033[1;37m$1\033[0m", $text); // White
	$text = preg_replace('/1(.*)/is', "\033[0;30m$1\033[0m", $text); // Black
	$text = preg_replace('/2(.*)/is', "\033[0;34m$1\033[0m", $text); // Blue
	$text = preg_replace('/3(.*)/is', "\033[0;32m$1\033[0m", $text); // Green
	$text = preg_replace('/4(.*)/is', "\033[1;31m$1\033[0m", $text); // Light Red
	$text = preg_replace('/5(.*)/is', "\033[0;31m$1\033[0m", $text); // Red
	$text = preg_replace('/6(.*)/is', "\033[0;35m$1\033[0m", $text); // Purple
	$text = preg_replace('/6(.*)/is', "\033[0;35m$1\033[0m", $text); // Purple
	return $text;
}
function string_duration($a,$b) {
	$uptime = $a - $b;
	$second = floor($uptime%60);
	$minute = floor($uptime/60%60);
	$hour = floor($uptime/3600);
	$day = floor($uptime/86400);
	$week = floor($uptime/604800);
	$month = floor($uptime/2419200);
	$year = floor($uptime/31536000);
	$uptime = "{$second}seconds";
	if ($minute) { $uptime = "{$minute}minutes " . $uptime; }
	if ($hour) { $uptime = "{$hour}hours " . $uptime; }
	if ($day) { $uptime = "{$day}days " . $uptime; }
	if ($week) { $uptime = "{$week}weeks " . $uptime; }
	if ($month) { $uptime = "{$month}months " . $uptime; }
	if ($year) { $uptime = "{$year}years " . $uptime; }
	return $uptime;
}
function ircexplode($str) {
	// Contributed by grawity
    $str = rtrim($str, "\r\n");
    $str = explode(" :", $str, 2);
    $params = explode(" ", $str[0]);
    if (count($str) > 1)
        $params[] = $str[1];
    return $params;
}
function data_get($url = false) {
	if ($url) {
		$data = file_get_contents("http://announcements.pitc.x10.mx/");
		$array = json_decode($data);
		return $array;
	}
	else {
		return false;
	}
}
function is_connected() {
    $connected = @fsockopen("google.com",80); //website and port
    if ($connected) {
        $is_conn = true; //action when connected
        fclose($connected);
    }
	else {
        $is_conn = false; //action in connection failure
    }
    return $is_conn;
}

function nick_tab($nicks,$text,$tab = 0) {
	// Allows you to tab a nickname.
	// Get last letter or word.
	$data = explode(" ",$text);
	$data = $data[count($data)-1];
	echo "Searching for {$data}\n";
	
	$nicknames = array();
	foreach ($nicks as $name) {
		$nicknames[] = trim(strtolower($name),"~&@%+");
	}
	$data = strtolower($data);
	$ret = preg_grep("/^{$data}.*/", $nicknames);
	if ($ret != FALSE) {
		reset($ret);
		$key = key($ret);
		$ret = array_values($ret);
		$ret = $ret[$tab];
		echo "I found {$ret}!\n";
		return trim($nicks[$key],"~&@%+");
	}
	else {
		return false;
	}
}
function get_prefix($nick,$nicks = array()) {
	echo "Searching for {$nick}\n";
	$old = $nick;
	$nicknames = array();
	foreach ($nicks as $name) {
		$nicknames[] = strtolower($name);
	}
	$nick = strtolower($nick);
	$ret = preg_grep("/(~|&|@|\%|\+|){$nick}$/", $nicknames);
	if ($ret > 0 && $ret != FALSE) {
		reset($ret);
		$key = key($ret);
		return $nicks[$key];
	}
	else {
		return $old;
	}
}
?>