<?php

function ascii_display($input,$window = 0) {
	global $scrollback,$shell_cols,$cserver;
	foreach ($input as $line) {
		$spacing = ($shell_cols - strlen($line)) /2;
		$scrollback[$cserver][$window][] = str_repeat(" ",$spacing).$line;
	}
}
function ascii_read_file($filename) {
	return explode("\n",base64_decode(gzuncompress(file_get_contents($filename))));
}
?>