<?php

$id	= valid_id($_GET['id'] ?? null);

$hasSubnet = false;

$q = "SELECT * FROM `ipamv6` WHERE `id`= ?;";
$res = $sql->query($q, array($id));
$row = $sql->array_result($res);

if (!$row)
{
	echo "Network not found!";
	return;
}

$ip = new IPv6();
$ip->initBinary($row['network_bin'], $row['mask']);


// check if there are subnets
$q="SELECT * FROM `ipamv6` WHERE `network_bin` LIKE ? AND `mask` > ? LIMIT 1;";
$res = $sql->query($q, array(substr($row['network_bin'], 0, $row['mask'])."%", $row['mask']));
if($sql->array_result($res))
	$hasSubnet=true;

?>

<form action="./deleteNetworks.php" method="post" class="delete-network-form">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="id" value="<?php echo $id; ?>">
    <input type="hidden" name="url" value="<?php echo e(local_redirect_url($_SERVER["HTTP_REFERER"] ?? '')); ?>">
    
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