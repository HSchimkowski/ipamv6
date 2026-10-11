<?php 
// only reachable through index.php
if (!defined('IPAMV6')) { http_response_code(403); exit('Forbidden'); }

$gNetwork	= valid_bin_network($_GET['network'] ?? null);
$gMask		= valid_mask($_GET['mask'] ?? null, 120);

if ($gNetwork === null || $gMask === null)
{
	echo "Invalid network or netmask!";
	return;
}

$gIP = new IPv6();
$gIP->initBinary($gNetwork, $gMask);

// Navigation
navBar();

// subnets
?>
<div id="subnetted">
	<form action="./?action=addNewNetwork" method="post">
		<fieldset>
			<legend>add new subnet</legend>
			<?php echo csrf_field(); ?>
			<table>
				<tr>
					<td>subnet:</td>
					<td>
						<input 						 type="hidden" name="newBaseNetwork" value="<?php echo $gNetwork;?>">
						<input id="form_basenetwork" type="hidden" name="newNetwork" 	 value="<?php echo $gNetwork;?>">
						<input id="form_basemask" 	 type="hidden" name="newBaseMask" 	 value="<?php echo $gMask; ?>">
						<input id="form_mask" 		 type="hidden" name="newMask">
						<input 						 type="hidden" name="format"		 value="bin">
						<?php 
						$r = "";
						$n=$ip=str_split(str_replace(":", "", $gIP->getMyExpandedAddress()),2);
						for($i=0; $i < ($gMask/8); $i++)
						{
							echo strtoupper($n[$i]);
							if((($i+1)) % 2 == 0)
								echo ":";
							}
						?><input size="2" id="form_network" type="text" name="newNetworkHex" disabled><?php if($gMask%16 == 0){echo " 00";}?>:: / 
						<input size="2" id="form_maska" type="text" disabled>
					</td>
				</tr>
				<tr>
					<td>description:</td>
					<td><input id="form_description" type="text" name="newDescription" disabled></td>
				</tr>
				<tr>
					<td>color:</td>
					<td>
					<script type="text/javascript" src="jscolor/jscolor.js"></script>
					<input id="form_color" class="color {hash:true,pickerPosition:'right',pickerClosable:true}" type="text" name="newColour" value="#FFFFAA" disabled>
					</td>
				</tr>
				<tr>
					<td></td>
					<td>
						<input id="form_submit" type="submit" value="save" disabled>
						<input id="form_reset" type="reset" value="cancel" onClick="clearCells()">
					</td>
			</table>
			<div style="text-align: right;"><a href="./?action=addCustomNetworkForm">add custom Network</a></div>
		</fieldset>
	</form>
	<hr /><?php echo $gIP->getMyCompressedAddress()."/".$gMask; ?> 
		has the following subnets with masks between /<?php echo $gMask; ?> and /<?php echo ($gMask+8); ?>:

<ul><?php 
$q = "	SELECT * 
		FROM `".DB_PREFIX."ipamv6` 
		WHERE  
			(`network_bin` LIKE ? AND `mask`> ? ) 
		ORDER BY `network_bin`, `mask`, `id`;";
$res = $sql->query($q, array(substr($gNetwork, 0, $gMask)."%", $gMask));
$subnets = array();
while ($row = $sql->array_result($res))
	$subnets[] = $row;

$hidden=0;
$hiddenCounter=0;
$lastNetwork_bin=str_repeat("1",128);
$lastMask=$gMask;
foreach ($subnets as $row)
{
	$ip = new IPv6();
	$ip->initBinary($row['network_bin'], $row['mask']);
	
	if($row['mask'] > ($gMask + 8) && $hidden == 0)
	{
		$last 		= substr($lastNetwork_bin, 		0, $lastMask);
		if($gMask == "0")
			$last = "1";
		
		$current 	= substr($row['network_bin'], 	0, $lastMask);
		
		
		if($last != $current)
		{?>		
				<ul><li class="expand">
					<a 
						href="#" 
						title="Here are 'hidden' subnets smaller than /<?php echo $gMask+8; ?> ! 

They are not visible into your current view with a depth between /<?php echo $gMask; ?> and /<?php echo $gMask+8; ?>.\"
						onClick="expandNetworks(<?php echo $hiddenCounter; ?>, '<?php echo substr($lastNetwork_bin, 0, ($gMask+8))?>', <?php echo $gMask ?>, '<?php echo $gIP->binNetwork()?>')"
						>
						...
					</a>
				</li>
				</ul>				
			<ul id="hiddennNetworks<?php echo $hiddenCounter?>"></ul>
		<?php }
		$hiddenCounter++;
		$hidden=1;
	}
	
	if($row['mask'] <= ($gMask + 8))
	{
	?><li>
		<a href="./?action=sudoku&amp;mask=<?php echo ($gMask + 8); ?>&amp;network=<?php echo $ip->binNetwork();?>">
			<?php echo $ip->hexCompressedNetwork();?>/<?php echo e($row['mask']); ?>
		</a>
		<span style="background: <?php echo e_color($row['color']); ?>">
			(<?php echo e($row['description']); ?>)
		</span>
		<a href="./?action=editNetworkForm&amp;id=<?php echo e($row['id']); ?>">(edit)</a>
		<a href="./?action=deleteNetwork&amp;id=<?php echo e($row['id']); ?>">(delete)</a>
		<?php 
		$hidden=0;
		$lastNetwork_bin = $row['network_bin'];
		$lastMask = $row['mask'];
	}
	
	
	
}?>
</ul>
		<hr />
</div>


<?php

// sudoku
$ipv6 = new IPv6();
$ipv6->initBinary($gNetwork, $gMask);
$MyIPv6 = $ipv6;

// the subnets are already loaded above, so the cells need no further queries
$cells = sudoku_cells($subnets, $gMask);
?>

<div id="sudoku" onkeydown="onkeypressed(event, this);">
<table>
<?php
// the 8 bits of a cell are interleaved: odd bits select the row, even bits the column
for ($y = 0; $y < 16; $y++)
{
	echo "\t<tr>\n";
	for ($x = 0; $x < 16; $x++)
	{
		$value = 0;
		for ($bit = 0; $bit < 4; $bit++)
			$value |= (($x >> $bit) & 1) << (2 * $bit) | (($y >> $bit) & 1) << (2 * $bit + 1);
		sudoku_cell(sprintf("%02X", $value), $cells[$value] ?? null);
	}
	echo "\t</tr>\n";
}
?>
</table><br />
</div>