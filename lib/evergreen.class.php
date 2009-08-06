<?php
final class Evergreen {
	function __construct() {
		## Register Autoloader Class ##
		spl_autoload_register(array('AutoLoaders', 'main'));
		
		## Register Error Handler Class ##
		set_error_handler(array("System", "log_error"), ini_get('error_reporting'));
		
		## URI Managment ##
		Factory::get_config()->check_uri();
		
		## Load in Controller ##
		if (Factory::get_config()->get_branch_name()) {
			## Unload Main Autoloader ##
			spl_autoload_unregister(array('AutoLoaders', 'main'));
			
			## Load Branch Autoloader ##
			spl_autoload_register(array('AutoLoaders', 'branches'));
		}
		
		if (($controller = System::load(array("name"=>reset(Factory::get_config()->get_working_uri()), "type"=>"controller", "branch"=>Factory::get_config()->get_branch_name()))) === false) {
			Error::load404();
			return false;
		} else {
			try {
				$controller->show_view();
			} catch(Exception $e) {
				if ($e->getCode() == 404) {
					Error::load404();
					return false;
				} else {
					echo "Caught exception: ".Error::getMessage();
				}
			}
		}
	}
}

class AutoLoaders {
	static function main($class_name) {
		self::parse_class_name($class_name);
		self::base_includes($class_name);
		
		## Controller Include ##
		if (file_exists(Factory::get_config()->get_base_path()."/controllers/{$class_name}.php")) {
			include_once("controllers/{$class_name}.php");
		}
	}
	
	static function branches($class_name) {
		self::parse_class_name($class_name);
		$branch_name = Factory::get_config()->get_branch_name();
		self::base_includes($class_name);
		
		## Branch Controller Include ##
		if (file_exists(Factory::get_config()->get_base_path()."/branches/{$branch_name}/controllers/{$class_name}.php")) {
			include_once("branches/{$branch_name}/controllers/{$class_name}.php");
		}
	}
	
	static function base_includes($class_name) {
		## Base System Includes ##
		require_once("lib/factory.class.php");
		require_once("lib/system.class.php");
		require_once("lib/config.class.php");
		require_once("lib/error.class.php");
		
		## Other Lib Includes ##
		if (file_exists(Factory::get_config()->get_base_path()."/lib/{$class_name}.class.php")) {
			require_once("lib/{$class_name}.class.php");
		}
	}
	
	static function parse_class_name(&$class_name) {
		$class_name[0] = strtolower($class_name[0]);
		
		$class_name = explode("_", $class_name);
		if (count($class_name) > 1) {
			array_pop($class_name);
			$class_name = implode("_", $class_name);
		} else {
			$class_name = $class_name[0];
		}
		
		if (!ctype_lower($class_name)) {
			$new_name = '';
			for($i = 0; $i < strlen($class_name); $i++) {
				$char = $class_name[$i];
				if (ctype_upper($char)) {
					$new_name .= '.' . strtolower($char);
				} else {
					$new_name .= $char;
				}
			}
			$class_name = $new_name;
		}
	}
}
?>