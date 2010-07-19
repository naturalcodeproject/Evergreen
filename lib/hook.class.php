<?php
/**
 * Hook Class
 *
 * Handles adding, removing and calling hooks
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
 * Hook Class
 *
 * Handles adding, removing and calling hooks
 *
 * @package       evergreen
 * @subpackage    lib
 */
class Hook {
	/**
	 * stores all hooks by the location and priority they are called
	 *
	 * @access private
	 * @static
	 */
	private static $hooks = array();
	
	/**
	 * stores which hooks have been run
	 *
	 * @access private
	 * @static
	 */
	private static $run = array();

	/**
	 * adds a hook to be executed
	 *
	 * @access public
	 * @static
	 * @param string $name the location to run the hook at
	 * @param mixed $function either a string of the function to call or an array of the class and function
	 * @param integer $priority the order to call the function. Default is 10. Lower gets called first. Higher last.
	 */
	public static function add($name, $function, $priority = 10) {
		// if an array is passed in then make it a string if it is only size 1
		if (is_array($function) && sizeof($function) == 1) {
			$function = $function[0];
		}
		
		// make sure the function is valid
		if (self::checkFunction($function) === false) {
			return false;
		}

		// make sure variables exists
		if (!isset(self::$hooks[$name])) {
			self::$hooks[$name] = array();
		}
		
		if (!isset(self::$hooks[$name][$priority])) {
			self::$hooks[$name][$priority] = array();
		}
		
		// add the hook to the array
		self::$hooks[$name][$priority][] = $function;

		return true;
	}

	/**
	 * removes a hook so that it isn't executed
	 *
	 * @access public
	 * @static
	 * @param string $name the location to run the hook at
	 * @param mixed $function either a string of the function to call or an array of the class and function
	 * @param integer $priority the order to call the function. Default is 10. Lower gets called first. Higher last. If not specified then it will remove the hook from any priority
	 */
	public static function remove($name, $function = '', $priority = null) {
		// if the priority is set then it is easier to remove the hook
		if (!empty($function) && $priority !== null) {
			if (empty(self::$hooks[$name][$priority])) {
				return;
			}

			// find the keys for the functions
			$keys = array_keys(self::$hooks[$name][$priority], $function);

			// loop through and remove them
			foreach($keys as $key) {
				unset(self::$hooks[$name][$priority][$key]);
			}
		} else {
			if (empty(self::$hooks[$name])) {
				return;
			}

			// loop through all possible hooks
			foreach(self::$hooks[$name] as $priority => &$functions) {
				// get the keys where the functions are
				$keys = array_keys($functions, $function);

				foreach($keys as $key) {
					unset($functions[$key]);
				}
			}
		}
	}

	/**
	 * executes all hooks on a section
	 *
	 * @access public
	 * @static
	 * @param string $name the location of the hooks that are being run
	 * @param array $args the arguments to pass on to the hooks
	 */
	public static function call($name, $args = array()) {
		$hooks = &self::get($name);

		foreach($hooks as $priority => $functions) {
			foreach($functions as $function) {
				if (self::checkFunction($function)) {
					self::$run[$name][$priority][] = $function;
					
					call_user_func_array($function, $args);
				}
			}
		}
	}

	/**
	 * Alias for Hook::call()
	 *
	 * @see Hook::call()
	 */
	public static function invoke($name, $args = array()) {
		self::call($name, $args);
	}

	/**
	 * gets the hooks for location
	 *
	 * @access public
	 * @static
	 * @param string $name the location of the hooks to get
	 * @return array all of the hooks for the location
	 */
	public static function &get($name) {
		if (!empty(self::$hooks[$name])) {
			asort(self::$hooks[$name]);

			return self::$hooks[$name];
		} else {
			$blank = array();
			return $blank;
		}
	}

	/**
	 * returns all of the hooks
	 *
	 * @access public
	 * @static
	 * @return array all of the hooks that have been set
	 */
	public static function &getAll() {
		return self::$hooks;
	}
	
	/**
	 * checks to see if a hook has run
	 *
	 * @access public
	 * @static
	 * @param string $name the name of the hook to check
	 * @param string $function the function to check
	 * @param integer $priority the priority
	 */
	public static function hasRun($function = '', $priority = null) {
		$hooks = self::$run;
		
		foreach($hooks as $location => $priorities) {
			foreach($priorities as $priorit => $functions) {
				if (empty($priority) || $priorit == $priority) {
					if (in_array($function, $functions)) {
						return true;
					}
				}
			}
		}
		
		return false;
	}

	/**
	 * checks to make sure a function is valid
	 *
	 * @access private
	 * @static
	 * @param mixed $function a string of the function or an array of the class/function to check to make sure it exists
	 */
	private static function checkFunction($function) {
		if (is_array($function)) {
			return method_exists($function[0], $function[1]);
		}

		return function_exists($function);
	}
}