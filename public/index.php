<?php
// PUT requests need to be redirected to push.php
$is_put = $_SERVER['REQUEST_METHOD'] === 'PUT' ||
	(
		!empty($_SERVER['HTTP_X_METHOD_OVERRIDE']) &&
		$_SERVER['HTTP_X_METHOD_OVERRIDE'] === 'PUT'
	);

// DELETE request need to be redirected to delete.php
$is_delete = $_SERVER['REQUEST_METHOD'] === 'DELETE' ||
	(
		!empty($_SERVER['HTTP_X_METHOD_OVERRIDE']) &&
		$_SERVER['HTTP_X_METHOD_OVERRIDE'] === 'DELETE'
	);

if ($is_put) {
	require(__DIR__ . '/push.php');
	die();
}

if ($is_delete) {
	require(__DIR__.'/delete.php');
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
