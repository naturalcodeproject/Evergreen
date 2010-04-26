<?php
/**
 * Error Class
 *
 * This class is a helper for throwing errors and handles the ability to trigger errors
 * that have been registered as well as having messages changed based on sprintf type
 * functionality as well as routing to a page or loading a page in the framework without
 * changing the url if a url is defined as part of the error.
 *
 *
 * Copyright 2007-2010, NaturalCodeProject (http://www.naturalcodeproject.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright		Copyright 2007-2010, NaturalCodeProject (http://www.naturalcodeproject.com)
 * @package			evergreen
 * @subpackage		lib
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * Error Class
 *
 * This class is a helper for throwing errors and handles the ability to trigger errors
 * that have been registered as well as having messages changed based on sprintf type
 * functionality as well as routing to a page or loading a page in the framework without
 * changing the url if a url is defined as part of the error.
 *
 * @package       evergreen
 * @subpackage    lib
 */
final class Error {
	/**
	 * Holder variable for the registered errors.
	 * 
	 * @access private
	 * @static
	 * @var array
	 */
	static private $registeredErrors = array();
	
	/**
	 * Indicator used to tell if an error has been triggered.
	 * 
	 * @access private
	 * @static
	 * @var boolean
	 */
	static private $triggered = false;
	
	/**
	 * Current triggered error's key.
	 * 
	 * @access private
	 * @static
	 * @var string
	 */
	static private $key;
	
	/**
	 * Current triggered error's message.
	 * 
	 * @access private
	 * @static
	 * @var string
	 */
	static private $message;
	
	/**
	 * Current triggered error's params.
	 * 
	 * @access private
	 * @static
	 * @var array
	 */
	static private $params;
	
	/**
	 * Current triggered error's error object.
	 * 
	 * @access private
	 * @static
	 * @var object
	 */
	static private $errorObj;
	
	/**
	 * Params passed into trigger function, can be used in addition to registered error params.
	 * 
	 * @access private
	 * @static
	 * @var array
	 */
    static private $triggerParams;
	
	/**
	 * Used to register a predefined error.
	 * 
	 * @access public
	 * @static
	 * @final
	 * @param string $key Key used to store and trigger the registered error
	 * @param array $params The parameters used to make up the error such as message, url, and code
	 */
	final public static function register($key, $params) {
		if (!is_array($params)) {
			$params = array("message"=>$params);
		}
		
		self::$registeredErrors[$key] = $params;
	}
	
	/**
	 * Triggers a error either predefined or not.
	 * 
	 * @access public
	 * @static
	 * @final
	 * @param string $message The error message or the key of the predefined error
	 * @param array $params The params that make up the error or params that merge with the predefined error's params
	 */
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
        
        self::$triggered = true;
        
        trigger_error($errMsg);
		
