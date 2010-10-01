<?php
/**
 * Configuration Class
 *
 * Class that sets up all the default registration variables,
 * handles and registers routes and processes the uri so that the framework
 * knows what controllers and views need to be loaded.
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
 * Configuration Class
 *
 * Class that sets up all the default registration variables,
 * handles and registers routes and processes the uri so that the framework
 * knows what controllers and views need to be loaded.
 *
 * @package       evergreen
 * @subpackage    lib
 */
final class Config {
	/**
	 * Holder variable for routes.
	 * 
	 * @access protected
	 * @static
	 * @var array
	 */
	protected static $routes = array();
	
	protected static $errors = array();
	
	/**
	 * Method used to setup the config class and the framework with defaults.
	 * 
	 * @access public
	 * @static
	 * @return boolean true
	 */
	public static function setup() {
		// setup the Path.physical configuration setting
		Reg::set('Path.physical', dirname(dirname(__FILE__)));
		
		// load the main hooks.php file. Do this near the beginning so that hooks can be just about anywhere
		if (file_exists(Reg::get("Path.physical").'/config/hooks.php')) {
			include(Reg::get("Path.physical").'/config/hooks.php');
		}
		
		// setup the System.version configuration setting
		Reg::set('System.version', "1.0b1");
		
		// setup the root identifier
		Reg::set('System.rootIdentifier', "MAIN");
		
		// setup the URI.base configuration setting
		$base_uri = dirname($_SERVER['SCRIPT_NAME']);
		$base_uri = ($base_uri{strlen($base_uri)-1} == DIRECTORY_SEPARATOR) ? substr($base_uri, 0, strlen($base_uri)-1) : $base_uri;
		Reg::set('URI.base', $base_uri);
		
		// setup the System.defaultError's configuration setting
		Reg::set('System.defaultError404', Reg::get('Path.physical')."/public/errors/404.php");
		Reg::set('System.defaultErrorGEN', Reg::get('Path.physical')."/public/errors/general.php");
        Reg::set('System.defaultErrorDB', Reg::get('Path.physical')."/public/errors/db.php");
		
		// setup configuration defaults
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
		Reg::set('Database.storeQueries', false);
		Reg::set('Database.autoExtract', false);
		
		// call hook
		Hook::call('Config.setup');
		
		// return true for good measure
		return true;
	}
	
	/**
	 * Legacy method used to register a global registry variable.
	 * 
	 * @access public
	 * @static
	 * @param string $key The definition of the registration variable
	 * @param mixed $value The value of the variable being registered
	 * @return boolean true if successful and boolean false if not
	 */
	public static function register($key, $value = null) {
		return Reg::set($key, $value);
	}
	
	/**
	 * Legacy method used to read a global registry variable.
	 * 
	 * @access public
	 * @static
	 * @param string $key The registration variable that is being accessed
	 * @return mixed
	 */
	public static function read($key) {
		return Reg::get($key);
	}
	
	/**
	 * Legacy method used to remove a global registry variable.
	 * 
	 * @access public
	 * @static
	 * @param string $key The registration variable that is being accessed
	 * @return boolean true if successful and boolean false if not
	 */
	public static function remove($key) {
		return Reg::del($key);
	}
	
	/**
	 * Used to register a route.
	 * 
	 * @access public
	 * @static
	 * @param string $definition The string that defines the url match for the route
	 * @param array $action The array containing the redefined uri map values for the route
	 * @param array $validation Array used to validate named properties found by the definition
	 */
	public static function registerRoute($definition, $action, $validation=array()) {
		// call hook
		Hook::call('Config.registerRoute', array(&$definition, &$action, &$validation));
		
		// add the current branch name to the route definition if it is not set and we are defining the route from a branch
		if (!isset($action['branch']) && Reg::hasVal("Branch.name")) {
			$action = array_merge(array('branch' => Reg::get("Branch.name")), $action);
		}
		
		// sets the route in a holding variable and uses a sha256 of the definition as a unique identifier
		self::$routes[hash("sha256", $definition)] = array(
			"definition" => $definition,
			"destination" => $action,
			"validation" => $validation
		);
	}
	
