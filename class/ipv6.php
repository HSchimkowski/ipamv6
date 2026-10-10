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
		elseif ($hex[$i] == "A" || $hex[$i] == "a")		$a = $a."1010";
		elseif ($hex[$i] == "B" || $hex[$i] == "b")		$a = $a."1011";
		elseif ($hex[$i] == "C" || $hex[$i] == "c")		$a = $a."1100";
		elseif ($hex[$i] == "D" || $hex[$i] == "d")		$a = $a."1101";
		elseif ($hex[$i] == "E" || $hex[$i] == "e")		$a = $a."1110";
		elseif ($hex[$i] == "F" || $hex[$i] == "f")		$a = $a."1111";
	}
	return $a;
}

class IPv6
{
	private $myAddress;		// My Address in expanded form
	public $myPrefix;		// My networkmask
	public $myDescription;
	public $myColor;
	
	/**
	 * Initializes the object from an IPv6 address in hex notation.
	 * Returns false if the address is not valid.
	 */
	public function initHex($net, $prefix)
	{
		$this->myPrefix = $prefix;
		$packed = @inet_pton($net);
		if ($packed === false || strlen($packed) != 16)
		{
			$this->myAddress = null;
			return false;
		}
		$this->myAddress = implode(':', str_split(bin2hex($packed), 4));
		return true;
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

