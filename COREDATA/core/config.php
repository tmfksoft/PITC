<?php
function run_config() {
	global $windows,$scrollback,$active;
	if (file_exists($_SERVER['HOME']."/.pitc/config.cfg")) { $default = load_config(); } else { $default = false; }
	// Load Config script.
	$windows['1'] = "PITC Config";
	$scrollback['1'][] = "PITC Configuration.";
	drawwindow(1);

	$lang = config_prompt($default['lang'],"lang","Language Pack EN/ES (PITC Restarted Required)");
	if ($lang) { $config[] = $lang; }
	$config[] = config_prompt($default['nick'],"nick",false);
	$config[] = config_prompt($default['altnick'],"altnick",false);
	$config[] = config_prompt($default['email'],"email",false);
	$config[] = config_prompt($default['realname'],"realname",false);
	$config[] = config_prompt($default['address'],"address","IRC Address");
	$quit = config_prompt($default['quit'],"quit","Quit Message",false);
	if ($quit) { $config[] = $quit; }
	
	$pass = config_prompt($default['password'],"password",false,false);
	if ($pass) { $config[] = $pass; }
	
	$ajoin = config_prompt($default['ajoin'],"ajoin","Channels to join on connect",false);
	if (strtolower($ajoin) != "ajoin=") {
		$config[] = $ajoin;
	}
	
	$sasl = config_prompt($default['sasl'],"sasl","Enable sasl? [Y/N]",false);
	if (strtolower($sasl) == "sasl=y") {
		$config[] = $sasl;
		$config[] = config_prompt($default['sasluser'],"sasluser","SASL Username",true);
		$config[] = config_prompt($default['saslpass'],"saslpass","SASL Password",true);
	}
	
	$scrollback['1'][] = "Please wait while your configuration is saved...";
	drawwindow(1);

	file_put_contents($_SERVER['HOME']."/.pitc/config.cfg",implode("\n",$config));
	//save_config($config);

	$scrollback['1'][] = "Done!";
	sleep(1);
	unset($windows['1'],$scrollback['1']);
	$active = 0;
}
function load_config() {
	global $scrollback;
	if (file_exists($_SERVER['HOME']."/.pitc/config.cfg")) {
		$_CONFIG = explode("\n",file_get_contents($_SERVER['HOME']."/.pitc/config.cfg"));
		$x = 0;
		while($x != count($_CONFIG)) {
			$data = explode("=",$_CONFIG[$x]);
			$_CONFIG[$data[0]] = urldecode($data[1]);
			unset($_CONFIG[$x]);
			$x++;
		}
		return $_CONFIG;
	}
	else {
		$scrollback['0'][] = "Error Loading config.";
		return false;
	}
}
function config_prompt($default = false,$item,$alias,$required = true) {
	global $scrollback;
	if (!$alias) {
		$itema = strtoupper($item[0]);
		$itemb = strtolower(substr($item,1));
		$item = $itema.$itemb;
	}
	
	$prompt = "Please enter an '{$item}'";
	$append = "";
	if ($default) {
		$append	.= ", leave blank to use \"{$default}\"";
	}
	if ($required && !$default) {
		$append .= " (Required)";
	}
	else {
		$append .= " (Optional)";
	}
	if ($alias) {
		$scrollback['1'][] = " = Please enter a '{$alias}'{$append} =";
	}
	else {
		$scrollback['1'][] = " = Please enter a '{$item}'{$append} =";
	}
	drawwindow(1);
	$input = urlencode(trim(fgets(STDIN)));
	if ($default && $input == "" && !$required) {
		return strtolower($item)."=".$default;
	}
	else if (!$default && $input == "" && $required) {
		$scrollback['1'][] = "{$item} Cannot be left blank!";
		drawwindow(1);
		config_prompt($default,$item,$alias,$required);
	}
	else {
		return strtolower($item)."=".$input;
	}
}
function save_config($array) {
	error_reporting(-1);
	//$config = array();
	//foreach ($array as $x => $data) { $config[] = $x."=".urlencode($data); }
	$r = file_put_contents($_SERVER['HOME']."/.pitc/config.cfg",implode("\n",$array));
	if (!$r) {
		die("VITAL ERROR! Unable to save configuration file.\b");
	}
	return true;
}
?>