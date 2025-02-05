<?php

$id = $_GET['id'];

$q = "SELECT * FROM `ipamv6` WHERE `id`='".$id."' LIMIT 1;";
$res = $sql->query($q);
$row = $sql->array_result($res);

// "Root" network
$rootNetwork = new IPv6();
$rootNetwork->initBinary($row['network_bin'], $row['mask']);

?>
<form action="./" method="get">
	<input type="hidden" name="action" value="storeEditedNetwork">
	<input type="hidden" name="format" value="hex">
	<input type="hidden" name="id" value="<?php echo $_GET['id']?>">
	<table>
		<tr>
			<td>Network:</td>
			<td><input type="text" name="newNetwork" value="<?php echo $rootNetwork->getMyCompressedAddress(); ?>"></td>
		</tr>
		<tr>
			<td>netmask:</td>
			<td><input type="text" name="newMask" value="<?php echo $row['mask']; ?>"></td>
		</tr>
		<tr>
			<td>description:</td>
			<td><input type="text" name="newDescription" value="<?php echo $row['description']; ?>"></td>
		</tr>
		<tr>
			<td>colour:</td>
			<td>
				<script type="text/javascript" src="jscolor/jscolor.js"></script>
				<input type="text" class="color {hash:true,pickerPosition:'right',pickerClosable:true}" name="newColor" value="<?php echo $row['color']; ?>">
			</td>
		</tr>
		<tr>
			<td>root:</td>
			<td><input type="checkbox" name="newRoot" value="1" <?php if($row['root'] == "1"){echo "checked";}?>></td>
		</tr>
		<tr>
			<td></td>
			<td><input type="submit" value="Save"></td>
		</tr>
	</table>
</form>