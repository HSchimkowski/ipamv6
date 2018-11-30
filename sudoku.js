var start='';
var end = '';
var bit = '';

var possibleBits;
var expanded = [];

window.document.onkeydown = function (e)
{
  if (!e) e = event;
  if (e.keyCode == 27)
    clearCells();
}

function isNetworkPossible()
{
	document.getElementById("save").disabled=true;
	
	network = document.getElementById("network").value;
	mask = document.getElementById("mask").value;
	
	var xmlhttp;
	
	if (window.XMLHttpRequest) { // code for IE7+, Firefox, Chrome, Opera, Safari
	    xmlhttp = new XMLHttpRequest();
	}
	else { // code for IE6, IE5
	    xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
	}
	
	xmlhttp.onreadystatechange = function() {
	    if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
	    	
	    	r=xmlhttp.responseText;
	    	z=document.getElementById("hint");
			z.innerHTML = r;
			if(r.indexOf("OK!")> -1)
			{
				document.getElementById("save").disabled=false;
			}
	    }
	}
	xmlhttp.open("GET", "isPossible.php?network=" + network + "&mask="+mask, true);
	xmlhttp.send();
}

function getPossibleSubnets(net, mask)
{
	var xmlhttp;
	
	if (window.XMLHttpRequest) { // code for IE7+, Firefox, Chrome, Opera, Safari
	    xmlhttp = new XMLHttpRequest();
	}
	else { // code for IE6, IE5
	    xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
	}
	
	xmlhttp.onreadystatechange = function() {
	    if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
	    	
	    	// Startfield in bin
			bs=Hex2Bin8(start);
			
			possibleBits = parseInt(xmlhttp.responseText);
	    	
			// mark possible space for subnetting in lightred
			for(i=1; i< Math.pow(2, possibleBits); i++)
			{
				z=document.getElementById("cell" + addLeadingZeros(Bin2Hex(bs.substr(0, 8 - bit).toString() + Dec2Bin8n(i,bit)).toUpperCase(),2));
				{
					z.style.backgroundColor = "#ff8888";
				}
			}
	    }
	}
	xmlhttp.open("GET", "getPossibleMasks.php?mask="+mask+"&subnet=" + net, true);
	xmlhttp.send();
}

function expandNetworks(id, lastNetwork_bin, mask, root) 
{
	if(expanded.indexOf([id])==-1 && expanded[id] != '')
	{
		ul = document.getElementById("hiddennNetworks" + id);
		expanded[id] = ul.innerHTML;
		ul.innerHTML = '';
		// for every new Subnet
		li = document.createElement("li");
		li.appendChild(document.createTextNode("loading subnets..."));
		ul.appendChild(li);
		
		var xmlhttp;
		
		if (window.XMLHttpRequest) { // code for IE7+, Firefox, Chrome, Opera, Safari
		    xmlhttp = new XMLHttpRequest();
		}
		else { // code for IE6, IE5
		    xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
		}
		
		xmlhttp.onreadystatechange = function() 
		{
		    if (xmlhttp.readyState == 4 && xmlhttp.status == 200) 
		    {
		    	ul.innerHTML = '';
		    	ul.innerHTML = xmlhttp.responseText;
		    }
		}
		
		xmlhttp.open("GET", "getHiddenNetworks.php?mask="+mask+"&lastNetwork_bin=" + lastNetwork_bin + "&root=" + root, true);
		xmlhttp.send();
	}
	else
	{
		ul = document.getElementById("hiddennNetworks" + id);
		ul.innerHTML = '';
		ul.innerHTML = expanded[id];
		delete expanded[id];
	}

}

function selectCell(cell) {
	
	if(start=='')
	{
		start=cell;
		
		// Startfield in bin
		bs=Hex2Bin8(start);
		
		// mark start-filed with special Color
		c=document.getElementById("cell" + cell);
		c.style.backgroundColor = "red";
		
		// calculate available bits for subnetting
		x=("1" + bs).lastIndexOf("1") -1;
		bit = 7 - x;
		
		s=document.getElementById("form_network");
		s.value=start;
		
		// calculate new binary network
		basenetwork = document.getElementById("form_basenetwork").value;
		basemask = document.getElementById("form_basemask").value;
		part1 = basenetwork.slice( 0, parseInt(basemask) );
		
		c = part1 + bs + basenetwork.slice( parseInt(basemask) + 8, 128);
		
		// get PossibleNetworks via ajax
		getPossibleSubnets(c, basemask);
		
		document.getElementById("form_maska").value = (parseInt(document.getElementById("form_basemask").value) + (8));
	}
	else
	{
		// Startfield in bin
		bs=Hex2Bin8(start);
		
		// cell in Bin
		bc=Hex2Bin8(cell);
		
		// action is only required if we are in a possible subnet
		if ( bs.substr(0, 8 - possibleBits) == bc.substr(0, 8 - possibleBits) )
		{
		end=cell;
		
		// calculate available bits for subnetting
		x=("1" + bs).lastIndexOf("1") -1;
		bit = 7 - x;
		
		// calculate changed bits
		y=bit-(bc.substr(8 - bit, bit)+"1").indexOf("1");
		
		// write mask to form
		document.getElementById("form_mask").value = (parseInt(document.getElementById("form_basemask").value) + (8-y));
		document.getElementById("form_maska").value = (parseInt(document.getElementById("form_basemask").value) + (8-y));
		
		// calculate new binary network
		basenetwork = document.getElementById("form_basenetwork").value;
		basemask = document.getElementById("form_basemask").value;
		part1 = basenetwork.slice( 0, parseInt(basemask) );
		
		c = part1 + bs + basenetwork.slice( parseInt(basemask) + 8, 128);
		document.getElementById("form_basenetwork").value = c;
		
		document.getElementById("form_description").value = "";
		document.getElementById("form_description").disabled = false;
		document.getElementById("form_color").disabled = false;
		document.getElementById("form_submit").disabled = false;
		}
	}
}

