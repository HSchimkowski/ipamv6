<?php 

$gNetwork	= $_GET['network'];
$gMask		= $_GET['mask'];

$gIP = new IPv6();
$gIP->initBinary($gNetwork, $gMask);

// Navigation
navBar();

// subnets
?>
<div id="subnetted">
	<form action="./" method="get">
		<fieldset>
			<legend>add new subnet</legend>
			<input type="hidden" name="action" value="addNewNetwork">
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
			(`network_bin` LIKE '".substr($_GET['network'], 0, $_GET['mask'])."%' AND `mask`> '".$gMask."' ) 
		ORDER BY `network_bin`, `mask`;";
$res = $sql->query($q);

$hidden=0;
$hiddenCounter=0;
$lastNetwork_bin=str_repeat("1",128);
$lastMask=$gMask;
while ($row = $sql->array_result($res))
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
			<?php echo $ip->hexCompressedNetwork();?>/<?php echo $row['mask']?>
		</a>
		<span style="background: <?php echo $row['color']; ?>">
			(<?php echo $row['description']?>)
		</span>
		<a href="./?action=editNetworkForm&amp;id=<?php echo $row['id']; ?>">(edit)</a>
		<a href="./?action=deleteNetwork&amp;id=<?php echo $row['id']; ?>">(delete)</a>
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




$mask = $gMask;
?>

