<?php
final class Config {
	## Base Config Holder ##
	protected static $config;
	protected static $isSetup = false;
	
	## Route Config Holder ##
	protected static $routes;
	
	public static function setup() {
		if (!self::$isSetup) {
			// Setup the System.version configuration setting
			self::$config['System']['version'] = "0.3.0";
			
			// Setup the root identifier
			self::$config['System']['rootIdentifier'] = "MAIN";
			
			// Setup the Path.physical configuration setting
			self::$config['Path']['physical'] = dirname(dirname(__FILE__));
			
			// Setup the URI.base configuration setting
			$base_uri = dirname($_SERVER['SCRIPT_NAME']);
			$base_uri = ($base_uri{strlen($base_uri)-1} == '/') ? substr($base_uri, 0, strlen($base_uri)-1) : $base_uri;
			self::$config['URI']['base'] = $base_uri;
			
			// Setup the System.defaultError's configuration setting
			self::$config['System']['defaultError404'] = self::$config['Path']['physical']."/public/errors/404.php";
			self::$config['System']['defaultErrorGEN'] = self::$config['Path']['physical']."/public/errors/general.php";
            self::$config['System']['defaultErrorDB'] = self::$config['Path']['physical']."/public/errors/db.php";
			
			// Setup Configuration defaults
			self::$config['System']['mode'] = "development";
			self::$config['System']['displayPageLoadInfo'] = false;
			self::$config['URI']['prependIdentifier'] = "url";
			self::$config['URI']['useModRewrite'] = true;
			self::$config['URI']['useDashes'] = true;
			self::$config['URI']['forceDashes'] = true;
			self::$config['URI']['map'] = array(
				"controller" 	=> "main",
				"view" 			=> "index",
				"action" 		=> "",
				"id" 			=> ""
			);
			self::$config['Error']['viewErrors'] = true;
    		self::$config['Error']['logErrors'] = true;
			self::$config['Error']['generalErrorMessage'] = "An error occurred. Please contact the administrator.";
			self::$config['Database']['viewQueries'] = false;
		}
		
		// Indicate that the setup function has been run and doesnt need to be run again
		self::$isSetup = true;
		return true;
	}
	
	public static function register($name, $value = "") {
		if (!is_array($name)) {
			$name = array(
				$name => $value
			);
		}
		
		foreach($name as $key => $value) {
			$path = explode('.', $key);
			$config_holder =& self::$config;
			foreach($path as $i => $path_key) {
				if ($i == (count($path) - 1)) {
					$config_holder[$path_key] = $value;
					break;
				} else {
					if (!isset($config_holder[$path_key])) {
						$config_holder[$path_key] = array();
					}
					$config_holder =& $config_holder[$path_key];
				}
			}
		}
		return true;
	}
	
	public static function read($key) {
		$path = explode('.', $key);
		$config_holder =& self::$config;
		foreach($path as $i => $path_key) {
			if ($i == (count($path) - 1)) {
				return (isset($config_holder[$path_key])) ? $config_holder[$path_key] : null;
			} else {
				$config_holder =& $config_holder[$path_key];
			}
		}
		return null;
	}
	
	public static function remove($key) {
		$path = explode('.', $key);
		$config_holder =& self::$config;
		foreach($path as $i => $path_key) {
			if ($i == (count($path) - 1)) {
				if (isset($config_holder[$path_key])) {
					unset($config_holder[$path_key]);
					return true;
				} else {
					return false;
				}
			} else {
				$config_holder =& $config_holder[$path_key];
			}
		}
		return null;
	}
	
	public static function registerRoute($definition, $action, $validation=array()) {
		
		// Check if in a branch and make it so the route loads up data for the branch by default
		if (!isset($action['branch']) && self::read("Branch.name")) {
			$action = array_merge(array('branch' => self::read("Branch.name")), $action);
		}
		
		self::$routes[hash("sha256", $definition)] = array(
			"definition" => $definition,
			"destination" => $action,
			"validation" => $validation
		);
	}
	
