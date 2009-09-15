<?php
class Designer {
	function __construct () {
		$view_info = Config::read("URI.working");
		$main_uri_names = array();
		
		##############################
		###       Constants        ###
		##############################
		$uri_to_use = array();
		/*
		if (Config::read("Branch.name") != "" && Factory::get_config()->get_branch_current_route_uri() != "") {
			# Branch Route URL #
			$uri_to_use = Factory::get_config()->get_branch_request_uri();
		} elseif (Config::read("Branch.name") != "" && Factory::get_config()->get_branch_uri_map() != "") {
			# Branch URL #
			$uri_to_use = array_merge(array("branch"=>Config::read("Branch.name")), Factory::get_config()->get_branch_uri_map());
		} elseif (Factory::get_config()->get_current_route_uri() != "") {
			# Route URL #
			$uri_to_use = Factory::get_config()->get_request_uri();
		} else {
		*/
			# Regular URL #
			$uri_to_use = Config::read("URI.working");
		//}
		
		$config_base_uri = Config::read("URI.base");
		$config_base_uri_prepend = Config::read("URI.prepend");
		if (empty($config_base_uri)) {
			$config_base_uri = "/";
		}
		define("URI_ROOT", $config_base_uri.$config_base_uri_prepend);
		if (Config::read("Branch.name") != "") {
			define("URI_BRANCH", URI_ROOT."/".Config::read("Branch.name"));
		}
		define("URI_SKIN", implode("/", array_merge(explode("/", $config_base_uri), array("public"))));
		
		foreach($uri_to_use as $key => $item) {
			$position = array_search($key, array_keys(Config::read("URI.working")));
			$new_base = explode("/", URI_ROOT);
			define("URI_".strtoupper($key), implode("/", array_merge($new_base, array_slice(Config::read("URI.working"), 0, ($position+1))))); 
		}
		
		$current_uri_map = array();
		
		foreach($uri_to_use as $key => $item) {
			if (!empty($item)) $current_uri_map[] = $item;
		}
		
		define("URI_CURRENT", implode("/", array_merge(explode("/", ((Config::read("Branch.name") != "") ? URI_BRANCH : URI_ROOT)), $current_uri_map)));
	}
	
	public function do_fixes (&$content) {
		$this->linkFix($content);
	}
	
	public function linkCallback ($link) {
		
		$link_arr = explode("/", $link[2]);
		$up_link_count = count(array_keys(array_slice($link_arr, 1), ".."));
		
		switch ($link_arr[0]) {
			case "[current]":
				$new_base = explode("/", URI_CURRENT);
				$return = implode("/", (($up_link_count) ? array_slice($new_base, 0, -$up_link_count) : $new_base)) . implode("/", array_pad(array_slice($link_arr, $up_link_count+1), -(count(array_slice($link_arr, $up_link_count+1))+1), ""));
			break;
			
			case "[site]":
				$new_base = explode("/", URI_ROOT);
				$return = implode("/", $new_base) . implode("/", array_pad(array_slice($link_arr, 1), -(count(array_slice($link_arr, 1))+1), ""));
			break;
			
			case "[skin]":
				$new_base = explode("/", URI_SKIN);
				$return = implode("/", $new_base) . implode("/", array_pad(array_slice($link_arr, 1), -(count(array_slice($link_arr, 1))+1), ""));
			break;
			
			case "[root]":
				$new_base = explode("/", dirname(URI_SKIN));
				$return = implode("/", $new_base) . implode("/", array_pad(array_slice($link_arr, 1), -(count(array_slice($link_arr, 1))+1), ""));
			break;
			
			default:
				$working_uri = Config::read("URI.working");
				
				if (strlen(Config::read("Branch.name"))) {
					$working_uri = array_merge(array("branch"=>Config::read("Branch.name")), $working_uri);
				}
				
				foreach($working_uri as $key => $item) {
					$tmp_key = "[".$key."]";
					
					if ($link_arr[0] == $tmp_key) {
						$position = array_search($key, array_keys($working_uri));
						$new_base = explode("/", URI_ROOT);
						
						$new_url = array_merge( array_merge($new_base, array_slice($working_uri, 0, ($position+1))), array_pad(array_slice($link_arr, $up_link_count+1), -(count(array_slice($link_arr, $up_link_count+1))), "") );
						
						$return = implode("/",  $new_url );
						break 1;
					}
				}
			break;
		}
		
		return $link[1].((!empty($return)) ? $return : $link[2]);
	}
	
	public function linkFix (&$skin) {
		$skin = preg_replace_callback("/(=\"|=\'|=)([\[\]][^(\"|\'|[:space:]|>)]+)/", array($this, "linkCallback"), $skin);
	}
}
?>