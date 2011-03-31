<?php
/**
 * Autoloader Class
 *
 * This is the class that automatically loads all classes in the framework. This class also parses the class name
 * and with that knows where to load the file from based on the framework's predefined naming scheme.
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
 * Autoloader Class
 *
 * This is the class that automatically loads all classes in the framework. This class also parses the class name
 * and with that knows where to load the file from based on the framework's predefined naming scheme.
 *
 * @package       evergreen
 * @subpackage    lib
 */
class AutoLoader {
	/**
	 * Holds an array of all the registered prefixes to load.
	 * 
	 * @access private
	 * @static
	 * @var array
	 */
	private static $prefixes = array();
	
	/**
	 * Holds an array of all the registered namespaces to load.
	 * 
	 * @access private
	 * @static
	 * @var array
	 */
	private static $namespaces = array();
	
	/**
	 * Holds an array of all the registered files to load.
	 * 
	 * @access private
	 * @static
	 * @var array
	 */
	private static $files = array();
	
	/**
	 * Holds an array of all the registered directories to load.
	 * 
	 * @access private
	 * @static
	 * @var array
	 */
	private static $dirs = array();
	
	/**
	 * Registers the Autoloader class as an autoloader.
	 * 
	 * @access public
	 * @static
	 */
	public static function register() {
		spl_autoload_register(array('AutoLoader', 'evergreenLoader'));
		spl_autoload_register(array('AutoLoader', 'generalLoader'));
	}
	
	/**
	 * Loads in the registered autoload files.
	 * 
	 * @access public
	 */
	public static function loadFiles() {
		foreach(self::$files as $file) {
			if (file_exists($file)) {
				require_once $file;
			} else {
				throw new EvergreenException('FILE_NOT_FOUND', array('messageArgs'=>array('file'=>$file)));
			}
		}
	}
	
	/**
	 * Registers an array of directories to load from.
	 * 
	 * @access public
	 * @static
	 * @param array $files An array of directories to load from
	 */
	public static function registerDirectories($dirs) {
		self::$dirs = array_merge(self::$dirs, $dirs);
	}
	
	/**
	 * Registers a directory to load from.
	 * 
	 * @access public
	 * @static
	 * @param string $identifier The unique identifier of the directory to load
	 * @param string $file The path to the directory to load from
	 */
	public static function registerDirectory($identifier, $dir) {
		self::$dirs[$identifier] = $dir;
	}
	
	/**
	 * Registers an array of files to load.
	 * 
	 * @access public
	 * @static
	 * @param array $files An array of the files to register
	 */
	public static function registerFiles($files) {
		self::$files = array_merge(self::$files, $files);
	}
	
	/**
	 * Registers a file to load.
	 * 
	 * @access public
	 * @static
	 * @param string $identifier The unique identifier of the file to load
	 * @param string $file The path to the file to load
	 */
	public static function registerFile($identifier, $file) {
		self::$files[$identifier] = $file;
	}
	
	/**
	 * Registers an array of prefixes.
	 * 
	 * @access public
	 * @static
	 * @param array $prefixes An array of the prefixes to register
	 */
	public static function registerPrefixes($prefixes) {
		self::$prefixes = array_merge(self::$prefixes, $prefixes);

	}
	
	/**
	 * Registers an prefix.
	 * 
	 * @access public
	 * @static
	 * @param string $prefix The prefix to match
	 * @param string $dir The directory to load from
	 */
	public static function registerPrefix($prefix, $dir) {
		self::$prefixes[$prefix] = $dir;

	}
	
	/**
	 * Registers an array of namespaces.
	 * 
	 * @access public
	 * @static
	 * @param array $namespaces An array of the namespaces to register
	 */
	public static function registerNamespaces($namespaces) {
		self::$namespaces = array_merge(self::$namespaces, $namespaces);

	}
	
	/**
	 * Registers a namespace.
	 * 
	 * @access public
	 * @static
	 * @param string $namespace The namespace name
	 * @param string $dir The directory to load the namespace from
	 */
	public static function registerNamespace($namespace, $dir) {
		self::$namespace[$namespace] = $dir;

	}
	
	/**
	 * The initial autoloader that runs before all other autoloaders and makes sure that the shutdown autoloader is always the last autoloader.
	 * 
	 * @access public
	 * @static
	 * @param string $class_name The name of the class that is to be loaded
	 */
	/*
	Decided to let PHP throw the error for missing class
	
	public static function initialLoader($className) {
		$autoloadFunctions = spl_autoload_functions();
		$last = end($autoloadFunctions);
		if (!is_array($last) || ((isset($last[0]) && $last[0] != 'AutoLoader') || (isset($last[1]) && $last[1] != 'shutdownLoader'))) {
			spl_autoload_unregister(array('AutoLoader', 'shutdownLoader'));
			spl_autoload_register(array('AutoLoader', 'shutdownLoader'));
		}
		unset($autoloadFunctions, $last);
	}*/
	