	final public static function registerError($key, $params) {
		if (!is_array($params)) {
			$params = array("message"=>$params);
		}
		
		// call hook
		Hook::call('Config.registerError', array(&$key, &$params));
		
		self::$errors[$key] = $params;
	}
	
	final public static function getError($key) {
		// call hook
		Hook::call('Config.getError', array(&$key, &self::$errors));
		
		if (isset(self::$errors[$key])) {
			return self::$errors[$key];
		}
		return false;
	}
	
	/**
	 * Processes the uri by figuring out what mode we are running in, mod_rewrite or querystring, and by setting up and checking if we are in a branch or a route
	 * it also merges the uri values with the uri map and sets up all the Param and Path variables for use in the framework.
	 * 
	 * @access public
	 * @static
	 * @return boolean true if successful and boolean false if a route was matched
	 */
	public static function processURI() {
		// call hook
		Hook::call('Config.processURI.before');
		
		// make sure that the uri map exists and is an array with at least 2 keys
		if (!is_array(Reg::get("URI.map")) || count(Reg::get("URI.map")) < 2) {
			throw new EvergreenException("NO_URI_MAP");
		}
		
		// make sure that the view and controller keys exist in the uri map
		if (!array_key_exists('controller', Reg::get("URI.map")) || !array_key_exists('view', Reg::get("URI.map"))) {
			throw new EvergreenException("URI_MAP_INVALID_KEYS");
		}
		
		// check that there is not already a value in URI.working
		if (!Reg::hasVal("URI.working")) {
			// check if we are using mod_rewrite or a querystring
			if (Reg::get("URI.useModRewrite")) {
				// set up URI.working with the url based off of mod_rewrite
				if (strpos($_SERVER['REQUEST_URI'], "?")) {
					$_SERVER['REQUEST_URI'] = substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], "?"));
				}
				$_SERVER['REQUEST_URI'] = preg_replace("/^(".str_replace("/", "\/", Reg::get("URI.base"))."?)/i", "", $_SERVER['REQUEST_URI']);
				
