<?php
final class Error {
	static private $registeredErrors = array();
	static private $message;
	
	final public static function register($key, $params) {
		self::$registeredErrors[$key] = $params;
	}
	
	final public static function load($message, $params = array()) {
		self::$message = $message;
		if (isset($params['code']) && $params['code'] == 404) {
			Error::load404();
		} else {
			throw new Exception($message);
		}
	}
	
	final public static function getMessage() {
		return self::$message;
	}
	
	final public static function load404() {
		header("HTTP/1.0 404 Not Found");
		
		if (Factory::get_config()->get_error('404')) {
			Factory::get_config(true)->set_working_uri(Factory::get_config()->get_error('404'));
			Factory::get_config()->check_uri();
			if (($controller = System::load(array("name"=>reset(Factory::get_config()->get_working_uri()), "type"=>"controller"))) === false) {
				include("public/errors/404.php");
			} else {
				try {
					$controller->show_view();
				} catch(Exception $e) {
					echo "Caught exception: ".$e->getMessage();
				}
			}
			
		} else {
			include("public/errors/404.php");
		}
	}
}
?>