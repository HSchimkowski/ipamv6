<?php
require_once 'config.inc.php';
require_once 'class/mysq.class.php';
require_once 'class/ipv6.php';
require_once 'functions.php';
send_security_headers();

$lastNetwork_bin = valid_bin_network($_GET['lastNetwork_bin'] ?? null, false);
$mask = valid_mask($_GET['mask'] ?? null, 120);
$root = valid_bin_network($_GET['root'] ?? null);
if ($lastNetwork_bin === null || $mask === null || $root === null)
	bad_request("Invalid network or netmask!");

// only the subnets of the current view; "1...1" marks a group before the first visible subnet
$where  = "`network_bin` LIKE ? AND `mask` > ?";
$params = array(substr($root, 0, $mask)."%", $mask);
if ($lastNetwork_bin != str_repeat("1", $mask + 8))
{
	// rows sorted before the last visible subnet are never shown
	$where   .= " AND `network_bin` >= ?";
	$params[] = str_pad($lastNetwork_bin, 128, "0");
}

// start database connection
$sql = new mysql();

// dont allow cache
header("Pragma: no-cache");

$q="SELECT * 
	FROM `".DB_PREFIX."ipamv6` 
	WHERE ".$where."
	ORDER BY `network_bin`, `mask`, `id`;";
$res = $sql->query($q, $params);
$show=0;
$i=0;
while ($row = $sql->array_result($res))
{
	if($i==0 && $lastNetwork_bin == str_repeat("1", $mask+8) )
	{
		$show=1;
	}
	$i++;
	
	if ($show == 1 && $row['mask'] <= $mask + 8 )
	{
		$show=0;
		return;
	}
	
	if($show == 1)
	{
		$ip = new IPv6();
		$ip->initBinary($row['network_bin'], $row['mask']);
		$n = floor(($row['mask']-1)/8)*8;
		?>
		<li>
			<a href="./?action=sudoku&mask=<?php echo $n; ?>&network=<?php echo substr($row['network_bin'], 0, $n).str_repeat("0", 128 - $n);?>">
				<?php echo $ip->getMyCompressedAddress(); ?>/<?php echo $ip->myPrefix; ?> (<?php echo e($row['description']); ?>)
			</a>
			<a href="./?action=editNetworkForm&id=<?php echo e($row['id']); ?>">(edit)</a>
			<a href="./?action=deleteNetwork&id=<?php echo e($row['id']); ?>">(delete)</a>
		</li>
	<?php }
	
	if(substr($row['network_bin'], 0, $mask + 8) == substr($lastNetwork_bin, 0, $mask+8))
		$show=1;
}
?>