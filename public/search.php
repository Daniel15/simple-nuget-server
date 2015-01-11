<?php
require(__DIR__ . '/../inc/core.php');
require(__DIR__ . '/../inc/feedwriter.php');

// TODO: Pagination
$results = DB::searchPackages([
	'includePrerelease' => !empty($_GET['includeprerelease']),
	'orderBy' => isset($_GET['$orderby']) ? $_GET['$orderby'] : '',
	'filter' => isset($_GET['$filter']) ? $_GET['$filter'] : '',
	'searchQuery' => isset($_GET['searchterm']) ? trim($_GET['searchterm'], '\'') : '',
]);
$feed = new FeedWriter('Search');
$feed->writeToOutput($results);
