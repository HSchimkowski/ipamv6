<?php
// only reachable through index.php
if (!defined('IPAMV6')) { http_response_code(403); exit('Forbidden'); }
?>
<form action="./?action=addNewNetwork" method="post" class="network-form">
	<?php echo csrf_field(); ?>
	<input type="hidden" name="format" value="hex">
	
	<div class="form-group">
		<label for="network">Network:</label>
		<input id="network" type="text" name="newNetwork" onkeyup="isNetworkPossible()" onblur="isNetworkPossible()">
		<span class="hint">example: 2001:db8:ab00::</span>
	</div>

	<div class="form-group">
		<label for="mask">netmask:</label>
		<input id="mask" type="text" name="newMask" value="0" onkeyup="isNetworkPossible()" onblur="isNetworkPossible()">
		<span class="hint">example: 40</span>
	</div>

	<div class="form-group">
		<label for="description">description:</label>
		<input id="description" type="text" name="newDescription">
		<span class="hint">example: Headquarter Backbone</span>
	</div>

	<div class="form-group">
		<label for="color">color:</label>
		<div class="color-input">
			<script type="text/javascript" src="jscolor/jscolor.js"></script>
			<input id="color" type="text" class="color {hash:true,pickerPosition:'right',pickerClosable:true}" name="newColour" value="#FFFFAA">
		</div>
		<span class="hint"></span>
	</div>

	<div class="form-group">
		<label for="root">root:</label>
		<input id="root" type="checkbox" name="root" value="1">
		<span class="hint">If you select this network as "root" it will show up into the left panel.</span>
	</div>

	<div class="form-group submit-group">
		<input id="save" type="submit" value="Save" disabled="disabled">
		<span id="hint" class="hint"></span>
	</div>
</form>