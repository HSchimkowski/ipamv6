<?php

$newDescription	= (string)($_GET['newDescription'] ?? '');
$newColour		= valid_color($_GET['newColour'] ?? null);
$newRoot		= (($_GET['root'] ?? '') == '1') ? 1 : 0;

$insIp = network_from_request($_GET['newNetwork'] ?? null, $_GET['newMask'] ?? null, $_GET['format'] ?? '');
if ($insIp === null)
	bad_request("Invalid network or netmask!");
$newMask = $insIp->myPrefix;

$q = "INSERT INTO `ipamv6` (
			`network` , 
			`network_bin` , 
			`mask` , 
			`color` , 
			`description`,
			`root`) 
		VALUES (?, ?, ?, ?, ?, ?);";

$res = $sql->query($q, array(
	$insIp->getMyExpandedAddress(),
	$insIp->binNetwork(),
	$insIp->myPrefix,
	$newColour,
	$newDescription,
	$newRoot,
));

$mask = (floor($newMask/8) * 8);

if($mask >= 128)
	$mask=120;

header("Location: ./?action=sudoku&mask=".$mask."&network=".substr($insIp->binNetwork(), 0, $mask).str_repeat("0", (128 - $mask)));

exit();
?>
