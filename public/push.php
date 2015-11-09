<?php
require_once(__DIR__ . '/../inc/core.php');

require_auth();

if (empty($_FILES['package'])) {
	api_error('400', 'No package file');
}

$upload_filename = $_FILES['package']['tmp_name'];
// Try to find NuSpec file
$package_zip = new ZipArchive();
$package_zip->open($upload_filename);
$nuspec_index = false;
for ($i = 0; $i < $package_zip->numFiles; $i++) {
	if (substr($package_zip->getNameIndex($i), -7) === '.nuspec') {
		$nuspec_index = $i;
		break;
	}
}
if ($nuspec_index === false) {
	api_error('400', 'NuSpec file not found in package');
}
$nuspec_string = $package_zip->getFromIndex($nuspec_index);
$nuspec = simplexml_load_string($nuspec_string);

if (!$nuspec->metadata->id || !$nuspec->metadata->version) {
	api_error('400', 'ID or version is missing');
}

$id = (string)$nuspec->metadata->id;
$version = (string)$nuspec->metadata->version;
$valid_id = '/^[A-Z0-9\.\~\+\_\-]+$/i';
if (!preg_match($valid_id, $id) || !preg_match($valid_id, $version)) {
	api_error('400', 'Invalid ID or version');
}

if (DB::validateIdAndVersion($id, $version)) {
	api_error('409', 'This package version already exists');
}

$hash = base64_encode(hash_file('sha512', $upload_filename, true));
$filesize = filesize($_FILES['package']['tmp_name']);
$dependencies = [];


if ($nuspec->metadata->dependencies) {
	if ($nuspec->metadata->dependencies->dependency) {
		// Dependencies that are not specific to any framework
		foreach ($nuspec->metadata->dependencies->dependency as $dependency) {
			$dependencies[] = [
				'framework' => null,
				'id' => (string)$dependency['id'],
				'version' => (string)$dependency['version']
			];
		}
	}

	if ($nuspec->metadata->dependencies->group) {
		// Dependencies that are specific to a particular framework
		foreach ($nuspec->metadata->dependencies->group as $group) {
			foreach ($group->dependency as $dependency) {
				$dependencies[] = [
					'framework' => (string)$group['targetFramework'],
					'id' => (string)$dependency['id'],
					'version' => (string)$dependency['version']
				];
			}
		}
	}
}

// Move package into place.
$dir = Config::$packageDir . $id . DIRECTORY_SEPARATOR;
$path = $dir . $version . '.nupkg';

if (!file_exists($dir)) {
	mkdir($dir, /* mode */ 0755, /* recursive */ true);
}
if (!move_uploaded_file($upload_filename, $path)) {
	api_error('500', 'Could not save file');
}

// Update database
DB::insertOrUpdatePackage([
	':id' => $id,
	':title' => $nuspec->metadata->title,
	':version' => $version
]);
DB::insertVersion([
	':Authors' => $nuspec->metadata->authors,
	':Copyright' => $nuspec->metadata->copyright,
	':Dependencies' => $dependencies,
	':Description' => $nuspec->metadata->description,
	':PackageHash' => $hash,
	':PackageHashAlgorithm' => 'SHA512',
	':PackageSize' => $filesize,
	':IconUrl' => $nuspec->metadata->iconUrl,
	':IsPrerelease' => strpos($version, '-') !== false,
	':LicenseUrl' => $nuspec->metadata->licenseUrl,
	':Owners' => $nuspec->metadata->owners,
	':PackageId' => $id,
	':ProjectUrl' => $nuspec->metadata->projectUrl,
	':ReleaseNotes' => $nuspec->metadata->releaseNotes,
	':RequireLicenseAcceptance' => $nuspec->metadata->requireLicenseAcceptance === 'true',
	':Tags' => $nuspec->metadata->tags,
	':Title' => $nuspec->metadata->title,
	':Version' => $version,
]);

// All done!
header('HTTP/1.1 201 Created');
