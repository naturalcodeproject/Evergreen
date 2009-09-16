<?php
final class Evergreen {
	function __construct() {
		## Register Autoloader Class ##
		spl_autoload_register(array('AutoLoaders', 'main'));
		
		## Register Error Handler Class ##
		set_error_handler(array("System", "logError"), ini_get("error_reporting"));
		
		## Load Base Configuration ##
		if (file_exists(Config::read("System.physicalPath")."/config/config.php")) {
			// Load in the config.php file
			include(Config::read("System.physicalPath")."/config/config.php");
		} else {
			// Error if the config.php file isnt present in the config directory
			echo "You are missing the configuration file and without it Evergreen cannot run.";
			exit;
		}
		
		## Load Base Errors ##
		if (file_exists(Config::read("System.physicalPath")."/config/errors.php")) {
			// Load in the errors.php file
			include(Config::read("System.physicalPath")."/config/errors.php");
		}
		
		## Check to see if the welcome content is present ##
		if (file_exists(Config::read("System.physicalPath")."/public/welcome.php")) {
			// Load the Designer class
			$designer = new Designer();
			
			// Load the welcome content and save the result to the $welcome_content variable
			ob_start();
				include(Config::read("System.physicalPath")."/public/welcome.php");
			$welcome_content = ob_get_clean();
			
			// Do the Designer class fixes on the $welcome_content variable content
			$designer->doFixes($welcome_content);
			
			// Print out the welcome content
			echo $welcome_content;
			exit;
		}
		
		## URI Managment ##
		Config::processURI();
		
		## Load in Controller ##
		if (Config::read("Branch.name")) {
			## Unload Main Autoloader ##
			spl_autoload_unregister(array('AutoLoaders', 'main'));
			
			## Load Branch Autoloader ##
			spl_autoload_register(array('AutoLoaders', 'branches'));
		}
		
		## Load in the requested controller ##
		if (($controller = System::load(array("name"=>reset(Config::read("URI.working")), "type"=>"controller", "branch"=>Config::read("Branch.name")))) === false) {
			// The controller wasn’t found so trigger an error
			Error::trigger("CONTROLLER_NOT_FOUND");
			exit;
		} else {
			try {
				// Load the view
				$controller->showView();
			} catch(Exception $e) {
				// Load error if something triggered an error
				Error::processError($e);
			}
		}
		

	}
}

class AutoLoaders {
	public static function main($class_name) {
		self::parseClassName($class_name);
		self::baseIncludes($class_name);
		
		## Controller Include ##
		if (file_exists(Config::read("System.physicalPath")."/controllers/{$class_name}.php")) {
			include_once(Config::read("System.physicalPath")."/controllers/{$class_name}.php");
		}
	}
	
	public static function branches($class_name) {
		self::parseClassName($class_name);
		$branch_name = Config::read("Branch.name");
		self::baseIncludes($class_name);
		
		## Branch Controller Include ##
		if (file_exists(Config::read("System.physicalPath")."/branches/{$branch_name}/controllers/{$class_name}.php")) {
			include_once(Config::read("System.physicalPath")."/branches/{$branch_name}/controllers/{$class_name}.php");
		}
	}
	
	public static function baseIncludes($class_name) {
		## Base System Includes ##
		require_once("lib/factory.class.php");
		require_once("lib/system.class.php");
		require_once("lib/config.class.php");
		require_once("lib/error.class.php");
        require_once("lib/db.driver.class.php");
		
		## Other Lib Includes ##
		if (file_exists(Config::read("System.physicalPath")."/lib/{$class_name}.class.php")) {
			require_once(Config::read("System.physicalPath")."/lib/{$class_name}.class.php");
		}
	}
	
	static function parseClassName(&$class_name) {
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