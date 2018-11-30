<?php

function myHex2Bin($hex)
{
	$a = "";
	for ($i=0; $i<=1; $i++)
	{
		if($hex[$i] == "0")								$a = $a."0000";
		elseif ($hex[$i] == "1")						$a = $a."0001";
		elseif ($hex[$i] == "2")						$a = $a."0010";
		elseif ($hex[$i] == "3")						$a = $a."0011";
		elseif ($hex[$i] == "4")						$a = $a."0100";
		elseif ($hex[$i] == "5")						$a = $a."0101";
		elseif ($hex[$i] == "6")						$a = $a."0110";
		elseif ($hex[$i] == "7")						$a = $a."0111";
		elseif ($hex[$i] == "8")						$a = $a."1000";
		elseif ($hex[$i] == "9")						$a = $a."1001";
		elseif ($hex[$i] == "A" || $hex[0] == "a")		$a = $a."1010";
		elseif ($hex[$i] == "B" || $hex[0] == "b")		$a = $a."1011";
		elseif ($hex[$i] == "C" || $hex[0] == "c")		$a = $a."1100";
		elseif ($hex[$i] == "D" || $hex[0] == "d")		$a = $a."1101";
		elseif ($hex[$i] == "E" || $hex[0] == "e")		$a = $a."1110";
		elseif ($hex[$i] == "F" || $hex[0] == "f")		$a = $a."1111";
	}
	return $a;
}

class IPv6
{
	private $myAddress;		// My Address in expanded form
	public $myPrefix;		// My networkmask
	public $mxDescription;
	public $myColor;
	
	public function initHex($net, $prefix)
	{
		$this->expand($net);
		$this->myPrefix = $prefix;
	}

	public function initBinary($bin, $mask)
	{
		$return = "";
		for($i=0; $i < strlen($bin)/4 ; $i++)
		{
			if(($i % 4)==0 && $i!=0)
				$return = $return.":";

			$part = substr($bin, ($i*4), 4);

			if($part=="0000")
				$return = $return."0";
			elseif($part=="0001")
			$return = $return."1";
			elseif($part=="0010")
			$return = $return."2";
			elseif($part=="0011")
			$return = $return."3";
			elseif($part=="0100")
			$return = $return."4";
			elseif($part=="0101")
			$return = $return."5";
			elseif($part=="0110")
			$return = $return."6";
			elseif($part=="0111")
			$return = $return."7";
			elseif($part=="1000")
			$return = $return."8";
			elseif($part=="1001")
			$return = $return."9";
			elseif($part=="1010")
			$return = $return."a";
			elseif($part=="1011")
			$return = $return."b";
			elseif($part=="1100")
			$return = $return."c";
			elseif($part=="1101")
			$return = $return."d";
			elseif($part=="1110")
			$return = $return."e";
			elseif($part=="1111")
			$return = $return."f";

		}
		$this->myPrefix = $mask;
		$this->myAddress = $return;
	}

	# Source: http://www.soucy.org/project/inet6/
	private function expand($addr)
	{
		/* Check if there are segments missing, insert if necessary */
		if (strpos($addr, '::') !== false) {
			$part = explode('::', $addr);
			$part[0] = explode(':', $part[0]);
			$part[1] = explode(':', $part[1]);
			$missing = array();
			for ($i = 0; $i < (8 - (count($part[0]) + count($part[1]))); $i++)
				array_push($missing, '0000');
			$missing = array_merge($part[0], $missing);
			$part = array_merge($missing, $part[1]);
		} else {
			$part = explode(":", $addr);
		}
		
		/* Pad each segment until it has 4 digits */
		foreach ($part as &$p) {
			while (strlen($p) < 4) $p = '0' . $p;
		}
		unset($p);
		
		/* Join segments */
		$result = implode(':', $part);
		/* Quick check to make sure the length is as expected */
		if (strlen($result) == 39) {
			$this->myAddress = $result;
		} else {
			return false;
		} // if .. else
	}

	public function getMyExpandedAddress()
	{
		return $this->myAddress;
	}

	public function getMyCompressedAddress()
	{
		return inet_ntop(inet_pton($this->myAddress));
	}

	public function binAddress()
	{
		$result ="";
		//remove":"
		$addr = str_replace(":", "", $this->myAddress);


		for ($i=0; $i < strlen($addr); $i++)
		{
			if($addr[$i]=="0")
				$result = $result."0000";
			elseif ($addr[$i]=="1")
			$result = $result."0001";
			elseif ($addr[$i]=="2")
			$result = $result."0010";
			elseif ($addr[$i]=="3")
			$result = $result."0011";
			elseif ($addr[$i]=="4")
			$result = $result."0100";
			elseif ($addr[$i]=="5")
			$result = $result."0101";
			elseif ($addr[$i]=="6")
			$result = $result."0110";
			elseif ($addr[$i]=="7")
			$result = $result."0111";
			elseif ($addr[$i]=="8")
			$result = $result."1000";
			elseif ($addr[$i]=="9")
			$result = $result."1001";
			elseif ($addr[$i]=="a" || $addr[$i]=="A")
			$result = $result."1010";
			elseif ($addr[$i]=="b" || $addr[$i]=="B")
			$result = $result."1011";
			elseif ($addr[$i]=="c" || $addr[$i]=="C")
			$result = $result."1100";
			elseif ($addr[$i]=="d" || $addr[$i]=="D")
			$result = $result."1101";
			elseif ($addr[$i]=="e" || $addr[$i]=="E")
			$result = $result."1110";
			elseif ($addr[$i]=="f" || $addr[$i]=="F")
			$result = $result."1111";
				
		}
		return $result;
	}

