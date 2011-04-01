<?php
/**
 * Autoloader Class
 *
 * This is the class that automatically loads all classes in the framework. This class also parses the class name
 * and with that knows where to load the file from.
 *
 *
 * Copyright 2007-2011, NaturalCodeProject (http://www.naturalcodeproject.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright		Copyright 2007-2011, NaturalCodeProject (http://www.naturalcodeproject.com)
 * @package			evergreen
 * @subpackage		lib
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */

namespace Evergreen\Loader;

/**
 * ClassLoader Class
 *
 * This is the class that automatically loads all classes in the framework. This class also parses the class name
 * and with that knows where to load the file from.
 *
 * @package       Evergreen
 * @subpackage    lib/Evergreen
 */
class ClassLoader {
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
	 * Registers the Autoloader class as an autoloader.
	 * 
	 * @access public
	 * @static
	 */
	public static function register() {
		spl_autoload_register(array(__CLASS__, 'loadClass'));
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
	 * Finds the path for a given class name and then includes the file.
	 * 
	 * @access private
	 * @static
	 * @param string $class The name of the class that is to be loaded
	 */
	private static function loadClass($class) {
		if ($file = self::findFilePath($class)) {
			require $file;
		}
	}
	
	/**
	 * Parses a class name and returns a file path to load the file with. 
	 * 
	 * @access private
	 * @static
	 * @param string $class The name of the class that is to be loaded
	 */
	private static function findFilePath($class) {
		if ('\\' == $class[0]) {
			$class = substr($class, 1);
		}
		if (false !== ($pos = strripos($class, '\\'))) {
			$namespace = substr($class, 0, $pos);
			foreach(self::$namespaces as $ns => $dir) {
				if (0 === strpos($namespace, $ns)) {
					$className = substr($class, $pos + 1);
					$file = $dir.DIRECTORY_SEPARATOR.str_replace('\\', DIRECTORY_SEPARATOR, $namespace).DIRECTORY_SEPARATOR.str_replace('_', DIRECTORY_SEPARATOR, $className).'.php';
					if (file_exists($file)) {
						return $file;
					}
				}
			}
			unset($file, $dir);
		} else {
			foreach(self::$prefixes as $prefix => $dir) {
				if (0 === strpos($class, $prefix)) {
					$file = $dir.DIRECTORY_SEPARATOR.str_replace('_', DIRECTORY_SEPARATOR, $class).'.php';
					if (file_exists($file)) {
						return $file;
					}
				}
			}
			unset($file, $dir);
		}
		
		return false;
	}
}
?>