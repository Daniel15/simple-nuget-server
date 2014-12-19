<?php
require(__DIR__ . '/../inc/core.php');
require(__DIR__ . '/../inc/feedwriter.php');

$package_ids = explode('|', trim($_GET['packageids'], '\''));
$versions = explode('|', trim($_GET['versions'], '\''));
$package_to_version = array_combine($package_ids, $versions);

$results = DB::packageUpdates([
	'includePrerelease' => !empty($_GET['includeprerelease']),
	'packages' => $package_to_version,
]);
$feed = new FeedWriter('GetUpdates');
$feed->writeToOutput($results);
