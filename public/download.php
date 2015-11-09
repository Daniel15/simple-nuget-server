<?php
require(__DIR__ . '/../inc/core.php');

$id = $_GET['id'];
$version = $_GET['version'];

$path = get_package_path($id, $version);
DB::incrementDownloadCount($id, $version);

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $id . '.' . $version . '.nupkg"');
header('X-Accel-Redirect: ' . $path);
