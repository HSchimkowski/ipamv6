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

<form action="./deleteNetworks.php" method="get" class="delete-network-form">
    <input type="hidden" name="id" value="<?php echo $id; ?>">
    <input type="hidden" name="url" value="<?php echo $_SERVER["HTTP_REFERER"]; ?>">
    
    <div class="confirmation-message">
        Do you really want to delete <?php echo $ip->getMyCompressedAddress(); ?>/<?php echo $ip->myPrefix; ?>?
    </div>

    <div class="form-actions">
        <?php if($hasSubnet) { ?>
            <div class="subnet-option">
                <label>
                    <input type="checkbox" name="subnets" value="1">
                    also delete <b>all</b> subnets!
                </label>
            </div>
        <?php } else { ?>
            <input type="hidden" name="subnets" value="0">
        <?php } ?>

        <div class="button-group">
            <input type="submit" value="Yes" class="btn btn-danger">
            <input type="button" value="Cancel" onclick="history.go(-1);" class="btn btn-secondary">
        </div>
    </div>
</form>