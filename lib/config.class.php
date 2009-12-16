<?php
final class Config {
	## Base Config Holder ##
	protected static $config;
	protected static $isSetup = false;
	
	## Route Config Holder ##
	protected static $routes;
	
	private static function setup() {
		if (!self::$isSetup) {
			// Setup the System.version configuration setting
			self::$config['System']['version'] = "1.0.0b1";
			
			// Setup the Path.physical configuration setting
			self::$config['Path']['physical'] = dirname(dirname(__FILE__));
			
			// Setup the URI.base configuration setting
			$base_uri = dirname($_SERVER['SCRIPT_NAME']);
			$base_uri = ($base_uri{strlen($base_uri)-1} == '/') ? substr($base_uri, 0, strlen($base_uri)-1) : $base_uri;
			self::$config['URI']['base'] = $base_uri;
			
			// Setup the System.defaultError's configuration setting
			self::$config['System']['defaultError404'] = self::$config['Path']['physical']."/public/errors/404.php";
			self::$config['System']['defaultErrorGEN'] = self::$config['Path']['physical']."/public/errors/general.php";
		}
		
		// Indicate that the setup function has been run and doesnt need to be run again
		self::$isSetup = true;
	}
	
	public static function register($name, $value = "") {
		self::setup();
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
		self::setup();
		$path = explode('.', $key);
		$config_holder =& self::$config;
		foreach($path as $i => $path_key) {
			if ($i == (count($path) - 1)) {
				return $config_holder[$path_key];
			} else {
				$config_holder =& $config_holder[$path_key];
			}
		}
		return null;
	}
	
	public static function registerRoute($definition, $action) {
		self::setup();
		self::$routes[$definition] = $action;
	}
	
	public static function processURI() {
		self::setup();
		if (!self::read("URI.working")) {
			if (self::read("URI.useModRewrite")) {
				if (strpos($_SERVER['REQUEST_URI'], "?")) $_SERVER['REQUEST_URI'] = substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], "?"));
				$_SERVER['REQUEST_URI'] = preg_replace("/^(".str_replace("/", "\/", self::read("URI.base"))."?)/i", "", $_SERVER['REQUEST_URI']);
				
