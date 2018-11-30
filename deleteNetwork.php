<?php

$id	= $_GET['id'];

$hasSubnet = false;

$q = "SELECT * FROM `ipamv6` WHERE `id`= '".$id."';";
$res = $sql->query($q);
$row = $sql->array_result($res);

$ip = new IPv6();
$ip->initBinary($row['network_bin'], $row['mask']);


// check if there are subnets
$q="SELECT * FROM `ipamv6` WHERE `network_bin` LIKE '".substr($row['network_bin'], 0, $row['mask'])."%' AND `mask` > '".$row['mask']."' LIMIT 1;";
$res = $sql->query($q);
$row = $sql->array_result($res);
if($row['id']!="")
	$hasSubnet=true;

?>
<form action="./deleteNetworks.php" method="get">

<input type="hidden" name="id"  value="<?php echo $id; ?>">
<input type="hidden" name="url" value="<?php echo $_SERVER["HTTP_REFERER"]; ?>">
Do you really want to delete <?php echo $ip->getMyCompressedAddress(); ?>/<?php echo $ip->myPrefix; ?>?<br /><br />

<?php 
if($hasSubnet)
{?>
	<input type="checkbox" name="subnets" value="1"> also delete <b>all</b> subnets!<br /><br />
<?php }
else {?>
	<input type="hidden" name="subnets" value="0">
<?php } ?>
<input type="submit" value="yes"> <input type="button" value="cancel" onclick="history.go(-1);">
</form>
<?php 



#$q = "DELETE FROM `ipamv6` WHERE `id`='".$id."';";
#$res = $sql->query($q);

#header("Location: ".$_SERVER["HTTP_REFERER"]);

#exit();
?>