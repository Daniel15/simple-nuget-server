<?php
class Config {
	public static $dbName;
	public static $apiKey;
	public static $packageDir;
}

Config::$dbName = 'sqlite:../db/packages.sqlite3';
Config::$packageDir = __DIR__ . '/../packagefiles/';
Config::$apiKey = 'f9f802a8-d69f-4533-923d-7a6d452fdec5';
