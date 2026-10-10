<?php
/*
 * Smoke- und Regressionstests fuer IPAMv6.
 *
 * Erwartet einen laufenden Webserver (z. B. "php -S 127.0.0.1:8000") und eine
 * MySQL/MariaDB-Datenbank. Vor jedem Test wird ipamv6.sql neu eingespielt.
 *
 * Umgebungsvariablen:
 *   BASE_URL       (default: http://127.0.0.1:8000)
 *   DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS (default: wie config.inc.php)
 *   PHP_ERROR_LOG  Logdatei des Webservers; jede neue Zeile darin laesst den Test scheitern
 */

error_reporting(E_ALL);

$baseUrl  = rtrim(getenv('BASE_URL') ?: 'http://127.0.0.1:8000', '/');
$errorLog = getenv('PHP_ERROR_LOG') ?: '';

$db = new mysqli(
	getenv('DB_HOST') ?: '127.0.0.1',
	getenv('DB_USER') ?: 'ipamv6',
	getenv('DB_PASS') ?: 'ipamv6',
	getenv('DB_NAME') ?: 'ipamv6',
	(int)(getenv('DB_PORT') ?: 3306)
);

const NET_2001_DB8_32 = '0010000000000001000011011011100000000000000000000000000000000000'
	. '0000000000000000000000000000000000000000000000000000000000000000';

$failures = 0;
$passed   = 0;

function resetDb()
{
	global $db;
	$db->query('DROP TABLE IF EXISTS `ipamv6`');
	$db->multi_query(file_get_contents(__DIR__ . '/../ipamv6.sql'));
	while ($db->more_results() && $db->next_result()) {
		;
	}
}

function rowCount($where = '1')
{
	global $db;
	return (int)$db->query("SELECT COUNT(*) FROM `ipamv6` WHERE $where")->fetch_row()[0];
}

function errorLogSize()
{
	global $errorLog;
	if ($errorLog === '' || !file_exists($errorLog)) {
		return 0;
	}
	clearstatcache(true, $errorLog);
	return filesize($errorLog);
}

function newLogLines($offset)
{
	global $errorLog;
	if ($errorLog === '' || !file_exists($errorLog)) {
		return '';
	}
	$content = (string)file_get_contents($errorLog, false, null, $offset);
	// Zugriffszeilen des eingebauten Webservers ignorieren
	$lines = array_filter(explode("\n", $content), function ($l) {
		return trim($l) !== ''
			&& !preg_match('/\[\d+\]: [\d.:\[\]]+ (Accepted|Closing|\[\d{3}\]:)/', $l);
	});
	return implode("\n", $lines);
}

/**
 * Fuehrt einen HTTP-Request aus und liefert [status, body, headers].
 */
function request($method, $path, array $params = array())
{
	global $baseUrl;
	$url  = $baseUrl . '/' . ltrim($path, '/');
	$opts = array('method' => $method, 'ignore_errors' => true, 'follow_location' => 0, 'timeout' => 30);
	if ($method === 'GET' && $params) {
		$url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($params);
	} elseif ($params) {
		$opts['header']  = "Content-Type: application/x-www-form-urlencoded\r\n";
		$opts['content'] = http_build_query($params);
	}
	$body    = @file_get_contents($url, false, stream_context_create(array('http' => $opts)));
	$headers = isset($http_response_header) ? $http_response_header : array();
	$status  = 0;
	if ($headers && preg_match('#HTTP/\S+ (\d{3})#', $headers[0], $m)) {
		$status = (int)$m[1];
	}
	return array($status, $body === false ? '' : $body, $headers);
}

function header_value(array $headers, $name)
{
	foreach ($headers as $h) {
		if (stripos($h, $name . ':') === 0) {
			return trim(substr($h, strlen($name) + 1));
		}
	}
	return null;
}

