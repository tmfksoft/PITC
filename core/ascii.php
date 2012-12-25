<?php

function ascii_display($input,$window = 0) {
	global $scrollback,$shell_cols;
	foreach ($input as $line) {
		$spacing = ($shell_cols - strlen($line)) /2;
		$scrollback[$window][] = str_repeat(" ",$spacing).$line;
	}
}
function ascii_read_file($filename) {
	return explode("\n",file_get_contents($filename));
}
?>