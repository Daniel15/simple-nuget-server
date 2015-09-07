<?php
require('config.php');
require('db.php');

// Convert all PHP errors to exceptions
set_error_handler(function($errno, $errstr, $errfile, $errline) {
	throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

function api_error($code, $message) {
	//http_response_code($code);
	header("HTTP/1.1 $code $message");
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
