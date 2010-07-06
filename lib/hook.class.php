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
 * @version			$Revision: 263 $
 * @modifiedby		$LastChangedBy: jack $
 * @lastmodified	$Date: 2010-05-13 10:57:05 -0600 (Thu, 13 May 2010) $
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
	 * adds a hook to be executed
	 *
	 * @access public
	 * @static
	 * @param string $name the location to run the hook at
	 * @param mixed $function either a string of the function to call or an array of the class and function
	 * @param integer $priority the order to call the function. Default is 10. Lower gets called first. Higher last.
	 * @param string $controller
	 * @param mixed $view
	 * @param string $type
	 */
	public static function add($name, $function, $priority = 10, $controller = '', $views = array(), $type = 'all') {
		// make sure the function is valid
		if (self::checkFunction($function) === false) {
			return false;
		}
		
		$views = (!is_array($views)) ? array($views) : $views;
		
		$existing = &self::find($name, $function, $priority);

		if (empty($existing) || sizeof($existing) > 1) {
			// add the hook to the array
			self::$hooks[$name][$priority][] = array(
				'function'	=> $function,
				'type'		=> $type,
				'controller'	=> $controller,
				'views'		=> (!is_array($views)) ? array($views) : $views,
			);
		} else {
			$existing = &$existing[0];
			
			switch($type) {
				case 'except':
					if ($existing['type'] == 'except') {
						foreach($views as $key => $method) {
							if (!in_array($method, $existing['views'])) {
								$existing['views'][] = $method;
							}
						}
					} else if ($existing['type'] == 'only') {
						$existing = array(
							'function'	=> $function,
							'type'		=> 'except',
							'controller'	=> $controller,
							'views'		=> $views,
						);
					}
				break;
				
				case 'only':
					if ($existing['type'] == 'except') {
						foreach($existing['views'] as $key => $method) {
							if (in_array($method, $views)) {
								unset($existing['views'][$key]);
							}
						}
					} else if ($existing['type'] == 'only') {
						foreach($views as $method) {
							if (!in_array($method, $existing['views'])) {
								$existing['views'][] = $method;
							}
						}
					}
				break;
				
				default:
					
				break;
			}
		}

		return true;
	}

	/**
	 * Alias of Hook::add()
	 *
	 * @see Hook::add()
	 */
	public static function addAll($name, $function, $priority = 10, $controller = '', $view = '') {
		return self::add($name, $function, $priority, $controller, $view, 'all');
	}

	/**
	 * adds a hook to only one view
	 *
	 * @see Hook::add()
	 */
	public static function addOn($name, $function, $priority, $controller, $view) {
		return self::add($name, $function, $priority, $controller, $view, 'only');
	}

	/**
	 * adds a hook to every view except one
	 *
	 * @see Hook::add()
	 */
	public static function addExcept($name, $function, $priority, $controller, $view) {
		return self::add($name, $function, $priority, $controller, $view, 'except');
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
	public static function remove($name, $function = '', $priority = null, $controller = '', $view = '') {
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
		$hooks = self::get($name);

		foreach($hooks as $priority => $functions) {
			foreach($functions as $data) {
				if (Hook::checkFunction($data['function'])) {
					call_user_func_array($data['function'], $args);
				}
			}
		}
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
	public static function getAll() {
		return self::$hooks;
	}
	
	private static function &find($name, $function, $priority = null, $controller = '', $view = '') {
		$hooks = &self::get($name);

		$return = array();
	
		// see if we are checking a priority
		if ($priority != null) {
			if (empty($hooks[$priority])) {
				$blank = array();
				return $blank;
			}
			
			// loop through each hook in the priority
			foreach($hooks[$priority] as &$hook) {
				// see if the function matches what we are looking for
				if ($hook['function'] === $function) {
					// check controller and view if they aren't empty
					// ugly!
					if ((!empty($controller) && !empty($view) && $hook['controller'] == $controller && in_array($view, $hook['views'])) || (!empty($controller) && $hook['controller'] == $controller) || (!empty($view) && in_array($view, $hook['views']))) {
						$return[] = &$hook;
					} else if (empty($controller) && empty($view)) {
						// if they are empty but the function matches so we need to return it
						$return[] = &$hook;
					}
				}
			}
		
			return $return;
		} else {
			foreach($hooks as $priority => $hook) {
				$return = array_merge($return, self::find($name, $function, $priority, $controller, $view));
			}
			
			return $return;
		}
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