				// set URI.prepend to nothing as we dont need it in mod_rewrite mode
				Reg::set("URI.prepend", "");
				Reg::set("URI.working", $_SERVER['REQUEST_URI']);
			} else {
				// set up URI.working with the url based off of querystring
				if (!is_string(Reg::get("URI.prependIdentifier")) || !strlen(Reg::get("URI.prependIdentifier"))) {
					throw new EvergreenException("NO_PREPEND_IDENTIFIER");
				}
				
				$queryParts = explode("&", $_SERVER['QUERY_STRING']);
				
				foreach($queryParts as $key => $value) {
					if (preg_match("/" . Reg::get("URI.prependIdentifier") . "=(.*)/i", $value)) {
						unset($queryParts[$key]);
						break;
					}
				}
				
				$_SERVER['QUERY_STRING'] = implode("&", $queryParts);
				
				// set URI.prepend to route to index.php and create a query string using the prepend identifier
				Reg::set("URI.prepend", "/index.php?" . Reg::get("URI.prependIdentifier") . "=");
				Reg::set("URI.working", (isset($_GET[Reg::get("URI.prependIdentifier")])) ? $_GET[Reg::get("URI.prependIdentifier")] : '');
			}
		}
		
		// make sure that the uri in URI.working is cleaned up and ready to be used
		if (substr(Reg::get("URI.working"), 0, 1) == "/") {
			$path_info = substr( Reg::get("URI.working"), 1, strlen(Reg::get("URI.working")) );
		} else {
			$path_info = ((is_array(Reg::get("URI.working"))) ? implode("/", Reg::get("URI.working")) : Reg::get("URI.working"));
		}
		
		// explode the cleaned URI.working so that those elements can become the new values for the URI.map
		if (!empty($path_info)) {
			$uri_vals = explode('/', $path_info );
		} else {
			$uri_vals = array();
		}
		
		// check for a leading slash in the uri and do a redirect to get rid of it so the url stays clean
		if (count($uri_vals) > 0 && empty($uri_vals[count($uri_vals)-1])) {
			unset($uri_vals[count($uri_vals)-1]);
			if (!is_array(Reg::get("Route.current")) && !Error::triggered()) {
				if (empty($_POST) && empty($_FILES) && !headers_sent()) {
					// only do the redirect if this isnt a route, an error hasn�t been triggered, headers havent been sent, and nothing is being posted or uploaded
					header("HTTP/1.1 301 Moved Permanently");
					header("Location: ".Reg::get("URI.base").Reg::get("URI.prepend")."/".implode("/", $uri_vals) . ((!empty($_SERVER['QUERY_STRING'])) ? ((!Reg::get("URI.useModRewrite")) ? "&" . $_SERVER['QUERY_STRING'] : "?" . $_SERVER['QUERY_STRING']) : ""));
					header("Connection: close");
					exit;
				}
			}
		}
		
		// check if there is a branch in the uri and if there is then load in it's configuration and reprocess the uri
		$uri_vals = self::checkForBranch($uri_vals);
		
		// check if the current uri matches a defined route, if so reprocess the uri
		if (self::checkRoutes("/".implode("/", $uri_vals))) {
			return false;
		}
		
		// assign the uri map to another variable for ease of use
		$uriMap = Reg::get("URI.map");
		$count = 0;
		foreach($uriMap as $key => $item) {
			if (!empty($item) && empty($uri_vals[$count])) {
				// if map key doesn't have a uri value but has a default set then set to default
				if (is_array($item)) {
					$uri_params[$key] = reset($item);
				} else {
					$uri_params[$key] = $item;
				}
			} else if (!empty($uri_vals[$count])) {
				if ((strtolower($key) == 'controller') || (is_array($item) && count($item) > 1 && (is_array($item[1]) || function_exists($item[1])))) {
					if (strtolower($key) != 'controller') {
						// if there is validation defined for a uri map key then test the value
						if ((!is_array($item[1]) && function_exists($item[1]) && $item[1]($uri_vals[$count]) == true) || (is_array($item[1]) && count($item[1]) > 1 && is_callable($item[1]) && call_user_func($item[1], $uri_vals[$count]) == true)) {
							// uri value passed map key validation so set value to key
							$uri_params[$key] = $uri_vals[$count];
							$count++;
							continue;
						} else if (strtolower($key) != 'controller') {
							// uri value didn't pass the validation so set this map's key to the default value
							$uri_params[$key] = (string)$item[0];
						}
					} else {
						// check if the controller exists
						if (file_exists(Reg::get("Path.physical").((strlen(Reg::get("Branch.name"))) ? "/branches/".self::uriToFile(Reg::get("Branch.name")) : "")."/controllers/".self::uriToFile($uri_vals[$count]).".php")) {
							$uri_params[$key] = $uri_vals[$count];
							$count++;
							continue;
						} else {
							// if the controller 
							if (is_array($item)) {
								$uri_params[$key] = reset($item);
							} else {
								$uri_params[$key] = $item;
							}
						}
					}
				} else {
					// a uri value matched a map key so set the key with the uri value
					$uri_params[$key] = $uri_vals[$count];
					$count++;
					continue;
				}
			} else {
				// no matching uri was found for this map key so set it's value to null
				$uri_params[$key] = null;
			}
		}
		unset($uriMap, $key, $item);
		
		// set the URI.working to the $uri_params variable which was generated above using the URI.map and the current uri
		Reg::set("URI.working", $uri_params);
		
		// set the Params to the $uri_params variable which was generated above using the URI.map and the current uri
		Reg::set("Param", $uri_params);
		
		// clean up the generated working uri variable
		unset($uri_params);
		
		// check if we are using mod_rewrite or querystring and grab the uri from the one we are using to generate paths for the Path variables
		if (Reg::get("URI.useModRewrite") == true) {
			$uri_paths = explode("/", ltrim($_SERVER['REQUEST_URI'], '/'));
		} else {
			if (isset($_GET[Reg::get("URI.prependIdentifier")])) {
				$uri_paths = explode("/", trim($_GET[Reg::get("URI.prependIdentifier")], '/'));
			} else {
				$uri_paths = array();
			}
		}
		
		// setup the main Path variables
		Reg::set("Path.site", Reg::get("URI.base").Reg::get("URI.prepend"));
		if (Reg::hasVal("Branch.name")) {
			// if we are in a branch then setup additional branch path variables
			Reg::set("Path.branch", str_replace("//", "/", Reg::get("Path.site")."/".Reg::get("Branch.name")));
			Reg::set("Path.branchRoot", str_replace("//", "/", Reg::get("URI.base")."/branches/".Reg::get("Branch.name")));
			Reg::set("Path.branchSkin", str_replace("//", "/", Reg::get("Path.branchRoot")."/public"));
		}
		Reg::set("Path.root", str_replace("//", "/", Reg::get("URI.base")));
		Reg::set("Path.skin", str_replace("//", "/", Reg::get("Path.root")."/public"));
		
		// setup all the other path variables based on the URI.map definition
		$count = 0;
		$position = 0;
		$skip = 0;
		$uriMap = Reg::get("URI.map");
		$uriWorking = Reg::get("URI.working");
		foreach($uriWorking as $key => $value) {
			// if the current item is a prepend with a default and the default is the current item in the uri then dont show that item in the paths
			if (is_array($uriMap[$key]) && $uriMap[$key][0] == $value) {
				$skip++;
				$count--;
				$position--;
			}
			
			// fix the paths so that they dont show the URI.map defined defaults
			if (isset($uriMap[$key]) && $uriMap[$key] == $value) {
				unset($uriWorking[$key]);
				$position = $count;
				$count--;
			} else {
				// if this is not a default then advance the position and have it show in the variable
				$position = ($count+1);
			}
			
			// set the actual variables
			Reg::set("Path.".$key, Reg::get("Path.site").'/'.trim(implode('/', array_merge((array)Reg::get('Branch.name'), array_slice($uriWorking, $skip, $position))), '/'));
			$count++;
		}
		
		// clean up after setting other path variables
		unset($uriMap);
		
		// setup the array to build the Path.current variable and make sure that the url is clean
		$current_uri_map = array();
		foreach($uri_paths as $item) {
			if (!empty($item)) $current_uri_map[] = $item;
		}
		Reg::set("Path.current", str_replace("//", "/", implode("/", array_merge(array(Reg::get("Path.site")), $current_uri_map))));
		
		// call hook
		Hook::call('Config.processURI.after');
		
		return true;
	}
	
	/**
	 * Return a uri item as a valid file name, changing - or _ for a . and lowercasing.
	 * 
	 * @access public
	 * @static
	 * @param string $uriItem The uri string that needs to be converted to the file format
	 * @return string
	 */
	public static function uriToFile($uriItem) {
		if (Reg::get('URI.useDashes') == true && Reg::get('URI.forceDashes') == false) {
			$regex = '/[_-]/';
		} else if (Reg::get('URI.forceDashes') == true) {
			$regex = '/[-]/';
		} else {
			$regex = '/[_]/';
		}
		$return = strtolower(preg_replace($regex, '.', $uriItem));
		
		// call hook
		Hook::call('Config.uriToFile', array(&$return));
		
		return $return;
	}
	
	/**
	 * Return a uri item as a valid method name, changing - or _ for the next character in the name being uppercased.
	 * 
	 * @access public
	 * @static
	 * @param string $uriItem The uri string that needs to be converted to the method format
	 * @return string
	 */
	public static function uriToMethod($uriItem) {
		if (Reg::get('URI.useDashes') == true && Reg::get('URI.forceDashes') == false) {
			$regex = '/[_-]/';
		} else if (Reg::get('URI.forceDashes') == true) {
			$regex = '/[-]/';
		} else {
			$regex = '/[_]/';
		}
		
		$uriItem = preg_replace('/[A-Z]/', ' $0', $uriItem);
		
		$uriItem = explode(' ', ucwords(preg_replace($regex, ' ', $uriItem)));
		if (count($uriItem) > 0) {
			$uriItem[0] = strtolower($uriItem[0]);
		}
		$return = implode('', $uriItem);
		
		// call hook
		Hook::call('Config.uriToMethod', array(&$return));
		
		return $return;
	}
	
	/**
	 * Return a uri item as a valid class name, changing - or _ for the next character in the name being uppercased.
	 * 
	 * @access public
	 * @static
	 * @param string $uriItem The uri string that needs to be converted to the class format
	 * @return string
	 */
	public static function uriToClass($uriItem) {
		if (Reg::get('URI.useDashes') == true && Reg::get('URI.forceDashes') == false) {
			$regex = '/[_-]/';
		} else if (Reg::get('URI.forceDashes') == true) {
			$regex = '/[-]/';
		} else {
			$regex = '/[_]/';
		}
		
		$uriItem = explode(' ', ucwords(preg_replace($regex, ' ', $uriItem)));
		$return = implode('', $uriItem);
		
		// call hook
		Hook::call('Config.uriToClass', array(&$return));
		
		return $return;
	}
	
	/**
	 * Return a method name as a valid file name by adding a . before all uppercased characters and lowercasing the string.
	 * 
	 * @access public
	 * @static
	 * @param string $methodItem The method name that needs to be converted to the file format
	 * @return string
	 */
	public static function methodToFile($methodItem) {
		$return = strtolower(trim(preg_replace('/[A-Z]/', '.$0', $methodItem), '.'));
		
		// call hook
		Hook::call('Config.methodToFile', array(&$return));
		
		return $return;
	}
	
	/**
	 * Return a method name as a valid file name by adding a . before all uppercased characters and lowercasing the string.
	 * 
	 * @access public
	 * @static
	 * @param string $classItem The class name that needs to be converted to the file format
	 * @return string
	 */
	public static function classToFile($classItem) {
		$return = self::methodToFile($classItem);
		
		// call hook
		Hook::call('Config.classToFile', array(&$return));
		
		return $return;
	}
	
	/**
	 * Return a file item as a valid method name, changing . for the next character in the name being uppercased with the first character in the name being lowercase.
	 * 
	 * @access public
	 * @static
	 * @param string $fileItem The file name that needs to be converted to the method format
	 * @return string
	 */
	public static function fileToMethod($fileItem) {
		$fileItem = preg_replace('/[A-Z]/', ' $0', $fileItem);
		$fileItem = explode(' ', ucwords(preg_replace('/\./', ' ', $fileItem)));
		if (count($fileItem) > 0) {
			$fileItem[0] = strtolower($fileItem[0]);
		}
		$return = implode('', $fileItem);
		
		// call hook
		Hook::call('Config.fileToMethod', array(&$return));
		
		return $return;
	}
	
	/**
	 * Return a file item as a valid class name, changing . for the next character in the name being uppercased with the first character in the name being uppercase.
	 * 
	 * @access public
	 * @static
	 * @param string $fileItem The file name that needs to be converted to the class format
	 * @return string
	 */
	public static function fileToClass($fileItem) {
		$fileItem = explode(' ', ucwords(preg_replace('/\./', ' ', $fileItem)));
		$return = implode('', $fileItem);
		
		// call hook
		Hook::call('Config.fileToClass', array(&$return));
		
		return $return;
	}
	
	/**
	 * Return true or false if the uri item that is passed in is a branch.
	 * 
	 * @access public
	 * @static
	 * @param string $branch_name A uri element that is being checked to see if it is a branch
	 * @return boolean true if the $branch_name matches a branch directory and boolean false if not
	 */
	public static function isBranch($branch_name) {
		$return = is_dir(Reg::get("Path.physical")."/branches/".self::uriToFile($branch_name));
		
		// call hook
		Hook::call('Config.isBranch', array(&$return));
		
		return $return;
	}
	
	/**
	 * Return the uri values after they have been checked for a branch and if there is a branch then load the branch configuration and setup the Branch.name variable.
	 * 
	 * @access public
	 * @static
	 * @param array $url_vals The url
	 * @return boolean true if the $branch_name matches a branch directory and boolean false if not
	 */
	public static function checkForBranch($url_vals) {
		if (is_array($url_vals) && !empty($url_vals) && self::isBranch($url_vals[0]) && !file_exists(Reg::get("Path.physical")."/controllers/".self::uriToFile($url_vals[0]).".php")) {
			self::setupBranch($url_vals[0]);
			array_shift($url_vals);
			return $url_vals;
		} else {
			return $url_vals;
		}
	}
	
	/**
	 * Loads everything necessary to get a branch setup.
	 * 
	 * @access public
	 * @static
	 * @param string $branch_name The name of the branch
	 */
	public static function setupBranch($branch_name) {
		if (self::isBranch($branch_name)) {
			Reg::set("Branch.name", self::uriToMethod($branch_name));
			Reg::set("Path.branchPhysical", str_replace("//", "/", Reg::get("Path.physical")."/branches/".Reg::get("Branch.name")));
			self::loadBranchConfig(Reg::get("Branch.name"));
		}
	}
	
	/**
	 * Loads in the branch's config.php and errors.php files and then checks that the branch is set to active and has the required system versions and mode set.
	 * 
	 * @access public
	 * @static
	 * @param string $branch_name The name of the branch
	 */
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
			throw new EvergreenException("BRANCH_INACTIVE");
		}
		
		if (Reg::get("Branch.requiredSystemMode") !== null && Reg::get("Branch.requiredSystemMode") != Reg::get("System.mode")) {
			// The system does not have the required mode so don't load the branch
			throw new EvergreenException("BRANCH_REQUIRED_SYSTEM_MODE");
		}
		
		if (Reg::get("Branch.minimumSystemVersion") !== null && !version_compare(Reg::get("System.version"), Reg::get("Branch.minimumSystemVersion"), ">=")) {
			// The system version is lower than the branch's required minimum so don't load the branch
			throw new EvergreenException("BRANCH_MINIMUM_SYSTEM_VERSION");
		}
		
		if (Reg::get("Branch.maximumSystemVersion") !== null && !version_compare(Reg::get("System.version"), Reg::get("Branch.maximumSystemVersion"), "<=")) {
			// The system version is higher than the branch's required maximum so don't load the branch
			throw new EvergreenException("BRANCH_MAXIMUM_SYSTEM_VERSION");
		}
	}
	
	/**
	 * Check's the current uri for a route match and reprocesses the uri if there is a match.
	 * 
	 * @access private
	 * @static
	 * @param string $request_uri The current uri with the base uri excluded
	 * @return boolean true if a route was found and boolean false if not
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
	
	/**
	 * Takes a route's simple regex and turns it into real regex and returns the regex and the named positions.
	 * 
	 * @access private
	 * @static
	 * @param string $regex The route's definition
	 * @return array
	 */
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