<div id="sudoku" onkeydown="onkeypressed(event, this);">
<table>
<!-- 1. row -->
	<tr>
		<?php 	
		# 00: 33,34,35,36,37,38,39,40
		$q="SELECT * FROM  `ipamv6` WHERE (
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`>='".($mask+1)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("00", $row);


		# 00: 33,34,35,36,37,38,39
		# 01: 40
		$q="SELECT * FROM  `ipamv6` WHERE (
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000001", $mask)."%' AND `mask`>='".($mask+8)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("01", $row);


		# 00: 33,34,35,36,37
		# 04: 38,39,40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000100", $mask)."%' AND `mask`>='".($mask+6)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("04", $row);


		# 00: 33,34,35,36,37
		# 04: 38,39
		# 05: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000100", $mask)."%' AND `mask`>='".($mask+6)."' AND `mask`<='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000101", $mask)."%' AND `mask`>='".($mask+8)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("05", $row);


		# 00: 33,34,35
		# 10: 36..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00010000", $mask)."%' AND `mask`>='".($mask+4)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("10", $row);


		# 00: 33,34,35
		# 10: 36..39
		# 11: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00010000", $mask)."%' AND `mask`>='".($mask+4)."' AND `mask`<='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00010001", $mask)."%' AND `mask`>='".($mask+8)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("11", $row);


		# 00: 33,34,35
		# 10: 36,37
		# 14: 38...40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00010000", $mask)."%' AND `mask`>='".($mask+4)."' AND `mask`<='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00010100", $mask)."%' AND `mask`>='".($mask+6)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("14", $row);


		# 00: 33,34,35
		# 10: 36,37
		# 14: 38...39
		# 15: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00010000", $mask)."%' AND `mask`>='".($mask+4)."' AND `mask`<='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00010100", $mask)."%' AND `mask`>='".($mask+6)."' AND `mask`<='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00010101", $mask)."%' AND `mask`>='".($mask+8)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("15", $row);

		# 00: 33
		# 40: 34..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000000", $mask)."%' AND `mask`>='".($mask+2)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("40", $row);
		
		# 00: 33
		# 40: 34..39
		# 41: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000000", $mask)."%' AND `mask`>='".($mask+2)."' AND `mask`<='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000001", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("41", $row);
		
		
		# 00: 33
		# 40: 34..37
		# 44: 38..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000000", $mask)."%' AND `mask`>='".($mask+2)."' AND `mask`<='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000100", $mask)."%' AND `mask`>='".($mask+6)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("44", $row);
		
		# 00: 33
		# 40: 34..37
		# 44: 38..39
		# 45: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000000", $mask)."%' AND `mask`>='".($mask+2)."' AND `mask`<='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000100", $mask)."%' AND `mask`>='".($mask+6)."' AND `mask`<='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000101", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("45", $row);
		
		
		# 00: 33
		# 40: 34..35
		# 50: 36..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000000", $mask)."%' AND `mask`>='".($mask+2)."' AND `mask`<='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01010000", $mask)."%' AND `mask`>='".($mask+4)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("50", $row);
		
		# 00: 33
		# 40: 34..35
		# 50: 36..39
		# 51: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000000", $mask)."%' AND `mask`>='".($mask+2)."' AND `mask`<='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01010000", $mask)."%' AND `mask`>='".($mask+4)."' AND `mask`<='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01010001", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("51", $row);
		
		# 00: 33
		# 40: 34..35
		# 50: 36..37
		# 54: 38..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000000", $mask)."%' AND `mask`>='".($mask+2)."' AND `mask`<='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01010000", $mask)."%' AND `mask`>='".($mask+4)."' AND `mask`<='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01010100", $mask)."%' AND `mask`>='".($mask+6)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("54", $row);
		
		# 00: 33
		# 40: 34..35
		# 50: 36..37
		# 54: 38..39
		# 55: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000000", $mask)."%' AND `mask`>='".($mask+2)."' AND `mask`<='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01010000", $mask)."%' AND `mask`>='".($mask+4)."' AND `mask`<='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01010100", $mask)."%' AND `mask`>='".($mask+6)."' AND `mask`<='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01010101", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("55", $row);
		?>
	</tr>
<!-- 2. row -->
	<tr>
		<?php 

		# 00: 33,34,35,36,37,38
		# 02: 39,40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000010", $mask)."%' AND `mask`>='".($mask+7)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("02", $row);


		# 00: 33,34,35,36,37,38
		# 02: 39
		# 03: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000010", $mask)."%' AND `mask`='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000011", $mask)."%' AND `mask`>='".($mask+8)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("03", $row);


		# 00: 33,34,35,36,37
		# 04: 38
		# 06: 39,40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000100", $mask)."%' AND `mask`='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000110", $mask)."%' AND `mask`>='".($mask+7)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("06", $row);

			
		# 00: 33,34,35,36,37
		# 04: 38
		# 06: 39
		# 07: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000100", $mask)."%' AND `mask`='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000110", $mask)."%' AND `mask`='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000111", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("07", $row);

			
		# 00: 33,34,35
		# 10: 36..38
		# 12: 39..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00010000", $mask)."%' AND `mask`>='".($mask+4)."' AND `mask`<='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00010010", $mask)."%' AND `mask`>='".($mask+7)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("12", $row);


		# 00: 33,34,35
		# 10: 36..38
		# 12: 39
		# 13: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00010000", $mask)."%' AND `mask`>='".($mask+4)."' AND `mask`<='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00010010", $mask)."%' AND `mask`='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00010011", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("13", $row);


		# 00: 33,34,35
		# 10: 36,37
		# 14: 38
		# 16: 39,40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00010000", $mask)."%' AND `mask`>='".($mask+4)."' AND `mask`<='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00010100", $mask)."%' AND `mask`='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00010110", $mask)."%' AND `mask`>='".($mask+7)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("16", $row);

			
		# 00: 33,34,35
		# 10: 36, 37
		# 14: 38
		# 16: 39
		# 17: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00010000", $mask)."%' AND `mask`>='".($mask+4)."' AND `mask`<='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00010100", $mask)."%' AND `mask`='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00010110", $mask)."%' AND `mask`='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00010111", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("17", $row);
		
		
		# 00: 33
		# 40: 34..38
		# 42: 39..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000000", $mask)."%' AND `mask`>='".($mask+2)."' AND `mask`<='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000010", $mask)."%' AND `mask`>='".($mask+7)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("42", $row);
		
		
		# 00: 33
		# 40: 34..38
		# 42: 39
		# 43: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000000", $mask)."%' AND `mask`>='".($mask+2)."' AND `mask`<='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000010", $mask)."%' AND `mask`='".($mask+7)."' OR 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000011", $mask)."%' AND `mask`>='".($mask+7)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("43", $row);
		
		
		# 00: 33
		# 40: 34..37
		# 44: 38
		# 46: 39..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000000", $mask)."%' AND `mask`>='".($mask+2)."' AND `mask`<='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000100", $mask)."%' AND `mask`='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000110", $mask)."%' AND `mask`>='".($mask+7)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("46", $row);
		
		# 00: 33
		# 40: 34..37
		# 44: 38
		# 46: 39
		# 47: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000000", $mask)."%' AND `mask`>='".($mask+2)."' AND `mask`<='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000100", $mask)."%' AND `mask`='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000110", $mask)."%' AND `mask`='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000111", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("47", $row);
		
		
		# 00: 33
		# 40: 34..35
		# 50: 36..38
		# 52: 39..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000000", $mask)."%' AND `mask`>='".($mask+2)."' AND `mask`<='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01010000", $mask)."%' AND `mask`>='".($mask+4)."' AND `mask`<='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01010010", $mask)."%' AND `mask`>='".($mask+7)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("52", $row);
		
		# 00: 33
		# 40: 34..35
		# 50: 36..38
		# 52: 39
		# 53: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000000", $mask)."%' AND `mask`>='".($mask+2)."' AND `mask`<='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01010000", $mask)."%' AND `mask`>='".($mask+4)."' AND `mask`<='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01010010", $mask)."%' AND `mask`='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01010011", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("53", $row);

		# 00: 33
		# 40: 34..35
		# 50: 36..37
		# 54: 38
		# 56: 39..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000000", $mask)."%' AND `mask`>='".($mask+2)."' AND `mask`<='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01010000", $mask)."%' AND `mask`>='".($mask+4)."' AND `mask`<='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01010100", $mask)."%' AND `mask`='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01010110", $mask)."%' AND `mask`>='".($mask+7)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("56", $row);
		
		# 00: 33
		# 40: 34..35
		# 50: 36..37
		# 54: 38
		# 56: 39
		# 57: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000000", $mask)."%' AND `mask`>='".($mask+2)."' AND `mask`<='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01010000", $mask)."%' AND `mask`>='".($mask+4)."' AND `mask`<='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01010100", $mask)."%' AND `mask`='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01010110", $mask)."%' AND `mask`='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01010111", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("57", $row);
		?>
	</tr>
<!-- 3. row -->
	<tr>
		<?php 

		# 00: 33,34,35,36
		# 08: 37...40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00001000", $mask)."%' AND `mask`>='".($mask+5)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("08", $row);
			
		# 00: 33,34,35,36
		# 08: 37...39
		# 09: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00001000", $mask)."%' AND `mask`>='".($mask+5)."' AND `mask`<='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00001001", $mask)."%' AND `mask`>='".($mask+8)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("09", $row);


		# 00: 33,34,35,36
		# 08: 37
		# 0C: 38,39,40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00001000", $mask)."%' AND `mask`='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00001100", $mask)."%' AND `mask`>='".($mask+6)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("0C", $row);

			
		# 00: 33,34,35,36
		# 08: 37
		# 0C: 38,39
		# 0D: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00001000", $mask)."%' AND `mask`='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00001100", $mask)."%' AND `mask`>='".($mask+6)."' AND `mask`<='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00001101", $mask)."%' AND `mask`>='".($mask+8)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("0D", $row);


		# 00: 33,34,35
		# 10: 36
		# 18: 37...40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00010000", $mask)."%' AND `mask`='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00011000", $mask)."%' AND `mask`>='".($mask+5)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("18", $row);

			
		# 00: 33,34,35
		# 10: 36
		# 18: 37...39
		# 19: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00010000", $mask)."%' AND `mask`='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00011000", $mask)."%' AND `mask`>='".($mask+5)."' AND `mask`<='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00011001", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("19", $row);

			
		# 00: 33,34,35
		# 10: 36
		# 18: 37
		# 1C: 38..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00010000", $mask)."%' AND `mask`='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00011000", $mask)."%' AND `mask`='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00011100", $mask)."%' AND `mask`>='".($mask+6)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("1C", $row);

			
		# 00: 33,34,35
		# 10: 36
		# 18: 37
		# 1C: 38,39
		# 1D: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00010000", $mask)."%' AND `mask`='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00011000", $mask)."%' AND `mask`='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00011100", $mask)."%' AND `mask`>='".($mask+6)."' AND `mask`<='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00011101", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("1D", $row);

		# 00: 33
		# 40: 34..36
		# 48: 37..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000000", $mask)."%' AND `mask`>='".($mask+2)."' AND `mask`<='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01001000", $mask)."%' AND `mask`>='".($mask+5)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("48", $row);
		
		# 00: 33
		# 40: 34..36
		# 48: 37..39
		# 49: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000000", $mask)."%' AND `mask`>='".($mask+2)."' AND `mask`<='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01001000", $mask)."%' AND `mask`>='".($mask+5)."' AND `mask`<='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01001001", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("49", $row);

		# 00: 33
		# 40: 34..36
		# 48: 37
		# 4C: 38..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000000", $mask)."%' AND `mask`>='".($mask+2)."' AND `mask`<='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01001000", $mask)."%' AND `mask`='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01001100", $mask)."%' AND `mask`>='".($mask+6)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("4C", $row);
		
		
		# 00: 33
		# 40: 34..36
		# 48: 37
		# 4C: 38..39
		# 4D: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000000", $mask)."%' AND `mask`>='".($mask+2)."' AND `mask`<='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01001000", $mask)."%' AND `mask`='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01001100", $mask)."%' AND `mask`>='".($mask+6)."' AND `mask`<='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01001101", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("4D", $row);
		
		# 00: 33
		# 40: 34..35
		# 50: 36
		# 58: 37..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000000", $mask)."%' AND `mask`>='".($mask+2)."' AND `mask`<='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01010000", $mask)."%' AND `mask`='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01011000", $mask)."%' AND `mask`>='".($mask+5)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("58", $row);
		
		# 00: 33
		# 40: 34..35
		# 50: 36
		# 58: 37..39
		# 59: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000000", $mask)."%' AND `mask`>='".($mask+2)."' AND `mask`<='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01010000", $mask)."%' AND `mask`='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01011000", $mask)."%' AND `mask`>='".($mask+5)."' AND `mask`<='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01011001", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("59", $row);
		
		
		# 00: 33
		# 40: 34..35
		# 50: 36
		# 58: 37
		# 5C: 38..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000000", $mask)."%' AND `mask`>='".($mask+2)."' AND `mask`<='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01010000", $mask)."%' AND `mask`='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01011000", $mask)."%' AND `mask`='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01011100", $mask)."%' AND `mask`>='".($mask+6)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("5C", $row);
		
		# 00: 33
		# 40: 34..35
		# 50: 36
		# 58: 37
		# 5C: 38..39
		# 5D: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000000", $mask)."%' AND `mask`>='".($mask+2)."' AND `mask`<='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01010000", $mask)."%' AND `mask`='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01011000", $mask)."%' AND `mask`='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01011100", $mask)."%' AND `mask`>='".($mask+6)."' AND `mask`<='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01011101", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("5D", $row);

		?>
	</tr>
<!-- 4. row -->
	<tr>
		<?php 

		# 00: 33,34,35,36
		# 08: 37...38
		# 0A: 39,40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00001000", $mask)."%' AND `mask`>='".($mask+5)."' AND `mask`<='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00001010", $mask)."%' AND `mask`>='".($mask+7)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("0A", $row);

			

		# 00: 33,34,35,36
		# 08: 37...38
		# 0A: 39
		# 0B: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00001000", $mask)."%' AND `mask`>='".($mask+5)."' AND `mask`<='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00001010", $mask)."%' AND `mask`='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00001011", $mask)."%' AND `mask`>='".($mask+8)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("0B", $row);


		# 00: 33,34,35,36
		# 08: 37
		# 0C: 38
		# 0E: 39,40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00001000", $mask)."%' AND `mask`='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00001100", $mask)."%' AND `mask`='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00001110", $mask)."%' AND `mask`>='".($mask+7)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("0E", $row);

			
		# 00: 33,34,35,36
		# 08: 37
		# 0C: 38
		# 0E: 39
		# 0F: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00001000", $mask)."%' AND `mask`='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00001100", $mask)."%' AND `mask`='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00001110", $mask)."%' AND `mask`='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00001111", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("0F", $row);

			
		# 00: 33,34,35
		# 10: 36
		# 18: 37,38
		# 1A: 39,40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00010000", $mask)."%' AND `mask`='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00011000", $mask)."%' AND `mask`>='".($mask+5)."' AND `mask`<='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00011010", $mask)."%' AND `mask`>='".($mask+7)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("1A", $row);

			
		# 00: 33,34,35
		# 10: 36
		# 18: 37,38
		# 1A: 39
		# 1B: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00010000", $mask)."%' AND `mask`='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00011000", $mask)."%' AND `mask`>='".($mask+5)."' AND `mask`<='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00011010", $mask)."%' AND `mask`='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00011011", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("1B", $row);


		# 00: 33,34,35
		# 10: 36
		# 18: 37
		# 1C: 38
		# 1E: 39,40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00010000", $mask)."%' AND `mask`='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00011000", $mask)."%' AND `mask`='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00011100", $mask)."%' AND `mask`='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00011110", $mask)."%' AND `mask`>='".($mask+7)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("1E", $row);


		# 00: 33,34,35
		# 10: 36
		# 18: 37
		# 1C: 38
		# 1E: 39
		# 1F: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00010000", $mask)."%' AND `mask`='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00011000", $mask)."%' AND `mask`='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00011100", $mask)."%' AND `mask`='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00011110", $mask)."%' AND `mask`='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00011111", $mask)."%' AND `mask`>='".($mask+8)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("1F", $row);

		# 00: 33
		# 40: 34..36
		# 48: 37..38
		# 4A: 39..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000000", $mask)."%' AND `mask`>='".($mask+2)."' AND `mask`<='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01001000", $mask)."%' AND `mask`>='".($mask+5)."' AND `mask`<='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01001010", $mask)."%' AND `mask`>='".($mask+7)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("4A", $row);
		
		# 00: 33
		# 40: 34..36
		# 48: 37..38
		# 4A: 39
		# 4B: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000000", $mask)."%' AND `mask`>='".($mask+2)."' AND `mask`<='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01001000", $mask)."%' AND `mask`>='".($mask+5)."' AND `mask`<='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01001010", $mask)."%' AND `mask`='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01001011", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("4B", $row);

		
		# 00: 33
		# 40: 34..36
		# 48: 37
		# 4C: 38
		# 4E: 39..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000000", $mask)."%' AND `mask`>='".($mask+2)."' AND `mask`<='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01001000", $mask)."%' AND `mask`='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01001100", $mask)."%' AND `mask`='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01001110", $mask)."%' AND `mask`>='".($mask+7)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("4E", $row);
		
		
		# 00: 33
		# 40: 34..36
		# 48: 37
		# 4C: 38
		# 4E: 39
		# 4F: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000000", $mask)."%' AND `mask`>='".($mask+2)."' AND `mask`<='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01001000", $mask)."%' AND `mask`='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01001100", $mask)."%' AND `mask`='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01001110", $mask)."%' AND `mask`='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01001111", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("4F", $row);
		
		# 00: 33
		# 40: 34..35
		# 50: 36
		# 58: 37..38
		# 5A: 39..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000000", $mask)."%' AND `mask`>='".($mask+2)."' AND `mask`<='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01010000", $mask)."%' AND `mask`='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01011000", $mask)."%' AND `mask`>='".($mask+5)."' AND `mask`<='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01011010", $mask)."%' AND `mask`>='".($mask+7)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("5A", $row);
		
		# 00: 33
		# 40: 34..35
		# 50: 36
		# 58: 37..38
		# 5A: 39
		# 5B: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000000", $mask)."%' AND `mask`>='".($mask+2)."' AND `mask`<='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01010000", $mask)."%' AND `mask`='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01011000", $mask)."%' AND `mask`>='".($mask+5)."' AND `mask`<='".($mask+6)."'OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01011010", $mask)."%' AND `mask`='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01011011", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("5B", $row);

		# 00: 33
		# 40: 34..35
		# 50: 36
		# 58: 37
		# 5C: 38
		# 5E: 39..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000000", $mask)."%' AND `mask`>='".($mask+2)."' AND `mask`<='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01010000", $mask)."%' AND `mask`='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01011000", $mask)."%' AND `mask`='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01011100", $mask)."%' AND `mask`='".($mask+6)."' OR 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01011110", $mask)."%' AND `mask`>='".($mask+7)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("5E", $row);
		
		# 00: 33
		# 40: 34..35
		# 50: 36
		# 58: 37
		# 5C: 38
		# 5E: 39
		# 5F: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000000", $mask)."%' AND `mask`>='".($mask+2)."' AND `mask`<='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01010000", $mask)."%' AND `mask`='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01011000", $mask)."%' AND `mask`='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01011100", $mask)."%' AND `mask`='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01011110", $mask)."%' AND `mask`='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01011111", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("5F", $row);

		?>
	</tr>
<!-- 5. row -->
	<tr>
		<?php 
		# 00: 33,34
		# 20: 35..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00100000", $mask)."%' AND `mask`>='".($mask+3)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("20", $row);

			
		# 00: 33,34
		# 20: 35..39
		# 21: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00100000", $mask)."%' AND `mask`>='".($mask+3)."' AND `mask`<='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00100001", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("21", $row);

			
		# 00: 33,34
		# 20: 35..37
		# 24: 38..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00100000", $mask)."%' AND `mask`>='".($mask+3)."' AND `mask`<='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00100100", $mask)."%' AND `mask`>='".($mask+6)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("24", $row);


		# 00: 33,34
		# 20: 35..37
		# 24: 38..39
		# 25: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00100000", $mask)."%' AND `mask`>='".($mask+3)."' AND `mask`<='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00100100", $mask)."%' AND `mask`>='".($mask+6)."' AND `mask`<='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00100101", $mask)."%' AND `mask`>='".($mask+8)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("25", $row);

		
		
		# 00: 33..34
		# 20: 35
		# 30: 36..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
			`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+2)."'  OR 
			`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00100000", $mask)."%' AND `mask`='".($mask+3)."'  OR 
			`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00110000", $mask)."%' AND `mask`>='".($mask+4)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("30", $row);
		
		# 00: 33..34
		# 20: 35
		# 30: 36..39
		# 31: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00100000", $mask)."%' AND `mask`='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00110000", $mask)."%' AND `mask`>='".($mask+4)."' AND `mask`<='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00110001", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("31", $row);
		
		# 00: 33..34
		# 20: 35
		# 30: 36..37
		# 34: 38..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00100000", $mask)."%' AND `mask`='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00110000", $mask)."%' AND `mask`>='".($mask+4)."' AND `mask`<='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00110100", $mask)."%' AND `mask`>='".($mask+6)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("34", $row);
		
		# 00: 33..34
		# 20: 35
		# 30: 36..37
		# 34: 38..39
		# 35: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00100000", $mask)."%' AND `mask`='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00110000", $mask)."%' AND `mask`>='".($mask+4)."' AND `mask`<='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00110100", $mask)."%' AND `mask`>='".($mask+6)."' AND `mask`<='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00110101", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("35", $row);
		
		
		# 00: 33
		# 40: 34
		# 60: 35..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000000", $mask)."%' AND `mask`='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01100000", $mask)."%' AND `mask`>='".($mask+3)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("60", $row);
		
		# 00: 33
		# 40: 34
		# 60: 35..39
		# 61: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000000", $mask)."%' AND `mask`='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01100000", $mask)."%' AND `mask`>='".($mask+3)."' AND `mask`<='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01100001", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("61", $row);

		# 00: 33
		# 40: 34
		# 60: 35..37
		# 64: 38..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000000", $mask)."%' AND `mask`='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01100000", $mask)."%' AND `mask`>='".($mask+3)."' AND `mask`<='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01100100", $mask)."%' AND `mask`>='".($mask+6)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("64", $row);
		
		# 00: 33
		# 40: 34
		# 60: 35..37
		# 64: 38..39
		# 63: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000000", $mask)."%' AND `mask`='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01100000", $mask)."%' AND `mask`>='".($mask+3)."' AND `mask`<='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01100100", $mask)."%' AND `mask`>='".($mask+6)."' AND `mask`<='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01100101", $mask)."%' AND `mask`>='".($mask+8)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("65", $row);

		
		
		
		
		
		# 00: 33
		# 40: 34
		# 60: 35
		# 70: 36..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000000", $mask)."%' AND `mask`='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01100000", $mask)."%' AND `mask`='".($mask+3)."'  OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01110000", $mask)."%' AND `mask`>='".($mask+4)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("70", $row);

		# 00: 33
		# 40: 34
		# 60: 35
		# 70: 36..39
		# 71: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000000", $mask)."%' AND `mask`='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01100000", $mask)."%' AND `mask`='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01110000", $mask)."%' AND `mask`>='".($mask+4)."' AND `mask`<='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01110001", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("71", $row);

		# 00: 33
		# 40: 34
		# 60: 35
		# 70: 36..37
		# 74: 38..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000000", $mask)."%' AND `mask`='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01100000", $mask)."%' AND `mask`='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01110000", $mask)."%' AND `mask`>='".($mask+4)."' AND `mask`<='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01110100", $mask)."%' AND `mask`>='".($mask+6)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("74", $row);

		# 00: 33
		# 40: 34
		# 60: 35
		# 70: 36..37
		# 74: 38..39
		# 75: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000000", $mask)."%' AND `mask`='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01100000", $mask)."%' AND `mask`='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01110000", $mask)."%' AND `mask`>='".($mask+4)."' AND `mask`<='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01110100", $mask)."%' AND `mask`>='".($mask+6)."' AND `mask`<='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01110101", $mask)."%' AND `mask`>='".($mask+8)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("75", $row);
		
		?>
	</tr>
