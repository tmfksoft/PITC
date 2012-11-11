<?php
// A total REWRITE, Use update(true) to force update else update() to practically install.
// If you use update() it's good for getting missing files.
function update($force = false) {
	// Check for internet connection.
	if (!file_get_contents("http://update.pitc.x10.mx/")) {
		die("Unable to connect to the update service.");
	}

	$index = explode("\n",file_get_contents("http://update.pitc.x10.mx/?action=index"));

	foreach ($index as $file) {
		// Let's get files and such
		$file = trim($file,"\r");
		$data = explode(" ",$file);
		if ($data[0] == "D") {
			if (!file_exists($data[1])) {
				mkdir($data[1]);
				echo "Made directory {$data[1]}\n";
			}
			else {
				echo "Directory {$data[1]} exists.\n";
			}
		}
		else if ($data[0] == "F") {
			if (!file_exists($data[1])) {
				file_put_contents($data[1],file_get_contents("http://update.pitc.x10.mx/?action=get&file={$data[1]}"));
				echo "Retrieved file {$data[1]}\n";
			}
			else {
				// Comparing files.
				$mde = md5(file_get_contents($data[1]));
				$mdu = md5("http://update.pitc.x10.mx/?action=get&file={$data[1]}");
				if ($mde != $mdu) {
					if ($force) {
						file_put_contents($data[1],file_get_contents("http://update.pitc.x10.mx/?action=get&file={$data[1]}"));
						echo "Overwrote {$data[1]}\n";
					}
					else {
						echo "{$data[1]}: File exists, Not overwritten.";
					}
				}
				else {
					echo "{$data[1]}: File exists, No need to overwrite.\n";
				}
			}
		}
	}
	echo "PITC Updated!\n";
}
?>