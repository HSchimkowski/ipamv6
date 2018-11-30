<?php
// load configfile
require_once 'config.inc.php';
require_once 'class/mysq.class.php';
require_once 'class/ipv6.php';
require_once 'functions.php';

// start database connection
$sql = new mysql("localhost","user","passwort","datenbank");


$new_mask = $_GET['mask'];
$new_root = $_GET['subnet'];

// "Root" network
$rootNetwork = new IPv6();
$rootNetwork->initBinary($new_root, $new_mask);

// dont allow cache
header("Pragma: no-cache");

// we must know:
// - Network Mask (not Every Mask is Possible!)
// - description
// - color


############
// detect possible network Masks

// Example:
// we got a /40 root Network (2001:db8:1000::/40),
// whitch is in bary form:
// 0010000000000001 0000110110111000 0001000000000000 0000000000000000 0000000000000000 0000000000000000 0000000000000000 0000000000000000
// |<--           network                -->||<---->| |<--          boring zeroes :)                                                  -->|
//                                               |--our interesting Part for subnetting
//
// from Our selection (+ sign) we got the Network-Part for Subnetting in barary form:
// 0010000000000001 0000110110111000 0001000011111111 0000000000000000 0000000000000000 0000000000000000 0000000000000000 0000000000000000
//
// now we can decide what Network Masks are Possible, based on the submitted network.
// we focus only on the interesting 8 bits (11111111). Lets store them into the variable $n
$n = substr($new_root, $new_mask, 8);

$i=0;
/*
 * @ToDo: Prüfen ob es nicht schon Netze in diesem Bereich gubt!
*/

// now we can decide possible network masks and store them in an array $m
#if($n[0]=="0" && $n[1]=="0" && $n[2]=="0" && $n[3]=="0" && $n[4]=="0" && $n[5]=="0" && $n[6]=="0" && $n[7]=="0")
	#	$m[] =($new_mask + 0);

if($n[1]=="0" && $n[2]=="0" && $n[3]=="0" && $n[4]=="0" && $n[5]=="0" && $n[6]=="0" && $n[7]=="0")
	if(!existNetwork($new_root, $new_mask + 1))
		$i++;

if($n[2]=="0" && $n[3]=="0" && $n[4]=="0" && $n[5]=="0" && $n[6]=="0" && $n[7]=="0")
	if(!existNetwork($new_root, $new_mask + 2))
		$i++;

if($n[3]=="0" && $n[4]=="0" && $n[5]=="0" && $n[6]=="0" && $n[7]=="0")
	if(!existNetwork($new_root, $new_mask + 3))
		$i++;

if($n[4]=="0" && $n[5]=="0" && $n[6]=="0" && $n[7]=="0")
	if(!existNetwork($new_root, $new_mask + 4))
		$i++;

if($n[5]=="0" && $n[6]=="0" && $n[7]=="0")
	if(!existNetwork($new_root, $new_mask + 5))
		$i++;

if($n[6]=="0" && $n[7]=="0")
	if(!existNetwork($new_root, $new_mask + 6))
		$i++;

if($n[7]=="0")
	if(!existNetwork($new_root, $new_mask + 7))
		$i++;

echo $i;

?>