<!-- 6. row -->
	<tr>
		<?php 

		# 00: 33,34
		# 20: 35..38
		# 22: 39,40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00100000", $mask)."%' AND `mask`>='".($mask+3)."' AND `mask`<='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00100010", $mask)."%' AND `mask`>='".($mask+7)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("22", $row);

			
		# 00: 33,34
		# 20: 35..38
		# 22: 39
		# 23: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00100000", $mask)."%' AND `mask`>='".($mask+3)."' AND `mask`<='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00100010", $mask)."%' AND `mask`='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00100011", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("23", $row);

		# 00: 33,34
		# 20: 35..37
		# 24: 38
		# 26: 39..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00100000", $mask)."%' AND `mask`>='".($mask+3)."' AND `mask`<='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00100100", $mask)."%' AND `mask`='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00100110", $mask)."%' AND `mask`>='".($mask+7)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("26", $row);
		
		
		# 00: 33,35
		# 20: 35..37
		# 24: 38
		# 26: 39
		# 27: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00100000", $mask)."%' AND `mask`>='".($mask+3)."' AND `mask`<='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00100100", $mask)."%' AND `mask`='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00100110", $mask)."%' AND `mask`='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00100111", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("27", $row);
		
		
		# 00: 33..34
		# 20: 35
		# 30: 36..38
		# 32: 39..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00100000", $mask)."%' AND `mask`='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00110000", $mask)."%' AND `mask`>='".($mask+4)."' AND `mask`<='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00110010", $mask)."%' AND `mask`>='".($mask+7)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("32", $row);
		
		# 00: 33..34
		# 20: 35
		# 30: 36..38
		# 32: 39
		# 33: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00100000", $mask)."%' AND `mask`='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00110000", $mask)."%' AND `mask`>='".($mask+4)."' AND `mask`<='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00110010", $mask)."%' AND `mask`='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00110011", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("33", $row);
		
		# 00: 33..34
		# 20: 35
		# 30: 36..37
		# 34: 38
		# 36: 39..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00100000", $mask)."%' AND `mask`='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00110000", $mask)."%' AND `mask`>='".($mask+4)."' AND `mask`<='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00110100", $mask)."%' AND `mask`='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00110110", $mask)."%' AND `mask`>='".($mask+7)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("36", $row);
		
		# 00: 33..34
		# 20: 35
		# 30: 36..37
		# 34: 38
		# 36: 39
		# 37: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00100000", $mask)."%' AND `mask`='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00110000", $mask)."%' AND `mask`>='".($mask+4)."' AND `mask`<='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00110100", $mask)."%' AND `mask`='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00110110", $mask)."%' AND `mask`='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00110111", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("37", $row);
		
		# 00: 33
		# 40: 34
		# 60: 35..38
		# 62: 39..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000000", $mask)."%' AND `mask`='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01100000", $mask)."%' AND `mask`>='".($mask+3)."' AND `mask`<='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01100010", $mask)."%' AND `mask`>='".($mask+7)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("62", $row);
		
		# 00: 33
		# 40: 34
		# 60: 35..38
		# 62: 39
		# 63: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000000", $mask)."%' AND `mask`='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01100000", $mask)."%' AND `mask`>='".($mask+3)."' AND `mask`<='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01100010", $mask)."%' AND `mask`='".($mask+7)."' OR 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01100011", $mask)."%' AND `mask`>='".($mask+8)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("63", $row);
		
		# 00: 33
		# 40: 34
		# 60: 35..37
		# 64: 38
		# 66: 39..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000000", $mask)."%' AND `mask`='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01100000", $mask)."%' AND `mask`>='".($mask+3)."' AND `mask`<='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01100100", $mask)."%' AND `mask`='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01100110", $mask)."%' AND `mask`>='".($mask+7)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("66", $row);
		
		# 00: 33
		# 40: 34
		# 60: 35..37
		# 64: 38
		# 66: 39
		# 67: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000000", $mask)."%' AND `mask`='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01100000", $mask)."%' AND `mask`>='".($mask+3)."' AND `mask`<='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01100100", $mask)."%' AND `mask`='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01100110", $mask)."%' AND `mask`='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01100111", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("67", $row);
		
		# 00: 33
		# 40: 34
		# 60: 35
		# 70: 36..38
		# 72: 39..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000000", $mask)."%' AND `mask`='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01100000", $mask)."%' AND `mask`='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01110000", $mask)."%' AND `mask`>='".($mask+4)."' AND `mask`<='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01110010", $mask)."%' AND `mask`>='".($mask+7)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("72", $row);

		# 00: 33
		# 40: 34
		# 60: 35
		# 70: 36..38
		# 72: 39
		# 73: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000000", $mask)."%' AND `mask`='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01100000", $mask)."%' AND `mask`='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01110000", $mask)."%' AND `mask`>='".($mask+4)."' AND `mask`<='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01110010", $mask)."%' AND `mask`='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01110011", $mask)."%' AND `mask`>='".($mask+8)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("73", $row);

		# 00: 33
		# 40: 34
		# 60: 35
		# 70: 36..37
		# 74: 38
		# 76: 39..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000000", $mask)."%' AND `mask`='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01100000", $mask)."%' AND `mask`='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01110000", $mask)."%' AND `mask`>='".($mask+4)."' AND `mask`<='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01110100", $mask)."%' AND `mask`='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01110110", $mask)."%' AND `mask`>='".($mask+7)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("76", $row);

		# 00: 33
		# 40: 34
		# 60: 35
		# 70: 36..37
		# 74: 38
		# 76: 39
		# 77: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000000", $mask)."%' AND `mask`='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01100000", $mask)."%' AND `mask`='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01110000", $mask)."%' AND `mask`>='".($mask+4)."' AND `mask`<='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01110100", $mask)."%' AND `mask`='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01110110", $mask)."%' AND `mask`='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01110111", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("77", $row);

		?>
	</tr>
