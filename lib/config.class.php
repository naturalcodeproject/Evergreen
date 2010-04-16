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
			Reg::set('System.version', "0.3.0");
			
			// Setup the root identifier
			Reg::set('System.rootIdentifier', "MAIN");
			
			// Setup the Path.physical configuration setting
			Reg::set('Path.physical', dirname(dirname(__FILE__)));
			
			// Setup the URI.base configuration setting
			$base_uri = dirname($_SERVER['SCRIPT_NAME']);
			$base_uri = ($base_uri{strlen($base_uri)-1} == '/') ? substr($base_uri, 0, strlen($base_uri)-1) : $base_uri;
			Reg::set('URI.base', $base_uri);
			
			// Setup the System.defaultError's configuration setting
			Reg::set('System.defaultError404', Reg::get('Path.physical')."/public/errors/404.php");
			Reg::set('System.defaultErrorGEN', Reg::get('Path.physical')."/public/errors/general.php");
            Reg::set('System.defaultErrorDB', Reg::get('Path.physical')."/public/errors/db.php");
			
			// Setup Configuration defaults
			Reg::set('System.mode', "development");
			Reg::set('System.displayPageLoadInfo', false);
			Reg::set('URI.prependIdentifier', "url");
			Reg::set('URI.useModRewrite', true);
			Reg::set('URI.useDashes', true);
			Reg::set('URI.forceDashes', true);
			Reg::set('URI.map', array(
				"controller" 	=> "main",
				"view" 			=> "index",
				"action" 		=> "",
				"id" 			=> ""
			));
			Reg::set('Error.viewErrors', true);
    		Reg::set('Error.logErrors', true);
			Reg::set('Error.generalErrorMessage', "An error occurred. Please contact the administrator.");
			Reg::set('Database.viewQueries', false);
		}
		
		// Indicate that the setup function has been run and doesnt need to be run again
		self::$isSetup = true;
		return true;
	}
	
	public static function register($key, $value = null) {
		return Reg::set($key, $value);
	}
	
	public static function read($key) {
		return Reg::get($key);
	}
	
	public static function remove($key) {
		return Reg::del($key);
	}
	
	public static function registerRoute($definition, $action, $validation=array()) {
		
		// Check if in a branch and make it so the route loads up data for the branch by default
		if (!isset($action['branch']) && Reg::get("Branch.name")) {
			$action = array_merge(array('branch' => Reg::get("Branch.name")), $action);
		}
		
		self::$routes[hash("sha256", $definition)] = array(
			"definition" => $definition,
			"destination" => $action,
			"validation" => $validation
		);
	}
	
	public static function processURI() {
		
		if (!is_array(Reg::get("URI.map")) || count(Reg::get("URI.map")) < 2) {
			Error::trigger("NO_URI_MAP");
		}
		
		if (!array_key_exists('controller', Reg::get("URI.map")) || !array_key_exists('view', Reg::get("URI.map"))) {
			//Error::trigger("NO_URI_MAP");
		}
		
		if (!Reg::get("URI.working")) {
			if (Reg::get("URI.useModRewrite")) {
				if (strpos($_SERVER['REQUEST_URI'], "?")) {
					$_SERVER['REQUEST_URI'] = substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], "?"));
				}
				$_SERVER['REQUEST_URI'] = preg_replace("/^(".str_replace("/", "\/", Reg::get("URI.base"))."?)/i", "", $_SERVER['REQUEST_URI']);
				
				Reg::set("URI.prepend", "");
				Reg::set("URI.working", $_SERVER['REQUEST_URI']);
			} else {
				if (!is_string(Reg::get("URI.prependIdentifier")) || !strlen(Reg::get("URI.prependIdentifier"))) {
					Error::trigger("NO_PREPEND_IDENTIFIER");
				}
				
				$queryParts = explode("&", $_SERVER['QUERY_STRING']);
				
				foreach($queryParts as $key => $value) {
					if (preg_match("/" . Reg::get("URI.prependIdentifier") . "=(.*)/i", $value)) {
						unset($queryParts[$key]);
						break;
					}
				}
				
				$_SERVER['QUERY_STRING'] = implode("&", $queryParts);
				
				Reg::set("URI.prepend", "/index.php?" . Reg::get("URI.prependIdentifier") . "=");
				Reg::set("URI.working", $_GET[Reg::get("URI.prependIdentifier")]);
			}
		}
		
		if (substr(Reg::get("URI.working"), 0, 1) == "/") {
			$path_info = substr( Reg::get("URI.working"), 1, strlen(Reg::get("URI.working")) );
		} else {
			$path_info = ((is_array(Reg::get("URI.working"))) ? implode("/", Reg::get("URI.working")) : Reg::get("URI.working"));
		}
		
		if (!empty($path_info)) {
			$url_vals = explode('/', $path_info );
		} else {
			$url_vals = array();
		}
		
		if (count($url_vals) > 0 && empty($url_vals[count($url_vals)-1])) {
			unset($url_vals[count($url_vals)-1]);
			if (!is_array(Reg::get("Route.current"))) {
				if (empty($_POST) && empty($_FILES) && !headers_sent()) {
					header("HTTP/1.1 301 Moved Permanently");
					header("Location: ".Reg::get("URI.base").Reg::get("URI.prepend")."/".implode("/", $url_vals) . ((!empty($_SERVER['QUERY_STRING'])) ? ((!Reg::get("URI.useModRewrite")) ? "&" . $_SERVER['QUERY_STRING'] : "?" . $_SERVER['QUERY_STRING']) : ""));
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
		
		$uriMap = Reg::get("URI.map");
		
		if (!empty($url_vals)) {
			foreach($url_vals as $key => $value) {
				if (!empty($value)) {
					if (file_exists(Reg::get("Path.physical").((strlen(Reg::get("Branch.name"))) ? "/branches/".self::uriToFile(Reg::get("Branch.name")) : "")."/controllers/".self::uriToFile($value).".php")) {
						$uri_vals = array(
							"prepend" => array_slice($url_vals, 0, ($key-count($url_vals))),
							"main" => array_slice($url_vals, $key)
						);
						
						if (!empty($uriMap['controller']) && $uriMap['controller'] == $value) {
							if (empty($_POST) && empty($_FILES) && !headers_sent() && !is_array(Reg::get("Route.current"))) {
								header("HTTP/1.1 301 Moved Permanently");
								header("Location: ".Reg::get("URI.base") . Reg::get("URI.prepend") . ((Reg::get("Branch.name")) ? "/" . Reg::get("Branch.name") : "") ."/".implode("/", array_merge($uri_vals['prepend'], array_slice($uri_vals['main'], 1))) . ((!empty($_SERVER['QUERY_STRING'])) ? ((!Reg::get("URI.useModRewrite")) ? "&" . $_SERVER['QUERY_STRING'] : "?" . $_SERVER['QUERY_STRING']) : ""));
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
		
		Reg::set("URI.working", $uri_params);
		
		// Setup the Param configuration setting
		Reg::set("Param", $uri_params);
		
		// Clean up the generated working uri variable
		unset($uri_params);
		
		if (Reg::get("URI.useModRewrite")) {
			$uri_paths = explode("/", ltrim($_SERVER['REQUEST_URI'], '/'));
		} else {
			if (isset($_GET[Reg::get("URI.prependIdentifier")])) {
				$uri_paths = explode("/", ltrim($_GET[Reg::get("URI.prependIdentifier")], '/'));
			} else {
				$uri_paths = array();
			}
		}
		
		// Setup the additional Path configuration settings based off the URI.map, the Skin, and the Branch
		Reg::set("Path.site", Reg::get("URI.base").Reg::get("URI.prepend"));
		if (Reg::get("Branch.name") != "") {
			Reg::set("Path.branch", str_replace("//", "/", Reg::get("Path.site")."/".Reg::get("Branch.name")));
			Reg::set("Path.branchRoot", str_replace("//", "/", Reg::get("URI.base")."/branches/".Reg::get("Branch.name")));
			Reg::set("Path.branchSkin", str_replace("//", "/", Reg::get("Path.branchRoot")."/public"));
			Reg::set("Path.branchPhysical", str_replace("//", "/", Reg::get("Path.physical")."/branches/".Reg::get("Branch.name")));
		}
		Reg::set("Path.root", str_replace("//", "/", Reg::get("URI.base")));
		Reg::set("Path.skin", str_replace("//", "/", Reg::get("Path.root")."/public"));
		
		$count = 0;
		$uriMap = Reg::get("URI.map");
		$uriWorking = Reg::get("URI.working");
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
			Reg::set("Path.".$key, Reg::get("URI.base").'/'.trim(implode('/', array_slice($uriWorking, 0, $position)), '/'));
			$count++;
		}
		unset($uriMap);
		
		$current_uri_map = array();
		
		foreach($uri_paths as $item) {
			if (!empty($item)) $current_uri_map[] = $item;
		}
		
		Reg::set("Path.current", str_replace("//", "/", implode("/", array_merge(array(Reg::get("Path.site")), $current_uri_map))));
		
		return true;
	}
	
	public static function uriToFile($uriItem) {
		if (Reg::get('URI.useDashes') == true && Reg::get('URI.forceDashes') == false) {
			$regex = '/[_-]/';
		} else if (Reg::get('URI.forceDashes') == true) {
			$regex = '/[-]/';
		} else {
			$regex = '/[_]/';
		}
		return strtolower(preg_replace($regex, '.', $uriItem));
	}
	
	public static function uriToMethod($uriItem) {
		if (Reg::get('URI.useDashes') == true && Reg::get('URI.forceDashes') == false) {
			$regex = '/[_-]/';
		} else if (Reg::get('URI.forceDashes') == true) {
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
		if (Reg::get('URI.useDashes') == true && Reg::get('URI.forceDashes') == false) {
			$regex = '/[_-]/';
		} else if (Reg::get('URI.forceDashes') == true) {
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
		return is_dir(Reg::get("Path.physical")."/branches/".self::uriToFile($branch_name));
	}
	
	public static function checkForBranch($url_vals) {
		if (is_array($url_vals) && !empty($url_vals) && self::isBranch($url_vals[0]) && !file_exists(Reg::get("Path.physical")."/controllers/".self::uriToFile($url_vals[0]).".php")) {
			Reg::set("Branch.name", self::uriToMethod($url_vals[0]));
			self::loadBranchConfig(Reg::get("Branch.name"));
			array_shift($url_vals);
			return $url_vals;
		} else {
			return $url_vals;
		}
	}
	
	public static function loadBranchConfig($branch_name) {
		if (file_exists(Reg::get("Path.physical")."/branches/".self::uriToFile(self::classToFile($branch_name))."/config/config.php")) {
			// Load the branch configuration
			include(Reg::get("Path.physical")."/branches/".self::uriToFile(self::classToFile($branch_name))."/config/config.php");
		}
		
		if (file_exists(Reg::get("Path.physical")."/branches/".self::uriToFile(self::classToFile($branch_name))."/config/errors.php")) {
			// Load the branch errors
			include(Reg::get("Path.physical")."/branches/".self::uriToFile(self::classToFile($branch_name))."/config/errors.php");
		}
		
		if (Reg::get("Branch.active") !== null && Reg::get("Branch.active") == false) {
			// The branch is not active so don't load it
			Error::trigger("BRANCH_INACTIVE");
		}
		
		if (Reg::get("Branch.requiredSystemMode") !== null && Reg::get("Branch.requiredSystemMode") != Reg::get("System.mode")) {
			// The system does not have the required mode so don't load the branch
			Error::trigger("BRANCH_REQUIRED_SYSTEM_MODE");
		}
		
		if (Reg::get("Branch.minimumSystemVersion") !== null && !version_compare(Reg::get("System.version"), Reg::get("Branch.minimumSystemVersion"), ">=")) {
			// The system version is lower than the branch's required minimum so don't load the branch
			Error::trigger("BRANCH_MINIMUM_SYSTEM_VERSION");
		}
		
		if (Reg::get("Branch.maximumSystemVersion") !== null && !version_compare(Reg::get("System.version"), Reg::get("Branch.maximumSystemVersion"), "<=")) {
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
					if (isset($destination['branch']) && $destination['branch'] == Reg::get('System.rootIdentifier')) {
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
					$newURI = array_merge((array)array('branch' => $branch), (array)Reg::get("URI.map"), (array)$destination, (array)$combinedMatches);
					
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
					Reg::set("Route.current", array_merge( $route, array("newWorkingURI" => $newURI) ));
					Reg::set("URI.working", $newURI);
					
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

final class Reg {
	protected static $config;
	
	public static function set($name, $value = null) {
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
	
	public static function get($key) {
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
	
	public static function has($key) {
		$path = explode('.', $key);
		$config_holder =& self::$config;
		foreach($path as $i => $path_key) {
			if ($i == (count($path) - 1)) {
				return isset($config_holder[$path_key]);
			} else {
				$config_holder =& $config_holder[$path_key];
			}
		}
		return null;
	}
	
	public static function hasVal($key) {
		$path = explode('.', $key);
		$config_holder =& self::$config;
		foreach($path as $i => $path_key) {
			if ($i == (count($path) - 1)) {
				return empty($config_holder[$path_key]);
			} else {
				$config_holder =& $config_holder[$path_key];
			}
		}
		return null;
	}
	
	public static function del($key) {
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

}
?>