/**
 * Registration Class
 *
 * Registration class that holds all the registered variables both user registered
 * and system registered so that they are available globaly.
 *
 * @package       evergreen
 * @subpackage    lib
 */
final class Reg {
	/**
	 * Holder for all of the defined variables.
	 * 
	 * @access private
	 * @static
	 * @var array
	 */
	private static $variables = array();
	
	/**
	 * Sets a variable with either an array or as the first argument being the key and the second being the value.
	 * 
	 * @access public
	 * @static
	 * @param string $key The definition of the registration variable
	 * @param mixed $value The value of the variable being registered
	 * @return boolean true
	 */
	public static function set($name, $value = null) {
		// if an array isnt being passed in then create an array with the passed in args
		if (!is_array($name)) {
			$name = array(
				$name => $value
			);
		}
		
		// loop through the array of variables that need to be set
		foreach($name as $key => $value) {
			// call hook
			if (method_exists('Hook', 'call')) {
				Hook::call('Reg.set.' . $key, array(&$value));
			}
			
			$path = explode('.', $key);
			$variablesHolder =& self::$variables;
			// loop through the exploded variable key to create all the array levels
			foreach($path as $i => $path_key) {
				if ($i == (count($path) - 1)) {
					// set the key and value once the end of the exploded variable key array is reached
					$variablesHolder[$path_key] = $value;
					break;
				} else {
					if (!isset($variablesHolder[$path_key])) {
						// setup element in array if it doesn�t exist
						$variablesHolder[$path_key] = array();
					}
					// set the current level of the array to the holder and continue to loop
					$variablesHolder =& $variablesHolder[$path_key];
				}
			}
		}
		return true;
	}
	
