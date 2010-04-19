<?php
final class Evergreen {
	function __construct() {
		$starttime = explode(' ', microtime());
		$starttime = $starttime[1] + $starttime[0];
		
		try {
			## Register Autoloader Class ##
			spl_autoload_register(array('AutoLoaders', 'main'));
			
			## Register Error Handler Class ##
			set_error_handler(array("Error", "logError"), ini_get("error_reporting"));
			
			## Load Base Configuration ##
			if (file_exists(Reg::get("Path.physical")."/config/config.php")) {
				// Load in the config.php file
				include_once(Reg::get("Path.physical")."/config/config.php");
			} else {
				echo "You are missing the configuration file and without it Evergreen cannot run.";
				exit;
			}
			
			## Load Base Errors ##
			if (file_exists(Reg::get("Path.physical")."/config/errors.php")) {
				// Load in the errors.php file
				include(Reg::get("Path.physical")."/config/errors.php");
			}
			
			## Check to see if the welcome content is present ##
			if (file_exists(Reg::get("Path.physical")."/public/welcome.php")) {
				// Load the welcome content
				include(Reg::get("Path.physical")."/public/welcome.php");
				exit;
			}
			
			## Process the URI ##
			Config::processURI();
			
			## Load in the requested controller ##
			$load['name'] = Config::uriToClass(Reg::get("URI.working.controller"));
			if (Reg::hasVal("Branch.name")) {
				$load['branch'] = Config::uriToClass(Reg::get("Branch.name"));
			}
			$load['type'] = 'Controller';
			$load = implode('_', $load);
			
			$controller = new $load();
			$controller->_showView();
		} catch(Exception $e) {
			// Load error if something triggered an error
			Error::processError($e);
		}

		if (Reg::get('System.displayPageLoadInfo') == true) {
			$mtime = explode(' ', microtime());
			$totaltime = $mtime[0] + $mtime[1] - $starttime;
			echo sprintf('Time : %.3fs seconds', $totaltime);
			
			if (class_exists('DB', false)) {
				echo ' | Queries Executed : ' . DB::queryCount();
			}
			
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
		## Don't Run if Class Exists ##
		if (class_exists($class_name, false)) {
		   return true;
		}
		
		## Base System Includes ##
		if (!class_exists('Config', false)) {
			include("lib/config.class.php");
			Config::setup();
		}
		
		## Parse Class Name ##
		$class = self::parseClassName($class_name);
		
		if (isset($class['type'])) {
			$basePath = Reg::get("Path.physical").((!empty($class['branch'])) ? "/branches/".$class['branch'] : "");
			if ($class['type'] == 'controller' && file_exists($basePath."/controllers/{$class['class']}.php")) {
				## Controller Include ##
				include_once($basePath."/controllers/{$class['class']}.php");
			} else if ($class['type'] == 'model' && file_exists($basePath."/models/{$class['class']}.php")) {
				## Model Include ##
				include_once($basePath."/models/{$class['class']}.php");
			} else if ($class['type'] == 'helper' && file_exists($basePath."/helpers/{$class['class']}.php")) {
				## Helper Include ##
				include_once($basePath."/helpers/{$class['class']}.php");
			} else if ($class['type'] == 'plugin' && file_exists($basePath."/plugins/{$class['class']}.php")) {
				## Plugin Include ##
				include_once($basePath."/plugins/{$class['class']}.php");
			} else if ($class['type'] == 'driver') {
				## Driver Include ##
				if (isset($class['specificDriver'])) {
					if (!empty($class['branch'])) {
						$branchDriverPath = Reg::get("Path.physical")."/branches/".$class['branch']."/config/drivers/".strtolower(str_replace('_', '.', $class['original'])).".class.php";
						if (file_exists($branchDriverPath)) {
							include_once($branchDriverPath);
						}
						unset($branchDriverPath);
					}
					
					$mainDriverPath = Reg::get("Path.physical")."/config/drivers/".strtolower(str_replace('_', '.', $class['original'])).".class.php";
					if (!class_exists($class['original'], false) && file_exists($mainDriverPath)) {
						include_once($mainDriverPath);
					}
					unset($mainDriverPath);
				} else {
					if (file_exists(Reg::get("Path.physical")."/lib/".strtolower(str_replace('_', '.', $class['original'])).".class.php")) {
						include_once(Reg::get("Path.physical")."/lib/".strtolower(str_replace('_', '.', $class['original'])).".class.php");
					}
				}
			}
			unset($basePath);
		} else {
			## Lib Includes ##
			if (file_exists(Reg::get("Path.physical")."/lib/{$class['class']}.class.php")) {
				include_once(Reg::get("Path.physical")."/lib/{$class['class']}.class.php");
			}
		}
		
		if (!class_exists($class['original'], false)) {
			if (isset($class['type'])) {
				if ($class['type'] == 'driver' && isset($class['specificDriver'])) {
					$class['type'] = 'model_driver';
				}
				eval(sprintf('
					class %1$s{
						public function __construct() {
							Error::trigger("%2$s_NOT_FOUND");
						}
						public function __call($m, $a) {
							Error::trigger("%2$s_NOT_FOUND");
						}
						public static function __callStatic($m, $a) {
							Error::trigger("%2$s_NOT_FOUND");
						}
					}', $class['original'], strtoupper($class['type'])));
			} else {
				eval(sprintf('
					class %1$s{
						public function __construct() {
							Error::trigger("Class \'%1$s\' not found", array("code"=>"GEN"));
						}
						public function __call($m, $a) {
							Error::trigger("Class \'%1$s\' not found", array("code"=>"GEN"));
						}
						public static function __callStatic($m, $a) {
							Error::trigger("Class \'%1$s\' not found", array("code"=>"GEN"));
						}
					}', $class['original']));
			}
		} else {
			if (strtolower($class['original']) == 'db') {
				DB::setup();
			}
			if (isset($class['type']) && in_array($class['type'], array('helper', 'plugin'))) {
				$classVars = get_class_vars($class['original']);
				if (isset($classVars['requiredSystemMode']) && $classVars['requiredSystemMode'] != Reg::get("System.mode")) {
					// The system does not have the required mode so don't load the object
					Error::trigger("REQUIRED_SYSTEM_MODE", array('messageArgs'=>array('name'=>$class['original'], 'type'=>ucwords($class['type']), 'class-required-mode'=>$classVars['requiredSystemMode'])));
				}

				if (isset($classVars['minimumSystemVersion']) && !version_compare(Reg::get("System.version"), $classVars['minimumSystemVersion'], ">=")) {
					// The system version is lower than the object's required minimum so don't load the object
					Error::trigger("MINIMUM_SYSTEM_VERSION", array('messageArgs'=>array('name'=>', '.$class['original'].',', 'type'=>ucwords($class['type']), 'class-required-version'=>$classVars['minimumSystemVersion'])));
				}

				if (isset($classVars['maximumSystemVersion'])  && !version_compare(Reg::get("System.version"), $classVars['maximumSystemVersion'], "<=")) {
					// The system version is higher than the object's required maximum so don't load the object
					Error::trigger("MAXIMUM_SYSTEM_VERSION", array('messageArgs'=>array('name'=>', '.$class['original'].',', 'type'=>ucwords($class['type']), 'class-required-version'=>$classVars['maximumSystemVersion'])));
				}
				unset($classVars);
			}
		}
		
		unset($class);
	}
	
	static function parseClassName($className) {
		$classArr = array( 'original'=>$className );
		$classPieces = explode('_', $classArr['original']);
		if (count($classPieces) > 1) {
			$classType = strtolower(reset(array_slice($classPieces, -1)));
			if (in_array($classType, array('controller', 'model', 'helper', 'plugin', 'driver'))) {
				$classArr['type'] = $classType;
				array_pop($classPieces);
			}
			if (count($classPieces) > 1) {
				if (isset($classArr['type']) && $classArr['type'] == 'driver') {
					$classArr['specificDriver'] = strtolower(array_pop($classPieces));
				} else {
					$classArr['branch'] = strtolower(array_pop($classPieces));
				}
				$className = $classPieces;
			} else {
				$className = $classPieces;
			}
		} else {
			$className = $classPieces;
		}
		$classArr['class'] = preg_replace('/^d\.b/', 'db', strtolower(trim(preg_replace('/[A-Z]/', '.$0', implode('_', $className)), '.')));
		
		unset($classType);
		unset($branchName);
		unset($classInfo);
		unset($className);
		unset($classPieces);
		
		return $classArr;
	}
}
?>