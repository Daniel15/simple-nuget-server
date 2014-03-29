<?php
require(__DIR__ . '/../inc/core.php');
require(__DIR__ . '/../inc/feedwriter.php');

$id = trim($_GET['id'], '\'');

$results = DB::findByID($id);
$feed = new FeedWriter('FindPackagesById');
$feed->writeToOutput($results);