	/**
	 * Gets a variable by the variable key and returns the value or returns null if the key doesn't exist.
	 * 
	 * @access public
	 * @static
	 * @param string $key The registration variable that is being accessed
	 * @return mixed
	 */
	public static function get($key) {
		$path = explode('.', $key);
		$variablesHolder =& self::$variables;
		// loop through the exploded variable key to get to the correct level in the variable array
		foreach($path as $i => $path_key) {
			if ($i == (count($path) - 1)) {
				// return the value of the variable key or null if it doesn�t exist
				$value = (isset($variablesHolder[$path_key])) ? $variablesHolder[$path_key] : null;
				
				// call hook
				if (method_exists('Hook', 'call')) {
					Hook::call('Reg.get.' . $key, array(&$value));
				}
				
				return $value;
			} else {
				// set the current level of the array to the holder and continue to loop
				$variablesHolder =& $variablesHolder[$path_key];
			}
		}
		return null;
	}
	
	/**
	 * Returns true if a variable key is set and false if not.
	 * 
	 * @access public
	 * @static
	 * @param string $key The registration variable that is being accessed
	 * @return boolean true if the variable exists and boolean false if not
	 */
	public static function has($key) {
		// call hook
		if (method_exists('Hook', 'call')) {
			Hook::call('Reg.has.' . $key, array(&$key));
		}
		
		$path = explode('.', $key);
		$variablesHolder =& self::$variables;
		// loop through the exploded variable key to get to the correct level in the variable array
		foreach($path as $i => $path_key) {
			if ($i == (count($path) - 1)) {
				// return true if the variable key does exist and false if it doesn't
				return isset($variablesHolder[$path_key]);
			} else {
				// set the current level of the array to the holder and continue to loop
				$variablesHolder =& $variablesHolder[$path_key];
			}
		}
		return false;
	}
	
