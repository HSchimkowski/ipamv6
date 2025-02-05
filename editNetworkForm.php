<?php

$id = $_GET['id'];

$q = "SELECT * FROM `ipamv6` WHERE `id`='".$id."' LIMIT 1;";
$res = $sql->query($q);
$row = $sql->array_result($res);

// "Root" network
$rootNetwork = new IPv6();
$rootNetwork->initBinary($row['network_bin'], $row['mask']);

?>

<form action="./" method="get" class="network-form">
	<input type="hidden" name="action" value="storeEditedNetwork">
	<input type="hidden" name="format" value="hex">
	<input type="hidden" name="id" value="<?php echo $_GET['id']?>">
	
	<div class="form-group">
		<label for="network">Network:</label>
		<input id="network" type="text" name="newNetwork" value="<?php echo $rootNetwork->getMyCompressedAddress(); ?>">
		<span class="hint"></span>
	</div>

	<div class="form-group">
		<label for="mask">netmask:</label>
		<input id="mask" type="text" name="newMask" value="<?php echo $row['mask']; ?>">
		<span class="hint"></span>
	</div>

	<div class="form-group">
		<label for="description">description:</label>
		<input id="description" type="text" name="newDescription" value="<?php echo $row['description']; ?>">
		<span class="hint"></span>
	</div>

	<div class="form-group">
		<label for="color">colour:</label>
		<div class="color-input">
			<script type="text/javascript" src="jscolor/jscolor.js"></script>
			<input id="color" type="text" class="color {hash:true,pickerPosition:'right',pickerClosable:true}" 
				   name="newColor" value="<?php echo $row['color']; ?>">
		</div>
		<span class="hint"></span>
	</div>

	<div class="form-group">
		<label for="root">root:</label>
		<input id="root" type="checkbox" name="newRoot" value="1" <?php if($row['root'] == "1"){echo "checked";}?>>
		<span class="hint">If you select this network as "root" it will show up into the left panel.</span>
	</div>

	<div class="form-group submit-group">
		<input type="submit" value="Save" class="btn btn-primary">
		<span class="hint"></span>
	</div>
</form>