	public static function processURI() {
		
		if (!is_array(self::read("URI.map")) || count(self::read("URI.map")) < 2) {
			Error::trigger("NO_URI_MAP");
		}
		
		if (!array_key_exists('controller', self::read("URI.map")) || !array_key_exists('view', self::read("URI.map"))) {
			//Error::trigger("NO_URI_MAP");
		}
		
		if (!self::read("URI.working")) {
			if (self::read("URI.useModRewrite")) {
				if (strpos($_SERVER['REQUEST_URI'], "?")) {
					$_SERVER['REQUEST_URI'] = substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], "?"));
				}
				$_SERVER['REQUEST_URI'] = preg_replace("/^(".str_replace("/", "\/", self::read("URI.base"))."?)/i", "", $_SERVER['REQUEST_URI']);
				
				self::register("URI.prepend", "");
				self::register("URI.working", $_SERVER['REQUEST_URI']);
			} else {
				if (!is_string(self::read("URI.prependIdentifier")) || !strlen(self::read("URI.prependIdentifier"))) {
					Error::trigger("NO_PREPEND_IDENTIFIER");
				}
				
				$queryParts = explode("&", $_SERVER['QUERY_STRING']);
				
				foreach($queryParts as $key => $value) {
					if (preg_match("/" . self::read("URI.prependIdentifier") . "=(.*)/i", $value)) {
						unset($queryParts[$key]);
						break;
					}
				}
				
				$_SERVER['QUERY_STRING'] = implode("&", $queryParts);
				
				self::register("URI.prepend", "/index.php?" . self::read("URI.prependIdentifier") . "=");
				self::register("URI.working", $_GET[self::read("URI.prependIdentifier")]);
			}
		}
		
		if (substr(self::read("URI.working"), 0, 1) == "/") {
			$path_info = substr( self::read("URI.working"), 1, strlen(self::read("URI.working")) );
		} else {
			$path_info = ((is_array(self::read("URI.working"))) ? implode("/", self::read("URI.working")) : self::read("URI.working"));
		}
		
		if (!empty($path_info)) {
			$url_vals = explode('/', $path_info );
		} else {
			$url_vals = array();
		}
		
		if (count($url_vals) > 0 && empty($url_vals[count($url_vals)-1])) {
			unset($url_vals[count($url_vals)-1]);
			if (!is_array(self::read("Route.current"))) {
				if (empty($_POST) && empty($_FILES) && !headers_sent()) {
					header("HTTP/1.1 301 Moved Permanently");
					header("Location: ".self::read("URI.base").self::read("URI.prepend")."/".implode("/", $url_vals) . ((!empty($_SERVER['QUERY_STRING'])) ? ((!self::read("URI.useModRewrite")) ? "&" . $_SERVER['QUERY_STRING'] : "?" . $_SERVER['QUERY_STRING']) : ""));
					header("Connection: close");
					exit;
				}
			}
		}
		
		## Branch Check ##
		$url_vals = self::checkForBranch($url_vals);
		
		## Route Check ##
		if (self::checkRoutes("/".implode("/", $url_vals))) {
			return false;
		}
		
		$uriMap = self::read("URI.map");
		
		if (!empty($url_vals)) {
			foreach($url_vals as $key => $value) {
				if (!empty($value)) {
					if (file_exists(self::read("Path.physical").((strlen(self::read("Branch.name"))) ? "/branches/".self::uriToFile(self::read("Branch.name")) : "")."/controllers/".self::uriToFile($value).".php")) {
						$uri_vals = array(
							"prepend" => array_slice($url_vals, 0, ($key-count($url_vals))),
							"main" => array_slice($url_vals, $key)
						);
						
						if (!empty($uriMap['controller']) && $uriMap['controller'] == $value) {
							if (empty($_POST) && empty($_FILES) && !headers_sent() && !is_array(self::read("Route.current"))) {
								header("HTTP/1.1 301 Moved Permanently");
								header("Location: ".self::read("URI.base") . self::read("URI.prepend") . ((self::read("Branch.name")) ? "/" . self::read("Branch.name") : "") ."/".implode("/", array_merge($uri_vals['prepend'], array_slice($uri_vals['main'], 1))) . ((!empty($_SERVER['QUERY_STRING'])) ? ((!self::read("URI.useModRewrite")) ? "&" . $_SERVER['QUERY_STRING'] : "?" . $_SERVER['QUERY_STRING']) : ""));
								header("Connection: close");
								exit;
							}
						}
						break;
					}
				}
			}
			unset($key, $value);
		}
		
		if (!isset($uri_vals['prepend']) && !isset($uri_vals['main'])) {
			$count = 0;
			foreach($uriMap as $key => $item) {
				if ((!empty($item) && empty($url_vals[$count])) || $key == 'controller') {
					if (is_array($item) && count($item) > 1) {
						$item = reset($item);
					}
					$uri_params[$key] = $item;
				} else if (!empty($url_vals[$count])) {
					if (is_array($item) && count($item) > 1 && function_exists($item[1])) {
						if ($item[1]($url_vals[$count]) == true) {
							$uri_params[$key] = $url_vals[$count];
							$count++;
						} else {
							$uri_params[$key] = (string)$item[0];
						}
					} else {
						$uri_params[$key] = $url_vals[$count];
						$count++;
					}
				} else {
					$uri_params[$key] = null;
				}
			}
		} else {
			$foundController = false;
			$count = 0;
			foreach($uriMap as $key => $item) {
				if ($key == 'controller') {
					$foundController = true;
					$count = 0;
				}
				
				if ($foundController) {
					$uriKey = "main";
				} else {
					$uriKey = "prepend";
				}
				
				if (!empty($item) && empty($uri_vals[$uriKey][$count])) {
					if (is_array($item)) {
						$uri_params[$key] = reset($item);
					} else {
						$uri_params[$key] = $item;
					}
				} else if (!empty($uri_vals[$uriKey][$count])) {
					if (is_array($item) && count($item) > 1 && function_exists($item[1])) {
						if ($item[1]($uri_vals[$uriKey][$count]) == true) {
							$uri_params[$key] = $uri_vals[$uriKey][$count];
							$count++;
						} else {
							$uri_params[$key] = (string)$item[0];
						}
					} else {
						$uri_params[$key] = $uri_vals[$uriKey][$count];
						$count++;
					}
				} else {
					$uri_params[$key] = null;
				}
			}
		}

		unset($key, $item, $count, $foundController, $url_vals, $uriMap, $uriKey);
		
		self::register("URI.working", $uri_params);
		
		// Setup the Param configuration setting
		self::register("Param", $uri_params);
		
		// Clean up the generated working uri variable
		unset($uri_params);
		
		if (self::read("URI.useModRewrite")) {
			$uri_paths = explode("/", ltrim($_SERVER['REQUEST_URI'], '/'));
		} else {
			if (isset($_GET[self::read("URI.prependIdentifier")])) {
				$uri_paths = explode("/", ltrim($_GET[self::read("URI.prependIdentifier")], '/'));
			} else {
				$uri_paths = array();
			}
		}
		
		// Setup the additional Path configuration settings based off the URI.map, the Skin, and the Branch
		self::register("Path.site", self::read("URI.base").self::read("URI.prepend"));
		if (self::read("Branch.name") != "") {
			self::register("Path.branch", str_replace("//", "/", self::read("Path.site")."/".self::read("Branch.name")));
			self::register("Path.branchRoot", str_replace("//", "/", self::read("URI.base")."/branches/".self::read("Branch.name")));
			self::register("Path.branchSkin", str_replace("//", "/", self::read("Path.branchRoot")."/public"));
			self::register("Path.branchPhysical", str_replace("//", "/", self::read("Path.physical")."/branches/".self::read("Branch.name")));
		}
		self::register("Path.root", str_replace("//", "/", self::read("URI.base")));
		self::register("Path.skin", str_replace("//", "/", self::read("Path.root")."/public"));
		
		$count = 0;
		$uriMap = self::read("URI.map");
		$uriWorking = self::read("URI.working");
		foreach($uriWorking as $key => $value) {
			if (empty($value)) {
				continue;
			}
			if (isset($uriMap[$key]) && $uriMap[$key] == $value) {
				unset($uriWorking[$key]);
				$position = $count;
				$count--;
			} else {
				$position = ($count+1);
			}
			self::register("Path.".$key, self::read("URI.base").'/'.trim(implode('/', array_slice($uriWorking, 0, $position)), '/'));
			$count++;
		}
		unset($uriMap);
		
		$current_uri_map = array();
		
		foreach($uri_paths as $item) {
			if (!empty($item)) $current_uri_map[] = $item;
		}
		
		self::register("Path.current", str_replace("//", "/", implode("/", array_merge(array(self::read("Path.site")), $current_uri_map))));
		
		return true;
	}
	
	public static function uriToFile($uriItem) {
		if (self::read('URI.useDashes') == true && self::read('URI.forceDashes') == false) {
			$regex = '/[_-]/';
		} else if (self::read('URI.forceDashes') == true) {
			$regex = '/[-]/';
		} else {
			$regex = '/[_]/';
		}
		return strtolower(preg_replace($regex, '.', $uriItem));
	}
	
	public static function uriToMethod($uriItem) {
		if (self::read('URI.useDashes') == true && self::read('URI.forceDashes') == false) {
			$regex = '/[_-]/';
		} else if (self::read('URI.forceDashes') == true) {
			$regex = '/[-]/';
		} else {
			$regex = '/[_]/';
		}
		
		$uriItem = explode(' ', ucwords(preg_replace($regex, ' ', $uriItem)));
		if (count($uriItem) > 0) {
			$uriItem[0] = strtolower($uriItem[0]);
		}
		return implode('', $uriItem);
	}
	
	public static function uriToClass($uriItem) {
		if (self::read('URI.useDashes') == true && self::read('URI.forceDashes') == false) {
			$regex = '/[_-]/';
		} else if (self::read('URI.forceDashes') == true) {
			$regex = '/[-]/';
		} else {
			$regex = '/[_]/';
		}
		
		$uriItem = explode(' ', ucwords(preg_replace($regex, ' ', $uriItem)));
		return implode('', $uriItem);
	}
	
	public static function methodToFile($methodItem) {
		return strtolower(trim(preg_replace('/[A-Z]/', '.$0', $methodItem), '.'));
	}
	
	public static function classToFile($classItem) {
		return self::methodToFile($classItem);
	}
	
	public static function isBranch($branch_name) {
		return is_dir(self::read("Path.physical")."/branches/".self::uriToFile($branch_name));
	}
	
	public static function checkForBranch($url_vals) {
		if (is_array($url_vals) && !empty($url_vals) && self::isBranch($url_vals[0]) && !file_exists(self::read("Path.physical")."/controllers/".self::uriToFile($url_vals[0]).".php")) {
			self::register("Branch.name", self::uriToMethod($url_vals[0]));
			self::loadBranchConfig(self::read("Branch.name"));
			array_shift($url_vals);
			return $url_vals;
		} else {
			return $url_vals;
		}
	}
	
	public static function loadBranchConfig($branch_name) {
		if (file_exists(self::read("Path.physical")."/branches/".self::uriToFile(self::classToFile($branch_name))."/config/config.php")) {
			// Load the branch configuration
			include(self::read("Path.physical")."/branches/".self::uriToFile(self::classToFile($branch_name))."/config/config.php");
		}
		
		if (file_exists(self::read("Path.physical")."/branches/".self::uriToFile(self::classToFile($branch_name))."/config/errors.php")) {
			// Load the branch errors
			include(self::read("Path.physical")."/branches/".self::uriToFile(self::classToFile($branch_name))."/config/errors.php");
		}
		
		if (self::read("Branch.active") !== null && self::read("Branch.active") == false) {
			// The branch is not active so don't load it
			Error::trigger("BRANCH_INACTIVE");
		}
		
		if (self::read("Branch.requiredSystemMode") !== null && self::read("Branch.requiredSystemMode") != self::read("System.mode")) {
			// The system does not have the required mode so don't load the branch
			Error::trigger("BRANCH_REQUIRED_SYSTEM_MODE");
		}
		
		if (self::read("Branch.minimumSystemVersion") !== null && !version_compare(self::read("System.version"), self::read("Branch.minimumSystemVersion"), ">")) {
			// The system version is lower than the branch's required minimum so don't load the branch
			Error::trigger("BRANCH_MINIMUM_SYSTEM_VERSION");
		}
		
		if (self::read("Branch.maximumSystemVersion") !== null && !version_compare(self::read("System.version"), self::read("Branch.maximumSystemVersion"), "<")) {
			// The system version is higher than the branch's required maximum so don't load the branch
			Error::trigger("BRANCH_MAXIMUM_SYSTEM_VERSION");
		}
	}
	
	/*
		TODO 
		- figure out how to allow null named positions
	*/
	private static function checkRoutes($request_uri) {
		
		if (is_array(self::$routes)) {
			foreach(self::$routes as $route) {
				$generatedRegex = self::createRouteRegex($route['definition']);
				$destination = $route['destination'];
				if (preg_match ($generatedRegex['regex'], $request_uri, $matches)) {
					array_shift($matches);
					$combinedMatches = array_combine(array_pad((array)$generatedRegex['definedPositions'], count($matches), 'wildcard'), array_pad((array)$matches, count($generatedRegex['definedPositions']), null));
					
					foreach($combinedMatches as $key => $match) {
						if (in_array($match, $generatedRegex['definedPositions'])) {
							unset($combinedMatches[$key]);
						}
					}
					
					// Validate named positions
					if (!empty($route['validation'])) {
						foreach($route['validation'] as $name => $regex) {
							//$regex = preg_quote($regex, '/');
							if ($combinedMatches[$name] == NULL && isset($destination[$name])) {
								continue;
							}
							
							if (array_key_exists($name, $combinedMatches) && !preg_match('/^'.$regex.'$/i', $combinedMatches[$name])) {
								return false;
							}
						}
					}
					
					// Check if the route is trying to load from main
					if (isset($destination['branch']) && $destination['branch'] == self::read('System.rootIdentifier')) {
						unset($destination['branch']);
					}
					
					// Check if routing to a branch, unset it from the destination, and load in the branch config
					if (!empty($destination['branch'])) {
						$branch = $destination['branch'];
						unset($destination['branch']);
						
						if (self::isBranch($branch)) {
							self::loadBranchConfig($branch);
						}
					}
					
					// Check if there is a wildcard match in the regex
					$wildcard_matches = null;
					if (!empty($combinedMatches['wildcard'])) {
						$wildcard_matches = explode("/", $combinedMatches['wildcard']);
						unset($combinedMatches['wildcard']);
					}
					
					// Clean up Null's from matches so that defaults aren't overridden
					foreach($combinedMatches as $key => $value) {
						if ($value == NULL) {
							unset($combinedMatches[$key]);
						}
					}
					
					// Build the new URI array that has been defined by the route
					$newURI = array_merge((array)array('branch' => $branch), (array)self::read("URI.map"), (array)$destination, (array)$combinedMatches);
					
					// Loop through the URI and handle empty positions
					foreach($newURI as $key => &$value) {
						if (is_array($value) && count($value) > 1) {
							$value = reset($value);
						}
						if (empty($value) && count($wildcard_matches)) {
							$newURI[$key] = array_shift($wildcard_matches);
						}
					}
					
					// Check if there are remaining wildcard matches that havent filled empty positions and append them to the URI
					if (isset($wildcard_matches) && count($wildcard_matches)) {
						$newURI[] = implode("/", $wildcard_matches);
					}
					
					// Build the final URI that will be used
					$newURI = rtrim("/".implode("/", (array)$newURI), '/');
					
					// Setup the needed configuration settings and re-process the URI
					self::register("Route.current", array_merge( $route, array("newWorkingURI" => $newURI) ));
					self::register("URI.working", $newURI);
					
					self::processURI();
					return true;
				}
			}
		}
		
		return false;
	}
	
	private static function createRouteRegex($regex) {
		$regex = explode('/', $regex);
		$parsed = array();
		$postitions = array();
		
		foreach($regex as $element) {
			if (empty($element)) {
				continue;
			}
			$element = trim($element);
			
			if ($element == '*') {
				$parsed[] = '(?:/(.*))?';
			} else if(preg_match("/(?!\\\\):([a-z_0-9]+)/i", $element, $namedMatches)) {
				$parsed[] = '(?:/([^\/]*))?';
				$positions[] = $namedMatches[1];
			} else {
				$parsed[] = '(?:/('.preg_quote($element, '/').'))';
				$positions[] = $element;
			}
		}
		
		return array('regex' => '#^' . implode('', $parsed) . '[\/]*$#', "definedPositions" => $positions);
	}
}
?>