function highlightCell(cell) {
	if(start != '' && end == '')
	{
		// Startfield in bin
		bs=Hex2Bin8(start);
		
		// cell in Bin
		bc=Hex2Bin8(cell);
		
		// calculate available bits for subnetting
		x=("1" + bs).lastIndexOf("1") -1;
		bit = 7 - x;
		
		
		// action is only required if we are in a possible subnet
		if ( bs.substr(0, 8 - possibleBits) == bc.substr(0, 8 - possibleBits) )
			{
			// calculate changed bits
			y=bit-(bc.substr(8 - bit, bit)+"1").indexOf("1");
			
			document.getElementById("form_maska").value = (parseInt(document.getElementById("form_basemask").value) + (8-y));
			
			// mark possible space for subnetting in lightred
			for(i=1; i< Math.pow(2, possibleBits); i++)
			{
				z=document.getElementById("cell" + addLeadingZeros(Bin2Hex(bs.substr(0, 8 - bit).toString() + Dec2Bin8n(i,bit)).toUpperCase(),2));
				z.style.backgroundColor = "#ff8888";
			}
			
			// mark selected space for subnetting in red
			for(i=1; i< Math.pow(2, y); i++)
			{
				z=document.getElementById("cell" + addLeadingZeros(Bin2Hex(bs.substr(0, 8 - bit).toString() + Dec2Bin8n(i,bit)).toUpperCase(),2));
				z.style.backgroundColor = "red";
			}
		}
	}
}

function clearCell(cell) {
	
}

function deleteNetework($id)
{
	// check if Network has Subnets
	
	// it subnets==true
		// askif user wants to delete allso all Subnets
			// if yes 
				window.location = './?action=deleteNetwork&id=620&subnets=1';
				return false;
			// if no
				return true;
}

function onkeypressed(evt, input) {
    var code = evt.charCode || evt.keyCode;
    if (code == 27) {
    	clearCells();
    }
}

function clearCells() {
	
	// just a Workaround because rest of function does not work propper if selected cell is "00"
	location.reload();
	return;
	
	// Startfield in bin
	bs=Hex2Bin8(start);
	
	// calculate available bits for subnetting
	x=("1" + bs).lastIndexOf("1") -1;
	bit = 7 - x;
	
	// mark possible space for subnetting in lightblue
	if(bit == '0')
	{
		z=document.getElementById("cell" + start);
		z.style.backgroundColor = "white";
	}
	else
	{
		for(i=0; i< Math.pow(2, bit); i++)
		{
			z=document.getElementById("cell" + addLeadingZeros(Bin2Hex(bs.substr(0, 8 - bit).toString() + Dec2Bin8n(i,bit)).toUpperCase(),2));
			z.style.backgroundColor = "white";
		}
	}
	start='';
	end='';
	bit='';
	document.getElementById("form_description").disabled=true;
	document.getElementById("form_color").disabled=true;
	document.getElementById("form_submit").disabled=true;
}

function Hex2Bin8(n)
{
	if(!checkHex(n))
		return 0;
	return addLeadingZeros(parseInt(n,16).toString(2), 8);
}

function Hex2Bin8N(n, c)
{
	if(!checkHex(n))
		return 0;
	return addLeadingZeros(parseInt(n,16).toString(2), c);
}

function Dec2Bin8n(n, c)
{
	if(!checkDec(n)||n<0)
		return 0;
	return addLeadingZeros(n.toString(2), c);
}


function checkHex(n)
{
	return/^[0-9A-Fa-f]{1,64}$/.test(n)
}

function addLeadingZeros(number, length) {
    var num = '' + number;
    while (num.length < length) num = '0' + num;
    return num;
}

//Useful Functions
function checkBin(n){return/^[01]{1,64}$/.test(n)}
function checkDec(n){return/^[0-9]{1,64}$/.test(n)}
function pad(s,z){s=""+s;return s.length<z?pad("0"+s,z):s}
function unpad(s){s=""+s;return s.replace(/^0+/,'')}

//Decimal operations
function Dec2Bin(n){if(!checkDec(n)||n<0)return 0;return n.toString(2)}
function Dec2Hex(n){if(!checkDec(n)||n<0)return 0;return n.toString(16)}

//Binary Operations
function Bin2Dec(n){if(!checkBin(n))return 0;return parseInt(n,2).toString(10)}
function Bin2Hex(n){if(!checkBin(n))return 0;return parseInt(n,2).toString(16)}

//Hexadecimal Operations
function Hex2Bin(n){if(!checkHex(n))return 0;return parseInt(n,16).toString(2)}
function Hex2Dec(n){if(!checkHex(n))return 0;return parseInt(n,16).toString(10)}

