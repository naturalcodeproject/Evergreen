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
			if (isset(self::$registeredErrors[$message]['messageArgs']) && isset($params['messageArgs'])) {
				$params['messageArgs'] = array_merge(self::$registeredErrors[$message]['messageArgs'], $params['messageArgs']);
			}
			$params = array_merge(self::$registeredErrors[$message], $params);
			$message = $params['message'];
		}
		
		if (isset($params['messageArgs']) && is_array($params['messageArgs'])) {
			//$countParams = count(self::parsePrintfParameters($message));
			$message = self::dsprintf($message, $params['messageArgs']);
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
	
	final private static function parsePrintfParameters($string) { 
	    $valid = '/^(?:%%|%(?:[0-9]+\$)?[+-]?(?:[ 0]|\'.)?-?[0-9]*(?:\.[0-9]+)?[bcdeufFosxX])/'; 
	    $originalString = $string; 
		
	    $result = array(); 
	    while(strlen($string)) { 
	      if(!$string = preg_replace('/^[^%]*/', '', $string)) 
	        break;
	       
	      if(preg_match($valid, $string, $matches)) { 
	      	$result[] = $matches[0]; 
	      	$string = substr($string, strlen($matches[0])); 
	      } else { 
	      	error(sprintf('"%s" has an error near "%s".', $originalString, $string)); 
	      	return NULL; 
	      } 
	    } 
	    return $result; 
	}
	
	final private static function dsprintf() {
		$data = func_get_args();
		$string = array_shift($data);
		if (is_array(func_get_arg(1))) {
			$data = func_get_arg(1);
		}
		$used_keys = array();
		
		$string = preg_replace('/\%\((.*?)\)(.)/e', 'self::dsprintfMatch(\'$1\',\'$2\',\$data,\$used_keys)', $string); 
		$data = array_diff_key($data,$used_keys);
		$countParams = count(self::parsePrintfParameters($string));
		return vsprintf($string,array_pad($data, $countParams, 'NULL'));
	}
	
	final private static function dsprintfMatch($m1,$m2,&$data,&$used_keys) {
		if (isset($data[$m1])) {
			$str = $data[$m1];
			$used_keys[$m1] = $m1;
			return sprintf("%".$m2,$str);
		} else if (Config::read($m1) != null) {
			$used_keys[$m1] = $m1;
			return sprintf("%".$m2,Config::read($m1));
		} else {
			return "NULL";
		}
	}
	
	final public static function processError($errorObj = null) {
		self::clearAllBuffers();
		if ($errorObj != null) {
			self::$errorObj = $errorObj;
		}
		
		if (isset(self::$params['code']) && !headers_sent()) {
			switch((string)self::$params['code']) {
				case "301":
					header("HTTP/1.1 301 Moved Permanently");
				break;
				case "304":
					header("HTTP/1.1 304 Not Modified");
				break;
				case "307":
					header("HTTP/1.1 307 Temporary Redirect");
				break;
				case "400":
					header("HTTP/1.1 400 Bad Request");
				break;
				case "401":
					header("HTTP/1.1 401 Unauthorized");
				break;
				case "403":
					header("HTTP/1.1 403 Forbidden");
				break;
				case "404":
					header("HTTP/1.1 404 Not Found");
				break;
				case "500":
					header("HTTP/1.1 500 Internal Server Error");
				break;
			}
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
		} else if (!empty(self::$errorObj)) {
			return get_class(self::$errorObj) . ' ' . self::$errorObj->getMessage();
		} else {
			return null;
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
		if (!empty($url)) {
			if (!is_array($url) && preg_match("/^(http:|https:|ftp:|ftps:)/im", $url)) {
				header('Location: '.$url);
				header('Connection: close');
				exit;
			}
			
			if (is_array($url)) {
				$url = '/'.implode('/', array_merge(Config::read("URI.map"), $url));
			}
			
			$url = str_replace(Config::read('Path.root'), "", $url);
			Config::register("URI.working", $url);
			Config::remove("Branch.name");
			Config::processURI();
			
			$load['name'] = Config::uriToClass(Config::read("URI.working.controller"));
			if (Config::read("Branch.name") != '') {
				$load['branch'] = Config::uriToClass(Config::read("Branch.name"));
			}
			$load['type'] = 'Controller';
			$load = implode('_', $load);
			
			$controller = new $load();
			if (!is_object($controller)) {
				if (!file_exists(Config::read("System.defaultError404"))) {
					include(Config::read("System.defaultError404"));
				} else {
					echo Config::read("System.defaultError404");
				}
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
                            	if (file_exists(Config::read("System.defaultErrorGEN"))) {
	                                include(Config::read("System.defaultErrorGEN"));
	                            } else {
	                            	echo Config::read("System.defaultErrorGEN");
	                            }
                                break;
                            case 'DB':
                            	if (file_exists(Config::read("System.defaultErrorDB"))) {
	                                include(Config::read("System.defaultErrorDB"));
	                            } else {
	                            	echo Config::read("System.defaultErrorDB");
	                            }
                                break;
                            default:
                            	if (file_exists(Config::read("System.defaultErrorGEN"))) {
	                                include(Config::read("System.defaultErrorGEN"));
	                            } else {
	                            	echo Config::read("System.defaultErrorGEN");
	                            }
                                break;
                        }
					}
				}
			}
			
		} else {
			if (file_exists(Config::read("System.defaultErrorGEN"))) {
				include(Config::read("System.defaultErrorGEN"));
			} else {
				echo Config::read("System.defaultErrorGEN");
			}
		}
	}
	
	public static function logError($errno, $errstr, $errfile, $errline, $errcontext) {
		$type = '';
   		$display = false;
   		$notify = false;
   		$halt_script = true;
        
        if (Config::read('Error.viewErrors')) {
            $display = true;
        }
        
        if (Config::read('Error.logErrors')) {
            $notify = true;
        }
   		
		switch($errno) {
   			case E_USER_NOTICE:
   				$notify = true;
   			case E_NOTICE:
   				$halt_script = false;        
       			$type = "Notice";
       			break;
   			case E_USER_WARNING:
   			case E_COMPILE_WARNING:
   			case E_CORE_WARNING:
   			case E_WARNING:
      			$halt_script = false;       
       			$type = "Warning";
       			break;
   			case E_USER_ERROR:
       		case E_COMPILE_ERROR:
   			case E_CORE_ERROR:
   			case E_ERROR:
       			$type = "Fatal Error";
       			$display = true;
       			$notify = true;
       			break;
   			case E_PARSE:
       			$type = "Parse Error";
       			$display = true;
       			$notify = true;
       			break;
   			default:
      			$type = "Unknown Error";
      			$display = true;
      			$notify = true;
       			break;
		}
        
        $error_msg = '['.date('d-M-Y H:i:s').'] ';
        $error_msg .= "$type: ";
        $error_msg .= "\"$errstr\" occurred in $errfile on line $errline\n";
        
        if($display) echo '<PRE>' . $error_msg . '</PRE>';

		if($notify) {
            $logDir = Config::read("Error.logDirectory");
            if (empty($logDir)) {
                error_log($error_msg, $errno);
            } else {
                $log_file = Config::read("Path.physical")."/".$logDir."/";
                
                $year = date('Y');
                $month = date('m');
                $day = date('d');
                
                $log_file .= $year;
                mkdir($log_file);
                $log_file .= "/$month";
                mkdir($log_file);
                $log_file .= "/$day";
                mkdir($log_file);
                
                $log_file .= "/error.log";
                
                if(empty($log_file)) {
                    error_log($error_msg, 0);
                } else {
                    error_log($error_msg, 3, $log_file);
                }
            }
   		}
   
   		if($halt_script) exit -1;
	}
}
?>