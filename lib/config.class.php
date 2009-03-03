<?php
final class Config {
	## General ##
	protected $physical_path;
	protected $working_uri;
	
	## Base Config Holder ##
	public $config;
	
	## Errors Config ##
	protected $errors_path;
	protected $errors;
	
	## Route Holder ##
	protected $routes;
	protected $current_route;
	//protected $branch_current_route;
	
	## Main Setup ##
	protected $uri_map;
	protected $uri_type;
	protected $base_uri = "/";
	protected $url_append;
	
	## Database Setup ##
	protected $database_info;
	
	## FTP Setup ##
	protected $ftp_info;
	
	## Branch Config ##
	protected $branch_name;
	protected $branch_config;
	protected $branch_layout;
	protected $branch_uri_map;
	protected $branch_routes;
	protected $branch_database_info;
	
	function __construct() {
		$this->set_physical_path(dirname(dirname(__FILE__)));
		
		include($this->get_base_path().'/config/config.php');
		
		$this->errors = $config['errors'];
		//$this->errors_path = $config['errors_path'];
		
		$this->database_info = $database;
		
		$this->uri_map = $uri;
		$this->uri_type = $config['uri_type'];
		
		$base_uri = dirname($_SERVER['SCRIPT_NAME']);
		$base_uri = ($base_uri{strlen($base_uri)-1} == '/') ? substr($base_uri, 0, strlen($base_uri)-1) : $base_uri;
		
		$this->set_base_uri($base_uri);
		$this->routes = $routes;
		
		if (!$this->get_working_uri()) {
			$this->set_uri();
		}
	}
	
	private function set_physical_path($path) {
		$this->physical_path = $path;
	}
	
	public function get_physical_path() {
		return $this->physical_path;
	}
	
	public function parse_get() {
		if (!empty($_GET)) {
			array_shift($_GET);
		}
	}
	
	public function get_routes() {
		return $this->routes;
	}
	
	public function get_branch_uri_map() {
		return $this->branch_uri_map;
	}
	
	public function get_branch_config() {
		return $this->branch_config;
	}
	
	public function set_branch_config($branch_name) {
		include($this->get_base_path()."/branches/{$branch_name}/config/config.php");
		
		if ($uri) $this->uri_map = $uri;
		$this->branch_layout = $config['default_layout'];
		if ($routes) $this->routes = $routes;
		if ($database) $this->database_info = $database;
	}
	
	public function get_base_path() {
		return $this->physical_path;
	}
	
	public function set_url_append($append) {
		$this->url_append = $append;
	}
	
	public function get_url_append() {
		return $this->url_append;
	}
	
	public function get_database_info() {
		return $this->database_info;
	}
	
	public function get_error($err_number) {
		return $this->errors[$err_number];
	}
	
	public function set_branch_name($name) {
		$this->branch_name = $name;
	}
	
	public function get_branch_name() {
		return $this->branch_name;
	}
	
	public function set_uri_map($map) {
		foreach($map as $key => $value) {
			if (!is_numeric($key)) {
				$parsedMap[$key] = $value;
			} else {
				$parsedMap[$value] = "";
			}
		}
		
		$this->uri_map = $parsedMap;
	}
	
	public function get_uri_map() {
		return $this->uri_map;
	}
	
	public function set_base_uri($uri) {
		if ($uri == "") {
			$uri = "/";
		}
		
		$this->base_uri = $uri;
	}
	
	public function get_base_uri() {
		return $this->base_uri;
	}
	
	public function get_request_uri() {
		return explode("/", $this->current_route['request_uri']);
	}
	
	public function get_full_current_route_uri() {
		return $this->current_route;
	}
	
	public function set_current_route($current_route) {
		$this->current_route = $current_route;
	}
	
	public function get_current_route() {
		return $this->current_route;
	}
	
	public function get_current_route_uri() {
		return $this->current_route['uri_map'];
	}
	
	public function get_branch_request_uri() {
		return explode("/", $this->branch_current_route['request_uri']);
	}
	
	public function get_full_branch_current_route_uri() {
		return $this->branch_current_route;
	}
	
	public function get_branch_current_route_uri() {
		return $this->branch_current_route['uri_map'];
	}
	