		throw new Exception(self::$message);
	}
	
	/**
	 * Returns an array of all the sprintf parameters in a string.
	 * 
	 * @access private
	 * @static
	 * @final
	 * @param string $string The string with sprintf style parameters
	 * @return array
	 */
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
	
	/**
	 * Returns a string with sprintf style parameters parsed into the string as well as parameters based on array keys in the format: %(key)s.
	 * 
	 * @access private
	 * @static
	 * @final
	 * @param string The string that needs to be parsed
	 * @param array The arguments that need to be parsed into the string
	 * @return string
	 */
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
	
	/**
	 * Callback function for dsprintf's preg_replace. It takes the returned key and the data and returns the matching data for the key
	 * and if it cant find anything it returns NULL
	 * 
	 * @access private
	 * @static
	 * @final
	 * @param string $m1 The found key
	 * @param string $m2 The sprintf type operator
	 * @param array &$data The data that was passed to the dsprintf function
	 * @param array &$used_keys The keys from $data that have already been found and used
	 * @return string
	 */
	final private static function dsprintfMatch($m1,$m2,&$data,&$used_keys) {
		if (isset($data[$m1])) {
			$str = $data[$m1];
			$used_keys[$m1] = $m1;
			return sprintf("%".$m2,$str);
		} else if (Reg::hasVal($m1)) {
			$used_keys[$m1] = $m1;
			return sprintf("%".$m2,Reg::get($m1));
		} else {
			return "NULL";
		}
	}
	
	/**
	 * Actually processes the triggered error such as setting a header based on the code passed and running loadURL if a url was set.
	 * 
	 * @access public
	 * @static
	 * @final
	 * @param object $errorObj Optional Error object passed when called in side of a catch statement
	 */
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
		
		if (isset(self::$params['code']) && array_key_exists(self::$params['code'], Reg::get("Error"))) {
			if (isset(self::$params['url'])) {
				Error::loadURL(self::$params['url']);
			} else {
				Error::loadURL(Reg::get("Error.".self::$params['code']));
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
                        include(Reg::get("System.defaultErrorGEN"));
                        break;
                    case 'DB':
                        include(Reg::get("System.defaultErrorDB"));
                        break;
                    default:
                        include(Reg::get("System.defaultErrorGEN"));
                        break;
                }
			}
		}
	}
	
	/**
	 * Returns if an error has been triggered or not.
	 * 
	 * @access public
	 * @static
	 * @final
	 * @return boolean true if an error has been triggered and boolean false if not
	 */
	final public static function triggered() {
		return self::$triggered;
	}
	
	/**
	 * Returns the current error message and null if there is no message.
	 * 
	 * @access public
	 * @static
	 * @final
	 * @return string
	 */
	final public static function getMessage() {
		if (!empty(self::$message)) {
			return self::$message;
		} else if (!empty(self::$errorObj)) {
			return get_class(self::$errorObj) . ' ' . self::$errorObj->getMessage();
		} else {
			return null;
		}
	}
	
	/**
	 * Returns the error trace from the current error object.
	 * 
	 * @access public
	 * @static
	 * @final
	 * @return array
	 */
	final public static function getTrace() {
		return self::$errorObj->getTrace();
	}
    
	/**
	 * Returns the triggered params from the currently triggered error.
	 * 
	 * @access public
	 * @static
	 * @final
	 * @return array
	 */
    final public static function getTriggerParams() {
        return self::$triggerParams;
    }
	
	/**
	 * Loops through all current output buffers and clears them.
	 * 
	 * @access public
	 * @static
	 * @final
	 */
	final public static function clearAllBuffers() {
		$buffer_count = ob_get_level();
		for($i = 1; $i <= $buffer_count; $i++) {
			ob_end_clean();
		}
	}
	
	/**
	 * Loads the url for the error. If the url points to a page inside the framework the function attempts to load it and handles any errors
	 * or issues associated such as loading in a default error. If the url points to a page outside the framework then header location is set
	 * and execution is stopped.
	 * 
	 * @access public
	 * @static
	 * @final
	 * @param string|array $url The url that should be loaded can be the url or an array in the URI.map format
	 */
	final public static function loadURL($url) {
		if (!empty($url)) {
			if (!is_array($url) && preg_match("/^(http:|https:|ftp:|ftps:)/im", $url)) {
				header('Location: '.$url);
				header('Connection: close');
				exit;
			}
			
			if (is_array($url)) {
				$url = '/'.implode('/', array_merge(Reg::get("URI.map"), $url));
			}
			
			$url = str_replace(Reg::get('Path.root'), "", $url);
			Reg::set("URI.working", $url);
			Config::remove("Branch.name");
			Config::processURI();
			
			$load['name'] = Config::uriToClass(Reg::get("URI.working.controller"));
			if (Reg::hasVal("Branch.name")) {
				$load['branch'] = Config::uriToClass(Reg::get("Branch.name"));
			}
			$load['type'] = 'Controller';
			$load = implode('_', $load);
			
			$controller = new $load();
			if (!is_object($controller)) {
				if (!file_exists(Reg::get("System.defaultError404"))) {
					include(Reg::get("System.defaultError404"));
				} else {
					echo Reg::get("System.defaultError404");
				}
			} else {
				try {
					$controller->_showView();
				} catch(Exception $e) {
					if (Reg::get("System.mode") == "development") {
                        if (isset(self::$params['code'])) {
                            $code = self::$params['code'];
                        }
                        switch ($code) {
                            case 'GEN':
                            	if (file_exists(Reg::get("System.defaultErrorGEN"))) {
	                                include(Reg::get("System.defaultErrorGEN"));
	                            } else {
	                            	echo Reg::get("System.defaultErrorGEN");
	                            }
                                break;
                            case 'DB':
                            	if (file_exists(Reg::get("System.defaultErrorDB"))) {
	                                include(Reg::get("System.defaultErrorDB"));
	                            } else {
	                            	echo Reg::get("System.defaultErrorDB");
	                            }
                                break;
                            default:
                            	if (file_exists(Reg::get("System.defaultErrorGEN"))) {
	                                include(Reg::get("System.defaultErrorGEN"));
	                            } else {
	                            	echo Reg::get("System.defaultErrorGEN");
	                            }
                                break;
                        }
					}
				}
			}
			
		} else {
			if (file_exists(Reg::get("System.defaultErrorGEN"))) {
				include(Reg::get("System.defaultErrorGEN"));
			} else {
				echo Reg::get("System.defaultErrorGEN");
			}
		}
	}
	
	/**
	 * Handles the output and logging of errors.
	 * 
	 * @access public
	 * @static
	 * @param integer $errno The level of the error raised
	 * @param string $errstr The error message
	 * @param string $errfile The name of the file the error was raised in
	 * @param integer $errline The line number the error was raised at
	 * @param array $errcontext An array containing every variable that existed in the scope the error was triggered
	 */
	public static function logError($errno, $errstr, $errfile, $errline, $errcontext) {
		$type = '';
   		$display = false;
   		$notify = false;
   		$halt_script = true;
        
        if (Reg::get('Error.viewErrors') == true) {
            $display = true;
        }
        
        if (Reg::get('Error.logErrors') == true) {
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
            $logDir = Reg::get("Error.logDirectory");
            if (empty($logDir)) {
                error_log($error_msg, $errno);
            } else {
                $log_file = Reg::get("Path.physical")."/".$logDir."/";
                
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