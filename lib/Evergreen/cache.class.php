<?php
/**
 * Cache Class
 *
 * Handles storing and getting data in a cache
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
 * Cache Class
 *
 * Handles storing and getting data in a cache
 *
 * @package       evergreen
 * @subpackage    lib
 */
class Cache {
	/**
	 * Loads data from the cache. If the cache doesn't exist or  it has expired then it
	 * regenerates and stores the data
	 * 
	 * @access public
	 * @static
	 * @param array $data_function An array of the class and function that returns the data to cache
	 * @param string $name An optional name to get/store the cache under
	 * @param integer $expires How long the cache is valid for in seconds
	 * @param array $extra_args Arguments to pass into the function that gets the data
	 * @return mixed
	 */
	public static function get($data_function, $name = '', $expires = 0, $extra_args = array()) {
		if (Reg::get('Cache.enabled') != true) {
			return self::callFunction($data_function, $extra_args);
		}
		
		$file = self::getFile($data_function, $name);

		// see if the cache file exists
		if (file_exists($file)) {
			@include($file);
			
			// make sure the data exists and is still valid
			if (isset($data) && !empty($created) && time() - $created < self::getExpires($expires)) {
				// the cache is valid, return it
				return $data;
			}
		}
		
		// need to re/build the cache
		return self::set($data_function, $name, $expires, $extra_args);
	}
	
	/**
	 * Loads data from the cache. If the cache doesn't exist or  it has expired then it
	 * regenerates and stores the data
	 * 
	 * @access public
	 * @static
	 * @param array $data_function An array of the class and function that returns the data to cache
	 * @param string $name An optional name to get/store the cache under
	 * @param integer $expires How long the cache is valid for in seconds
	 * @param array $extra_args Arguments to pass into the function that gets the data
	 * @return boolean True/false if it worked
	 */
	public static function set($data_function, $name = '', $expires = 0, $extra_args = array()) {
		if (Reg::get('Cache.enabled') != true) {
			return false;
		}
		
		$data = self::callFunction($data_function, $extra_args);
		$file = self::getFile($data_function, $name);
		
		// make sure file is writeable
		if (self::isWritable($file) === false) {
			// file was not writeable so just return the data
			// should throw some sort of warning
			return $data;
		}
		
		@touch($file);
		@file_put_contents($file, '<?php
// cache file for ' . self::getClassName($data_function) . '::' . $data_function[1] . '-' . $name . '
// created ' . date('r') . '
$created = ' . time() . ';
$data = ' . var_export($data, true) . ';');
		
		return $data;
	}
	
	/**
	 * returns the full path and name of the cache file to use
	 *
	 * @access private
	 * @static
	 * @param array $data_function An array of the class and function that returns the data to cache
	 * @param string $name An optional name to get/store the cache under
	 * @return string Full path and filename of the cache file
	 */
	private static function getFile($data_function, $name = '') {
		$class = self::getClassName($data_function);
		
		// name of the cache file is a md5 hash of the class, function and name
		$filename = 'cache_' . md5($class . '::' . $data_function[1] . '-' . $name) . '.php';
		
		return self::getPath() . '/' . $filename;
	}
	
	/**
	 * Gets the path to where the cache is being stored
	 *
	 * @access private
	 * @static
	 * @return string
	 */
	private static function getPath() {
		$path = Reg::get('Cache.path');
		
		if (empty($path)) {
			$path = Reg::get('Path.physical') . '/cache';
		}
		
		return $path;
	}
	
	/**
	 * Gets how many seconds for an item to expire
	 *
	 * @access private
	 * @static
	 * @param integer $expires How long in seconds for it to expire. If this is set then it is used. If not then it goes to the default
	 * @return integer
	 */
	private static function getExpires($expires = 0) {
		if (!empty($expires)) {
			return $expires;
		}
		
		$expires = Reg::get('Cache.expires');
		
		if (empty($expires)) {
			$expires = 3600; // one hour
		}	
		return $expires;
	}
	
	/**
	 * Checks to see if a file is writable
	 *
	 * @access private
	 * @static
	 * @param string $file the file to check
	 * @return boolean
	 */
	private static function isWritable($file) {
		return is_writable(dirname($file));
	}
	
	/**
	 * Checks the class name if an object is passed in
	 *
	 * @access private
	 * @static
	 * @param array $data_function Class and function name
	 * @return string
	 */
	private static function getClassName($data_function) {
		// if it is an object then need to get the string name of the function
		if (is_object($data_function[0])) {
			$class = get_class($data_function[0]);
		} else {
			$class = $data_function[0];
		}
		
		return $class;
	}
	
	/**
	 * Calls the function to get the data
	 *
	 * @access private
	 * @static
	 * @param array $function An array of the class and function that returns the data to cache
	 * @param array $extra_args Arguments to pass into the function that gets the data
	 * @return mixed
	 */
	private static function callFunction($function, $extra_args = array()) {
		// make sure the method exists
		if (method_exists($function[0], $function[1]) === false) {
			return false;
		}
		
		return call_user_func_array($function, $extra_args);
	}
	
	/**
	 * deletes all cache files
	 *
	 * @access public
	 * @static
	 */
	public static function clear() {
		if (Reg::get('Cache.enabled') != true) {
			return;
		}
		
		$files = glob(self::getPath() . '/cache_*.php');
		
		foreach($files as $file) {
			unlink($file);
		}
	}
	
	/**
	 * calculates the filesize of the cache
	 *
	 * @access public
	 * @static
	 * @return string
	 */
	public static function getFilesize() {
		
		if (Reg::get('Cache.enabled') != true) {
			return 'Disabled';
		}
		
		$size = 0;
		$files = glob(self::getPath() . '/cache_*.php');
		
		foreach ($files as $file) {
			
			$size += filesize($file);
		}
		
		return self::formatFilesize($size);
	}
	
	/**
	 * formats a float into a human-readable filesize
	 *
	 * @access pirvate
	 * @static
	 * @return string
	 */
	private static function formatFilesize($size = 0) {
		
		$sizes = array(' Bytes', ' KB', ' MB', ' GB', ' TB', ' PB', ' EB', ' ZB', ' YB');
		
		if ($size == 0) return '0' . $sizes[0];
		
		return (round($size/pow(1024, ($i = floor(log($size, 1024)))), $i > 1 ? 2 : 0) . $sizes[$i]);
	}
}