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
				Error::loadURL(Config::read("Errors.404"));
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
		if (isset(self::$params['code']) && self::$params['code'] == 404) {
			header("HTTP/1.0 404 Not Found");
		}
		/*
			TODO : Check for an external url
		*/
		if (!empty($url)) {
			$url = str_replace(URI_ROOT, "", $url);
			Config::register("URI.working", $url);
			Config::register("Branch.name", "");
			Config::processURI();
			
			if (Config::read("Branch.name")) {
				## Unload Main Autoloader ##
				spl_autoload_unregister(array('AutoLoaders', 'main'));
				
				## Load Branch Autoloader ##
				spl_autoload_register(array('AutoLoaders', 'branches'));
			} else {
				## Unload Branch Autoloader ##
				spl_autoload_unregister(array('AutoLoaders', 'branches'));
				
				## Load Main Autoloader ##
				spl_autoload_register(array('AutoLoaders', 'main'));
			}
			
			if (($controller = System::load(array("name"=>reset(Config::read("URI.working")), "type"=>"controller", "branch"=>Config::read("Branch.name")))) === false) {
				include(Config::read("System.defaultError404"));
			} else {
				try {
					$controller->show_view();
				} catch(Exception $e) {
					if (Config::read("System.mode") == "development") {
						echo "Caught exception: ".$e->getMessage();
					}
				}
			}
			
		} else {
			include(Config::read("System.defaultError404"));
		}
	}
	
	
}
?>