	public function binMask()
	{
		$result=str_repeat("1", $this->myPrefix);
		$result=$result.str_repeat("0", 128-$this->myPrefix);
		return $result;
	}

	public function binNetwork()
	{
		$result = "";
		$binAddr=$this->binAddress();
		$binMask=$this->binMask();
		for($i=0; $i < strlen($binMask); $i++)
		{
			if($binMask[$i]=='1')
				$result = $result.$binAddr[$i];
			else
				$result = $result."0";
		}
		return $result;
	}

	public function hexExpandedNetwork()
	{
		$return = "";
		$bin = $this->binNetwork();
		for($i=0; $i < strlen($bin)/4 ; $i++)
		{
			if(($i % 4)==0 && $i!=0)
				$return = $return.":";
				
			$part = substr($bin, ($i*4), 4);
				
			if($part=="0000")
				$return = $return."0";
			elseif($part=="0001")
			$return = $return."1";
			elseif($part=="0010")
			$return = $return."2";
			elseif($part=="0011")
			$return = $return."3";
			elseif($part=="0100")
			$return = $return."4";
			elseif($part=="0101")
			$return = $return."5";
			elseif($part=="0110")
			$return = $return."6";
			elseif($part=="0111")
			$return = $return."7";
			elseif($part=="1000")
			$return = $return."8";
			elseif($part=="1001")
			$return = $return."9";
			elseif($part=="1010")
			$return = $return."a";
			elseif($part=="1011")
			$return = $return."b";
			elseif($part=="1100")
			$return = $return."c";
			elseif($part=="1101")
			$return = $return."d";
			elseif($part=="1110")
			$return = $return."e";
			elseif($part=="1111")
			$return = $return."f";
				
		}
		return $return;
	}

	public function hexCompressedNetwork()
	{
		return inet_ntop(inet_pton($this->hexExpandedNetwork()));
	}
}





# Source: http://www.soucy.org/project/inet6/

/**
 * Generate an IPv6 mask from prefix notation
*
* This will convert a prefix to an IPv6 address mask (used for IPv6 math)
*
* @param  integer $prefix The prefix size, an integer between 1 and 127 (inclusive)
* @return string  The IPv6 mask address for the prefix size
*/
function inet6_prefix_to_mask($prefix)
{
	/* Make sure the prefix is a number between 1 and 127 (inclusive) */
	$prefix = intval($prefix);
	if ($prefix < 0 || $prefix > 128) return false;
	$mask = '0b';
	for ($i = 0; $i < $prefix; $i++) $mask .= '1';
	for ($i = strlen($mask) - 2; $i < 128; $i++) $mask .= '0';
	$mask = gmp_strval(gmp_init($mask), 16);
	for ($i = 0; $i < 8; $i++) {
		$result .= substr($mask, $i * 4, 4);
		if ($i != 7) $result .= ':';
	} // for
	return inet6_compress($result);
} // inet6_prefix_to_mask

/**
 * Convert an IPv6 address and prefix size to an address range for the network.
 *
 * This will take an IPv6 address and prefix and return the first and last address available for the network.
 *
 * @param  string  $addr A valid IPv6 address
 * @param  integer $prefix The prefix size, an integer between 1 and 127 (inclusive)
 * @return array   An array with two strings containing the start and end address for the IPv6 network
 */
function inet6_to_range($addr, $prefix)
{
	$size = 128 - $prefix;
	$addr = gmp_init('0x' . str_replace(':', '', inet6_expand($addr)));
	$mask = gmp_init('0x' . str_replace(':', '', inet6_expand(inet6_prefix_to_mask($prefix))));
	$prefix = gmp_and($addr, $mask);
	$start = gmp_strval(gmp_add($prefix, '0x1'), 16);
	$end = '0b';
	for ($i = 0; $i < $size; $i++) $end .= '1';
	$end = gmp_strval(gmp_add($prefix, gmp_init($end)), 16);
	for ($i = 0; $i < 8; $i++) {
		$start_result .= substr($start, $i * 4, 4);
		if ($i != 7) $start_result .= ':';
	} // for
	for ($i = 0; $i < 8; $i++) {
		$end_result .= substr($end, $i * 4, 4);
		if ($i != 7) $end_result .= ':';
	} // for
	$result = array(inet6_compress($start_result), inet6_compress($end_result));
	return $result;
} // inet6_to_range


?>