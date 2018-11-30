<?php

$newNetwork 	= $_GET['newNetwork'];
$newMask		= $_GET['newMask'];
$newDescription	= $_GET['newDescription'];
$newColour		= $_GET['newColour'];
$newRoot		= $_GET['root'];

$insIp = new IPv6();

if($_GET['format'] == "hex")
	$insIp->initHex($newNetwork, $newMask);
else
	$insIp->initBinary($newNetwork, $newMask);

$q = "INSERT INTO `ipamv6` (
			`network` , 
			`network_bin` , 
			`mask` , 
			`color` , 
			`description`,
			`root`) 
		VALUES (
			'".$insIp->getMyExpandedAddress()."',
			'".$insIp->binNetwork()."',
			'".$insIp->myPrefix."',
			'".$newColour."',
			'".$newDescription."',
			'".$newRoot."'
			);";

$res = $sql->query($q);

$mask = (floor($newMask/8) * 8);

if($mask >= 128)
	$mask=120;

header("Location: ./?action=sudoku&mask=".$mask."&network=".substr($insIp->binNetwork(), 0, $mask).str_repeat("0", (128 - $mask)));

exit();
?>