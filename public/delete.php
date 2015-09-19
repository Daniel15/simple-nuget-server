<?php
require(__DIR__ . '/../inc/core.php');
$url = $_SERVER['REQUEST_URI'];
$url_parts = explode("/", $url);


if (empty($_SERVER['HTTP_X_NUGET_APIKEY']) || $_SERVER['HTTP_X_NUGET_APIKEY'] != Config::$apiKey) {
	api_error('403', 'Invalid API key from Omar');
}

if (count($url_parts)!= 6 ) {
	api_error('400','Invalid URL format ['.$url.']');
}

$id = $url_parts[4];
$version = $url_parts[5];

// Validate the package is in the database
if (!DB::validateIdAndVersion($id, $version)) {
    api_error('400', 'Invalid ID or version');
}


// Setup directory place
$dir =  Config::$packageDir . $id . DIRECTORY_SEPARATOR;
$path = $dir . $version . '.nupkg';

// Delete package version 
if ( file_exists($path)) {
	//api_error('403',$path);
	unlink($path);
}

// Delete root directory if it is empty (last version deleted)
if ( file_exists($dir) ) {
	$fi = new FilesystemIterator($dir,FilesystemIterator::SKIP_DOTS);
	if (iterator_count($fi) == 0) {
		rmdir($dir);
	}
}

// Delete the requested version
DB::deleteVersion($id,$version);

if (!DB::hasVersions($id)) {
	// Delete record - no more versions exist
	DB::deletePackage($id);
}
else {
	// Provide a new version for the package - the top one was deleted
	$row = DB::getTopVersion($id);
	if ($row) {
		DB::insertOrUpdatePackage([
			':id' => $id,
			':title' => $row['Title'],
			':version' => $row['Version']
		]);
	}
	else {
		DB::deletePackage($id);	
	}
}



//All done!
header('HTTP:/1.1 200 OK');
