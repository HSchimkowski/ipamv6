<?php
// load configfile
require_once 'config.inc.php';
require_once 'class/mysq.class.php';
require_once 'class/ipv6.php';
require_once 'functions.php';

$network = valid_hex_network($_REQUEST['network'] ?? null);
$mask    = valid_mask($_REQUEST['mask'] ?? null);

# Network is not valid!
if($network === null)
{
	echo "Network is not valid!";
}
elseif($mask === null)
{
	echo "netmask is not valid!";
}
else
{
	// start database connection
	$sql = new mysql();

	$ip = new IPv6();
	$ip->initHex($network, $mask);
	
	$q= "SELECT * FROM `ipamv6` WHERE `network_bin` = ? AND `mask` = ? LIMIT 1";
	$res = $sql->query($q, array($ip->binNetwork(), $ip->myPrefix));
	if($sql->num_result($res) > 0)
	{
		echo "Network already exists!<br />";
		$row = $sql->array_result($res);
		$ip->initBinary($row['network_bin'], $row['mask']);
		echo $ip->getMyCompressedAddress()."/".$ip->myPrefix." (".$row['description'].")";
	}
	else 
	{
		echo "network ".$ip->hexCompressedNetwork()."/".$ip->myPrefix." is OK!";
	}
}
?>