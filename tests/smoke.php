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

// Die Tests setzen die Datenbank zurueck. Ueber den Webserver aufgerufen
// wuerde das die Daten loeschen, deshalb nur auf der Kommandozeile ausfuehren.
if (PHP_SAPI !== 'cli') {
	http_response_code(403);
	exit('Forbidden');
}

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

/**
 * Adds the example networks to the sample data of ipamv6.sql:
 * a second root network, subnets that are only reachable by "...",
 * a subnet behind a visible network and a very deep one.
 */
function addExampleNetworks()
{
	global $db;
	$networks = array(
		// network, mask, root, color, description
		array('fd00::',              8,  1, '#AAFFAA', 'ULA'),
		array('fd12:3400::',         24, 0, '#AAAAFF', 'Lab'),
		array('fd12:3400:5600::',    40, 0, '#FFAAAA', 'Lab Rack 1'),
		array('2001:db8:4100::',     40, 0, '#FFCC00', 'Site A'),
		array('2001:db8:4100:1200::', 56, 0, '#00CCFF', 'Server-VLAN'),
		array('2001:db8:ac00::',     48, 0, '#CCCCCC', 'Hidden'),
		array('2001:db8:ac00:ab00::', 120, 0, '#CCCCCC', 'Deep'),
	);
	$st = $db->prepare('INSERT INTO `ipamv6` (`network`, `network_bin`, `mask`, `root`, `color`, `description`) VALUES (?, ?, ?, ?, ?, ?)');
	foreach ($networks as $n) {
		$expanded = implode(':', str_split(bin2hex(inet_pton($n[0])), 4));
		$st->execute(array($expanded, bin($n[0]), $n[1], $n[2], $n[3], $n[4]));
	}
}

/**
 * Binary network of an IPv6 address (128 characters "0"/"1").
 */
function bin($hex)
{
	$bin = '';
	foreach (str_split(inet_pton($hex)) as $byte) {
		$bin .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
	}
	return $bin;
}

/**
 * Background colors of the sudoku cells, e.g. array('00' => 'white', 'C0' => '#4D0CFF', ...).
 */
function cellColors($html)
{
	preg_match_all('/<td\s+id="cell([0-9A-F]{2})"\s+class="field_\1"\s+style="background-color: ([^"]*)"/', $html, $m);
	return array_combine($m[1], $m[2]);
}

/**
 * Number of SQL queries the database server answered so far (all connections).
 */
function queryCount()
{
	global $db;
	$n = 0;
	foreach ($db->query("SHOW GLOBAL STATUS WHERE Variable_name IN ('Com_select', 'Com_stmt_execute')") as $row) {
		$n += (int)$row['Value'];
	}
	return $n;
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
function request($method, $path, array $params = array(), array $headers = array())
{
	global $baseUrl;
	$url  = $baseUrl . '/' . ltrim($path, '/');
	$opts = array('method' => $method, 'ignore_errors' => true, 'follow_location' => 0, 'timeout' => 30);
	if ($method === 'GET' && $params) {
		$url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($params);
	} elseif ($method !== 'GET') {
		$headers[]       = 'Content-Type: application/x-www-form-urlencoded';
		$opts['content'] = http_build_query($params);
	}
	if ($headers) {
		$opts['header'] = implode("\r\n", $headers) . "\r\n";
	}
	$body    = @file_get_contents($url, false, stream_context_create(array('http' => $opts)));
	$headers = isset($http_response_header) ? $http_response_header : array();
	$status  = 0;
	if ($headers && preg_match('#HTTP/\S+ (\d{3})#', $headers[0], $m)) {
		$status = (int)$m[1];
	}
	return array($status, $body === false ? '' : $body, $headers);
}

/**
 * Opens a form page and returns [Cookie-Header, CSRF-Token] of the session.
 */
function csrfSession()
{
	static $session = null;
	if ($session === null) {
		list(, $body, $headers) = request('GET', '/', array('action' => 'addCustomNetworkForm'));
		$cookie = header_value($headers, 'Set-Cookie');
		preg_match('/name="csrf_token" value="([0-9a-f]+)"/', $body, $m);
		if (!$cookie || !$m) {
			throw new RuntimeException('keine Session oder kein CSRF-Token erhalten');
		}
		$session = array('Cookie: ' . explode(';', $cookie)[0], $m[1]);
	}
	return $session;
}

/**
 * Sends a form like the browser does: POST with session cookie and CSRF token.
 */
function submit($path, array $params, array $headers = array())
{
	list($cookie, $token) = csrfSession();
	$headers[] = $cookie;
	return request('POST', $path, $params + array('csrf_token' => $token), $headers);
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
	$r = submit('/?action=addNewNetwork', array(
		'format' => 'hex', 'newNetwork' => '2001:db8:1234::',
		'newMask' => 48, 'newDescription' => 'neu', 'newColour' => '#123456',
	));
	expectTrue($e, $r[0] === 302, "HTTP-Status {$r[0]} statt 302");
	expectTrue($e, rowCount("`network`='2001:0db8:1234:0000:0000:0000:0000:0000' AND `mask`=48 AND `description`='neu'") === 1,
		'Netz wurde nicht angelegt');
});

