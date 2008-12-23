<?php
class Factory
{
	private static $class_holder = null;
	
	final public static function get_config()
	{
		if (!isset(self::$class_holder['config']))
		{
			self::$class_holder['config'] = new Config();
		}
		
		return self::$class_holder['config'];
	}
	
	final public static function get_db()
	{
		if (!isset(self::$class_holder['db']))
		{
			self::$class_holder['db'] = new DB();
		}
		
		return self::$class_holder['db'];
	}
	
	final public static function get_spyc()
	{
		if (!isset(self::$class_holder['spyc']))
		{
			self::$class_holder['spyc'] = new Spyc();
		}
		
		return self::$class_holder['spyc'];
	}
}
?>