<?php
// load configfile
require_once 'config.inc.php';
require_once 'class/mysq.class.php';
require_once 'functions.php';
send_security_headers();

// start database connection
$sql = new mysql();

require_post_with_csrf();

$id = valid_id($_POST['id'] ?? null);
if ($id === null)
	bad_request("Invalid network!");

if(($_POST['subnets'] ?? '') != "1")
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
header("Location: ".local_redirect_url($_POST['url'] ?? ''));
exit();
?>