check('Subnetz anlegen (bin, aus Sudoku-Ansicht)', function (&$e) {
	$bin = substr_replace(NET_2001_DB8_32, '00100000', 32, 8);
	$r = submit('/?action=addNewNetwork', array(
		'format' => 'bin', 'newNetwork' => $bin,
		'newMask' => 40, 'newDescription' => 'subnetz', 'newColour' => '#AABBCC',
	));
	expectTrue($e, $r[0] === 302, "HTTP-Status {$r[0]} statt 302");
	expectTrue($e, rowCount("`network_bin`='$bin' AND `mask`=40") === 1, 'Subnetz wurde nicht angelegt');
});

check('Netz bearbeiten', function (&$e) {
	$r = submit('/?action=storeEditedNetwork', array(
		'format' => 'hex', 'id' => 820, 'newNetwork' => '2001:db8:c000::',
		'newMask' => 34, 'newDescription' => 'geaendert', 'newColor' => '#000000',
	));
	expectTrue($e, $r[0] === 302, "HTTP-Status {$r[0]} statt 302");
	expectTrue($e, rowCount("`id`=820 AND `description`='geaendert' AND `color`='#000000'") === 1, 'Netz wurde nicht geaendert');
	expectTrue($e, rowCount() === 5, 'Anzahl der Netze hat sich veraendert');
});

check('Netz bearbeiten: root-Flag bleibt erhalten', function (&$e) {
	submit('/?action=storeEditedNetwork', array(
		'format' => 'hex', 'id' => 819, 'newNetwork' => '2001:db8::',
		'newMask' => 32, 'newDescription' => 'SH', 'newColor' => '#FFFFAA', 'newRoot' => 1,
	));
	expectTrue($e, rowCount('`id`=819 AND `root`=1') === 1, 'root-Flag wurde zurueckgesetzt');
});

check('Ungueltige Farbe wird nicht gespeichert', function (&$e) {
	submit('/?action=addNewNetwork', array(
		'format' => 'hex', 'newNetwork' => '2001:db8:1234::',
		'newMask' => 48, 'newDescription' => 'farbe', 'newColour' => 'red;x:y',
	));
	expectTrue($e, rowCount("`description`='farbe' AND `color`='#FFFFAA'") === 1, 'ungueltige Farbe wurde uebernommen');
});

