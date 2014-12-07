<?php
require(__DIR__ . '/../inc/core.php');

$_GET = array_change_key_case($_GET, CASE_LOWER);

$id = $_GET['id'];
$version = $_GET['version'];

if (
	!DB::validateIdAndVersion($id, $version)
	// These should be caught by validateIdAndVersion, but better to be safe.
	|| strpos($id, '/') !== false
	|| strpos($version, '/') !== false
) {
	api_error('404', 'Package version not found');
}

DB::incrementDownloadCount($id, $version);

// This is safe - These values have been validated via validateIdAndVersion above
$path = '/packagefiles/' . $id . '/' . $version . '.nupkg';
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $id . '.' . $version . '.nupkg"');
header('X-Accel-Redirect: ' . $path);
