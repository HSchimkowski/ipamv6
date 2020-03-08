<?php
// load configfile
require_once 'config.inc.php';
require_once 'class/mysq.class.php';

// start database connection
$sql = new mysql("localhost","user","passwort","datenbank"); 

if($_GET['subnets']!="1")
{
	$q = "DELETE FROM `ipamv6` WHERE `id`='".$_GET['id']."';";
	$res = $sql->query($q);	
}	
else
{	
	$q = "SELECT * FROM `ipamv6` WHERE `id` = '".$_GET['id']."';";
	$res = $sql->query($q);
	$row = $sql->array_result($res);

	// check if there are subnets
	$q="DELETE FROM `ipamv6` WHERE `network_bin` LIKE '".substr($row['network_bin'], 0, $row['mask'])."%' AND `mask` >= '".$row['mask']."';";
	$res = $sql->query($q);
	#$row = $sql->array_result($res);
}
header("Location: ".$_GET['url']);
exit();
?>