<!-- 7. row -->
	<tr>
		<?php 
		# 00: 33..34
		# 20: 35..36
		# 28: 37..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00100000", $mask)."%' AND `mask`>='".($mask+3)."' AND `mask`<='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00101000", $mask)."%' AND `mask`>='".($mask+5)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("28", $row);
		
		# 00: 33..34
		# 20: 35..36
		# 28: 37..39
		# 29: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00100000", $mask)."%' AND `mask`>='".($mask+3)."' AND `mask`<='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00101000", $mask)."%' AND `mask`>='".($mask+5)."' AND `mask`<='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00101001", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("29", $row);
		
		# 00: 33..34
		# 20: 35..36
		# 28: 37
		# 2C: 38..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00100000", $mask)."%' AND `mask`>='".($mask+3)."' AND `mask`<='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00101000", $mask)."%' AND `mask`='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00101100", $mask)."%' AND `mask`>='".($mask+6)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("2C", $row);
		
		# 00: 33..34
		# 20: 35..36
		# 28: 37
		# 2C: 38..39
		# 2D: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00100000", $mask)."%' AND `mask`>='".($mask+3)."' AND `mask`<='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00101000", $mask)."%' AND `mask`='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00101100", $mask)."%' AND `mask`>='".($mask+6)."' AND `mask`<='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00101101", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("2D", $row);
		
		# 00: 33..34
		# 20: 35
		# 30: 36
		# 38: 37..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00100000", $mask)."%' AND `mask`='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00110000", $mask)."%' AND `mask`='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00111000", $mask)."%' AND `mask`>='".($mask+5)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("38", $row);
		
		# 00: 33..34
		# 20: 35
		# 30: 36
		# 38: 37..39
		# 39: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00100000", $mask)."%' AND `mask`='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00110000", $mask)."%' AND `mask`='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00111000", $mask)."%' AND `mask`>='".($mask+5)."' AND `mask`<='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00111001", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("39", $row);
		
		# 00: 33..34
		# 20: 35
		# 30: 36
		# 38: 37
		# 3C: 38..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00100000", $mask)."%' AND `mask`='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00110000", $mask)."%' AND `mask`='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00111000", $mask)."%' AND `mask`='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00111100", $mask)."%' AND `mask`>='".($mask+6)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("3C", $row);
		
		# 00: 33..34
		# 20: 35
		# 30: 36
		# 38: 37
		# 3C: 38..39
		# 3D: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00100000", $mask)."%' AND `mask`='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00110000", $mask)."%' AND `mask`='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00111000", $mask)."%' AND `mask`='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00111100", $mask)."%' AND `mask`>='".($mask+6)."' AND `mask`<='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00111101", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("3D", $row);
		
		
		# 00: 33
		# 40: 34
		# 60: 35..36
		# 68: 37..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000000", $mask)."%' AND `mask`='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01100000", $mask)."%' AND `mask`>='".($mask+3)."' AND `mask`<='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01101000", $mask)."%' AND `mask`>='".($mask+5)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("68", $row);
		
		# 00: 33
		# 40: 34
		# 60: 35..36
		# 68: 37..39
		# 69: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000000", $mask)."%' AND `mask`='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01100000", $mask)."%' AND `mask`>='".($mask+3)."' AND `mask`<='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01101000", $mask)."%' AND `mask`>='".($mask+5)."' AND `mask`<='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01101001", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("69", $row);

		# 00: 33
		# 40: 34
		# 60: 35..36
		# 68: 37
		# 6C: 38..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000000", $mask)."%' AND `mask`='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01100000", $mask)."%' AND `mask`>='".($mask+3)."' AND `mask`<='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01101000", $mask)."%' AND `mask`='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01101100", $mask)."%' AND `mask`>='".($mask+6)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("6C", $row);
		
		# 00: 33
		# 40: 34
		# 60: 35..36
		# 68: 37
		# 6C: 38..39
		# 6D: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000000", $mask)."%' AND `mask`='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01100000", $mask)."%' AND `mask`>='".($mask+3)."' AND `mask`<='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01101000", $mask)."%' AND `mask`='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01101100", $mask)."%' AND `mask`>='".($mask+6)."' AND `mask`<='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01101101", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("6D", $row);
		
		# 00: 33
		# 40: 34
		# 60: 35
		# 70: 36
		# 78: 37..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000000", $mask)."%' AND `mask`='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01100000", $mask)."%' AND `mask`='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01110000", $mask)."%' AND `mask`='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01111000", $mask)."%' AND `mask`>='".($mask+5)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("78", $row);

		# 00: 33
		# 40: 34
		# 60: 35
		# 70: 36
		# 78: 37..39
		# 79: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000000", $mask)."%' AND `mask`='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01100000", $mask)."%' AND `mask`='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01110000", $mask)."%' AND `mask`='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01111000", $mask)."%' AND `mask`>='".($mask+5)."' AND `mask`<='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01111001", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("79", $row);

		# 00: 33
		# 40: 34
		# 60: 35
		# 70: 36
		# 78: 37
		# 7C: 38..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000000", $mask)."%' AND `mask`='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01100000", $mask)."%' AND `mask`='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01110000", $mask)."%' AND `mask`='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01111000", $mask)."%' AND `mask`='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01111100", $mask)."%' AND `mask`>='".($mask+6)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("7C", $row);

		# 00: 33
		# 40: 34
		# 60: 35
		# 70: 36
		# 78: 37
		# 7C: 38..39
		# 7D: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000000", $mask)."%' AND `mask`='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01100000", $mask)."%' AND `mask`='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01110000", $mask)."%' AND `mask`='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01111000", $mask)."%' AND `mask`='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01111100", $mask)."%' AND `mask`>='".($mask+6)."' AND `mask`<='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01111101", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("7D", $row);
		?>
	</tr>
<!-- 8. row -->
	<tr>
		<?php 
		# 00: 33..34
		# 20: 35..36
		# 28: 37..38
		# 2A: 39..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00100000", $mask)."%' AND `mask`>='".($mask+3)."' AND `mask`<='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00101000", $mask)."%' AND `mask`>='".($mask+5)."' AND `mask`<='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00101010", $mask)."%' AND `mask`>='".($mask+7)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("2A", $row);
		
		# 00: 33..34
		# 20: 35..36
		# 28: 37..38
		# 2A: 39
		# 2B: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00100000", $mask)."%' AND `mask`>='".($mask+3)."' AND `mask`<='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00101000", $mask)."%' AND `mask`>='".($mask+5)."' AND `mask`<='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00101010", $mask)."%' AND `mask`='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00101011", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("2B", $row);
		
		# 00: 33..34
		# 20: 35..36
		# 28: 37
		# 2C: 38
		# 2E: 39..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00100000", $mask)."%' AND `mask`>='".($mask+3)."' AND `mask`<='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00101000", $mask)."%' AND `mask`='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00101100", $mask)."%' AND `mask`='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00101110", $mask)."%' AND `mask`>='".($mask+7)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("2E", $row);
		
		# 00: 33..34
		# 20: 35..36
		# 28: 37
		# 2C: 38
		# 2E: 39
		# 2F: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00100000", $mask)."%' AND `mask`>='".($mask+3)."' AND `mask`<='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00101000", $mask)."%' AND `mask`='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00101100", $mask)."%' AND `mask`='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00101110", $mask)."%' AND `mask`='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00101111", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("2F", $row);
		
		# 00: 33..34
		# 20: 35
		# 30: 36
		# 38: 37..38
		# 3A: 39..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00100000", $mask)."%' AND `mask`='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00110000", $mask)."%' AND `mask`='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00111000", $mask)."%' AND `mask`>='".($mask+5)."' AND `mask`<='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00111010", $mask)."%' AND `mask`>='".($mask+7)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("3A", $row);
		
		# 00: 33..34
		# 20: 35
		# 30: 36
		# 38: 37..38
		# 3A: 39
		# 3B: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00100000", $mask)."%' AND `mask`='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00110000", $mask)."%' AND `mask`='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00111000", $mask)."%' AND `mask`>='".($mask+5)."' AND `mask`<='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00111010", $mask)."%' AND `mask`='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00111011", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("3B", $row);
		
		# 00: 33..34
		# 20: 35
		# 30: 36
		# 38: 37
		# 3C: 38
		# 3E: 39..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00100000", $mask)."%' AND `mask`='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00110000", $mask)."%' AND `mask`='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00111000", $mask)."%' AND `mask`='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00111100", $mask)."%' AND `mask`='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00111110", $mask)."%' AND `mask`>='".($mask+7)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("3E", $row);
		
		# 00: 33..34
		# 20: 35
		# 30: 36
		# 38: 37
		# 3C: 38
		# 3E: 39
		# 3F: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00100000", $mask)."%' AND `mask`='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00110000", $mask)."%' AND `mask`='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00111000", $mask)."%' AND `mask`='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00111100", $mask)."%' AND `mask`='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00111110", $mask)."%' AND `mask`='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00111111", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("3F", $row);
		
		# 00: 33
		# 40: 34
		# 60: 35..36
		# 68: 37..38
		# 6A: 39..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000000", $mask)."%' AND `mask`='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01100000", $mask)."%' AND `mask`>='".($mask+3)."' AND `mask`<='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01101000", $mask)."%' AND `mask`>='".($mask+5)."' AND `mask`<='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01101010", $mask)."%' AND `mask`>='".($mask+7)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("6A", $row);

		# 00: 33
		# 40: 34
		# 60: 35..36
		# 68: 37..38
		# 6A: 39
		# 6B: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000000", $mask)."%' AND `mask`='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01100000", $mask)."%' AND `mask`>='".($mask+3)."' AND `mask`<='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01101000", $mask)."%' AND `mask`>='".($mask+5)."' AND `mask`<='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01101010", $mask)."%' AND `mask`='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01101011", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("6B", $row);

		# 00: 33
		# 40: 34
		# 60: 35..36
		# 68: 37
		# 6C: 38
		# 6E: 39..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000000", $mask)."%' AND `mask`='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01100000", $mask)."%' AND `mask`>='".($mask+3)."' AND `mask`<='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01101000", $mask)."%' AND `mask`='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01101100", $mask)."%' AND `mask`='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01101110", $mask)."%' AND `mask`>='".($mask+7)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("6E", $row);
		
		# 00: 33
		# 40: 34
		# 60: 35..36
		# 68: 37
		# 6C: 38
		# 6E: 39
		# 6F: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000000", $mask)."%' AND `mask`='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01100000", $mask)."%' AND `mask`>='".($mask+3)."' AND `mask`<='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01101000", $mask)."%' AND `mask`='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01101100", $mask)."%' AND `mask`='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01101110", $mask)."%' AND `mask`='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01101111", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("6F", $row);
		

		# 00: 33
		# 40: 34
		# 60: 35
		# 70: 36
		# 68: 37..38
		# 6A: 39..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000000", $mask)."%' AND `mask`='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01100000", $mask)."%' AND `mask`='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01110000", $mask)."%' AND `mask`='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01111000", $mask)."%' AND `mask`>='".($mask+5)."' AND `mask`<='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01111010", $mask)."%' AND `mask`>='".($mask+7)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("7A", $row);

		# 00: 33
		# 40: 34
		# 60: 35
		# 70: 36
		# 78: 37..38
		# 7A: 39
		# 7B: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000000", $mask)."%' AND `mask`='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01100000", $mask)."%' AND `mask`='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01110000", $mask)."%' AND `mask`='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01111000", $mask)."%' AND `mask`>='".($mask+5)."' AND `mask`<='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01111010", $mask)."%' AND `mask`='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01111011", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("7B", $row);

		# 00: 33
		# 40: 34
		# 60: 35
		# 70: 36
		# 78: 37
		# 7C: 38
		# 7E: 39..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000000", $mask)."%' AND `mask`='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01100000", $mask)."%' AND `mask`='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01110000", $mask)."%' AND `mask`='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01111000", $mask)."%' AND `mask`='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01111100", $mask)."%' AND `mask`='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01111110", $mask)."%' AND `mask`>='".($mask+7)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("7E", $row);

		# 00: 33
		# 40: 34
		# 60: 35
		# 70: 36
		# 68: 37
		# 6C: 38
		# 6E: 39
		# 6F: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "00000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01000000", $mask)."%' AND `mask`='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01100000", $mask)."%' AND `mask`='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01110000", $mask)."%' AND `mask`='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01111000", $mask)."%' AND `mask`='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01111100", $mask)."%' AND `mask`='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01111110", $mask)."%' AND `mask`='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "01111111", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("7F", $row);

		?>
	</tr>