	/**
	 * Returns true if a variable key has a value and false if not.
	 * 
	 * @access public
	 * @static
	 * @param string $key The registration variable that is being accessed
	 * @return boolean true if the variable has a value and boolean false if not
	 */
	public static function hasVal($key) {
		$path = explode('.', $key);
		$variablesHolder =& self::$variables;
		// loop through the exploded variable key to get to the correct level in the variable array
		foreach($path as $i => $path_key) {
			if ($i == (count($path) - 1)) {
				// return true if the variable key does have a value and false if it doesnt
				$val = !empty($variablesHolder[$path_key]);
				
				// call hook
				if (method_exists('Hook', 'call')) {
					Hook::call('Reg.hasVal.' . $key, array(&$val));
				}
				
				return $val;
			} else {
				// set the current level of the array to the holder and continue to loop
				$variablesHolder =& $variablesHolder[$path_key];
			}
		}
		return false;
	}
	
	/**
	 * Deletes a variable by the key and returns true if deleted otherwise returns false.
	 * 
	 * @access public
	 * @static
	 * @param string $key The registration variable that is being accessed
	 * @return boolean true if the variable was deleted and boolean false if it wasn't or doesn't exist
	 */
	public static function del($key) {
		// call hook
		if (method_exists('Hook', 'call')) {
			Hook::call('Reg.del.' . $key, array(&$key));
		}
		
		$path = explode('.', $key);
		$variablesHolder =& self::$variables;
		// loop through the exploded variable key to get to the correct level in the variable array
		foreach($path as $i => $path_key) {
			if ($i == (count($path) - 1)) {
				if (isset($variablesHolder[$path_key])) {
					// unset the variable key
					unset($variablesHolder[$path_key]);
					return true;
				} else {
					return false;
				}
			} else {
				// set the current level of the array to the holder and continue to loop
				$variablesHolder =& $variablesHolder[$path_key];
			}
		}
		return false;
	}

}
?>