function check($name, callable $fn)
{
	global $failures, $passed;
	resetDb();
	$logOffset = errorLogSize();
	$errors = array();
	try {
		$fn($errors);
	} catch (Throwable $e) {
		$errors[] = 'Exception: ' . $e->getMessage();
	}
	$log = newLogLines($logOffset);
	if ($log !== '') {
		$errors[] = "PHP-Fehlerlog:\n      " . str_replace("\n", "\n      ", $log);
	}
	if ($errors) {
		$failures++;
		echo "FAIL  $name\n";
		foreach ($errors as $err) {
			echo "      - $err\n";
		}
	} else {
		$passed++;
		echo "ok    $name\n";
	}
}

/**
 * Prueft eine Antwort auf Status 200, PHP-Fehlermeldungen und erwartete Inhalte.
 */
function expectPage(array &$errors, array $response, array $contains = array(), $status = 200)
{
	list($code, $body) = $response;
	if ($code !== $status) {
		$errors[] = "HTTP-Status $code statt $status";
	}
	if (preg_match('/(Fatal error|Parse error|Warning|Notice|Deprecated|Uncaught)\b.{0,300}/s', $body, $m)) {
		$errors[] = 'PHP-Meldung in der Ausgabe: ' . trim(strip_tags(substr($m[0], 0, 300)));
	}
	foreach ($contains as $needle) {
		if (strpos($body, $needle) === false) {
			$errors[] = "erwarteter Inhalt fehlt: " . $needle;
		}
	}
}

function expectTrue(array &$errors, $cond, $message)
{
	if (!$cond) {
		$errors[] = $message;
	}
}

// ---------------------------------------------------------------------------
// Seiten
// ---------------------------------------------------------------------------

check('Startseite', function (&$e) {
	expectPage($e, request('GET', '/'), array('IPv6 root Networks', 'Please select your root network first!'));
});

check('Sudoku-Ansicht /32', function (&$e) {
	expectPage($e, request('GET', '/', array('action' => 'sudoku', 'mask' => 32, 'network' => NET_2001_DB8_32)),
		array('id="sudoku"', 'id="cellFF"', 'police'));
});

check('Sudoku-Ansicht /40', function (&$e) {
	$net = substr_replace(NET_2001_DB8_32, '10101011', 32, 8);
	expectPage($e, request('GET', '/', array('action' => 'sudoku', 'mask' => 40, 'network' => $net)),
		array('id="sudoku"', 'id="cell00"'));
});

check('Sudoku-Ansicht /0', function (&$e) {
	expectPage($e, request('GET', '/', array('action' => 'sudoku', 'mask' => 0, 'network' => str_repeat('0', 128))),
		array('id="sudoku"'));
});

check('Netzuebersicht', function (&$e) {
	expectPage($e, request('GET', '/', array('action' => 'showIpv6Route')), array('2001:db8::/32', 'police'));
});

check('Formular: Netz anlegen', function (&$e) {
	expectPage($e, request('GET', '/', array('action' => 'addCustomNetworkForm')), array('name="newNetwork"'));
});

check('Formular: Netz bearbeiten', function (&$e) {
	expectPage($e, request('GET', '/', array('action' => 'editNetworkForm', 'id' => 820)),
		array('value="2001:db8:c000::"', 'value="police"'));
});

check('Formular: Netz loeschen', function (&$e) {
	expectPage($e, request('GET', '/', array('action' => 'deleteNetwork', 'id' => 819)),
		array('Do you really want to delete 2001:db8::/32', 'name="subnets"'));
});

// ---------------------------------------------------------------------------
// AJAX-Endpunkte
// ---------------------------------------------------------------------------

check('isPossible: vorhandenes Netz', function (&$e) {
	expectPage($e, request('GET', '/isPossible.php', array('network' => '2001:db8::', 'mask' => 32)),
		array('Network already exists!'));
});