<!-- 9. row -->
	<tr>
		<?php 	
		# 80: 33..40
		$q="SELECT * FROM  `ipamv6` WHERE (
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`>'".($mask)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("80", $row);


		# 80: 33..39
		# 81: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000001", $mask)."%' AND `mask`>='".($mask+8)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("81", $row);


		# 80: 33..37
		# 84: 38..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000100", $mask)."%' AND `mask`>='".($mask+6)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("84", $row);


		# 80: 33..37
		# 84: 38..39
		# 85: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000100", $mask)."%' AND `mask`>='".($mask+6)."' AND `mask`<='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000101", $mask)."%' AND `mask`>='".($mask+8)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("85", $row);


		# 80: 33..35
		# 90: 36..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10010000", $mask)."%' AND `mask`>='".($mask+4)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("90", $row);


		# 80: 33..35
		# 90: 36..39
		# 91: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10010000", $mask)."%' AND `mask`>='".($mask+4)."' AND `mask`<='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10010001", $mask)."%' AND `mask`>='".($mask+8)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("91", $row);


		# 80: 33..35
		# 90: 36..37
		# 94: 38..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10010000", $mask)."%' AND `mask`>='".($mask+4)."' AND `mask`<='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10010100", $mask)."%' AND `mask`>='".($mask+6)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("94", $row);


		# 80: 33,34,35
		# 90: 36,37
		# 94: 38...39
		# 95: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10010000", $mask)."%' AND `mask`>='".($mask+4)."' AND `mask`<='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10010100", $mask)."%' AND `mask`>='".($mask+6)."' AND `mask`<='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10010101", $mask)."%' AND `mask`>='".($mask+8)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("95", $row);

		# 80: 33
		# C0: 34..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000000", $mask)."%' AND `mask`>='".($mask+2)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("C0", $row);
		
		# 80: 33
		# C0: 34..39
		# C1: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000000", $mask)."%' AND `mask`>='".($mask+2)."' AND `mask`<='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000001", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("C1", $row);
		
		
		# 80: 33
		# C0: 34..37
		# C4: 38..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000000", $mask)."%' AND `mask`>='".($mask+2)."' AND `mask`<='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000100", $mask)."%' AND `mask`>='".($mask+6)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("C4", $row);
		
		# 80: 33
		# C0: 34..37
		# C4: 38..39
		# C5: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000000", $mask)."%' AND `mask`>='".($mask+2)."' AND `mask`<='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000100", $mask)."%' AND `mask`>='".($mask+6)."' AND `mask`<='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000101", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("C5", $row);
		
		
		# 80: 33
		# C0: 34..35
		# D0: 36..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000000", $mask)."%' AND `mask`>='".($mask+2)."' AND `mask`<='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11010000", $mask)."%' AND `mask`>='".($mask+4)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("D0", $row);
		
		# 80: 33
		# C0: 34..35
		# D0: 36..39
		# D1: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000000", $mask)."%' AND `mask`>='".($mask+2)."' AND `mask`<='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11010000", $mask)."%' AND `mask`>='".($mask+4)."' AND `mask`<='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11010001", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("D1", $row);
		
		# 80: 33
		# C0: 34..35
		# D0: 36..37
		# D4: 38..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000000", $mask)."%' AND `mask`>='".($mask+2)."' AND `mask`<='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11010000", $mask)."%' AND `mask`>='".($mask+4)."' AND `mask`<='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11010100", $mask)."%' AND `mask`>='".($mask+6)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("D4", $row);
		
		# 80: 33
		# C0: 34..35
		# D0: 36..37
		# D4: 38..39
		# D5: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000000", $mask)."%' AND `mask`>='".($mask+2)."' AND `mask`<='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11010000", $mask)."%' AND `mask`>='".($mask+4)."' AND `mask`<='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11010100", $mask)."%' AND `mask`>='".($mask+6)."' AND `mask`<='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11010101", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("D5", $row);
		?>
	</tr>
<!-- 10. row -->
	<tr>
		<?php 

		# 80: 33,34,35,36,37,38
		# 82: 39,40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000010", $mask)."%' AND `mask`>='".($mask+7)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("82", $row);


		# 80: 33,34,35,36,37,38
		# 82: 39
		# 83: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000010", $mask)."%' AND `mask`='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000011", $mask)."%' AND `mask`>='".($mask+8)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("83", $row);


		# 80: 33,34,35,36,37
		# 84: 38
		# 86: 39,40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000100", $mask)."%' AND `mask`='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000110", $mask)."%' AND `mask`>='".($mask+7)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("86", $row);

			
		# 80: 33,34,35,36,37
		# 84: 38
		# 86: 39
		# 87: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000100", $mask)."%' AND `mask`='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000110", $mask)."%' AND `mask`='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000111", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("87", $row);

			
		# 80: 33,34,35
		# 90: 36..38
		# 92: 39..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10010000", $mask)."%' AND `mask`>='".($mask+4)."' AND `mask`<='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10010010", $mask)."%' AND `mask`>='".($mask+7)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("92", $row);


		# 80: 33,34,35
		# 90: 36..38
		# 92: 39
		# 93: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10010000", $mask)."%' AND `mask`>='".($mask+4)."' AND `mask`<='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10010010", $mask)."%' AND `mask`='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10010011", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("93", $row);


		# 80: 33,34,35
		# 90: 36,37
		# 94: 38
		# 96: 39,40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10010000", $mask)."%' AND `mask`>='".($mask+4)."' AND `mask`<='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10010100", $mask)."%' AND `mask`='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10010110", $mask)."%' AND `mask`>='".($mask+7)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("96", $row);

			
		# 80: 33,34,35
		# 90: 36, 37
		# 94: 38
		# 96: 39
		# 97: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10010000", $mask)."%' AND `mask`>='".($mask+4)."' AND `mask`<='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10010100", $mask)."%' AND `mask`='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10010110", $mask)."%' AND `mask`='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10010111", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("97", $row);
		
		
		# 80: 33
		# C0: 34..38
		# C2: 39..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000000", $mask)."%' AND `mask`>='".($mask+2)."' AND `mask`<='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000010", $mask)."%' AND `mask`>='".($mask+7)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("C2", $row);
		
		
		# 80: 33
		# C0: 34..38
		# C2: 39
		# C3: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000000", $mask)."%' AND `mask`>='".($mask+2)."' AND `mask`<='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000010", $mask)."%' AND `mask`='".($mask+7)."' OR 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000011", $mask)."%' AND `mask`>='".($mask+7)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("C3", $row);
		
		
		# 80: 33
		# C0: 34..37
		# C4: 38
		# C6: 39..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000000", $mask)."%' AND `mask`>='".($mask+2)."' AND `mask`<='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000100", $mask)."%' AND `mask`='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000110", $mask)."%' AND `mask`>='".($mask+7)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("C6", $row);
		
		# 80: 33
		# C0: 34..37
		# C4: 38
		# C6: 39
		# C7: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000000", $mask)."%' AND `mask`>='".($mask+2)."' AND `mask`<='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000100", $mask)."%' AND `mask`='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000110", $mask)."%' AND `mask`='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000111", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("C7", $row);
		
		
		# 80: 33
		# C0: 34..35
		# D0: 36..38
		# D2: 39..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000000", $mask)."%' AND `mask`>='".($mask+2)."' AND `mask`<='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11010000", $mask)."%' AND `mask`>='".($mask+4)."' AND `mask`<='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11010010", $mask)."%' AND `mask`>='".($mask+7)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("D2", $row);
		
		# 80: 33
		# C0: 34..35
		# D0: 36..38
		# D2: 39
		# D3: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000000", $mask)."%' AND `mask`>='".($mask+2)."' AND `mask`<='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11010000", $mask)."%' AND `mask`>='".($mask+4)."' AND `mask`<='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11010010", $mask)."%' AND `mask`='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11010011", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("D3", $row);

		# 80: 33
		# C0: 34..35
		# D0: 36..37
		# D4: 38
		# D6: 39..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000000", $mask)."%' AND `mask`>='".($mask+2)."' AND `mask`<='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11010000", $mask)."%' AND `mask`>='".($mask+4)."' AND `mask`<='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11010100", $mask)."%' AND `mask`='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11010110", $mask)."%' AND `mask`>='".($mask+7)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("D6", $row);
		
		# 80: 33
		# C0: 34..35
		# D0: 36..37
		# D4: 38
		# D6: 39
		# D7: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000000", $mask)."%' AND `mask`>='".($mask+2)."' AND `mask`<='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11010000", $mask)."%' AND `mask`>='".($mask+4)."' AND `mask`<='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11010100", $mask)."%' AND `mask`='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11010110", $mask)."%' AND `mask`='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11010111", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("D7", $row);
		?>
	</tr>
<!-- 11. row -->
	<tr>
		<?php 

		# 80: 33,34,35,36
		# 88: 37...40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10001000", $mask)."%' AND `mask`>='".($mask+5)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("88", $row);
			
		# 80: 33,34,35,36
		# 88: 37...39
		# 89: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10001000", $mask)."%' AND `mask`>='".($mask+5)."' AND `mask`<='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10001001", $mask)."%' AND `mask`>='".($mask+8)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("89", $row);


		# 80: 33,34,35,36
		# 88: 37
		# 8C: 38,39,40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10001000", $mask)."%' AND `mask`='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10001100", $mask)."%' AND `mask`>='".($mask+6)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("8C", $row);

			
		# 80: 33,34,35,36
		# 88: 37
		# 8C: 38,39
		# 8D: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10001000", $mask)."%' AND `mask`='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10001100", $mask)."%' AND `mask`>='".($mask+6)."' AND `mask`<='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10001101", $mask)."%' AND `mask`>='".($mask+8)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("8D", $row);


		# 80: 33,34,35
		# 90: 36
		# 98: 37...40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10010000", $mask)."%' AND `mask`='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10011000", $mask)."%' AND `mask`>='".($mask+5)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("98", $row);

			
		# 80: 33,34,35
		# 90: 36
		# 98: 37...39
		# 99: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10010000", $mask)."%' AND `mask`='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10011000", $mask)."%' AND `mask`>='".($mask+5)."' AND `mask`<='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10011001", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("99", $row);

			
		# 80: 33,34,35
		# 90: 36
		# 98: 37
		# 9C: 38..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10010000", $mask)."%' AND `mask`='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10011000", $mask)."%' AND `mask`='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10011100", $mask)."%' AND `mask`>='".($mask+6)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("9C", $row);

			
		# 80: 33,34,35
		# 90: 36
		# 98: 37
		# 9C: 38,39
		# 9D: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10010000", $mask)."%' AND `mask`='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10011000", $mask)."%' AND `mask`='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10011100", $mask)."%' AND `mask`>='".($mask+6)."' AND `mask`<='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10011101", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("9D", $row);

		# 80: 33
		# C0: 34..36
		# C8: 37..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000000", $mask)."%' AND `mask`>='".($mask+2)."' AND `mask`<='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11001000", $mask)."%' AND `mask`>='".($mask+5)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("C8", $row);
		
		# 80: 33
		# C0: 34..36
		# C8: 37..39
		# C9: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000000", $mask)."%' AND `mask`>='".($mask+2)."' AND `mask`<='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11001000", $mask)."%' AND `mask`>='".($mask+5)."' AND `mask`<='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11001001", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("C9", $row);

		# 80: 33
		# C0: 34..36
		# C8: 37
		# CC: 38..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000000", $mask)."%' AND `mask`>='".($mask+2)."' AND `mask`<='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11001000", $mask)."%' AND `mask`='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11001100", $mask)."%' AND `mask`>='".($mask+6)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("CC", $row);
		
		
		# 80: 33
		# C0: 34..36
		# C8: 37
		# CC: 38..39
		# CD: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000000", $mask)."%' AND `mask`>='".($mask+2)."' AND `mask`<='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11001000", $mask)."%' AND `mask`='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11001100", $mask)."%' AND `mask`>='".($mask+6)."' AND `mask`<='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11001101", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("CD", $row);
		
		# 80: 33
		# C0: 34..35
		# D0: 36
		# D8: 37..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000000", $mask)."%' AND `mask`>='".($mask+2)."' AND `mask`<='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11010000", $mask)."%' AND `mask`='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11011000", $mask)."%' AND `mask`>='".($mask+5)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("D8", $row);
		
		# 80: 33
		# C0: 34..35
		# D0: 36
		# D8: 37..39
		# D9: 41
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000000", $mask)."%' AND `mask`>='".($mask+2)."' AND `mask`<='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11010000", $mask)."%' AND `mask`='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11011000", $mask)."%' AND `mask`>='".($mask+5)."' AND `mask`<='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11011001", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("D9", $row);
		
		
		# 80: 33
		# C0: 34..35
		# D0: 36
		# D8: 37
		# DC: 38..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000000", $mask)."%' AND `mask`>='".($mask+2)."' AND `mask`<='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11010000", $mask)."%' AND `mask`='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11011000", $mask)."%' AND `mask`='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11011100", $mask)."%' AND `mask`>='".($mask+6)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("DC", $row);
		
		# 80: 33
		# C0: 34..35
		# D0: 36
		# D8: 37
		# DC: 38..39
		# DD: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000000", $mask)."%' AND `mask`>='".($mask+2)."' AND `mask`<='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11010000", $mask)."%' AND `mask`='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11011000", $mask)."%' AND `mask`='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11011100", $mask)."%' AND `mask`>='".($mask+6)."' AND `mask`<='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11011101", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("DD", $row);

		?>
	</tr>
<!-- 12. row -->
	<tr>
		<?php 

		# 80: 33,34,35,36
		# 88: 37...38
		# 8A: 39,40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10001000", $mask)."%' AND `mask`>='".($mask+5)."' AND `mask`<='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10001010", $mask)."%' AND `mask`>='".($mask+7)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("8A", $row);

			

		# 80: 33,34,35,36
		# 88: 37...38
		# 8A: 39
		# 8B: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10001000", $mask)."%' AND `mask`>='".($mask+5)."' AND `mask`<='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10001010", $mask)."%' AND `mask`='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10001011", $mask)."%' AND `mask`>='".($mask+8)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("8B", $row);


		# 80: 33,34,35,36
		# 88: 37
		# 8C: 38
		# 8E: 39,40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10001000", $mask)."%' AND `mask`='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10001100", $mask)."%' AND `mask`='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10001110", $mask)."%' AND `mask`>='".($mask+7)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("8E", $row);

			
		# 80: 33,34,35,36
		# 88: 37
		# 8C: 38
		# 8E: 39
		# 8F: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10001000", $mask)."%' AND `mask`='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10001100", $mask)."%' AND `mask`='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10001110", $mask)."%' AND `mask`='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10001111", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("8F", $row);

			
		# 80: 33,34,35
		# 90: 36
		# 98: 37,38
		# 9A: 39,40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10010000", $mask)."%' AND `mask`='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10011000", $mask)."%' AND `mask`>='".($mask+5)."' AND `mask`<='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10011010", $mask)."%' AND `mask`>='".($mask+7)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("9A", $row);

			
		# 80: 33,34,35
		# 90: 36
		# 98: 37,38
		# 9A: 39
		# 9B: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10010000", $mask)."%' AND `mask`='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10011000", $mask)."%' AND `mask`>='".($mask+5)."' AND `mask`<='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10011010", $mask)."%' AND `mask`='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10011011", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("9B", $row);


		# 80: 33,34,35
		# 90: 36
		# 98: 37
		# 9C: 38
		# 9E: 39,40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10010000", $mask)."%' AND `mask`='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10011000", $mask)."%' AND `mask`='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10011100", $mask)."%' AND `mask`='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10011110", $mask)."%' AND `mask`>='".($mask+7)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("9E", $row);


		# 80: 33,34,35
		# 90: 36
		# 98: 37
		# 9C: 38
		# 9E: 39
		# 9F: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10010000", $mask)."%' AND `mask`='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10011000", $mask)."%' AND `mask`='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10011100", $mask)."%' AND `mask`='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10011110", $mask)."%' AND `mask`='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10011111", $mask)."%' AND `mask`>='".($mask+8)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("9F", $row);

		# 80: 33
		# C0: 34..36
		# C8: 37..38
		# CA: 39..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000000", $mask)."%' AND `mask`>='".($mask+2)."' AND `mask`<='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11001000", $mask)."%' AND `mask`>='".($mask+5)."' AND `mask`<='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11001010", $mask)."%' AND `mask`>='".($mask+7)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("CA", $row);
		
		# 80: 33
		# C0: 34..36
		# C8: 37..38
		# CA: 39
		# CB: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000000", $mask)."%' AND `mask`>='".($mask+2)."' AND `mask`<='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11001000", $mask)."%' AND `mask`>='".($mask+5)."' AND `mask`<='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11001010", $mask)."%' AND `mask`='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11001011", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("CB", $row);

		
		# 80: 33
		# C0: 34..36
		# C8: 37
		# CC: 38
		# CE: 39..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000000", $mask)."%' AND `mask`>='".($mask+2)."' AND `mask`<='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11001000", $mask)."%' AND `mask`='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11001100", $mask)."%' AND `mask`='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11001110", $mask)."%' AND `mask`>='".($mask+7)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("CE", $row);
		
		
		# 80: 33
		# C0: 34..36
		# C8: 37
		# CC: 38
		# CE: 39
		# CF: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000000", $mask)."%' AND `mask`>='".($mask+2)."' AND `mask`<='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11001000", $mask)."%' AND `mask`='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11001100", $mask)."%' AND `mask`='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11001110", $mask)."%' AND `mask`='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11001111", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("CF", $row);
		
		# 80: 33
		# C0: 34..35
		# D0: 36
		# D8: 37..38
		# DA: 39..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000000", $mask)."%' AND `mask`>='".($mask+2)."' AND `mask`<='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11010000", $mask)."%' AND `mask`='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11011000", $mask)."%' AND `mask`>='".($mask+5)."' AND `mask`<='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11011010", $mask)."%' AND `mask`>='".($mask+7)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("DA", $row);
		
		# 80: 33
		# C0: 34..35
		# D0: 36
		# D8: 37..38
		# DA: 39
		# DB: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000000", $mask)."%' AND `mask`>='".($mask+2)."' AND `mask`<='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11010000", $mask)."%' AND `mask`='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11011000", $mask)."%' AND `mask`>='".($mask+5)."' AND `mask`<='".($mask+6)."'OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11011010", $mask)."%' AND `mask`='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11011011", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("DB", $row);

		# 80: 33
		# C0: 34..35
		# D0: 36
		# D8: 37
		# DC: 38
		# DE: 39..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000000", $mask)."%' AND `mask`>='".($mask+2)."' AND `mask`<='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11010000", $mask)."%' AND `mask`='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11011000", $mask)."%' AND `mask`='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11011100", $mask)."%' AND `mask`='".($mask+6)."' OR 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11011110", $mask)."%' AND `mask`>='".($mask+7)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("DE", $row);
		
		# 80: 33
		# C0: 34..35
		# D0: 36
		# D8: 37
		# DC: 38
		# DE: 39
		# DF: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000000", $mask)."%' AND `mask`>='".($mask+2)."' AND `mask`<='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11010000", $mask)."%' AND `mask`='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11011000", $mask)."%' AND `mask`='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11011100", $mask)."%' AND `mask`='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11011110", $mask)."%' AND `mask`='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11011111", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("DF", $row);

		?>
	</tr>
<!-- 13. row -->
	<tr>
		<?php 
		# 80: 33,34
		# A0: 35..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10100000", $mask)."%' AND `mask`>='".($mask+3)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("A0", $row);

			
		# 80: 33,34
		# A0: 35..39
		# A1: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10100000", $mask)."%' AND `mask`>='".($mask+3)."' AND `mask`<='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10100001", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("A1", $row);

			
		# 80: 33,34
		# A0: 35..37
		# A4: 38..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10100000", $mask)."%' AND `mask`>='".($mask+3)."' AND `mask`<='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10100100", $mask)."%' AND `mask`>='".($mask+6)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("A4", $row);


		# 80: 33,34
		# A0: 35..37
		# A4: 38..39
		# A5: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10100000", $mask)."%' AND `mask`>='".($mask+3)."' AND `mask`<='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10100100", $mask)."%' AND `mask`>='".($mask+6)."' AND `mask`<='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10100101", $mask)."%' AND `mask`>='".($mask+8)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("A5", $row);

		
		
		# 80: 33..34
		# A0: 35
		# B0: 36..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
			`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+2)."'  OR 
			`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10100000", $mask)."%' AND `mask`='".($mask+3)."'  OR 
			`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10110000", $mask)."%' AND `mask`>='".($mask+4)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("B0", $row);
		
		# 80: 33..34
		# A0: 35
		# B0: 36..39
		# B1: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10100000", $mask)."%' AND `mask`='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10110000", $mask)."%' AND `mask`>='".($mask+4)."' AND `mask`<='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10110001", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("B1", $row);
		
		# 80: 33..34
		# A0: 35
		# B0: 36..37
		# B4: 38..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10100000", $mask)."%' AND `mask`='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10110000", $mask)."%' AND `mask`>='".($mask+4)."' AND `mask`<='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10110100", $mask)."%' AND `mask`>='".($mask+6)."' )
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("B4", $row);
		
		# 80: 33..34
		# A0: 35
		# B0: 36..37
		# B4: 38..39
		# B5: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10100000", $mask)."%' AND `mask`='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10110000", $mask)."%' AND `mask`>='".($mask+4)."' AND `mask`<='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10110100", $mask)."%' AND `mask`>='".($mask+6)."' AND `mask`<='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10110101", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("B5", $row);
		
		
		# 80: 33
		# E0: 34
		# E0: 35..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000000", $mask)."%' AND `mask`='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11100000", $mask)."%' AND `mask`>='".($mask+3)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("E0", $row);
		
		# 80: 33
		# C0: 34
		# E0: 35..39
		# E1: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000000", $mask)."%' AND `mask`='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11100000", $mask)."%' AND `mask`>='".($mask+3)."' AND `mask`<='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11100001", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("E1", $row);

		# 80: 33
		# C0: 34
		# E0: 35..37
		# E4: 38..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000000", $mask)."%' AND `mask`='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11100000", $mask)."%' AND `mask`>='".($mask+3)."' AND `mask`<='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11100100", $mask)."%' AND `mask`>='".($mask+6)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("E4", $row);
		
		# 80: 33
		# C0: 34
		# E0: 35..37
		# E4: 38..39
		# E3: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000000", $mask)."%' AND `mask`='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11100000", $mask)."%' AND `mask`>='".($mask+3)."' AND `mask`<='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11100100", $mask)."%' AND `mask`>='".($mask+6)."' AND `mask`<='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11100101", $mask)."%' AND `mask`>='".($mask+8)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("E5", $row);

		
		
		
		
		
		# 80: 33
		# C0: 34
		# E0: 35
		# F0: 36..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000000", $mask)."%' AND `mask`='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11100000", $mask)."%' AND `mask`='".($mask+3)."'  OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11110000", $mask)."%' AND `mask`>='".($mask+4)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("F0", $row);

		# 80: 33
		# C0: 34
		# E0: 35
		# F0: 36..39
		# F1: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000000", $mask)."%' AND `mask`='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11100000", $mask)."%' AND `mask`='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11110000", $mask)."%' AND `mask`>='".($mask+4)."' AND `mask`<='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11110001", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("F1", $row);

		# 80: 33
		# C0: 34
		# E0: 35
		# F0: 36..37
		# F4: 38..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000000", $mask)."%' AND `mask`='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11100000", $mask)."%' AND `mask`='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11110000", $mask)."%' AND `mask`>='".($mask+4)."' AND `mask`<='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11110100", $mask)."%' AND `mask`>='".($mask+6)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("F4", $row);

		# 80: 33
		# C0: 34
		# E0: 35
		# F0: 36..37
		# F4: 38..39
		# F5: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000000", $mask)."%' AND `mask`='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11100000", $mask)."%' AND `mask`='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11110000", $mask)."%' AND `mask`>='".($mask+4)."' AND `mask`<='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11110100", $mask)."%' AND `mask`>='".($mask+6)."' AND `mask`<='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11110101", $mask)."%' AND `mask`>='".($mask+8)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("F5", $row);
		
		?>
	</tr>
