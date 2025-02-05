<?php
// load configfile
require_once 'config.inc.php';
require_once 'class/mysq.class.php';
require_once 'class/ipv6.php';
require_once 'functions.php';
// load Session cookie

error_reporting(E_ERROR | E_PARSE);

// start database connection
$sql = new mysql("localhost","user","passwort","datenbank"); 

if (! isset($_GET['action']))
	$_GET['action'] = "none";

if ($_GET['action']=="addNewNetwork")
	include_once 'addNewNetwork.php';
elseif ($_GET['action']=="storeEditedNetwork")
	include_once 'storeEditedNetwork.php';


?>
<!DOCTYPE html>
<html lang="de">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>IPv6 Address Management Tool | by Helmut Schimkowski</title>
		<link rel="stylesheet" href="css/main.css">
		<script src="sudoku.js"></script>
	</head>
	<body>
		<header>
			<h1 style="list-style-type: none;">IPv6 address management tool</h1>
		</header>
		
		<main>
			<div class="container">
				<aside class="sidebar">
					<?php left(); ?>
				</aside>

				<section class="content">
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
				</section>
			</div>
		</main>

		<footer>    
			<nav class="footer-links">
				<a href="mailto:helge.holz@dataport.de">Concept by Helge Holz</a>
				<a href="http://www.schimkowski.net" target="_blank">developed by Helmut Schimkowski</a>
			</nav>
		</footer>
	</body>
</html>
<?php 

$sql->free_result();

$sql->close_connect();
?>
