<?php

$id 			= $_GET['id'];
$newNetwork 	= $_GET['newNetwork'];
$newMask		= $_GET['newMask'];
$newDescription	= $_GET['newDescription'];
$newColor		= $_GET['newColor'];
$newRoot		= isset($_GET['root']) ? $_GET['root'] : 0;

$insIp = new IPv6();
if($_GET['format']=="hex")
	$insIp->initHex($newNetwork, $newMask);
else
	$insIp->initBinary($newNetwork, $newMask);


$q= "UPDATE `ipamv6` SET 
		`network` = '".$insIp->getMyExpandedAddress()."',
		`network_bin` = '".$insIp->binNetwork()."',
		`mask` = '".$newMask."',
		`color` = '".$newColor."',
		`root` = '".$newRoot."', 
		`description` = '".$newDescription."' 
	WHERE 
		`id` = ".$id.";";

$res = $sql->query($q);

#header("Location: ./?action=showIpv6Route");

$mask = (floor($newMask/8) * 8);

if($mask >= 128)
	$mask=120;


header("Location: ./?action=sudoku&mask=".$mask."&network=".substr($insIp->binNetwork(), 0, $mask).str_repeat("0", (128 - $mask)));

exit();
?>