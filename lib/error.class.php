<?php
final class Error {
	static private $registeredErrors = array();
	static private $message;
	
	final public static function register($key, $params) {
		if (!is_array($params)) {
			$params = array("message"=>$params);
		}
		
		self::$registeredErrors[$key] = $params;
	}
	
	final public static function load($message, $params = array()) {
		self::clearAllBuffers();
		if (array_key_exists($message, self::$registeredErrors)) {
			$params = self::$registeredErrors[$message];
			$message = self::$registeredErrors[$message]['message'];
		}
		
		self::$message = $message;
		if (isset($params['code']) && $params['code'] == 404) {
			Error::load404();
		} else {
			throw new Exception(self::$message);
		}
	}
	
	final public static function getMessage() {
		return self::$message;
	}
	
	final public static function clearAllBuffers() {
		$buffer_count = ob_get_level();
		for($i = 1; $i <= $buffer_count; $i++) {
			ob_end_clean();
		}
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