check('Netz loeschen (ohne Subnetze)', function (&$e) {
	$r = submit('/deleteNetworks.php', array('id' => 826, 'subnets' => 0, 'url' => './?action=showIpv6Route'));
	expectTrue($e, $r[0] === 302, "HTTP-Status {$r[0]} statt 302");
	expectTrue($e, rowCount('`id`=826') === 0, 'Netz wurde nicht geloescht');
	expectTrue($e, rowCount() === 4, 'falsche Anzahl verbleibender Netze');
});

check('Netz loeschen (mit Subnetzen)', function (&$e) {
	$r = submit('/deleteNetworks.php', array('id' => 819, 'subnets' => 1, 'url' => './'));
	expectTrue($e, $r[0] === 302, "HTTP-Status {$r[0]} statt 302");
	expectTrue($e, rowCount() === 0, 'nicht alle Subnetze wurden geloescht');
});

// ---------------------------------------------------------------------------
// Sicherheit: SQL-Injection
// ---------------------------------------------------------------------------

check('SQLi: Bearbeiten mit id="1 OR 1=1" aendert nicht alle Netze', function (&$e) {
	submit('/?action=storeEditedNetwork', array(
		'format' => 'hex', 'id' => '820 OR 1=1', 'newNetwork' => '2001:db8:c000::',
		'newMask' => 34, 'newDescription' => 'pwned', 'newColor' => '#000000',
	));
	expectTrue($e, rowCount("`description`='pwned'") <= 1, 'mehrere Netze wurden ueberschrieben');
});

check("SQLi: Loeschen mit id=\"' OR '1'='1\" loescht nicht alles", function (&$e) {
	submit('/deleteNetworks.php', array('id' => "' OR '1'='1", 'subnets' => 0, 'url' => './'));
	expectTrue($e, rowCount() === 5, 'Netze wurden geloescht');
});

