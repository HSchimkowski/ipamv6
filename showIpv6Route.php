<pre>
<?php


$q="SELECT mask FROM  `ipamv6` order by mask ASC limit 1;";
$res = $sql->query($q);
$row = $sql->array_result($res);
$o=$row['mask'];


$q="SELECT * FROM  `ipamv6` order by network_bin, mask ASC;";
$res = $sql->query($q);

$r=new IPv6();
$d=0;
while ($row = $sql->array_result($res))
{
	$r->initBinary($row['network_bin'], $row['mask']);
	echo str_repeat(" ", $r->myPrefix - $o);
	echo "<a href=\".?action=sudoku&amp;mask=".(floor($row['mask'] / 8)*8)."&amp;network=".$r->binNetwork()."\">";
	echo $r->getMyCompressedAddress()."/".$r->myPrefix. " <span style=\"background: ".$row['color']."\">".$row['description']."</span></a> "; 
	echo "<a href=\"./?action=editNetworkForm&amp;id=".$row['id']."\">(edit)</a> ";
	echo "<a href=\"./?action=deleteNetwork&amp;id=".$row['id']."\">(delete)</a> \n";
}

?>
</pre>