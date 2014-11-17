<?php
class Config {
	public static $dbName;
	public static $apiKey;
	public static $packageDir;
}

Config::$dbName = 'sqlite:../db/packages.sqlite3';
Config::$packageDir = __DIR__ . '/../packagefiles/';
Config::$apiKey = 'ChangeThisKey';