	/**
	 * The shutdown autoloader used to be a catchall for when classes cant be found.
	 * 
	 * @access public
	 * @static
	 * @param string $class_name The name of the class that is to be loaded
	 */
	/* 
	Decided to let PHP throw the error for missing class
	
	public static function shutdownLoader($className) {
		if (!class_exists($className, false) && !interface_exists($className, false)) {
			eval(sprintf('
					class %1$s {
						public function __construct() {
							throw new EvergreenException(\'CLASS_NOT_FOUND\', array(\'messageArgs\'=>array(\'class\'=>\'%1$s\')));
						}
						public function __call($m, $a) {
							throw new EvergreenException(\'CLASS_NOT_FOUND\', array(\'messageArgs\'=>array(\'class\'=>\'%1$s\')));
						}
						public static function __callStatic($m, $a) {
							throw new EvergreenException(\'CLASS_NOT_FOUND\', array(\'messageArgs\'=>array(\'class\'=>\'%1$s\')));
						}
					}', $className));
		}
	}*/
	
	/**
	 * The general autoloader that loads in defined namespaces, prefixes, and directories.
	 * 
	 * @access public
	 * @static
	 * @param string $class_name The name of the class that is to be loaded
	 */
	public static function generalLoader($className) {
		if (false !== ($pos = strripos($className, '\\'))) {
			$namespace = substr($className, 0, $pos);
			foreach(self::$namespaces as $ns => $dir) {
				if (0 === strpos($namespace, $ns)) {
					$className = substr($className, $pos + 1);
					$file = $dir.'/'.str_replace('\\', '/', $namespace).'/'.str_replace('_', '/', $className).'.php';
					if (file_exists($file)) {
						require $file;
					}
				}
			}
			unset($file, $dir);
		} else {
			foreach(self::$prefixes as $prefix => $dir) {
				if (0 === strpos($className, $prefix)) {
					$file = $dir.'/'.str_replace('_', '/', $className).'.php';
					if (file_exists($file)) {
						require $file;
					}
				}
			}
			unset($file, $dir);
			
			if (!class_exists($className, false) && !interface_exists($className, false)) {
				foreach(self::$dirs as $dir) {
					
					// try to load in a file preserving case
					if (!class_exists($className, false) && !interface_exists($className, false)) {
						$file = $dir.'/'.$className.'.php';
						if (file_exists($file)) {
							require $file;
						}
					}
					
					// try to load in a file based on a lowercased class
					if (!class_exists($className, false) && !interface_exists($className, false)) {
						$file = $dir.'/'.strtolower($className).'.php';
						if (file_exists($file)) {
							require $file;
						}
					}
					
					// try to load in file based on adding a . before uppercased characters and then lowercasing
					if (!class_exists($className, false) && !interface_exists($className, false)) {
						$file = $dir.'/'.strtolower(trim(preg_replace('/[A-Z]/', '.$0', $className), '.')).'.php';
						if (file_exists($file)) {
							require $file;
						}
					}
					
					// try to load in file based on adding a _ before uppercased characters and then lowercasing
					if (!class_exists($className, false) && !interface_exists($className, false)) {
						$file = $dir.'/'.strtolower(trim(preg_replace('/[A-Z]/', '_$0', $className), '_')).'.php';
						if (file_exists($file)) {
							require $file;
						}
					}
				}
				unset($file, $dir);
				
				// try to load all the registered files again incase some more were registered later
				if (!class_exists($className, false) && !interface_exists($className, false)) {
					self::loadFiles();
				}
			}
		}
	}
	
