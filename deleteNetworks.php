<?php
// load configfile
require_once 'config.inc.php';
require_once 'class/mysq.class.php';
require_once 'functions.php';

// start database connection
$sql = new mysql();

$id = valid_id($_GET['id'] ?? null);
if ($id === null)
	bad_request("Invalid network!");

if(($_GET['subnets'] ?? '') != "1")
{
	$q = "DELETE FROM `ipamv6` WHERE `id`= ?;";
	$res = $sql->query($q, array($id));
}	
else
{	
	$q = "SELECT * FROM `ipamv6` WHERE `id` = ?;";
	$res = $sql->query($q, array($id));
	$row = $sql->array_result($res);

	// delete the network and all its subnets
	if ($row)
	{
		$q="DELETE FROM `ipamv6` WHERE `network_bin` LIKE ? AND `mask` >= ?;";
		$res = $sql->query($q, array(substr($row['network_bin'], 0, $row['mask'])."%", $row['mask']));
	}
}
header("Location: ".($_GET['url'] ?? './'));
exit();
?>