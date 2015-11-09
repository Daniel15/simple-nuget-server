<?php
require(__DIR__ . '/../inc/core.php');

// PUT requests need to be redirected to push.php
if (request_method() === 'PUT') {
	require(__DIR__ . '/push.php');
	die();
}

header('Content-Type: text/xml; charset=utf-8');
$base_url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);
echo "<" . "?xml version='1.0' encoding='utf-8' standalone='yes'?>";
?>
<service xml:base="<?= $base_url ?>/"
	xmlns:atom="http://www.w3.org/2005/Atom"
	xmlns:app="http://www.w3.org/2007/app"
	xmlns="http://www.w3.org/2007/app">
  <workspace>
	<atom:title>Default</atom:title>
	<collection href="Packages">
		<atom:title>Packages</atom:title>
	</collection>
  </workspace>
</service>
