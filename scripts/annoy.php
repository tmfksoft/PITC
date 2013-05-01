<?php
// Annoyance script.
// Says something in a channel every 1min

$timerchan = "#tmfksoft";

// Usage: $timer->addtimer(Delay Between Each time,Repition False for infinite,Function to call,Function Args);
// It returns the timers ID.
$timerid = $timer->addtimer("1m","5","annoy",$timerchan);
if ($timerid) {
	$api->pecho("Timer Added, ID is :".$timerid);
}
else {
	$api->pecho("ERROR Adding Timer *sadface*");
}

function annoy($cn) {
	global $api;
	$api->msg($cn,"I'll annoy you 5times every 1m.");
}