	public function get_working_uri() {
		return $this->working_uri;
	}
	
	public function set_working_uri($working_uri) {
		$this->working_uri = $working_uri;
	}
	
	private function check_for_branch($url_vals) {
		if (is_array($url_vals) && is_dir($this->physical_path."/branches/".$url_vals[0]) && !file_exists($this->physical_path."/controllers/".$url_vals[0].".php")) {
			$this->set_branch_name($url_vals[0]);
			$this->set_branch_config($url_vals[0]);
			array_shift($url_vals);
			return $url_vals;
		} else {
			return $url_vals;
		}
	}
	
	public function set_uri() {
		switch ($this->uri_type) {
			case 'QUERY_STRING':
				$this->set_url_append("/index.php?url=");
				
				$this->set_working_uri($_GET['url']);
			break;
			
			default:
				if (strpos($_SERVER['REQUEST_URI'], "?")) $_SERVER['REQUEST_URI'] = substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], "?"));
				$_SERVER['REQUEST_URI'] = preg_replace("/^(".str_replace("/", "\/", $this->get_base_uri())."?)/i", "", $_SERVER['REQUEST_URI']);
				
				$this->set_working_uri($_SERVER['REQUEST_URI']);
			break;
		}
	}
	
	public function check_uri() {
		if (substr($this->get_working_uri(), 0, 1) == "/") {
			$path_info = substr( $this->get_working_uri(), 1, strlen($this->get_working_uri()) );
		} else {
			$path_info = ((is_array($this->get_working_uri())) ? implode("/", $this->get_working_uri()) : $this->get_working_uri());
		}
		
		if (!empty($path_info)) {
			$url_vals = explode('/', $path_info );
		} else {
			$url_vals = array();
		}
		
		if (count($url_vals) > 0 && empty($url_vals[count($url_vals)-1])) {
			unset($url_vals[count($url_vals)-1]);
			
			if (!is_array($this->get_current_route())) {
				header("HTTP/1.1 301 Moved Permanently");
				header("Location: ".$this->get_base_uri()."/".implode("/", $url_vals));
				header("Connection: close");
				exit;
			}
		}
		
		## Branch Check ##
		$url_vals = $this->check_for_branch($url_vals);
		$branch_name = $this->get_branch_name();
		
		## Route Check ##
		if ($this->check_routes("/".implode("/", $url_vals))) {
			return false;
		}
		
		$count = 0;
		
		foreach($this->get_uri_map() as $key => $item) {
			if ($url_vals[0] == $default_controller && !file_exists($this->physical_path."/controllers/".$url_vals[1].".php") &&  !is_dir($this->physical_path."/branches/".$url_vals[$count])) {
				header("Location: ".$this->get_base_uri()."/".implode("/", array_slice($url_vals, 1)));
			}
			if ($count == 0 && !file_exists($this->get_physical_path()."/branches/".$branch_name."/controllers/".$url_vals[$count].".php") && !empty($branch_name)) {
				$url_vals = array_merge(array($item), $url_vals);
			} elseif ($count == 0 && !file_exists($this->get_physical_path()."/controllers/".$url_vals[$count].".php") && empty($branch_name)) {
				$url_vals = array_merge(array($item), $url_vals);
			}
			
			$uri_params[$key] = ((!empty($item) && empty($url_vals[$count])) ? $item : $url_vals[$count]);
			$count++;
		}
		
		if (!$return) {
			$this->set_working_uri($uri_params);
			return false;
		} else {
			$this->set_working_uri($uri_params);
			return $uri_params;
		}
	}
	
	private function check_routes($request_uri) {
		foreach($this->get_routes() as $regex=>$destination) {
			$regex = str_replace("/", "\/", $regex);
			if (preg_match("/^{$regex}/i", $request_uri)) {
				$new_uri = preg_replace("/{$regex}/i", "{$destination}", $this->get_working_uri());
				$this->set_current_route(array($regex=>$destination));
				$_SERVER['REQUEST_URI'] = $new_uri;
				$this->set_working_uri($new_uri);
				$this->check_uri();
				return true;
			}
		}
		
		return false;
	}
}
?>
