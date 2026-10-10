<?php

// ---------------------------------------------------------------------------
// input validation
// ---------------------------------------------------------------------------

/**
 * Returns the netmask as integer if it is a whole number between 0 and $max,
 * otherwise null.
 */
function valid_mask($value, $max = 128)
{
	if (!is_string($value) && !is_int($value))
		return null;
	$value = (string)$value;
	if ($value === '' || !ctype_digit($value) || strlen($value) > 3)
		return null;
	$value = (int)$value;
	return ($value <= $max) ? $value : null;
}

/**
 * Returns the network if it is a binary string of exactly 128 bits
 * (or of up to 128 bits if $exact is false), otherwise null.
 */
function valid_bin_network($value, $exact = true)
{
	if (!is_string($value))
		return null;
	$pattern = $exact ? '/^[01]{128}$/' : '/^[01]{0,128}$/';
	return preg_match($pattern, $value) ? $value : null;
}

/**
 * Returns the IPv6 address if it is valid, otherwise null.
 */
function valid_hex_network($value)
{
	if (!is_string($value) || strpos($value, ':') === false)
		return null;
	$packed = @inet_pton($value);
	return ($packed !== false && strlen($packed) == 16) ? $value : null;
}

/**
 * Returns the database id as integer if it is a positive whole number, otherwise null.
 */
function valid_id($value)
{
	if (!is_string($value) && !is_int($value))
		return null;
	$value = (string)$value;
	if ($value === '' || !ctype_digit($value) || strlen($value) > 10)
		return null;
	return (int)$value;
}

/**
 * Returns the color if it has the form #RRGGBB, otherwise $default.
 */
function valid_color($value, $default = '#FFFFAA')
{
	return (is_string($value) && preg_match('/^#[0-9A-Fa-f]{6}$/', $value)) ? $value : $default;
}

/**
 * Reads a network from the request (hex or binary notation) and returns an
 * initialized IPv6 object, or null if network or netmask are invalid.
 */
function network_from_request($network, $mask, $format)
{
	$mask = valid_mask($mask);
	if ($mask === null)
		return null;

	$ip = new IPv6();
	if ($format == 'hex')
	{
		if (valid_hex_network($network) === null || !$ip->initHex($network, $mask))
			return null;
	}
	else
	{
		if (valid_bin_network($network) === null)
			return null;
		$ip->initBinary($network, $mask);
	}
	return $ip;
}

/**
 * Stops the request with HTTP status 400 and a short error message.
 */
function bad_request($message)
{
	http_response_code(400);
	header('Content-Type: text/plain; charset=utf-8');
	echo $message;
	exit();
}

// ---------------------------------------------------------------------------
// CSRF protection
// ---------------------------------------------------------------------------

/**
 * Starts the session that holds the CSRF token.
 */
function start_session()
{
	if (session_status() == PHP_SESSION_ACTIVE)
		return;
	session_set_cookie_params(array(
		'httponly' => true,
		'samesite' => 'Lax',
		'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
	));
	session_start();
}

function csrf_token()
{
	start_session();
	if (empty($_SESSION['csrf_token']))
		$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
	return $_SESSION['csrf_token'];
}

/**
 * Hidden form field with the CSRF token. Every form that changes data must contain it.
 */
function csrf_field()
{
	return '<input type="hidden" name="csrf_token" value="'.e(csrf_token()).'">';
}

/**
 * Stops the request unless it is a POST request with a valid CSRF token.
 */
function require_post_with_csrf()
{
	if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST')
	{
		header('Allow: POST');
		http_response_code(405);
		exit('Method not allowed.');
	}
	$token = $_POST['csrf_token'] ?? '';
	if (!is_string($token) || !hash_equals(csrf_token(), $token))
	{
		http_response_code(403);
		exit('Invalid or expired form. Please reload the page and try again.');
	}
}

/**
 * Returns a redirect target inside this application. Only the query string of
 * $url is kept, so the redirect can never lead to another site.
 */
function local_redirect_url($url)
{
	$query = is_string($url) ? (string)parse_url($url, PHP_URL_QUERY) : '';
	return ($query === '') ? './' : './?'.$query;
}

// ---------------------------------------------------------------------------
// output
// ---------------------------------------------------------------------------

/**
 * Escapes a value for HTML output (text and attribute values).
 */
function e($value)
{
	return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Returns the color for a style attribute, or an empty string if it is no #RRGGBB value.
 */
function e_color($value)
{
	return e(valid_color($value, ''));
}

function sudoku_cell($field, $row)
{
	global $MyIPv6;
	global $gMask;
	if (!$row)
		$row = array('id' => '', 'mask' => 0, 'color' => '', 'description' => '');
	?>
<td 
	id="cell<?php echo $field; ?>" 
	class="field_<?php echo $field; ?>" 
	style="background-color: <?php if(e_color($row['color'])=="" || $row['mask'] > $gMask+8){echo "white";}else{echo e_color($row['color']); }?>" 
	<?php if($row['id']=='' || $row['mask'] > $gMask+8){?>	onclick="selectCell('<?php echo $field; ?>')" onMouseOver="highlightCell('<?php echo $field; ?>')"<?php }?>>
	<?php if($row['id']!="" && $gMask < 120 && $row['mask'] < $gMask+9){?>
		<a href="./?action=sudoku&amp;mask=<?php echo ($MyIPv6->myPrefix + 8); ?>&amp;network=<?php	echo substr_replace($MyIPv6->binNetwork(), myHex2Bin($field), $MyIPv6->myPrefix, 8);?>"	title="<?php echo e($row['description']); ?>">
			<?php 
	}
	echo $field; 
	if($row['id']!="" && $gMask < 120 && $row['mask'] < $gMask+9){
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
				<a 	href="./?action=sudoku&amp;mask=<?php echo min(120, floor($ipv6->myPrefix/8)*8); ?>&amp;network=<?php echo $ipv6->binNetwork() ?>" title="<?php echo e($row['description']); ?>">
					<?php echo $ipv6->hexCompressedNetwork()."/".e($row['mask']); ?>
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

function existNetwork($net, $mask, $maxMask)
{
	global $sql;
	$q = "SELECT `id` from `ipamv6` WHERE `network_bin` LIKE ? AND `mask` > ? AND `mask` < ? limit 1 ;";
	$res = $sql->query($q, array(substr($net, 0, $mask)."%", $mask, $maxMask));
	return $sql->num_result($res) > 0;
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
		$q = "SELECT * FROM `ipamv6` WHERE `network_bin` LIKE ? ORDER BY mask; ";
		$res = $sql->query($q, array(substr($gNetwork, 0, $i)."%"));
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
		$q = "SELECT * FROM `ipamv6` WHERE `network_bin` LIKE ? AND `mask` <= ? ORDER BY mask DESC; ";
		$res = $sql->query($q, array(substr($gNetwork, 0, $i)."%", $gMask));
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
	<span style="background: <?php echo e_color($row['color'] ?? ''); ?>">
		(<?php echo e($row['description'] ?? ''); ?>)
	</span>
	</div>
	<?php 
}

?>