<!-- 14. row -->
	<tr>
		<?php 

		# 80: 33,34
		# A0: 35..38
		# A2: 39,40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10100000", $mask)."%' AND `mask`>='".($mask+3)."' AND `mask`<='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10100010", $mask)."%' AND `mask`>='".($mask+7)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("A2", $row);

			
		# 80: 33,34
		# A0: 35..38
		# A2: 39
		# A3: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10100000", $mask)."%' AND `mask`>='".($mask+3)."' AND `mask`<='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10100010", $mask)."%' AND `mask`='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10100011", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("A3", $row);

		# 80: 33,34
		# A0: 35..37
		# A4: 38
		# A6: 39..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10100000", $mask)."%' AND `mask`>='".($mask+3)."' AND `mask`<='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10100100", $mask)."%' AND `mask`='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10100110", $mask)."%' AND `mask`>='".($mask+7)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("A6", $row);
		
		
		# 80: 33,34
		# A0: 36..37
		# A4: 38
		# A6: 39
		# A7: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10100000", $mask)."%' AND `mask`>='".($mask+3)."' AND `mask`<='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10100100", $mask)."%' AND `mask`='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10100110", $mask)."%' AND `mask`='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10100111", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("A7", $row);
		
		
		# 80: 33..34
		# A0: 35
		# B0: 36..38
		# B2: 39..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10100000", $mask)."%' AND `mask`='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10110000", $mask)."%' AND `mask`>='".($mask+4)."' AND `mask`<='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10110010", $mask)."%' AND `mask`>='".($mask+7)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("B2", $row);
		
		# 80: 33..34
		# A0: 35
		# B0: 36..38
		# B2: 39
		# B3: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10100000", $mask)."%' AND `mask`='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10110000", $mask)."%' AND `mask`>='".($mask+4)."' AND `mask`<='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10110010", $mask)."%' AND `mask`='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10110011", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("B3", $row);
		
		# 80: 33..34
		# A0: 35
		# B0: 36..37
		# B4: 38
		# B6: 39..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10100000", $mask)."%' AND `mask`='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10110000", $mask)."%' AND `mask`>='".($mask+4)."' AND `mask`<='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10110100", $mask)."%' AND `mask`='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10110110", $mask)."%' AND `mask`>='".($mask+7)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("B6", $row);
		
		# 80: 33..34
		# A0: 35
		# B0: 36..37
		# B4: 38
		# B6: 39
		# B7: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10100000", $mask)."%' AND `mask`='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10110000", $mask)."%' AND `mask`>='".($mask+4)."' AND `mask`<='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10110100", $mask)."%' AND `mask`='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10110110", $mask)."%' AND `mask`='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10110111", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("B7", $row);
		
		# 80: 33
		# E0: 34
		# E0: 35..38
		# E2: 39..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000000", $mask)."%' AND `mask`='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11100000", $mask)."%' AND `mask`>='".($mask+3)."' AND `mask`<='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11100010", $mask)."%' AND `mask`>='".($mask+7)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("E2", $row);
		
		# 80: 33
		# E0: 34
		# E0: 35..38
		# E2: 39
		# E3: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000000", $mask)."%' AND `mask`='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11100000", $mask)."%' AND `mask`>='".($mask+3)."' AND `mask`<='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11100010", $mask)."%' AND `mask`='".($mask+7)."' OR 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11100011", $mask)."%' AND `mask`>='".($mask+8)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("E3", $row);
		
		# 80: 33
		# E0: 34
		# E0: 35..37
		# E4: 38
		# E6: 39..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000000", $mask)."%' AND `mask`='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11100000", $mask)."%' AND `mask`>='".($mask+3)."' AND `mask`<='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11100100", $mask)."%' AND `mask`='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11100110", $mask)."%' AND `mask`>='".($mask+7)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("E6", $row);
		
		# 80: 33
		# E0: 34
		# E0: 35..37
		# E4: 38
		# E6: 39
		# E7: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000000", $mask)."%' AND `mask`='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11100000", $mask)."%' AND `mask`>='".($mask+3)."' AND `mask`<='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11100100", $mask)."%' AND `mask`='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11100110", $mask)."%' AND `mask`='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11100111", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("E7", $row);
		
		# 80: 33
		# E0: 34
		# E0: 35
		# F0: 36..38
		# F2: 39..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000000", $mask)."%' AND `mask`='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11100000", $mask)."%' AND `mask`='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11110000", $mask)."%' AND `mask`>='".($mask+4)."' AND `mask`<='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11110010", $mask)."%' AND `mask`>='".($mask+7)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("F2", $row);

		# 80: 33
		# E0: 34
		# E0: 35
		# F0: 36..38
		# F2: 39
		# F3: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000000", $mask)."%' AND `mask`='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11100000", $mask)."%' AND `mask`='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11110000", $mask)."%' AND `mask`>='".($mask+4)."' AND `mask`<='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11110010", $mask)."%' AND `mask`='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11110011", $mask)."%' AND `mask`>='".($mask+8)."') 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("F3", $row);

		# 80: 33
		# E0: 34
		# E0: 35
		# F0: 36..37
		# F4: 38
		# F6: 39..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000000", $mask)."%' AND `mask`='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11100000", $mask)."%' AND `mask`='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11110000", $mask)."%' AND `mask`>='".($mask+4)."' AND `mask`<='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11110100", $mask)."%' AND `mask`='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11110110", $mask)."%' AND `mask`>='".($mask+7)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("F6", $row);

		# 80: 33
		# E0: 34
		# E0: 35
		# F0: 36..37
		# F4: 38
		# F6: 39
		# F7: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000000", $mask)."%' AND `mask`='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11100000", $mask)."%' AND `mask`='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11110000", $mask)."%' AND `mask`>='".($mask+4)."' AND `mask`<='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11110100", $mask)."%' AND `mask`='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11110110", $mask)."%' AND `mask`='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11110111", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("F7", $row);

		?>
	</tr>
