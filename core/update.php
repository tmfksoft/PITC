<?php
// Simple script.
echo "Getting PITC Update...\n";
echo "Version ".file_get_contents("http://s1.ilkotech.co.uk/pitc/latest")."\n";
file_put_contents("pitc.php",file_get_contents("http://s1.ilkotech.co.uk/pitc/pitc_latest"));
echo "Start now? Y/N\n> ";
while (1) {
	$text = trim(fgets(STDIN));
	if ($text = "y") {
		die(exec("php pitc.php"));
	}
	else if ($text = "n") {
		die("Bye!\n");
	}
	else {
		echo "Unknown answer!\n> ";
	}
}
?>