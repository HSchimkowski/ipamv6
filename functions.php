<?php

function sudoku_cell($field, $row)
{
	global $MyIPv6;
	global $gMask;
	?>
<td 
	id="cell<?php echo $field; ?>" 
	class="field_<?php echo $field; ?>" 
	style="background-color: <?php if($row['color']=="" || $row['mask'] > $gMask+8){echo "white";}else{echo $row['color']; }?>" 
	<?php if($row['id']=='' || $row['mask'] > $gMask+8){?>	onclick="selectCell('<?php echo $field; ?>')" onMouseOver="highlightCell('<?php echo $field; ?>')"<?php }?>>
	<?php if($row['id']!="" && $_GET['mask']<'120' && $row['mask'] < $gMask+9){?>
		<a href="./?action=sudoku&amp;mask=<?php echo ($MyIPv6->myPrefix + 8); ?>&amp;network=<?php	echo substr_replace($MyIPv6->binNetwork(), myHex2Bin($field), $MyIPv6->myPrefix, 8);?>"	title="<?php echo $row['description']; ?>">
			<?php 
	}
	echo $field; 
	if($row['id']!="" && $_GET['mask']<'120' && $row['mask'] < $gMask+9){
	?>
		</a><?php
	}
	if($row['mask'] > $gMask+8)
	{?>
		<br /><img src="img/info.png" width="16" height="16" title="This network has subnets smaller than /<?php echo $gMask+8; ?> ! 

They are not visible into your current view with a depth between /<?php echo $gMask; ?> and /<?php echo $gMask+8; ?>.">
	<?php } 
	?>
</td>
<?php 
}

function left()
{
	global $sql;
	
	$res = $sql->query("SELECT * FROM `".DB_PREFIX."ipamv6` WHERE `root`=1 ORDER BY `network_bin` ASC;");
	
	?>
	IPv6 root Networks:
	<ul>
		<?php
		while($row = $sql->array_result($res))
		{
			$ipv6 = new IPv6();
			$ipv6->initHex($row['network'], $row['mask']);
			?><li>
				<a 	href="./?action=sudoku&amp;mask=<?php echo floor($ipv6->myPrefix/8)*8; ?>&amp;network=<?php echo $ipv6->binNetwork() ?>" title="<?php echo $row['description']; ?>">
					<?php echo $ipv6->hexCompressedNetwork()."/".$row['mask']; ?>
				</a>
		<?php }
		?>
		</ul>
		<hr />
		<a href="./?action=addCustomNetworkForm">add new network</a><br />
		<br />
		<a href="./?action=showIpv6Route">show all networks</a><br />
		<br />
	<!--	<a href="./?action=todo">TODO-List</a><br />
		<br /> -->
	<!--	<a href="./?action=help">info / help</a><br /> -->
		<a href="http://ipamv6.schimkowski.net" target="_blank">info / help</a><br />
	<?php 
}

function existNetwork($net, $mask)
{
	global $sql;
	$q = "SELECT `id` from `ipamv6` WHERE `network_bin` LIKE '".substr($net, 0, $mask)."%' AND `mask` > '".$mask."' AND `mask` < '".($_GET['mask']+8)."' limit 1 ;";
	$res = $sql->query($q);
	$row = $sql->array_result($res);
	if($sql->num_result()>0)
	{
		return true;
	}
	else
	{
		return false;
	}
}

function navBar()
{
	global $sql, $gNetwork, $gMask, $gIP;
	$r = "";
	
	$root = new IPv6();
	$par  = new IPv6();
	$cur  = new IPv6();
	
	// find my Root Network
	for($i=$gMask; $i>=0; $i--)
	{
		$q = "SELECT * FROM `ipamv6` WHERE `network_bin` LIKE '".substr($gNetwork, 0, $i)."%' ORDER BY mask; ";
		$res = $sql->query($q);
		if($row = $sql->array_result($res))
		{
			$root->initBinary($row['network_bin'], $row['mask']);
			$root->myDescription=$row['description'];
			//break;
		}
	}
	
	// get Parent Network
	for($i=$gMask; $i>$root->myPrefix; $i--)
	{
		$q = "SELECT * FROM `ipamv6` WHERE `network_bin` LIKE '".substr($gNetwork, 0, $i)."%' AND `mask` <= '".$gMask."' ORDER BY mask DESC; ";
		$res = $sql->query($q);
		if($row = $sql->array_result($res))
		{
			$par->initBinary($row['network_bin'], $row['mask']);
			break;
		}
			
	}
	
	// get current Network
	$cur->initBinary($gNetwork, $gMask);
	
	// plain Hex
	$ip=str_split(str_replace(":", "", $cur->getMyExpandedAddress()),2);
	
	#echo "root: ".$root->getMyExpandedAddress()."/".$root->myPrefix."<br />";
	#echo "par: ".$par->getMyExpandedAddress()."/".$par->myPrefix."<br />";
	#echo "cur: ".$cur->getMyExpandedAddress()."/".$cur->myPrefix."<br />";
	
	
	?>
		<div id="navBar">
	<?php 
	
	for($i=0; $i<sizeof($ip); $i++)
	{
		if($i==0)
		{
			?><a href="./?action=sudoku&amp;mask=<?php echo $root->myPrefix; ?>&amp;network=<?php echo $root->binNetwork(); ?>"><?php 
		}
		
	//	if($i*8+8 >= $root->myPrefix && $i*8 <= $root->myPrefix)
	//		echo "</a>";
		
		if($i*8 >= $root->myPrefix && $i*8+8 <= $gMask)
		{
			?><a href="./?action=sudoku&mask=<?php echo $i*8+8; ?>&network=<?php echo substr_replace($cur->binNetwork(), str_repeat("0",	128 - ($i*8+8)), ($i*8 + 8)); ?>"><?php 
		}
		if(($i*8) == $gMask)
			echo "<span class=\"current\">";
		
		if($i >= (64/8))
			echo "<span class=\"host\">";

		echo strtoupper($ip[$i]);
		
		if($i >= (64/8))
                        echo "</span>";

		if(($i*8) == $gMask)
			echo "</span>";
		
		if($i*8 >= $root->myPrefix && $i*8+8 <= $gMask)
			echo "</a>";
		
		if($i*8+8==$root->myPrefix)
			echo "</a>";
		
		if(($i*8+8)%16 == 0 && $i < 15)
			echo ":";
		
	}
	?></a>/<?php echo $cur->myPrefix; ?>
	<span style="background: <?php echo $row['color']; ?>">
		(<?php echo $row['description']; ?>)
	</span>
	</div>
	<?php 
}

?>