<!-- 15. row -->
	<tr>
		<?php 
		# 80: 33..34
		# A0: 35..36
		# A8: 37..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10100000", $mask)."%' AND `mask`>='".($mask+3)."' AND `mask`<='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10101000", $mask)."%' AND `mask`>='".($mask+5)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("A8", $row);
		
		# 80: 33..34
		# A0: 35..36
		# A8: 37..39
		# A9: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10100000", $mask)."%' AND `mask`>='".($mask+3)."' AND `mask`<='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10101000", $mask)."%' AND `mask`>='".($mask+5)."' AND `mask`<='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10101001", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("A9", $row);
		
		# 80: 33..34
		# A0: 35..36
		# A8: 37
		# AC: 38..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10100000", $mask)."%' AND `mask`>='".($mask+3)."' AND `mask`<='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10101000", $mask)."%' AND `mask`='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10101100", $mask)."%' AND `mask`>='".($mask+6)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("AC", $row);
		
		# 80: 33..34
		# A0: 35..36
		# A8: 37
		# AC: 38..39
		# AD: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10100000", $mask)."%' AND `mask`>='".($mask+3)."' AND `mask`<='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10101000", $mask)."%' AND `mask`='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10101100", $mask)."%' AND `mask`>='".($mask+6)."' AND `mask`<='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10101101", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("AD", $row);
		
		# 80: 33..34
		# A0: 35
		# B0: 36
		# B8: 37..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10100000", $mask)."%' AND `mask`='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10110000", $mask)."%' AND `mask`='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10111000", $mask)."%' AND `mask`>='".($mask+5)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("B8", $row);
		
		# 80: 33..34
		# A0: 35
		# B0: 36
		# B8: 37..39
		# B9: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10100000", $mask)."%' AND `mask`='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10110000", $mask)."%' AND `mask`='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10111000", $mask)."%' AND `mask`>='".($mask+5)."' AND `mask`<='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10111001", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("B9", $row);
		
		# 80: 33..34
		# A0: 35
		# B0: 36
		# B8: 37
		# BC: 38..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10100000", $mask)."%' AND `mask`='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10110000", $mask)."%' AND `mask`='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10111000", $mask)."%' AND `mask`='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10111100", $mask)."%' AND `mask`>='".($mask+6)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("BC", $row);
		
		# 80: 33..34
		# A0: 35
		# B0: 36
		# B8: 37
		# BC: 38..39
		# BD: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10100000", $mask)."%' AND `mask`='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10110000", $mask)."%' AND `mask`='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10111000", $mask)."%' AND `mask`='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10111100", $mask)."%' AND `mask`>='".($mask+6)."' AND `mask`<='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10111101", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("BD", $row);
		
		
		# 80: 33
		# C0: 34
		# E0: 35..36
		# E8: 37..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000000", $mask)."%' AND `mask`='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11100000", $mask)."%' AND `mask`>='".($mask+3)."' AND `mask`<='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11101000", $mask)."%' AND `mask`>='".($mask+5)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("E8", $row);
		
		# 80: 33
		# C0: 34
		# E0: 35..36
		# E8: 37..39
		# E9: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000000", $mask)."%' AND `mask`='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11100000", $mask)."%' AND `mask`>='".($mask+3)."' AND `mask`<='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11101000", $mask)."%' AND `mask`>='".($mask+5)."' AND `mask`<='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11101001", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("E9", $row);

		# 80: 33
		# C0: 34
		# E0: 35..36
		# E8: 37
		# EC: 38..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000000", $mask)."%' AND `mask`='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11100000", $mask)."%' AND `mask`>='".($mask+3)."' AND `mask`<='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11101000", $mask)."%' AND `mask`='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11101100", $mask)."%' AND `mask`>='".($mask+6)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("EC", $row);
		
		# 80: 33
		# C0: 34
		# E0: 35..36
		# E8: 37
		# EC: 38..39
		# ED: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000000", $mask)."%' AND `mask`='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11100000", $mask)."%' AND `mask`>='".($mask+3)."' AND `mask`<='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11101000", $mask)."%' AND `mask`='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11101100", $mask)."%' AND `mask`>='".($mask+6)."' AND `mask`<='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11101101", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("ED", $row);
		
		# 80: 33
		# C0: 34
		# E0: 35
		# F0: 36
		# F8: 37..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000000", $mask)."%' AND `mask`='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11100000", $mask)."%' AND `mask`='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11110000", $mask)."%' AND `mask`='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11111000", $mask)."%' AND `mask`>='".($mask+5)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("F8", $row);

		# 80: 33
		# C0: 34
		# E0: 35
		# F0: 36
		# F8: 37..39
		# F9: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000000", $mask)."%' AND `mask`='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11100000", $mask)."%' AND `mask`='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11110000", $mask)."%' AND `mask`='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11111000", $mask)."%' AND `mask`>='".($mask+5)."' AND `mask`<='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11111001", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("F9", $row);

		# 80: 33
		# C0: 34
		# E0: 35
		# F0: 36
		# F8: 37
		# FC: 38..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000000", $mask)."%' AND `mask`='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11100000", $mask)."%' AND `mask`='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11110000", $mask)."%' AND `mask`='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11111000", $mask)."%' AND `mask`='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11111100", $mask)."%' AND `mask`>='".($mask+6)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("FC", $row);

		# 80: 33
		# C0: 34
		# E0: 35
		# F0: 36
		# F8: 37
		# FC: 38..39
		# FD: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000000", $mask)."%' AND `mask`='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11100000", $mask)."%' AND `mask`='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11110000", $mask)."%' AND `mask`='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11111000", $mask)."%' AND `mask`='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11111100", $mask)."%' AND `mask`>='".($mask+6)."' AND `mask`<='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11111101", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("FD", $row);
		?>
	</tr>
