<?php

require_post_with_csrf();

$newDescription	= (string)($_POST['newDescription'] ?? '');
$newColour		= valid_color($_POST['newColour'] ?? null);
$newRoot		= (($_POST['root'] ?? '') == '1') ? 1 : 0;

$insIp = network_from_request($_POST['newNetwork'] ?? null, $_POST['newMask'] ?? null, $_POST['format'] ?? '');
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
