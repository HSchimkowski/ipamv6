<?php
require_once 'config.inc.php';
require_once 'class/mysq.class.php';
require_once 'class/ipv6.php';

$_SERVER['REQUEST_URI'];

$lastNetwork_bin = $_GET['lastNetwork_bin'];
$mask = $_GET['mask'];
$root = $_isset($_GET['root']) ? $_GET['root'] : 0;

// start database connection
$sql = new mysql("localhost","user","passwort","datenbank"); 

// dont allow cache
header("Pragma: no-cache");

$q="SELECT * 
	FROM `".DB_PREFIX."ipamv6` 
	WHERE (
		`mask` > ".($mask) ."
	)
	ORDER BY network_bin ASC;";
$res = $sql->query($q);
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
				<?php echo $ip->getMyCompressedAddress(); ?>/<?php echo $ip->myPrefix; ?> (<?php echo ($row['description']); ?>)
			</a>
			<a href="./?action=editNetworkForm&id=<?php echo $row['id']?>">(edit)</a>
			<a href="./?action=deleteNetwork&id=<?php echo $row['id']; ?>">(delete)</a>
		</li>
	<?php }
	
	if(substr($row['network_bin'], 0, $mask + 8) == substr($lastNetwork_bin, 0, $mask+8))
		$show=1;
}
?>