				self::register("URI.prepend", "");
				self::register("URI.working", $_SERVER['REQUEST_URI']);
			} else {
				self::register("URI.prepend", "/index.php?url=");
				self::register("URI.working", $_GET['url']);
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
			
			if (!is_array(self::read("Routes.current"))) {
				header("HTTP/1.1 301 Moved Permanently");
				header("Location: ".self::read("URI.base")."/".implode("/", $url_vals));
				header("Connection: close");
				exit;
			}
		}
		
		## Branch Check ##
		$url_vals = self::checkForBranch($url_vals);
		$branch_name = self::read("Branch.name");
		
		## Route Check ##
		if (self::checkRoutes("/".implode("/", $url_vals))) {
			return false;
		}
		
		$count = 0;
		
		foreach(self::read("URI.map") as $key => $item) {
			if ($url_vals[0] == $default_controller && !file_exists(self::read("Path.physical")."/controllers/".$url_vals[1].".php") &&  !is_dir(self::read("Path.physical")."/branches/".$url_vals[$count])) {
				header("Location: ".self::read("URI.base")."/".implode("/", array_slice($url_vals, 1)));
			}
			if ($count == 0 && !file_exists(self::read("Path.physical")."/branches/".$branch_name."/controllers/".$url_vals[$count].".php") && !empty($branch_name)) {
				$url_vals = array_merge(array($item), $url_vals);
			} elseif ($count == 0 && !file_exists(self::read("Path.physical")."/controllers/".$url_vals[$count].".php") && empty($branch_name)) {
				$url_vals = array_merge(array($item), $url_vals);
			}
			
			$uri_params[$key] = ((!empty($item) && empty($url_vals[$count])) ? $item : $url_vals[$count]);
			$count++;
		}
		
		if(self::read("URI.useDashes") || self::read("URI.forceDashes")) {
			if (self::read("URI.forceDashes")) {
				$uri_params[reset(array_slice(array_keys($uri_params), 0, 1))] = str_replace("_", "", $uri_params[reset(array_slice(array_keys($uri_params), 0, 1))]);
				$uri_params[reset(array_slice(array_keys($uri_params), 1, 1))] = str_replace("_", "", $uri_params[reset(array_slice(array_keys($uri_params), 1, 1))]);
			}
			
			$uri_params[reset(array_slice(array_keys($uri_params), 0, 1))] = str_replace("-", "_", $uri_params[reset(array_slice(array_keys($uri_params), 0, 1))]);
			$uri_params[reset(array_slice(array_keys($uri_params), 1, 1))] = str_replace("-", "_", $uri_params[reset(array_slice(array_keys($uri_params), 1, 1))]);
		}
		
		
		self::register("URI.working", $uri_params);
		
		// Setup the Param configuration setting
		foreach(self::read("URI.working") as $param => $value) {
			self::register("Param.".$param, $value);
		}
		
		// Setup the additional Path configuration settings based off the URI.map, the Skin, and the Branch
		self::register("Path.site", self::read("URI.base").self::read("URI.prepend"));
		if (self::read("Branch.name") != "") {
			self::register("Path.branch", str_replace("//", "/", self::read("Path.site")."/".self::read("Branch.name")));
		}
		self::register("Path.skin", str_replace("//", "/", implode("/", array_merge(explode("/", self::read("URI.base")), array("public")))));
		self::register("Path.root", str_replace("//", "/", self::read("URI.base")));
		
		foreach(self::read("URI.working") as $key => $value) {
			if (empty($value)) {
				continue;
			}
			
			$position = array_search($key, array_keys(self::read("URI.working")));
			self::register("Path.".$key, str_replace("//", "/", implode("/", array_merge(explode("/", self::read("Path.site")), array_slice(self::read("URI.working"), 0, ($position+1))))));
		}
		
		$current_uri_map = array();
		
		foreach(self::read("URI.working") as $key => $item) {
			if (!empty($item)) $current_uri_map[] = $item;
		}
		
		self::register("Path.current", str_replace("//", "/", implode("/", array_merge(explode("/", ((self::read("Branch.name") != "") ? self::read("Path.branch") : self::read("Path.site"))), $current_uri_map))));
		
		return true;
	}
	
	public static function checkForBranch($url_vals) {
		if (is_array($url_vals) && is_dir(self::read("Path.physical")."/branches/".$url_vals[0]) && !file_exists(self::read("Path.physical")."/controllers/".$url_vals[0].".php")) {
			self::register("Branch.name", $url_vals[0]);
			self::loadBranchConfig(self::read("Branch.name"));
			array_shift($url_vals);
			return $url_vals;
		} else {
			return $url_vals;
		}
	}
	
	public static function loadBranchConfig($branch_name) {
		self::setup();
		if (file_exists(self::read("Path.physical")."/branches/{$branch_name}/config/config.php")) {
			// Load the branch configuration
			include(self::read("Path.physical")."/branches/{$branch_name}/config/config.php");
		}
		
		if (file_exists(self::read("Path.physical")."/branches/{$branch_name}/config/errors.php")) {
			// Load the branch errors
			include(self::read("Path.physical")."/branches/{$branch_name}/config/errors.php");
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
	
	public static function checkRoutes($request_uri) {
		self::setup();
		foreach(self::$routes as $regex=>$destination) {
			$regex_fixed = str_replace("/", "\/", $regex);
			if (preg_match("/^{$regex_fixed}/i", $request_uri) && !(self::read("Routes.current") !== null && array_key_exists($regex, self::read("Routes.current")))) {
				if (self::read("Branch.name")) {
					$regex_branch = "\/".self::read("Branch.name");
				}
				$new_uri = preg_replace("/^{$regex_branch}{$regex_fixed}/i", "{$destination}", self::read("URI.working"));
				
				$_SERVER['REQUEST_URI'] = $new_uri;
				self::register("Routes.current", array($regex=>$destination));
				self::register("URI.working", $new_uri);
				self::processURI();
				return true;
			}
		}
		
		return false;
	}
}
?>
