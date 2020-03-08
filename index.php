<?php
// load configfile
require_once 'config.inc.php';
require_once 'class/mysq.class.php';
require_once 'class/ipv6.php';
require_once 'functions.php';
// load Session cookie

// start database connection
$sql = new mysql("localhost","user","passwort","datenbank"); 


if ($_GET['action']=="addNewNetwork")
	include_once 'addNewNetwork.php';
elseif ($_GET['action']=="storeEditedNetwork")
	include_once 'storeEditedNetwork.php';


?>
<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="content-type" content="text/html; charset=utf-8">
		<title>IPv6 Address Management Tool | by Helmut Schimkowski</title>
		<link rel="stylesheet" href="css/main.css" />
		<script type="text/javascript" src="sudoku.js"></script>
	</head>
	<body>
		<!-- top banner -->
		<header>
			<div id="header_page">
				IPv6 address management tool
			</div>
		</header>
		
		<!-- main section -->
		<div id="main">
			<div id="main_page">
				<div id="left">
					<?php left(); ?>
				</div>

				
				
				
<!-- center -->
			<div id="right">
<?php 
if($_GET['action']=="sudoku")
	include_once 'sudoku.php';
elseif ($_GET['action']=="showIpv6Route")
	include_once 'showIpv6Route.php';
elseif ($_GET['action']=="addCustomNetworkForm")
	include_once 'addCustomNetworkForm.php';
elseif ($_GET['action']=="editNetworkForm")
	include_once 'editNetworkForm.php';
elseif ($_GET['action']=="todo")
{
	echo "<pre>";
	include_once 'todo.txt';
	echo "</pre>";
}
elseif ($_GET['action']=="help")
	include_once 'help.html';
elseif ($_GET['action']=="deleteNetwork")
	include_once 'deleteNetwork.php';
else 
	echo "Please select your root network first!";
?>
			</div>
			</div>
		</div>
		<footer>	
			<a href="mailto:helge.holz@dataport.de">Conncept by Helge Holz</a>
			<a href="http://www.schimkowski.net" target="_blank">developed by Helmut Schimkowski</a>
		</footer>
	</body>
</html>
<?php 

$sql->free_result();

$sql->close_connect();
?>