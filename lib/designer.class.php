<?php
class Designer {
	function __construct () { }
	
	public function doFixes (&$content) {
		$this->linkFix($content);
	}
	
	public function linkCallback ($link) {
		
		$link_arr = explode("/", $link[2]);
		$up_link_count = count(array_keys(array_slice($link_arr, 1), ".."));
		
		$return = '';
		switch ($link_arr[0]) {
			case "[current]":
				$new_base = explode("/", Config::read("Path.current"));
				$return = implode("/", (($up_link_count) ? array_slice($new_base, 0, -$up_link_count) : $new_base)) . implode("/", array_pad(array_slice($link_arr, $up_link_count+1), -(count(array_slice($link_arr, $up_link_count+1))+1), ""));
			break;
			
			case "[site]":
				$new_base = explode("/", Config::read("Path.site"));
				$return = implode("/", $new_base) . implode("/", array_pad(array_slice($link_arr, 1), -(count(array_slice($link_arr, 1))+1), ""));
			break;
			
			case "[skin]":
				$new_base = explode("/", Config::read("Path.skin"));
				$return = implode("/", $new_base) . implode("/", array_pad(array_slice($link_arr, 1), -(count(array_slice($link_arr, 1))+1), ""));
			break;
			
			case "[root]":
				$new_base = explode("/", Config::read("Path.root"));
				$return = implode("/", $new_base) . implode("/", array_pad(array_slice($link_arr, 1), -(count(array_slice($link_arr, 1))+1), ""));
			break;
			
			case "[branch.site]":
				$new_base = explode("/", Config::read("Path.branch"));
				$return = implode("/", $new_base) . implode("/", array_pad(array_slice($link_arr, 1), -(count(array_slice($link_arr, 1))+1), ""));
			break;
			
			case "[branch.skin]":
				$new_base = explode("/", Config::read("Path.branchSkin"));
				$return = implode("/", $new_base) . implode("/", array_pad(array_slice($link_arr, 1), -(count(array_slice($link_arr, 1))+1), ""));
			break;
			
			case "[branch.root]":
				$new_base = explode("/", Config::read("Path.branchRoot"));
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
						$new_base = explode("/", Config::read("Path.root"));
						
						$new_url = array_merge( array_merge($new_base, array_slice($working_uri, 0, ($position+1))), array_pad(array_slice($link_arr, $up_link_count+1), -(count(array_slice($link_arr, $up_link_count+1))), "") );
						
						$return = implode("/",  $new_url );
						break 1;
					}
				}
			break;
		}
		
		$return = str_replace("//", "/", $return);
		
		if (!Config::read("URI.useModRewrite") && !empty($return)) {
			if (substr_count($return, "?", 0) > 1) {
				$return = strrev(preg_replace("/\?/i", "&", strrev($return), (substr_count($return, "?", 0) - 1)));
			}

		}
		
		return $link[1].((!empty($return)) ? $return : $link[2]);
	}
	
	public function linkFix (&$skin) {
		$skin = preg_replace_callback("/(=\"|=\'|=)([\[\]][^(\"|\'|[:space:]|>)]+)/", array($this, "linkCallback"), $skin);
	}
}
?>