<?php

// Nur einfache Fehler melden
error_reporting(E_ERROR | E_PARSE);

# Network is not valid!
if( inet_pton($_REQUEST['network']) == false)
{
	echo "Network is not valid!";
}
elseif($_REQUEST['mask']<0 || $_REQUEST['mask']>128)
{
	echo "netmask is not valid!";
}
else
{
	// load configfile
	require_once 'config.inc.php';
	require_once 'class/mysq.class.php';
	require_once 'class/ipv6.php';
	require_once 'functions.php';
	// load Session cookie
	
	// start database connection
	$sql = new mysql("localhost","user","passwort","datenbank");

	$ip = new IPv6();
	$ip->initHex($_REQUEST['network'], $_REQUEST['mask']);
	
	$q= "SELECT * FROM `ipamv6` WHERE `network_bin` = '".$ip->binNetwork()."' AND `mask` = '".$ip->myPrefix."' LIMIT 1";
	$res = $sql->query($q);
	if($sql->num_result(NULL) > 0)
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