check('SQLi: Beschreibung mit Hochkomma wird korrekt gespeichert', function (&$e) {
	submit('/?action=addNewNetwork', array(
		'format' => 'hex', 'newNetwork' => '2001:db8:1234::',
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

// ---------------------------------------------------------------------------
// Sicherheit: CSRF, XSS, Open Redirect
// ---------------------------------------------------------------------------

check('CSRF: Anlegen per GET wird abgewiesen', function (&$e) {
	$r = request('GET', '/', array(
		'action' => 'addNewNetwork', 'format' => 'hex', 'newNetwork' => '2001:db8:1234::',
		'newMask' => 48, 'newDescription' => 'csrf', 'newColour' => '#123456',
	));
	expectTrue($e, $r[0] === 405, "HTTP-Status {$r[0]} statt 405");
	expectTrue($e, rowCount("`description`='csrf'") === 0, 'Netz wurde angelegt');
});

check('CSRF: Loeschen per GET wird abgewiesen', function (&$e) {
	$r = request('GET', '/deleteNetworks.php', array('id' => 819, 'subnets' => 1, 'url' => './'));
	expectTrue($e, $r[0] === 405, "HTTP-Status {$r[0]} statt 405");
	expectTrue($e, rowCount() === 5, 'Netze wurden geloescht');
});

check('CSRF: POST ohne Token wird abgewiesen', function (&$e) {
	list($cookie) = csrfSession();
	$r = request('POST', '/deleteNetworks.php', array('id' => 819, 'subnets' => 1, 'url' => './'), array($cookie));
	expectTrue($e, $r[0] === 403, "HTTP-Status {$r[0]} statt 403");
	expectTrue($e, rowCount() === 5, 'Netze wurden geloescht');
});

check('CSRF: POST mit falschem Token wird abgewiesen', function (&$e) {
	list($cookie) = csrfSession();
	$r = request('POST', '/?action=storeEditedNetwork', array(
		'csrf_token' => str_repeat('0', 64), 'format' => 'hex', 'id' => 820, 'newNetwork' => '2001:db8:c000::',
		'newMask' => 34, 'newDescription' => 'csrf', 'newColor' => '#000000',
	), array($cookie));
	expectTrue($e, $r[0] === 403, "HTTP-Status {$r[0]} statt 403");
	expectTrue($e, rowCount("`description`='csrf'") === 0, 'Netz wurde geaendert');
});

check('CSRF: Token ist an die Session gebunden', function (&$e) {
	list(, $token) = csrfSession();
	$r = request('POST', '/deleteNetworks.php', array('csrf_token' => $token, 'id' => 819, 'subnets' => 1, 'url' => './'));
	expectTrue($e, $r[0] === 403, "HTTP-Status {$r[0]} statt 403");
	expectTrue($e, rowCount() === 5, 'Netze wurden geloescht');
});

check('Formulare enthalten CSRF-Token und nutzen POST', function (&$e) {
	$pages = array(
		array('action' => 'addCustomNetworkForm'),
		array('action' => 'editNetworkForm', 'id' => 820),
		array('action' => 'deleteNetwork', 'id' => 820),
		array('action' => 'sudoku', 'mask' => 32, 'network' => NET_2001_DB8_32),
	);
	foreach ($pages as $params) {
		list(, $body) = request('GET', '/', $params);
		expectTrue($e, strpos($body, 'name="csrf_token"') !== false, "kein CSRF-Token: action={$params['action']}");
		expectTrue($e, stripos($body, 'method="get"') === false, "Formular mit GET: action={$params['action']}");
	}
});

check('XSS: Beschreibung wird ueberall escaped', function (&$e) {
	global $db;
	$payload = '<script>alert(1)</script>"\'';
	$stmt = $db->prepare("UPDATE `ipamv6` SET `description` = ? WHERE `id` IN (819, 820, 824)");
	$stmt->bind_param('s', $payload);
	$stmt->execute();
	$bin48 = substr_replace(NET_2001_DB8_32, '1100000000000001', 32, 16);
	$stmt = $db->prepare("INSERT INTO `ipamv6` (`network`,`network_bin`,`mask`,`root`,`color`,`description`)
		VALUES ('2001:0db8:c001:0000:0000:0000:0000:0000', ?, 48, 0, '#FFFFAA', ?)");
	$stmt->bind_param('ss', $bin48, $payload);
	$stmt->execute();

	$pages = array(
		'Startseite'       => array('/', array()),
		'Sudoku'           => array('/', array('action' => 'sudoku', 'mask' => 32, 'network' => NET_2001_DB8_32)),
		'Netzuebersicht'   => array('/', array('action' => 'showIpv6Route')),
		'Bearbeiten'       => array('/', array('action' => 'editNetworkForm', 'id' => 820)),
		'isPossible'       => array('/isPossible.php', array('network' => '2001:db8::', 'mask' => 32)),
		'getHiddenNetworks' => array('/getHiddenNetworks.php', array(
			'mask' => 32, 'lastNetwork_bin' => substr_replace(NET_2001_DB8_32, '11000000', 32, 8), 'root' => NET_2001_DB8_32)),
	);
	foreach ($pages as $name => $page) {
		list(, $body) = request('GET', $page[0], $page[1]);
		expectTrue($e, strpos($body, '<script>alert(1)') === false, "$name: Beschreibung nicht escaped");
		expectTrue($e, strpos($body, '&lt;script&gt;alert(1)') !== false, "$name: Beschreibung fehlt");
	}
});

check('XSS: ungueltige Farbe aus der Datenbank wird nicht ausgegeben', function (&$e) {
	global $db;
	$db->query("UPDATE `ipamv6` SET `color` = 'red;x:\"' WHERE `id` = 820");
	foreach (array(array('action' => 'showIpv6Route'), array('action' => 'sudoku', 'mask' => 32, 'network' => NET_2001_DB8_32)) as $params) {
		list(, $body) = request('GET', '/', $params);
		expectTrue($e, strpos($body, 'red;x:') === false, "ungueltige Farbe ausgegeben: action={$params['action']}");
	}
});

check('XSS: Referer wird im Loeschformular nicht roh ausgegeben', function (&$e) {
	$r = request('GET', '/', array('action' => 'deleteNetwork', 'id' => 820),
		array('Referer: http://127.0.0.1/?a="><script>alert(1)</script>'));
	expectPage($e, $r);
	expectTrue($e, strpos($r[1], '<script>alert(1)') === false, 'Referer nicht escaped');
});

check('Open Redirect: nach dem Loeschen nur innerhalb der Anwendung', function (&$e) {
	$r = submit('/deleteNetworks.php', array('id' => 826, 'subnets' => 0, 'url' => 'https://evil.example/phish?x=1'));
	$location = header_value($r[2], 'Location');
	expectTrue($e, $r[0] === 302, "HTTP-Status {$r[0]} statt 302");
	expectTrue($e, $location === './?x=1', 'Weiterleitung nach: ' . var_export($location, true));
	$r = submit('/deleteNetworks.php', array('id' => 825, 'subnets' => 0, 'url' => '//evil.example'));
	$location = header_value($r[2], 'Location');
	expectTrue($e, $location === './', 'Weiterleitung nach: ' . var_export($location, true));
});

check('Sicherheits-Header', function (&$e) {
	list(, , $headers) = request('GET', '/');
	expectTrue($e, header_value($headers, 'X-Frame-Options') === 'DENY', 'X-Frame-Options fehlt');
	expectTrue($e, header_value($headers, 'X-Content-Type-Options') === 'nosniff', 'X-Content-Type-Options fehlt');
	$cookie = (string)header_value($headers, 'Set-Cookie');
	expectTrue($e, stripos($cookie, 'HttpOnly') !== false && stripos($cookie, 'SameSite=Lax') !== false,
		'Session-Cookie ohne HttpOnly/SameSite: ' . $cookie);
});

check('Sicherheits-Header an den AJAX-Endpunkten', function (&$e) {
	$endpoints = array(
		array('GET', '/isPossible.php', array('network' => '2001:db8::', 'mask' => 32)),
		array('GET', '/getPossibleMasks.php', array('mask' => 32, 'subnet' => NET_2001_DB8_32)),
		array('GET', '/getHiddenNetworks.php', array('mask' => 32, 'lastNetwork_bin' => NET_2001_DB8_32)),
		array('GET', '/deleteNetworks.php', array()),
	);
	foreach ($endpoints as $ep) {
		list(, , $headers) = request($ep[0], $ep[1], $ep[2]);
		expectTrue($e, header_value($headers, 'X-Content-Type-Options') === 'nosniff', "nosniff fehlt: {$ep[1]}");
		expectTrue($e, header_value($headers, 'X-Frame-Options') === 'DENY', "X-Frame-Options fehlt: {$ep[1]}");
	}
	list(, , $headers) = request('GET', '/getPossibleMasks.php', array('mask' => 32, 'subnet' => NET_2001_DB8_32));
	expectTrue($e, stripos((string)header_value($headers, 'Content-Type'), 'text/plain') === 0,
		'getPossibleMasks liefert kein text/plain');
});

check('Seitenteile sind nicht direkt aufrufbar', function (&$e) {
	$parts = array('addNewNetwork.php', 'storeEditedNetwork.php', 'editNetworkForm.php', 'deleteNetwork.php',
		'showIpv6Route.php', 'sudoku.php', 'addCustomNetworkForm.php');
	foreach ($parts as $part) {
		list($code, $body) = request('GET', '/' . $part);
		expectTrue($e, $code === 403, "$part: HTTP-Status $code statt 403");
		expectTrue($e, trim($body) === 'Forbidden', "$part: unerwartete Ausgabe");
	}
	$before = rowCount();
	list($code) = request('POST', '/addNewNetwork.php',
		array('newNetwork' => '2001:db8:ff00::', 'newMask' => 40, 'format' => 'hex'));
	expectTrue($e, $code === 403 && rowCount() === $before, 'addNewNetwork.php direkt aufgerufen legt Netz an');
});

check('Smoke-Tests sind nicht per Webserver ausfuehrbar', function (&$e) {
	$before = rowCount();
	list($code) = request('GET', '/tests/smoke.php');
	expectTrue($e, $code === 403, "HTTP-Status $code statt 403");
	expectTrue($e, rowCount() === $before, 'Datenbank wurde veraendert');
});

check('TODO-Liste wird escaped statt eingebunden', function (&$e) {
	$r = request('GET', '/', array('action' => 'todo'));
	expectPage($e, $r, array('(done)', '&quot;'));
});

// ---------------------------------------------------------------------------
// Beispielnetze: Sudoku, Navigation, versteckte Subnetze, Anzahl der Abfragen
// ---------------------------------------------------------------------------

check('Sudoku: Zellen zeigen das groesste Netz', function (&$e) {
	addExampleNetworks();
	$r = request('GET', '/', array('action' => 'sudoku', 'mask' => 32, 'network' => bin('2001:db8::')));
	expectPage($e, $r);
	$expected = array();
	for ($i = 0; $i < 256; $i++) {
		$expected[sprintf('%02X', $i)] = 'white';
	}
	foreach (array(0x0C, 0x0D, 0x0E, 0x0F, 0xAB) as $i) {
		$expected[sprintf('%02X', $i)] = '#FFFFAA';     // 2001:db8:c00::/38 und 2001:db8:ab00::/40
	}
	for ($i = 0x40; $i <= 0x4F; $i++) {
		$expected[sprintf('%02X', $i)] = '#FF266E';     // 2001:db8:4000::/36, ueberdeckt "Site A" (/40)
	}
	for ($i = 0xC0; $i <= 0xFF; $i++) {
		$expected[sprintf('%02X', $i)] = '#4D0CFF';     // 2001:db8:c000::/34
	}
	$colors = cellColors($r[1]);
	ksort($colors);
	ksort($expected);
	expectTrue($e, count($colors) === 256, count($colors) . ' Zellen statt 256');
	foreach ($expected as $cell => $color) {
		expectTrue($e, ($colors[$cell] ?? null) === $color, "Zelle $cell: " . var_export($colors[$cell] ?? null, true) . " statt $color");
	}
	// 2001:db8:ac00::/48 ist kleiner als /40: weisse Zelle mit Hinweis
	expectTrue($e, (bool)preg_match('/id="cellAC".*?<\/td>/s', $r[1], $m) && strpos($m[0], 'img/info.png') !== false,
		'Zelle AC ohne Hinweis auf kleinere Subnetze');
	// die Zellen stehen in der verschraenkten Reihenfolge 00 01 04 05 10 ...
	preg_match_all('/id="cell([0-9A-F]{2})"/', $r[1], $m);
	expectTrue($e, implode(' ', array_slice($m[1], 0, 9)) === '00 01 04 05 10 11 14 15 40', 'Reihenfolge der Zellen: ' . implode(' ', array_slice($m[1], 0, 9)));
	expectTrue($e, implode(' ', array_slice($m[1], 16, 2)) === '02 03', 'zweite Zeile beginnt nicht mit 02 03');
});

check('Navigation: Root-Netz und Beschriftung', function (&$e) {
	addExampleNetworks();
	$cases = array(
		// Ansicht => [Root-Netz, Root-Maske, Beschriftung]
		'2001:db8:4100::/40'      => array('2001:db8::', 32, 'Site A'),
		'2001:db8:4100:1200::/56' => array('2001:db8::', 32, 'Server-VLAN'),
		'2001:db8:4100:1300::/56' => array('2001:db8::', 32, 'Site A'),   // kein eigenes Netz: das naechstgroessere
		'2001:db8:4200::/40'      => array('2001:db8::', 32, 'abcde'),    // nicht das Nachbarnetz "Site A"
		'fd12:3400:5600::/48'     => array('fd00::', 8, 'Lab Rack 1'),
		'2001:db9::/32'           => array('::', 0, ''),                  // ausserhalb aller Netze
	);
	foreach ($cases as $view => $exp) {
		list($net, $mask) = explode('/', $view);
		$r = request('GET', '/', array('action' => 'sudoku', 'mask' => $mask, 'network' => bin($net)));
		expectPage($e, $r);
		preg_match('/<div id="navBar">.*?<\/div>/s', $r[1], $nav);
		$nav = $nav[0] ?? '';
		$root = 'mask=' . $exp[1] . '&amp;network=' . bin($exp[0]);
		expectTrue($e, strpos($nav, '<a href="./?action=sudoku&amp;' . $root . '">') !== false, "$view: Root-Netz ist nicht {$exp[0]}/{$exp[1]}");
		expectTrue($e, (bool)preg_match('/\(\s*' . preg_quote($exp[2], '/') . '\s*\)\s*<\/span>/', $nav), "$view: Beschriftung ist nicht \"{$exp[2]}\"");
	}
});

/**
 * Opens the sudoku view, clicks every "..." like the browser does and returns the HTML of the expanded lists.
 */
function expandHidden(array &$e, $net, $mask)
{
	$r = request('GET', '/', array('action' => 'sudoku', 'mask' => $mask, 'network' => bin($net)));
	expectPage($e, $r);
	preg_match_all("/expandNetworks\((\d+), '([01]*)', (\d+), '([01]+)'\)/", $r[1], $m, PREG_SET_ORDER);
	$html = array();
	foreach ($m as $x) {
		$h = request('GET', '/getHiddenNetworks.php', array('mask' => $x[3], 'lastNetwork_bin' => $x[2], 'root' => $x[4]));
		expectPage($e, $h);
		$html[] = $h[1];
	}
	return $html;
}

check('Versteckte Subnetze: nach einem sichtbaren Netz', function (&$e) {
	addExampleNetworks();
	$lists = expandHidden($e, '2001:db8::', 32);
	expectTrue($e, count($lists) === 1, count($lists) . ' "..."-Eintraege statt 1');
	$html = implode('', $lists);
	foreach (array('2001:db8:ac00::/48 (Hidden)', '2001:db8:ac00:ab00::/120 (Deep)') as $needle) {
		expectTrue($e, strpos($html, $needle) !== false, "fehlt: $needle");
	}
	foreach (array('Lab', 'ULA') as $other) {
		expectTrue($e, strpos($html, "($other") === false, "Netz aus einem anderen Root-Netz: $other");
	}
});

check('Versteckte Subnetze: vor dem ersten sichtbaren Netz', function (&$e) {
	addExampleNetworks();
	$lists = expandHidden($e, 'fd00::', 8);
	expectTrue($e, count($lists) === 1, count($lists) . ' "..."-Eintraege statt 1');
	$html = implode('', $lists);
	foreach (array('fd12:3400::/24 (Lab)', 'fd12:3400:5600::/40 (Lab Rack 1)') as $needle) {
		expectTrue($e, strpos($html, $needle) !== false, "fehlt: $needle");
	}
	expectTrue($e, strpos($html, '2001:db8') === false, 'Netz aus einem anderen Root-Netz: 2001:db8::');
});

check('Sudoku-Ansicht braucht nur wenige Datenbankabfragen', function (&$e) {
	addExampleNetworks();
	$before = queryCount();
	expectPage($e, request('GET', '/', array('action' => 'sudoku', 'mask' => 32, 'network' => bin('2001:db8::'))));
	$queries = queryCount() - $before;
	expectTrue($e, $queries <= 10, "$queries Abfragen");
});

echo "\n$passed bestanden, $failures fehlgeschlagen\n";
exit($failures > 0 ? 1 : 0);
