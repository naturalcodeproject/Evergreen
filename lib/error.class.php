<?php
final class Error {
	static private $registeredErrors = array();
	static private $message;
	static private $params;
	
	final public static function register($key, $params) {
		if (!is_array($params)) {
			$params = array("message"=>$params);
		}
		
		self::$registeredErrors[$key] = $params;
	}
	
	final public static function trigger($message, $params = array()) {
		self::clearAllBuffers();
		if (array_key_exists($message, self::$registeredErrors)) {
			$params = self::$registeredErrors[$message];
			$message = self::$registeredErrors[$message]['message'];
		}
		
		self::$message = $message;
		self::$params = $params;
		if (isset($params['code']) && $params['code'] == 404) {
			if (isset($params['url'])) {
				Error::loadURL($params['url']);
			} else {
				Error::loadURL(Factory::get_config()->get_error('404'));
			}
		} else {
			if (isset($params['url'])) {
				Error::loadURL($params['url']);
			} else {
				throw new Exception(self::$message);
			}
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
	
	final public static function loadURL($url) {
		header("HTTP/1.0 404 Not Found");
		
		if ($url) {
			Factory::get_config(true)->set_working_uri($url);
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