<?php

$new_mask = $_GET['mask'];
$new_root = $_GET['subnet'];

// "Root" network
$rootNetwork = new IPv6();
$rootNetwork->initBinary($new_root, $new_mask);



// we must know:
// - Network Mask (not Every Mask is Possible!)
// - description
// - color


############
// detect possible network Masks

// Example:
// we got a /40 root Network (2001:db8:1000::/40),
// whitch is in bary form:
// 0010000000000001 0000110110111000 0001000000000000 0000000000000000 0000000000000000 0000000000000000 0000000000000000 0000000000000000
// |<--           network                -->||<---->| |<--          boring zeroes :)                                                  -->|
//                                               |--our interesting Part for subnetting
//
// from Our selection (+ sign) we got the Network-Part for Subnetting in barary form:
// 0010000000000001 0000110110111000 0001000011111111 0000000000000000 0000000000000000 0000000000000000 0000000000000000 0000000000000000
//
// now we can decide what Network Masks are Possible, based on the submitted network.
// we focus only on the interesting 8 bits (11111111). Lets store them into the variable $n 
$n = substr($new_root, $new_mask, 8);


/*
 * @ToDo: Prüfen ob es nicht schon Netze in diesem Bereich gubt!
 */

// now we can decide possible network masks an store them in an array $m
#if($n[0]=="0" && $n[1]=="0" && $n[2]=="0" && $n[3]=="0" && $n[4]=="0" && $n[5]=="0" && $n[6]=="0" && $n[7]=="0")
#	$m[] =($new_mask + 0);

if($n[1]=="0" && $n[2]=="0" && $n[3]=="0" && $n[4]=="0" && $n[5]=="0" && $n[6]=="0" && $n[7]=="0")
	if(!existNetwork($new_root, $new_mask + 1))
	$m[] =($new_mask + 1);

if($n[2]=="0" && $n[3]=="0" && $n[4]=="0" && $n[5]=="0" && $n[6]=="0" && $n[7]=="0")
	if(!existNetwork($new_root, $new_mask + 2))
	$m[] =($new_mask + 2);

if($n[3]=="0" && $n[4]=="0" && $n[5]=="0" && $n[6]=="0" && $n[7]=="0")
	if(!existNetwork($new_root, $new_mask + 3))
	$m[] =($new_mask + 3);

if($n[4]=="0" && $n[5]=="0" && $n[6]=="0" && $n[7]=="0")
	if(!existNetwork($new_root, $new_mask + 4))
	$m[] =($new_mask + 4);

if($n[5]=="0" && $n[6]=="0" && $n[7]=="0")
	if(!existNetwork($new_root, $new_mask + 5))
	$m[] =($new_mask + 5);

if($n[6]=="0" && $n[7]=="0")
	if(!existNetwork($new_root, $new_mask + 6))
	$m[] =($new_mask + 6);

if($n[7]=="0")
	if(!existNetwork($new_root, $new_mask + 7))
	$m[] =($new_mask + 7);

$m[] = ($new_mask + 8)
?>



<form action="./" method="get">
	<input type="hidden" name="action" value="addNewNetwork">
	<table>
		<tr>
			<td>your selected network is:</td>
			<td><?php echo $rootNetwork->getMyCompressedAddress()?><input type="hidden" name="newNetwork" value="<?php echo $new_root ?>"></td>
		</tr>
		<tr>
			<td>select your network Mask:</td>
			<td>
				<select name="newMask">
					<?php for($i=0; $i < sizeof($m); $i++) {echo "<option value=\"".$m[$i]."\">".$m[$i]."</option>\n"; }?>
				</select>
			</td>
		</tr>
		<tr>
			<td>enter a description for your network:</td>
			<td><input type="text" name="newDescription"></td>
		</tr>
		<tr>
			<td>select a colour:</td>
			<td><input type="color" name="newColour" value="#dcdcdc"></td>
		</tr>
		<tr>
			<td></td>
			<td><input type="submit" value="Speichern"></td>
	</table>
</form>