	/**
	 * The evergreen autoloader class that loads in an evergreen class using the evergreen naming convention.
	 * 
	 * @access public
	 * @static
	 * @param string $class_name The name of the class that is to be loaded
	 */
	public static function evergreenLoader($className) {
		// if class already exists then dont continue
		if (class_exists($className, false) || interface_exists($className, false)) {
		   return true;
		}
		
		// setup the config
		if (!class_exists('Config', false)) {
			include(dirname(__FILE__) .'/config.class.php');
			Config::setup();
		}
		
		// parse the incoming class name and assign the resulting array to $class
		$class = self::parseClassName($className);
		
		// run through $class and find what needs to be loaded
		if (isset($class['type'])) {
			$basePath = Reg::get("Path.physical").((!empty($class['branch'])) ? '/branches/'.$class['branch'] : '');
			if ($class['type'] == 'controller' && file_exists($basePath.'/controllers/'.$class['class'].'.php')) {
				//controller include
				include_once($basePath.'/controllers/'.$class['class'].'.php');
			} else if ($class['type'] == 'model' && file_exists($basePath.'/models/'.$class['class'].'.php')) {
				// model include
				include_once($basePath.'/models/'.$class['class'].'.php');
			} else if ($class['type'] == 'helper' && file_exists($basePath.'/helpers/'.$class['class'].'.php')) {
				// helper include
				include_once($basePath.'/helpers/'.$class['class'].'.php');
			} else if ($class['type'] == 'plugin' && file_exists($basePath.'/plugins/'.$class['class'].'.php')) {
				// plugin include
				include_once($basePath.'/plugins/'.$class['class'].'.php');
			} else if ($class['type'] == 'driver') {
				// model driver include
				if (isset($class['specificDriver'])) {
					// check if driver needs to be loaded from a branch
					if (!empty($class['branch'])) {
						$branchDriverPath = Reg::get("Path.physical").'/branches/'.$class['branch'].'/config/drivers/'.strtolower(str_replace('_', '.', $class['original'])).'.class.php';
						if (file_exists($branchDriverPath)) {
							include_once($branchDriverPath);
						}
						unset($branchDriverPath);
					}
					
					// load main driver if not loading from a branch
					$mainDriverPath = Reg::get("Path.physical").'/config/drivers/'.strtolower(str_replace('_', '.', $class['original'])).'.class.php';
					if (!class_exists($class['original'], false) && file_exists($mainDriverPath)) {
						include_once($mainDriverPath);
					}
					unset($mainDriverPath);
				} else {
					// other db class includes
					if (file_exists(Reg::get("Path.physical").'/lib/'.strtolower(str_replace('_', '.', $class['original'])).'.class.php')) {
						include_once(Reg::get("Path.physical").'/lib/'.strtolower(str_replace('_', '.', $class['original'])).'.class.php');
					}
				}
			}
			unset($basePath);
		} else {
			// lib file includes
			if (file_exists(Reg::get("Path.physical").'/lib/'.$class['class'].'.class.php')) {
				include_once(Reg::get("Path.physical").'/lib/'.$class['class'].'.class.php');
			}
		}
		
		// after running through all the loads if the desired class now still doesnÕt exist then create a dummy class that will throw an error
		if (class_exists($class['original'], false) || interface_exists($class['original'], false)) {
			// if the db class was called then run setup
			if (strtolower($class['original']) == 'db') {
				DB::setup();
			}
			
			// if the class has a type and it is a helper or a plugin check for versioning and mode requirements
			if (isset($class['type']) && in_array($class['type'], array('helper', 'plugin'))) {
				$classVars = get_class_vars($class['original']);
				if (isset($classVars['requiredSystemMode']) && $classVars['requiredSystemMode'] != Reg::get("System.mode")) {
					// The system does not have the required mode so don't load the object
					throw new EvergreenException("REQUIRED_SYSTEM_MODE", array('messageArgs'=>array('name'=>$class['original'], 'type'=>ucwords($class['type']), 'class-required-mode'=>$classVars['requiredSystemMode'])));
				}

				if (isset($classVars['minimumSystemVersion']) && !version_compare(Reg::get("System.version"), $classVars['minimumSystemVersion'], ">=")) {
					// The system version is lower than the object's required minimum so don't load the object
					throw new EvergreenException("MINIMUM_SYSTEM_VERSION", array('messageArgs'=>array('name'=>', '.$class['original'].',', 'type'=>ucwords($class['type']), 'class-required-version'=>$classVars['minimumSystemVersion'])));
				}

				if (isset($classVars['maximumSystemVersion'])  && !version_compare(Reg::get("System.version"), $classVars['maximumSystemVersion'], "<=")) {
					// The system version is higher than the object's required maximum so don't load the object
					throw new EvergreenException("MAXIMUM_SYSTEM_VERSION", array('messageArgs'=>array('name'=>', '.$class['original'].',', 'type'=>ucwords($class['type']), 'class-required-version'=>$classVars['maximumSystemVersion'])));
				}
				unset($classVars);
			}
		}
		
		unset($class);
	}
	
	/**
	 * Parses a class name and returns an array with the original name, target class name, type/driver, and branch.
	 * 
	 * @access private
	 * @static
	 * @param string $className The name of the class that needs to be parsed
	 * @return array
	 */
	private static function parseClassName($className) {
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
		
		unset($classType, $branchName, $classInfo, $className, $classPieces);
		
		return $classArr;
	}
}
?>