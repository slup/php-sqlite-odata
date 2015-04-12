<?php

class Config {

	private static $host = 'slup.ch';
	private static $subdir = '/ODataTest';
	private static $model_name = 'ODataTest';
	private static $database_path = 'ODataTest.sqlite';
	
	public static function get_host() {
		return self::$host;
	}
	
	public static function get_subdir() {
		return self::$subdir;
	}
	
	public static function get_model_name() {
		return self::$model_name;
	}
	
	public static function get_database_path() {
		return self::$database_path;
	}
}

?>