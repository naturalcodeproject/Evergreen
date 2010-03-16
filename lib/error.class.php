<?php
final class Error {
	static private $registeredErrors = array();
	
	## Used to track the current error ##
	static private $key;
	static private $message;
	static private $params;
	static private $errorObj;
    
    // params passed into trigger function, can be used in addition to
    // registered error params
    static private $triggerParams;
	
	final public static function register($key, $params) {
		if (!is_array($params)) {
			$params = array("message"=>$params);
		}
		
		self::$registeredErrors[$key] = $params;
	}
	
	final public static function trigger($message, $params = array()) {
        self::$triggerParams = $params;
        
		if (array_key_exists($message, self::$registeredErrors)) {
			$key = $message;
			$params = self::$registeredErrors[$message];
			$message = self::$registeredErrors[$message]['message'];
		}
		
		self::$key = $key;
		self::$message = $message;
		self::$params = $params;
        
        $errMsg = self::$message;
        if (count(self::$triggerParams)) {
            $errMsg .= "\n" . print_r(self::$triggerParams, true);
        }
        
        trigger_error($errMsg);
		
		throw new Exception(self::$message);
	}
	
	final public static function processError($errorObj = null) {
		self::clearAllBuffers();
		if ($errorObj != null) {
			self::$errorObj = $errorObj;
		}
		
		if (isset(self::$params['code']) && array_key_exists(self::$params['code'], Config::read("Error"))) {
			if (isset(self::$params['url'])) {
				Error::loadURL(self::$params['url']);
			} else {
				Error::loadURL(Config::read("Error.".self::$params['code']));
			}
		} else {
			if (isset(self::$params['url'])) {
				Error::loadURL(self::$params['url']);
			} else {
				$code = null;
                if (isset(self::$params['code'])) {
                    $code = self::$params['code'];
                }

                switch ($code) {
                    case 'GEN':
                        include(Config::read("System.defaultErrorGEN"));
                        break;
                    case 'DB':
                        include(Config::read("System.defaultErrorDB"));
                        break;
                    default:
                        include(Config::read("System.defaultErrorGEN"));
                        break;
                }
			}
		}
	}
	
	final public static function getMessage() {
		if (!empty(self::$message)) {
			return self::$message;
		} else {
			return get_class(self::$errorObj) . ' ' . self::$errorObj->getMessage();
		}
	}
	
	final public static function getTrace() {
		return self::$errorObj->getTrace();
	}
    
    final public static function getTriggerParams() {
        return self::$triggerParams;
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
		
		if (!empty($url) && !preg_match("/^(http:|https:|ftp:)/im", $url)) {
			$url = str_replace(Config::read('Path.root'), "", $url);
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
			
			if (($controller = System::load(array("name"=>reset(Config::loadableURI(Config::read("URI.working"))), "type"=>"controller", "branch"=>Config::read("Branch.name")))) === false) {
				include(Config::read("System.defaultError404"));
			} else {
				try {
					$controller->_showView();
				} catch(Exception $e) {
					if (Config::read("System.mode") == "development") {
                        if (isset(self::$params['code'])) {
                            $code = self::$params['code'];
                        }
                        switch ($code) {
                            case 'GEN':
                                include(Config::read("System.defaultErrorGEN"));
                                break;
                            case 'DB':
                                include(Config::read("System.defaultErrorDB"));
                                break;
                            default:
                                include(Config::read("System.defaultErrorGEN"));
                                break;
                        }
					}
				}
			}
			
		} else {
			include(Config::read("System.defaultError404"));
		}
	}
	
	
}
?>