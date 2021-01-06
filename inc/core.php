<?php
require('config.php');
require('db.php');

// Convert all PHP errors to exceptions
set_error_handler(function($errno, $errstr, $errfile, $errline) {
	throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

function api_error($code, $message) {
	header('Status: ' . $code . ' ' . $message);
	header('Content-Type: text/plain');
	echo htmlspecialchars($message);
	die();
}
set_exception_handler(function($exception) {
	api_error('500', $exception->getMessage());
});

// Make $_GET keys lower-case for improved NuGet client compatibility.
$_GET = array_change_key_case($_GET, CASE_LOWER);

/**
 * Ensures that the API key is valid.
 */
function require_auth() {
	if (empty($_SERVER['HTTP_X_NUGET_APIKEY']) || $_SERVER['HTTP_X_NUGET_APIKEY'] != Config::$apiKey) {
		api_error('403', 'Invalid API key');
	}
}

/**
 * Gets the HTTP method used for the current request.
 */
function request_method() {
	return !empty($_SERVER['HTTP_X_METHOD_OVERRIDE'])
		? $_SERVER['HTTP_X_METHOD_OVERRIDE']
		: $_SERVER['REQUEST_METHOD'];
}

/**
 * Gets the file path for the specified package version. Throws an exception if
 * the package version does not exist.
 */
function get_package_path($id, $version) {
	if (
		!DB::validateIdAndVersion($id, $version)
		// These should be caught by validateIdAndVersion, but better to be safe.
		|| strpos($id, '/') !== false
		|| strpos($version, '/') !== false
	) {
		api_error('404', 'Package version not found');
	}

	// This is safe - These values have been validated via validateIdAndVersion above
	return '/packagefiles/' . $id . '/' . $version . '.nupkg';
}

/**
 * Gets the Base URL, directly if set by SetEnv / fastcgi_params, or computes it.
 */
function get_base_url() {
	if (isset($_SERVER['BASE_URL'])) {
		return $_SERVER['BASE_URL'];
	}

	$scheme = isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
		? $_SERVER['HTTP_X_FORWARDED_PROTO']
		: (
			( (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['HTTP_X_FORWARDED_PORT']) && $_SERVER['HTTP_X_FORWARDED_PORT'] == '443') || $_SERVER['SERVER_PORT'] == '443')
				? 'https'
				: 'http'
			);
	$host = isset($_SERVER['HTTP_X_FORWARDED_HOST'])
		? $_SERVER['HTTP_X_FORWARDED_HOST']
		: $_SERVER['SERVER_NAME'];
	$port = isset($_SERVER['HTTP_X_FORWARDED_PORT'])
		? $_SERVER['HTTP_X_FORWARDED_PORT']
		: $_SERVER['SERVER_PORT'];
	$path = rtrim(dirname($_SERVER['REQUEST_URI']), '/');

	$baseURL = $scheme . '://' . $host;
	if (($scheme != 'http' || $port != '80') && ($scheme != 'https' || $port != '443')) {
		$baseURL .= ':' . $port;
	}
	$baseURL .= $path;
	return $baseURL;
}
