<?php
class Config {
	public static $dbName;
	// Username and password are not needed if using the default SQLite config,
	// but may be needed if you want to use a different database system.
	public static $dbUsername;
	public static $dbPassword;
	public static $apiKey;
	public static $packageDir;
}

Config::$dbName = 'sqlite:../db/packages.sqlite3';
Config::$packageDir = __DIR__ . '/../packagefiles/';
Config::$apiKey = 'ChangeThisKey';
