<?php
require(__DIR__ . '/../inc/core.php');
require(__DIR__ . '/../inc/feedwriter.php');

$results = DB::searchPackages([
	'includePrerelease' => !empty($_GET['includeprerelease']),
	'orderBy' => isset($_GET['$orderby']) ? $_GET['$orderby'] : '',
	'filter' => isset($_GET['$filter']) ? $_GET['$filter'] : '',
	'searchQuery' => isset($_GET['searchterm']) ? trim($_GET['searchterm'], '\'') : '',
	'top' => isset($_GET['$top']) ? $_GET['$top'] : '',
	'offset' => isset($_GET['$skip']) ? $_GET['$skip'] : ''
]);
$feed = new FeedWriter('Search');
$feed->writeToOutput($results);
