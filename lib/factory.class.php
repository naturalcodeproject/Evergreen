<?php
class Factory {
	private static $class_holder = null;
	
	final public static function get_config($reset=false) {
		if (!isset(self::$class_holder['config']) || $reset) {
			self::$class_holder['config'] = new Config();
		}
		
		return self::$class_holder['config'];
	}
	
	final public static function get_db($reset=false) {
		if (!isset(self::$class_holder['db']) || $reset) {
			self::$class_holder['db'] = new DB();
		}
		
		return self::$class_holder['db'];
	}
}
?>