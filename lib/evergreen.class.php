<?php
final class Evergreen {
	function __construct() {
		$starttime = explode(' ', microtime());
		$starttime = $starttime[1] + $starttime[0];
		
		try {
			## Register Autoloader Class ##
			spl_autoload_register(array('AutoLoaders', 'main'));
			
			## Register Error Handler Class ##
			set_error_handler(array("System", "logError"), ini_get("error_reporting"));
			
			## Load Base Configuration ##
			if (file_exists(Config::read("Path.physical")."/config/config.php")) {
				// Load in the config.php file
				include_once(Config::read("Path.physical")."/config/config.php");
			} else {
				echo "You are missing the configuration file and without it Evergreen cannot run.";
				exit;
			}
			
			## Load Base Errors ##
			if (file_exists(Config::read("Path.physical")."/config/errors.php")) {
				// Load in the errors.php file
				include(Config::read("Path.physical")."/config/errors.php");
			}
			
			## Check to see if the welcome content is present ##
			if (file_exists(Config::read("Path.physical")."/public/welcome.php")) {
				// Load the Designer class
				$designer = new Designer();
				
				// Load the welcome content and save the result to the $welcome_content variable
				ob_start();
					include(Config::read("Path.physical")."/public/welcome.php");
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
			if (($controller = System::load(array("name"=>reset(Config::loadableURI(Config::read("URI.working"))), "type"=>"controller", "branch"=>Config::read("Branch.name")))) === false) {
				// The controller wasn't found so trigger an error
				Error::trigger("CONTROLLER_NOT_FOUND");
				exit;
			} else {
				// Load the view
				$controller->_showView();
				
			}
		} catch(Exception $e) {
			// Load error if something triggered an error
			Error::processError($e);
		}

		if (Config::read('System.displayPageLoadInfo') == true) {
			$mtime = explode(' ', microtime());
			$totaltime = $mtime[0] + $mtime[1] - $starttime;
			echo sprintf('Time : %.3fs seconds', $totaltime);
			
			if (function_exists('memory_get_usage')) {
				// php has to be compiled with --enable-memory-limit for this to exist
				// prior to version 5.2.1
				echo ' | Memory Used : ' . self::convertBytes(memory_get_usage(true));
			}
			
			if (function_exists('memory_get_peak_usage')) {
				// php 5.2+
				echo ' | Peak Memory Used: ' . self::convertBytes(memory_get_peak_usage(true));
			}
		}
	}
	
	public function convertBytes($size)
	{
		$unit = array('b','kb','mb','gb','tb','pb');
		return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
	}
}

class AutoLoaders {
	public static function main($class_name) {
		$class_name = self::parseClassName($class_name);
		self::baseIncludes($class_name);
		
		## Controller Include ##
		if (file_exists(Config::read("Path.physical")."/controllers/{$class_name}.php")) {
			include_once(Config::read("Path.physical")."/controllers/{$class_name}.php");
		}
	}
	
	public static function branches($class_name) {
		$class_name = self::parseClassName($class_name);
		$branch_name = Config::read("Branch.name");
		self::baseIncludes($class_name);
		
		## Branch Controller Include ##
		if (file_exists(Config::read("Path.physical")."/branches/{$branch_name}/controllers/{$class_name}.php")) {
			include_once(Config::read("Path.physical")."/branches/{$branch_name}/controllers/{$class_name}.php");
		}
	}
	
	public static function baseIncludes($class_name) {
		## Base System Includes ##
		require_once("lib/config.class.php");
		
		## Other Lib Includes ##
		if (file_exists(Config::read("Path.physical")."/lib/{$class_name}.class.php")) {
			include_once(Config::read("Path.physical")."/lib/{$class_name}.class.php");
		}
	}
	
	static function parseClassName($class_name) {
		$class_name = implode('_', array_slice(explode('_', $class_name), 0, 1));
		return strtolower(ltrim(preg_replace('/[A-Z]/', '.$0', $class_name), '.'));
	}
}
?>