check('isPossible: freies Netz', function (&$e) {
	expectPage($e, request('GET', '/isPossible.php', array('network' => '2001:db8:1::', 'mask' => 48)),
		array('is OK!'));
});

check('isPossible: ungueltiges Netz', function (&$e) {
	expectPage($e, request('GET', '/isPossible.php', array('network' => 'kein-netz', 'mask' => 48)),
		array('Network is not valid!'));
});

check('getPossibleMasks', function (&$e) {
	$r = request('GET', '/getPossibleMasks.php', array('mask' => 32, 'subnet' => NET_2001_DB8_32));
	expectPage($e, $r);
	expectTrue($e, preg_match('/^\s*\d\s*$/', $r[1]) === 1, 'Antwort ist keine Zahl: ' . substr($r[1], 0, 100));
});

check('getHiddenNetworks', function (&$e) {
	global $db;
	// ein /48 unterhalb von 2001:db8:c000::/34 ist in der /32-Ansicht "versteckt"
	$bin = substr_replace(NET_2001_DB8_32, '1100000000000001', 32, 16);
	$db->query("INSERT INTO `ipamv6` (`network`,`network_bin`,`mask`,`root`,`color`,`description`)
		VALUES ('2001:0db8:c001:0000:0000:0000:0000:0000', '$bin', 48, 0, '#FFFFAA', 'hidden48')");
	$r = request('GET', '/getHiddenNetworks.php', array(
		'mask' => 32,
		'lastNetwork_bin' => substr_replace(NET_2001_DB8_32, '11000000', 32, 8),
		'root' => NET_2001_DB8_32,
	));
	expectPage($e, $r, array('2001:db8:c001::/48', 'hidden48'));
});

// ---------------------------------------------------------------------------
// Schreibende Aktionen
// ---------------------------------------------------------------------------

check('Netz anlegen (hex)', function (&$e) {
	$r = request('GET', '/', array(
		'action' => 'addNewNetwork', 'format' => 'hex', 'newNetwork' => '2001:db8:1234::',
		'newMask' => 48, 'newDescription' => 'neu', 'newColour' => '#123456',
	));
	expectTrue($e, $r[0] === 302, "HTTP-Status {$r[0]} statt 302");
	expectTrue($e, rowCount("`network`='2001:0db8:1234:0000:0000:0000:0000:0000' AND `mask`=48 AND `description`='neu'") === 1,
		'Netz wurde nicht angelegt');
});

check('Subnetz anlegen (bin, aus Sudoku-Ansicht)', function (&$e) {
	$bin = substr_replace(NET_2001_DB8_32, '00100000', 32, 8);
	$r = request('GET', '/', array(
		'action' => 'addNewNetwork', 'format' => 'bin', 'newNetwork' => $bin,
		'newMask' => 40, 'newDescription' => 'subnetz', 'newColour' => '#AABBCC',
	));
	expectTrue($e, $r[0] === 302, "HTTP-Status {$r[0]} statt 302");
	expectTrue($e, rowCount("`network_bin`='$bin' AND `mask`=40") === 1, 'Subnetz wurde nicht angelegt');
});

check('Netz bearbeiten', function (&$e) {
	$r = request('GET', '/', array(
		'action' => 'storeEditedNetwork', 'format' => 'hex', 'id' => 820, 'newNetwork' => '2001:db8:c000::',
		'newMask' => 34, 'newDescription' => 'geaendert', 'newColor' => '#000000',
	));
	expectTrue($e, $r[0] === 302, "HTTP-Status {$r[0]} statt 302");
	expectTrue($e, rowCount("`id`=820 AND `description`='geaendert' AND `color`='#000000'") === 1, 'Netz wurde nicht geaendert');
	expectTrue($e, rowCount() === 5, 'Anzahl der Netze hat sich veraendert');
});

check('Netz loeschen (ohne Subnetze)', function (&$e) {
	$r = request('GET', '/deleteNetworks.php', array('id' => 826, 'subnets' => 0, 'url' => './?action=showIpv6Route'));
	expectTrue($e, $r[0] === 302, "HTTP-Status {$r[0]} statt 302");
	expectTrue($e, rowCount('`id`=826') === 0, 'Netz wurde nicht geloescht');
	expectTrue($e, rowCount() === 4, 'falsche Anzahl verbleibender Netze');
});

check('Netz loeschen (mit Subnetzen)', function (&$e) {
	$r = request('GET', '/deleteNetworks.php', array('id' => 819, 'subnets' => 1, 'url' => './'));
	expectTrue($e, $r[0] === 302, "HTTP-Status {$r[0]} statt 302");
	expectTrue($e, rowCount() === 0, 'nicht alle Subnetze wurden geloescht');
});

// ---------------------------------------------------------------------------
// Sicherheit: SQL-Injection
// ---------------------------------------------------------------------------

check('SQLi: Bearbeiten mit id="1 OR 1=1" aendert nicht alle Netze', function (&$e) {
	request('GET', '/', array(
		'action' => 'storeEditedNetwork', 'format' => 'hex', 'id' => '820 OR 1=1', 'newNetwork' => '2001:db8:c000::',
		'newMask' => 34, 'newDescription' => 'pwned', 'newColor' => '#000000',
	));
	expectTrue($e, rowCount("`description`='pwned'") <= 1, 'mehrere Netze wurden ueberschrieben');
});

check("SQLi: Loeschen mit id=\"' OR '1'='1\" loescht nicht alles", function (&$e) {
	request('GET', '/deleteNetworks.php', array('id' => "' OR '1'='1", 'subnets' => 0, 'url' => './'));
	expectTrue($e, rowCount() === 5, 'Netze wurden geloescht');
});

check('SQLi: Beschreibung mit Hochkomma wird korrekt gespeichert', function (&$e) {
	request('GET', '/', array(
		'action' => 'addNewNetwork', 'format' => 'hex', 'newNetwork' => '2001:db8:1234::',
		'newMask' => 48, 'newDescription' => "O'Brien", 'newColour' => '#123456',
	));
	expectTrue($e, rowCount("`description`='O\\'Brien'") === 1, 'Beschreibung mit Hochkomma wurde nicht gespeichert');
});

check('SQLi: getHiddenNetworks mit UNION', function (&$e) {
	$r = request('GET', '/getHiddenNetworks.php', array(
		'mask' => '0 UNION SELECT 1,2,3,4,5,user(),7 -- ',
		'lastNetwork_bin' => str_repeat('1', 128),
	));
	expectTrue($e, strpos($r[1], 'ipamv6@') === false, 'Datenbankbenutzer wurde ausgegeben');
});

check('SQLi: Sudoku-Ansicht mit manipuliertem network', function (&$e) {
	$start = microtime(true);
	$r = request('GET', '/', array('action' => 'sudoku', 'mask' => 32, 'network' => "' OR SLEEP(1) OR '"));
	expectTrue($e, microtime(true) - $start < 1, 'SLEEP() wurde in der Datenbank ausgefuehrt');
	expectTrue($e, $r[0] < 500, "HTTP-Status {$r[0]}");
	expectTrue($e, strpos($r[1], 'id="sudoku"') === false, 'ungueltiges Netz wurde nicht abgewiesen');
});

check('Ungueltige Maske wird abgewiesen', function (&$e) {
	$r = request('GET', '/', array('action' => 'sudoku', 'mask' => 'abc', 'network' => NET_2001_DB8_32));
	expectTrue($e, $r[0] < 500, "HTTP-Status {$r[0]}");
	expectTrue($e, strpos($r[1], 'id="sudoku"') === false, 'ungueltige Maske wurde nicht abgewiesen');
});

echo "\n$passed bestanden, $failures fehlgeschlagen\n";
exit($failures > 0 ? 1 : 0);
