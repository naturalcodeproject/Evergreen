<?php
class Factory {
	private static $class_holder = null;
	
	final public static function get_db($reset=false) {
		if (!isset(self::$class_holder['db']) || $reset) {
            require_once('db.class.php');
			self::$class_holder['db'] = new DB();
		}
		
		return self::$class_holder['db'];
	}
}
?>