<?php
require(__DIR__ . '/../inc/core.php');
require(__DIR__ . '/../inc/feedwriter.php');

$id = trim($_GET['id'], '\'');
$version = null;
if (!empty($_GET['version'])) {
	$version = trim($_GET['version'], '\'');
}

$results = DB::findByID($id, $version);
$feed = new FeedWriter('FindPackagesById');
$feed->writeToOutput($results);
