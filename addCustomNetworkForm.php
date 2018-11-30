<form action="./" method="get">
	<input type="hidden" name="action" value="addNewNetwork">
	<input type="hidden" name="format" value="hex">
	
	<table>
		<tr>
			<td>Network:</td>
			<td><input id="network" type="text" name="newNetwork" onkeyup="isNetworkPossible()" onblur="isNetworkPossible()"></td>
			<td class="hint">example: 2001:db8:ab00::</td>
		</tr>
		<tr>
			<td>netmask:</td>
			<td><input id="mask" type="text" name="newMask" value="0" onkeyup="isNetworkPossible()" onblur="isNetworkPossible()"></td>
			<td class="hint">example: 40</td>
		</tr>
		<tr>
			<td>description:</td>
			<td><input type="text" name="newDescription"></td>
			<td class="hint">example: Headquarter Backbone</td>
		</tr>
		<tr>
			<td>color:</td>
			<td>
				<script type="text/javascript" src="jscolor/jscolor.js"></script>
				<input type="text" class="color {hash:true,pickerPosition:'right',pickerClosable:true}" name="newColour" value="#FFFFAA">
			</td>
			<td class="hint"></td>
		</tr>
		<tr>
			<td>root:</td>
			<td><input type="checkbox" name="root" value="1"></td>
			<td class="hint">If you select this network as "root" it will show up into the left panel.</td>
		</tr>
		<tr>
			<td></td>
			<td><input id="save" type="submit" value="Save" disabled="disabled"></td>
			<td id="hint"></td>
		</tr>
	</table>
</form>
