<?php
/**
 * Evergreen Exception Class
 *
 * This is the class that extends PHP's default exception class to handle errors for Evergreen.
 * This class sets up the Error class, handles messages, and processing errors such as loading urls.
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
 * Evergreen Exception Class
 *
 * This is the class that extends PHP's default exception class to handle errors for Evergreen.
 * This class sets up the Error class, handles messages, and processing errors such as loading urls.
 *
 * Hooks:
 * Exeception.construct
 * Exception.toString
 * Exception.parsePrintfParameters
 * Exception.dsprintf
 * Exception.dsprintfMatch
 * Exception.processError.before
 * Exception.processError.include
 * Exception.processError.after
 * Exception.clearAllBuffers
 * Exception.loadURL.before
 * Exception.loadURL.redirect
 * Exception.loadURL.controller
 * Exception.loadURL.default
 *
 * @package       evergreen
 * @subpackage    lib
 */
class EvergreenException extends Exception {
	/**
	 * Holds the params passed to the exception.
	 * 
	 * @access protected
	 * @var array
	 */
	protected $params = array();
	
	/**
	 * The constructor for the exception that sets up the exception and the Error class.
	 * 
	 * @access public
	 * @param string $message The error message or the key matching a registered error
	 * @param mixed $params The error params or the error code
	 */
	public function __construct($message, $params = 0) {
		$code = null;
		
		if (!is_array($params) && $params != 0) {
			$params = array(
				'code' => $params
			);
		}
		
		// call hook
		Hook::call('Exeception.construct', array(&$message, &$params));
		
		if ($error = Config::getError($message)) {
			if (isset($error['messageArgs']) && isset($params['messageArgs'])) {
				$params['messageArgs'] = array_merge($error['messageArgs'], $params['messageArgs']);
			}
			$params = array_merge($error, (array)$params);
			$message = $params['message'];
			if (isset($params['code'])) {
				$code = $params['code'];
			}
		}
		
		if (isset($params['messageArgs']) && is_array($params['messageArgs'])) {
			$message = $this->dsprintf($message, $params['messageArgs']);
		}
		$this->params = (array)$params;

		parent::__construct($message, ((is_long($code)) ? $code : null));
		Error::setupError($this);
	}
	
	/**
	 * Returns a string representation of the error.
	 * 
	 * @access public
	 * @return string
	 */
	public function __toString() {
		$return = __CLASS__ . ": [{$this->code}]: {$this->message}\n";
		
		// call hook
		Hook::call('Exception.toString', array(&$return));
		
		return $return;
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
	private function parsePrintfParameters($string) { 
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
		
		// call hook
		Hook::call('Exception.parsePrintfParameters', array(&$string, &$result));
		
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
	private function dsprintf() {
		$data = func_get_args();
		$string = array_shift($data);
		if (is_array(func_get_arg(1))) {
			$data = func_get_arg(1);
		}
		$used_keys = array();

		$string = preg_replace('/\%\((.*?)\)(.)/e', '\$this->dsprintfMatch(\'$1\',\'$2\',\$data,\$used_keys)', $string); 
		$data = array_diff_key($data,$used_keys);
		$countParams = count($this->parsePrintfParameters($string));
		$return = vsprintf($string,array_pad($data, $countParams, 'NULL'));
		
		// call hook
		Hook::call('Exception.dsprintf', array(&$return));
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
	private function dsprintfMatch($m1,$m2,&$data,&$used_keys) {
		if (isset($data[$m1])) {
			$str = $data[$m1];
			$used_keys[$m1] = $m1;
			$return = sprintf("%".$m2,$str);
		} else if (Reg::hasVal($m1)) {
			$used_keys[$m1] = $m1;
			$return = sprintf("%".$m2,Reg::get($m1));
		} else {
			$return = "NULL";
		}
		
		// call hook
		Hook::call('Exception.dsprintfMatch', array(&$m1, &$m2, &$data, &$used_keys, &$return));
		
		return $return;
	}

	/**
	 * Actually processes the triggered error such as setting a header based on the code passed and running loadURL if a url was set.
	 * 
	 * @access public
	 * @static
	 * @final
	 * @param object $errorObj Optional Error object passed when called in side of a catch statement
	 */
	public function processError() {		
		$this->clearAllBuffers();
		
		// call hook
		Hook::call('Exception.processError.before', array(&$this));

		if (isset($this->params['code']) && !headers_sent()) {
			switch((string)$this->code) {
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

		if (isset($this->params['code']) && array_key_exists($this->params['code'], (array)Reg::get("Error"))) {
			if (isset($this->params['url'])) {
				$this->loadURL($this->params['url']);
			} else {
				$this->loadURL(Reg::get("Error.".$this->code));
			}
		} else {
			if (isset($this->params['url'])) {
				$this->loadURL($this->params['url']);
			} else {
				$code = null;
                if (isset($this->params['code'])) {
                    $code = $this->params['code'];
                }

				// call hook
				Hook::call('Exception.processError.include', array(&$code));

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
		
		// call hook
		Hook::call('Exception.processError.after', array(&$this));
	}

	/**
	 * Loops through all current output buffers and clears them.
	 * 
	 * @access public
	 * @static
	 * @final
	 */
	public function clearAllBuffers() {
		// call hook
		Hook::call('Exception.clearAllBuffers');
		
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
	public function loadURL($url) {
		// call hook
		Hook::call('Exception.loadURL.before', array(&$url));
		
		if (!empty($url)) {
			if (!is_array($url) && preg_match("/^(http:|https:|ftp:|ftps:)/im", $url)) {
				// call hook
				Hook::call('Exception.loadURL.redirect', array(&$url));
				
				header('Location: '.$url);
				header('Connection: close');
				exit;
			}

			if (is_array($url)) {
				$url = '/'.implode('/', array_merge(Reg::get("URI.map"), $url));
			}

			$url = str_replace(Reg::get('Path.root'), "", $url);
			$_SERVER['REQUEST_URI'] = $url;
			Reg::set("URI.working", $url);
			Reg::del("Branch.name");
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
					// call hook
					Hook::call('Exception.loadURL.controller', array(&$controller));
					
					$controller->_showView();
				} catch(EvergreenException $e) {
					var_dump($e); exit;
					if (Reg::get("System.mode") == "development") {
                        if (isset(self::$params['code'])) {
                            $code = self::$params['code'];
                        }
						
						// call hook
						Hook::call('Exception.loadURL.default', array(&$url, &$code));
						
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
	
	final public function getParams() {
		return $this->params;
	}
}
?>