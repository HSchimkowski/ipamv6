<?php

$id 			= valid_id($_GET['id'] ?? null);
$newDescription	= (string)($_GET['newDescription'] ?? '');
$newColor		= valid_color($_GET['newColor'] ?? null);
$newRoot		= (($_GET['newRoot'] ?? '') == '1') ? 1 : 0;

$insIp = network_from_request($_GET['newNetwork'] ?? null, $_GET['newMask'] ?? null, $_GET['format'] ?? '');
if ($id === null || $insIp === null)
	bad_request("Invalid network or netmask!");
$newMask = $insIp->myPrefix;


$q= "UPDATE `ipamv6` SET 
		`network` = ?,
		`network_bin` = ?,
		`mask` = ?,
		`color` = ?,
		`root` = ?, 
		`description` = ? 
	WHERE 
		`id` = ?;";

$res = $sql->query($q, array(
	$insIp->getMyExpandedAddress(),
	$insIp->binNetwork(),
	$newMask,
	$newColor,
	$newRoot,
	$newDescription,
	$id,
));

#header("Location: ./?action=showIpv6Route");

$mask = (floor($newMask/8) * 8);

if($mask >= 128)
	$mask=120;


header("Location: ./?action=sudoku&mask=".$mask."&network=".substr($insIp->binNetwork(), 0, $mask).str_repeat("0", (128 - $mask)));

exit();
?>
