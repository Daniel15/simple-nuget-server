<?php
require('../inc/core.php');
require('../inc/feedwriter.php');

// TODO: Pagination
$results = DB::searchPackages([
	'includePrerelease' => !empty($_GET['includePrerelease']),
	'orderBy' => isset($_GET['$orderby']) ? $_GET['$orderby'] : '',
	'filter' => isset($_GET['$filter']) ? $_GET['$filter'] : '',
	'searchQuery' => isset($_GET['searchTerm']) ? trim($_GET['searchTerm'], '\'') : '',
]);
$feed = new FeedWriter('Search');
$feed->writeToOutput($results);
