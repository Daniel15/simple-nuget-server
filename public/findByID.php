<?php
require('../inc/core.php');
require('../inc/feedwriter.php');

$id = trim($_GET['id'], '\'');

$results = DB::findByID($id);
$feed = new FeedWriter('FindPackagesById');
$feed->writeToOutput($results);