<!-- 16. row -->
	<tr>
		<?php 
		# 80: 33..34
		# A0: 35..36
		# A8: 37..38
		# AA: 39..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10100000", $mask)."%' AND `mask`>='".($mask+3)."' AND `mask`<='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10101000", $mask)."%' AND `mask`>='".($mask+5)."' AND `mask`<='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10101010", $mask)."%' AND `mask`>='".($mask+7)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("AA", $row);
		
		# 80: 33..34
		# A0: 35..36
		# A8: 37..38
		# AA: 39
		# AB: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10100000", $mask)."%' AND `mask`>='".($mask+3)."' AND `mask`<='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10101000", $mask)."%' AND `mask`>='".($mask+5)."' AND `mask`<='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10101010", $mask)."%' AND `mask`='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10101011", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("AB", $row);
		
		# 80: 33..34
		# A0: 35..36
		# A8: 37
		# AC: 38
		# AE: 39..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10100000", $mask)."%' AND `mask`>='".($mask+3)."' AND `mask`<='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10101000", $mask)."%' AND `mask`='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10101100", $mask)."%' AND `mask`='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10101110", $mask)."%' AND `mask`>='".($mask+7)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("AE", $row);
		
		# 80: 33..34
		# A0: 35..36
		# A8: 37
		# AC: 38
		# AE: 39
		# AF: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10100000", $mask)."%' AND `mask`>='".($mask+3)."' AND `mask`<='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10101000", $mask)."%' AND `mask`='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10101100", $mask)."%' AND `mask`='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10101110", $mask)."%' AND `mask`='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10101111", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("AF", $row);
		
		# 80: 33..34
		# A0: 35
		# B0: 36
		# B8: 37..38
		# BA: 39..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10100000", $mask)."%' AND `mask`='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10110000", $mask)."%' AND `mask`='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10111000", $mask)."%' AND `mask`>='".($mask+5)."' AND `mask`<='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10111010", $mask)."%' AND `mask`>='".($mask+7)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("BA", $row);
		
		# 80: 33..34
		# A0: 35
		# B0: 36
		# B8: 37..38
		# BA: 39
		# BB: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10100000", $mask)."%' AND `mask`='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10110000", $mask)."%' AND `mask`='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10111000", $mask)."%' AND `mask`>='".($mask+5)."' AND `mask`<='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10111010", $mask)."%' AND `mask`='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10111011", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("BB", $row);
		
		# 80: 33..34
		# A0: 35
		# B0: 36
		# B8: 37
		# BC: 38
		# BE: 39..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10100000", $mask)."%' AND `mask`='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10110000", $mask)."%' AND `mask`='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10111000", $mask)."%' AND `mask`='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10111100", $mask)."%' AND `mask`='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10111110", $mask)."%' AND `mask`>='".($mask+7)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("BE", $row);
		
		# 80: 33..34
		# A0: 35
		# B0: 36
		# B8: 37
		# BC: 38
		# BE: 39
		# BF: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`>='".($mask+1)."' AND `mask`<='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10100000", $mask)."%' AND `mask`='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10110000", $mask)."%' AND `mask`='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10111000", $mask)."%' AND `mask`='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10111100", $mask)."%' AND `mask`='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10111110", $mask)."%' AND `mask`='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10111111", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("BF", $row);
		
		# 80: 33
		# C0: 34
		# E0: 35..36
		# E8: 37..38
		# EA: 39..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000000", $mask)."%' AND `mask`='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11100000", $mask)."%' AND `mask`>='".($mask+3)."' AND `mask`<='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11101000", $mask)."%' AND `mask`>='".($mask+5)."' AND `mask`<='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11101010", $mask)."%' AND `mask`>='".($mask+7)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("EA", $row);

		# 80: 33
		# C0: 34
		# E0: 35..36
		# E8: 37..38
		# EA: 39
		# EB: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000000", $mask)."%' AND `mask`='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11100000", $mask)."%' AND `mask`>='".($mask+3)."' AND `mask`<='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11101000", $mask)."%' AND `mask`>='".($mask+5)."' AND `mask`<='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11101010", $mask)."%' AND `mask`='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11101011", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("EB", $row);

		# 80: 33
		# C0: 34
		# E0: 35..36
		# E8: 37
		# EC: 38
		# EE: 39..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000000", $mask)."%' AND `mask`='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11100000", $mask)."%' AND `mask`>='".($mask+3)."' AND `mask`<='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11101000", $mask)."%' AND `mask`='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11101100", $mask)."%' AND `mask`='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11101110", $mask)."%' AND `mask`>='".($mask+7)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("EE", $row);
		
		# 80: 33
		# C0: 34
		# E0: 35..36
		# E8: 37
		# EC: 38
		# EE: 39
		# EF: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000000", $mask)."%' AND `mask`='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11100000", $mask)."%' AND `mask`>='".($mask+3)."' AND `mask`<='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11101000", $mask)."%' AND `mask`='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11101100", $mask)."%' AND `mask`='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11101110", $mask)."%' AND `mask`='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11101111", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("EF", $row);
		

		# 80: 33
		# C0: 34
		# E0: 35
		# F0: 36
		# E8: 37..38
		# EA: 39..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000000", $mask)."%' AND `mask`='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11100000", $mask)."%' AND `mask`='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11110000", $mask)."%' AND `mask`='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11111000", $mask)."%' AND `mask`>='".($mask+5)."' AND `mask`<='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11111010", $mask)."%' AND `mask`>='".($mask+7)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("FA", $row);

		# 80: 33
		# C0: 34
		# E0: 35
		# F0: 36
		# F8: 37..38
		# FA: 39
		# FB: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000000", $mask)."%' AND `mask`='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11100000", $mask)."%' AND `mask`='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11110000", $mask)."%' AND `mask`='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11111000", $mask)."%' AND `mask`>='".($mask+5)."' AND `mask`<='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11111010", $mask)."%' AND `mask`='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11111011", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("FB", $row);

		# 80: 33
		# C0: 34
		# E0: 35
		# F0: 36
		# F8: 37
		# FC: 38
		# FE: 39..40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000000", $mask)."%' AND `mask`='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11100000", $mask)."%' AND `mask`='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11110000", $mask)."%' AND `mask`='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11111000", $mask)."%' AND `mask`='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11111100", $mask)."%' AND `mask`='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11111110", $mask)."%' AND `mask`>='".($mask+7)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("FE", $row);

		# 80: 33
		# C0: 34
		# E0: 35
		# F0: 36
		# E8: 37
		# EC: 38
		# EE: 39
		# EF: 40
		$q="SELECT * FROM  `ipamv6` WHERE ( 
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "10000000", $mask)."%' AND `mask`='".($mask+1)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11000000", $mask)."%' AND `mask`='".($mask+2)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11100000", $mask)."%' AND `mask`='".($mask+3)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11110000", $mask)."%' AND `mask`='".($mask+4)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11111000", $mask)."%' AND `mask`='".($mask+5)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11111100", $mask)."%' AND `mask`='".($mask+6)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11111110", $mask)."%' AND `mask`='".($mask+7)."' OR
		`network_bin` LIKE  '".substr_replace($ipv6->binNetwork(), "11111111", $mask)."%' AND `mask`>='".($mask+8)."' ) 
		ORDER by mask ".NET_ORDER." LIMIT 1;";
		$res = $sql->query($q);
		$row = $sql->array_result($res);
		sudoku_cell("FF", $row);

		?>
	</tr>
</table><br />
</div>