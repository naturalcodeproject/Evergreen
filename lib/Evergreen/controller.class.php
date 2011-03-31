<?php
/**
 * Controller Class
 *
 * This class handles the loading of a view and things associated such as loading
 * layouts, running filters, bouncebacks, handling override logic, handling view 404's,
 * running the formhandler helper class, and running the designer fixes.
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
 * Controller Class
 *
 * This class handles the loading of a view and things associated such as loading
 * layouts, running filters, bouncebacks, handling override logic, handling view 404's,
 * running the formhandler helper class, and running the designer fixes.
 *
 * Hooks:
 * Controller.setup.before
 * Controller.setup.after
 * Controller.destruct.before
 * Controller.destruct.after
 * Controller.showView.before
 * Controller.showView.after
 * Controller.loadView.before
 * Controller.loadView.after
 * Controller.getView.before
 * Controller.getView.setPath
 * Controller.getView.after
 * Controller.viewExists.before
 * Controller.viewExists.setPath
 * Controller.viewExists.after
 * Controller.setLayout.before
 * Controller.setLayout.setPath
 * Controller.setLayout.after
 * Controller.removeLayout.before
 * Controller.removeLayout.after
 * Controller.renderLayout.before
 * Controller.renderLayout.after
 * Controller.setBounceBack.before
 * Controller.setBounceBack.after
 * Controller.removeBounceBack.before
 * Controller.removeBounceBack.after
 * Controller.runBounceBack.before
 * Controller.runBounceBack.after
 * Controller.getViewContent
 * Controller.setViewContent
 * Controller.getFullPageContent
 * Controller.setFullPageContent
 * Controller.designerFixCallback
 *
 * @package       evergreen
 * @subpackage    lib
 * @abstract
 */
abstract class Controller {
	/**
	 * The current view that needs to be loaded.
	 * 
	 * @access private
	 * @var string
	 */
	private $viewToLoad = null;
	
	/**
	 * Holder for the called Formhandler class.
	 * 
	 * @access protected
	 * @var object
	 */
	protected $formhandler = null;
	
	/**
	 * The layout to be loaded.
	 * 
	 * @access private
	 * @var string
	 */
	private $layout = null;
	
	/**
	 * Holder for URI.working so it can be processed by the class.
	 * 
	 * @access private
	 * @var array
	 */
	private $params = array();
	
	/**
	 * Holder for the registered filters.
	 * 
	 * @access private
	 * @var array
	 */
	private $filters = array();
	
	/**
	 * Indicates whether the view that is supposed to be loaded has been over ridden.
	 * 
	 * @access private
	 * @var boolean
	 */
	private $overriddenView = false;
	
	/**
	 * Holder variable indicating which view to load in place of the one that would have regularly be loaded.
	 * 
	 * @access private
	 * @var array
	 */
	private	$overriddenViewToLoad = array();
	
	/**
	 * Holds the set bouceback data.
	 * 
	 * @access private
	 * @var array
	 */
	private $bounceback = null;
	
	/**
	 * The generated content of the view.
	 * 
	 * @access private
	 * @var string
	 */
	private $viewContent = null;
	
	/**
	 * The generated content of the view and the layout combined.
	 * 
	 * @access private
	 * @var string
	 */
	private $fullPageContent = null;
	
	/**
	 * Indicates certain function names that are to be ignored as views.
	 * 
	 * @access protected
	 * @var array
	 */
	protected $notAView = array();
	
	/**
	 * Sets up the controller and figures out the view that needs to be loaded.
	 * 
	 * @access private
	 * @final
	 */
	final private function _controllerSetup() {
		// call hook
		Hook::call('Controller.setup.before');
		
		// Construct Code
		$this->params = Reg::get("URI.working");
		if (!strlen($this->params['view'])) {
			$this->params['view'] = Reg::get("URI.map.view");
		}
		
		$this->viewToLoad = Config::uriToMethod($this->params['view']);
		
		$this->formhandler = new Formhandler($this);
		
		// add hooks for designer fix and form handler
		Hook::add("Controller.loadView.after", array($this, "_designerFix"));
		Hook::add("Controller.loadView.after", array($this->formhandler, "decode"));
		
		// call hook
		Hook::call('Controller.setup.after', array(&$this));
	}
	
	/**
	 * Closes out the processing of the controller.
	 * 
	 * @access private
	 * @final
	 */
	final private function _controllerDestruct() {
		// call hook
		Hook::call('Controller.destruct.before');
		
		unset($this->viewContent);
		unset($this->fullPageContent);
		unset($this->formhandler);
		
		// call hook
		Hook::call('Controller.destruct.after');
	}
	
	/**
	 * Runs the controller, processes and output's the view.
	 * 
	 * @access public
	 * @final
	 */
	final public function _showView() {
		// call hook
		Hook::call('Controller.showView.before');
		
		// Run the controller's Setup
		$this->_controllerSetup();
		// Set up the actual page
		$this->_loadView();
		
		// Output Page
		$this->_runFilters('Page.output.before');
		echo $this->fullPageContent;
		$this->_runFilters('Page.output.after');
		$this->_controllerDestruct();
		
		// call hook
		Hook::call('Controller.showView.after');
	}
	
	/**
	 * Check's that the view being loaded exists, processes the bounceback, runs the view and the layout.
	 * 
	 * @access private
	 * @final
	 */
	final private function _loadView() {
		// call hook
		Hook::call('Controller.loadView.before', array(&$this->viewToLoad));
		
		ob_start();
		$error = false;
		if ((is_callable(array($this, $this->viewToLoad)) && $this->_viewExists(array("name" => $this->viewToLoad, "checkmethod" => true))) || (!$this->_viewExists(array("name" => $this->viewToLoad, "checkmethod" => true)) && ($this->_runBounceBack()))) {
			$this->_runFilters('Page.before');
			$this->_runFilters('View.before');
			ob_start();
				$this->_runFilters('View.content.before');
				if (is_callable(array($this, $this->viewToLoad)) && call_user_func(array($this, $this->viewToLoad)) === false) {
					throw new EvergreenException("VIEW_NOT_FOUND");
				}
				if ($this->overriddenView) {
					$this->_getView($this->overriddenViewToLoad);
				} else {
					$this->_getView($this->viewToLoad);
				}
				$this->_runFilters('View.content.after');
			$this->viewContent = ob_get_clean();
			$this->_runFilters('View.after');
		} else {
			$error = true;
			throw new EvergreenException("VIEW_NOT_FOUND");
		}
		
		$this->_runFilters('Layout.before');
		if(!$this->_renderLayout() && !$error) {
			echo $this->viewContent;
		}
		unset($this->viewContent);
		$this->_runFilters('Layout.after');
		
		$this->fullPageContent = ob_get_clean();
		$this->_runFilters('Page.after');
		
		// call hook
		Hook::call('Controller.loadView.after', array(&$this->fullPageContent));
	}
	
	/**
	 * Loads in a view file and allows the default view file that is being loaded to be overridden.
	 * 
	 * @access protected
	 * @final
	 * @param array|string $args Can be either the name of the view to load or an array with name and controller defined
	 * @param string $controller The name of the controller to load the view from, if left blank assumes the current controller
	 * @param string $branch The name of the branch to load the view from, if left blank assumes the current branch
	 * @param boolean $override Indicates whether to override the current view's default with the requested one
	 * @return boolean true if the view was loaded and boolean false if not
	 */
	final protected function _getView() {
		$args = func_get_args();
		if (count($args) < 1 || count($args) > 4) {
			return false;
		}
		if (count($args) == 1 && is_array($args[0])) {
			$args = $args[0];
		} else {
			$override = false;
			if (is_bool($args[count($args)-1]) === true) {
				$override = array_pop($args);
			}
			$args = array_combine(
				array_merge(array_slice(array(
					'name',
					'controller',
					'branch'
				), 0, count($args)),
				(array)'override'),
				array_merge($args, (array)$override)
			);
			unset($override);
		}
		
		// call hook
		Hook::call('Controller.getView.before', array(&$args));
		
		if (empty($args['name'])) {
			return false;
		}
		if (empty($args['controller'])) {
			$args['controller'] = $this->params['controller'];
		}
		if (empty($args['branch']) && Reg::hasVal('Branch.name')) {
			$args['branch'] = Reg::get('Branch.name');
		}
		if (!empty($args['branch']) && $args['branch'] == Reg::get('System.rootIdentifier')) {
			unset($args['branch']);
		}
		if (!isset($args['override'])) {
			$args['override'] = false;
		}
		
		$return = false;
		if ($this->overriddenView == false && $args['override'] == true) {
			$this->overriddenView = $args['override'];
			unset($args['override']);
			$this->overriddenViewToLoad = $args;
			$return = true;
		} else {
			$path = Reg::get("Path.physical").((!empty($args['branch'])) ? "/branches/".Config::uriToFile(Config::classToFile($args['branch'])) : "")."/views/".Config::uriToFile(Config::classToFile($args['controller']))."/".Config::uriToFile(Config::methodToFile($args['name'])).".php";
			
			// call hook
			Hook::call('Controller.getView.setPath', array(&$args, &$path));
			
			if (((file_exists($path) && (include($path)) == true))) {
				$return = true;
			}
			unset($path);
		}
		
		// call hook
		Hook::call('Controller.getView.after', array(&$return));
		
		return $return;
	}
	
	/**
	 * Checks if a view exists by file, method, or both.
	 * 
	 * @access protected
	 * @final
	 * @param array|string $args Can be either the name of the view or an array with name and controller defined
	 * @param string $controller The name of the controller where the view is located, if left blank assumes the current controller
	 * @param mixed $checkmethod Indicates whether to check if the method exists, file exists, or both
	 * @return boolean true if the view exists and boolean false if not
	 */
	final protected function _viewExists($args, $controller="", $checkmethod = false) {
		$args = func_get_args();
		if (count($args) < 1 || count($args) > 4) {
			return false;
		}		
		if (count($args) == 1 && is_array($args[0])) {
			$args = $args[0];
		} else {
			$checkmethod = false;
			if (is_bool($args[count($args)-1]) === true || $args[count($args)-1] == 'both') {
				$checkmethod = array_pop($args);
			}
			$args = array_combine(
				array_merge(array_slice(array(
					'name',
					'controller',
					'branch'
				), 0, count($args)),
				(array)'checkmethod'),
				array_merge($args, (array)$checkmethod)
			);
			unset($checkmethod);
		}
		
		// call hook
		Hook::call('Controller.viewExists.before', array(&$args));
		
		if (empty($args['name'])) {
			return false;
		}
		if (empty($args['controller'])) {
			$args['controller'] = $this->params['controller'];
		}
		if (empty($args['branch']) && Reg::hasVal('Branch.name')) {
			$args['branch'] = Reg::get('Branch.name');
		}
		if (!empty($args['branch']) && $args['branch'] == Reg::get('System.rootIdentifier')) {
			unset($args['branch']);
		}
		if (!isset($args['checkmethod'])) {
			$args['checkmethod'] = false;
		}
		if (($args['name'][0] != '_' && (!isset($this->bounceback['check']) || $this->bounceback['check'] != $args['controller']) && !in_array($args['controller'], $this->notAView))) {
			if ($args['checkmethod'] === true) {
				$load['name'] = Config::uriToClass(Config::fileToClass($args['controller']));
				if (!empty($args['branch'])) {
					$load['branch'] = Config::uriToClass(Config::fileToClass($args['branch']));
				}
				$load['type'] = 'Controller';
				$load = implode('_', $load);
				
				if (is_callable(array($load, Config::uriToMethod(Config::fileToMethod($args['name'])))) && method_exists($load, Config::uriToMethod(Config::fileToMethod($args['name'])))) {
					$return = true;
				} else {
					$return = false;
				}
			} else {
				$path = Reg::get("Path.physical").(($args['branch']) ? "/branches/".Config::uriToFile(Config::classToFile($args['branch'])) : "")."/views/".Config::uriToFile(Config::classToFile($args['controller']))."/".Config::uriToFile(Config::methodToFile($args['name'])).".php";
				
				// call hook
				Hook::call('Controller.viewExists.setPath', array(&$args, &$path));
				
				if (file_exists($path)) {
					if ($args['checkmethod'] == 'both') {
						$load['name'] = Config::uriToClass(Config::fileToClass($args['controller']));
						if (!empty($args['branch'])) {
							$load['branch'] = Config::uriToClass(Config::fileToClass($args['branch']));
						}
						$load['type'] = 'Controller';
						$load = implode('_', $load);
						
						if (is_callable(array($load, Config::uriToMethod(Config::fileToMethod($args['name'])))) && method_exists($load, Config::uriToMethod(Config::fileToMethod($args['name'])))) {
							$return = true;
						} else {
							$return = false;
						}
					} else {
						$return = true;
					}
				} else {
					$return = false;
				}
				unset($path);
			}
		} else {
			$return = false;
		}
		
		// call hook
		Hook::call('Controller.viewExists.after', array(&$return));
		
		return $return;
	}
	
	/**
	 * Sets the layout to load. This will only set if the layout exists.
	 * 
	 * @access protected
	 * @final
	 * @param string $name The name of the layout to load
	 * @param string $branch Optional The branch to load the layout from assumes the current if none is defined
	 * @return boolean true if the layout was set and boolean false if not
	 */
	final protected function _setLayout($name, $branch = '') {
		$layout = array('name' => $name, 'branch' => $branch);
		
		// call hook
		Hook::call('Controller.setLayout.before', array(&$layout));
		
		if (($layout['branch'] == Reg::get('System.rootIdentifier')) || (!Reg::hasVal("Branch.name") && empty($layout['branch']))) {
			$path = Reg::get("Path.physical")."/views/layouts/".Config::uriToFile(Config::methodToFile($layout['name'])).".php";
			
			// call hook
			Hook::call('Controller.setLayout.setPath', array(&$layout, &$path));
			
			if (file_exists($path)) {
				$this->layout = $path;
				$return = true;
			} else {
				$return = false;
			}
		} else if ((Reg::hasVal("Branch.name") && empty($layout['branch'])) || !empty($layout['branch'])) {
			if (!empty($layout['branch'])) {
				$branchToUse = $layout['branch'];
			} else {
				$branchToUse = Reg::get("Branch.name");
			}
			
			$path = Reg::get("Path.physical")."/branches/".Config::uriToFile(Config::classToFile($branchToUse))."/views/layouts/".Config::uriToFile(Config::methodToFile($layout['name'])).".php";
			
			// call hook
			Hook::call('Controller.setLayout.setPath', array(&$layout, &$path));
			
			if (file_exists($path)) {
				$this->layout = $path;
				$return = true;
			} else {
				$return = false;
			}
		} else {
			$return = false;
		}
		
		// call hook
		Hook::call('Controller.setLayout.after', array(&$return));
		
		return $return;
	}
	
	/**
	 * Unsets the currently set layout.
	 * 
	 * @access protected
	 * @final
	 * @return boolean true
	 */
	final protected function _removeLayout() {
		// call hook
		Hook::call('Controller.removeLayout.before');
		
		$this->layout = null;
		
		// call hook
		Hook::call('Controller.removeLayout.after');
		
		return true;
	}
	
	/**
	 * Loads the layout.
	 * 
	 * @access private
	 * @final
	 * @return boolean true if the layout was loaded and boolean false if not
	 */
	final private function _renderLayout() {
		// call hook
		Hook::call('Controller.renderLayout.before', array(&$this->layout));
		
		if (empty($this->layout)) {
			return false;
		}
		if ((file_exists($this->layout) && (include($this->layout)) == true)) {
			$return = true;
		} else {
			$return =  false;
		}
		
		// call hook
		Hook::call('Controller.renderLayout.after', array(&$return));
		
		return $return;
	}
	
	/**
	 * Add's a filter for all the views in the controller.
	 * 
	 * @access protected
	 * @final
	 * @param string $filter The name of the function to use as the filter
	 * @param string $schedule Optional The schedule when to run the filter
	 * @return boolean true
	 */
	final protected function _addFilterAll($filter, $schedule = 'Page.before') {
		$filterKey = $this->_createFilterKey($filter);
		if (!is_array($filter)) {
			$filter = array(get_class($this), $filter);
		}
		if (!isset($this->filters[$schedule])) {
			$this->filters[$schedule] = array();
		}
		$this->filters[$schedule][$filterKey] = array(
			'filter' => $filter,
			'type' => 'except',
			'methods' => array()
		);
		return true;
	} 
	
	/**
	 * Add's a filter on specific views in the controller.
	 * 
	 * @access protected
	 * @final
	 * @param string $filter The name of the function to use as the filter
	 * @param string|array $methods The name of the method or methods to set the filter on
	 * @param string $schedule Optional The schedule when to run the filter
	 * @return boolean true if successfully set and boolean false if not
	 */
	final protected function _addFilterOn($filter, $methods, $schedule = 'Page.before') {
		$methods = (array)$methods;
		$filterKey = $this->_createFilterKey($filter);
		if (!is_array($filter)) {
			$filter = array(get_class($this), $filter);
		}
		if (!isset($this->filters[$schedule])) {
			$this->filters[$schedule] = array();
		}
		if (!isset($this->filters[$schedule][$filterKey])) {
			$this->filters[$schedule][$filterKey] = array(
				'filter' => $filter,
				'type' => 'only',
				'methods' => array()
			);
		}
		if ($this->filters[$schedule][$filterKey]['type'] == 'except') {
			foreach($this->filters[$schedule][$filterKey]['methods'] as $key => $method) {
				if (in_array($method, $methods)) {
					unset($this->filters[$schedule][$filterKey]['methods'][$key]);
				}
			}
		} else if ($this->filters[$schedule][$filterKey]['type'] == 'only') {
			foreach($methods as $key => $method) {
				if (!in_array($method, $this->filters[$schedule][$filterKey]['methods'])) {
					$this->filters[$schedule][$filterKey]['methods'][] = $method;
				}
			}
		} else {
			return false;
		}
		return true;
	}
	
	/**
	 * Adds a filter on all views except the ones defined.
	 * 
	 * @access protected
	 * @final
	 * @param string $filter The name of the function to use as the filter
	 * @param string|array $methods The name of the method or methods to be exempt from the filter
	 * @param string $schedule Optional The schedule when to run the filter
	 * @return boolean true if successfully set and boolean false if not
	 */
	final protected function _addFilterExcept($filter, $methods, $schedule = 'Page.before') {
		$methods = (array)$methods;
		$filterKey = $this->_createFilterKey($filter);
		if (!is_array($filter)) {
			$filter = array(get_class($this), $filter);
		}
		if (!isset($this->filters[$schedule])) {
			$this->filters[$schedule] = array();
		}
		if (!isset($this->filters[$schedule][$filterKey])) {
			$this->filters[$schedule][$filterKey] = array(
				'filter' => $filter,
				'type' => 'except',
				'methods' => array()
			);
		}
		if ($this->filters[$schedule][$filterKey]['type'] == 'except') {
			foreach($methods as $key => $method) {
				if (!in_array($method, $this->filters[$schedule][$filterKey]['methods'])) {
					$this->filters[$schedule][$filterKey]['methods'][] = $method;
				}
			}
		} else if ($this->filters[$schedule][$filterKey]['type'] == 'only') {
			$this->filters[$schedule][$filterKey] = array(
				'filter' => $filter,
				'type' => 'except',
				'methods' => $methods
			);
		} else {
			return false;
		}
		return true;
	}
	
	/**
	 * Removes a specific filter on the defined methods.
	 * 
	 * @access protected
	 * @final
	 * @param string $filter The name of the filter function to remove
	 * @param string|array $methods The name of the method or methods to remove the filter from
	 * @param string $schedule Optional The filter's schedule
	 * @return boolean true if successfully removed and boolean false if not
	 */
	final protected function _removeFilterOn($filter, $methods, $schedule = 'Page.before') {
		$methods = (array)$methods;
		$filterKey = $this->_createFilterKey($filter);
		if (!isset($this->filters[$schedule][$filterKey])) {
			return true;
		}
		if ($this->filters[$schedule][$filterKey]['type'] == 'except') {
			foreach($methods as $key => $method) {
				if (!in_array($method, $this->filters[$schedule][$filterKey]['methods'])) {
					$this->filters[$schedule][$filterKey]['methods'][] = $method;
				}
			}
			return true;
		} else if ($this->filters[$schedule][$filterKey]['type'] == 'only') {
			foreach($this->filters[$schedule][$filterKey]['methods'] as $key => $method) {
				if (in_array($method, $methods)) {
					unset($this->filters[$schedule][$filterKey]['methods'][$key]);
				}
			}
			
			if (count($this->filters[$schedule][$filterKey]['methods']) == 0) {
				$this->_removeFilter($filter);
			}
			return true;
		}
		return false;
	}
	
	/**
	 * Removes a filter at a specific schedule.
	 * 
	 * @access protected
	 * @final
	 * @param string $filter The name of the filter function to remove
	 * @param string $schedule Optional The filter's schedule
	 * @return boolean true
	 */
	final protected function _removeFilter($filter, $schedule = 'Page.before') {
		$filterKey = $this->_createFilterKey($filter);
		if (isset($this->filters[$schedule][$filterKey])) {
			unset($this->filters[$schedule][$filterKey]);
		}
		return true;
	}
	
	/**
	 * Runs all filters for a specific schedule.
	 * 
	 * @access private
	 * @final
	 * @param string $schedule Optional The schedule of filters to run
	 * @return boolean true if there are filters for the specified schedule and boolean false if not
	 */
	final private function _runFilters($schedule) {
		if (isset($this->filters[$schedule])) {
			foreach($this->filters[$schedule] as $attributes) {
				if ($attributes['type'] == 'except') {
					if (!in_array($this->viewToLoad, $attributes['methods'])) {
						if ($attributes['filter'][0] == get_class($this)) {
							call_user_func(array($this, $attributes['filter'][1]));
						} else {
							call_user_func($attributes['filter']);
						}
					}
				} else if ($attributes['type'] == 'only') {
					if (in_array($this->viewToLoad, $attributes['methods'])) {
						if ($attributes['filter'][0] == get_class($this)) {
							call_user_func(array($this, $attributes['filter'][1]));
						} else {
							call_user_func($attributes['filter']);
						}
					}
				}
			}
			return true;
		}
		return false;
	}
	
	/**
	 * Creates a key from the provided filter that can be used to store filters.
	 * 
	 * @access private
	 * @final
	 * @param mixed $filter The filter to create the key from
	 * @return string
	 */
	final private function _createFilterKey($filter) {
		if (is_array($filter)) {
			if (is_object($filter[0])) {
				$filter[0] = get_class($filter[0]);
			}
			return hash('sha256', implode($filter));
		} else {
			return hash('sha256', (string)$filter);
		}
	}
	
	/**
	 * Sets a bounceback for a controller which catches any 404's caught in the controller and allows the check method to
	 * indicate if its a real view or not by returning true or false and if true is returned the the bounce method is loaded
	 * as the view.
	 * 
	 * @access protected
	 * @final
	 * @param string|array $check The method to use to check if a view is valid
	 * @param string $bounce The method to load if the check returns true
	 * @return boolean true
	 */
	final protected function _setBounceBack($check, $bounce) {
		// call hook
		Hook::call('Controller.setBounceBack.before', array(&$check, &$bounce));
		
		if (!is_array($check)) {
			$check = array($this, $check);
		}
		
		if (!is_array($bounce)) {
			$bounce = array($this, $bounce);
		}
		
		$this->bounceback = array(
			'check' => $check,
			'bounce' => $bounce
		);
		
		// call hook
		Hook::call('Controller.setBounceBack.after', array(&$this->bounceback));
		
		return true;
	}
	
	/**
	 * Remove a set bounceback.
	 * 
	 * @access protected
	 * @final
	 * @return boolean true
	 */
	final protected function _removeBounceBack() {
		// call hook
		Hook::call('Controller.removeBounceBack.before');
			
		$this->bounceback = null;
		
		// call hook
		Hook::call('Controller.removeBounceBack.after');
		
		return true;
	}
	
	/**
	 * Run's the set bounceback.
	 * 
	 * @access private
	 * @final
	 * @return boolean true if the check returns true and boolean false if not
	 */
	final private function _runBounceBack() {
		// call hook
		Hook::call('Controller.runBounceBack.before', array(&$this->bounceback));
		
		if (((isset($this->bounceback['check']) && method_exists($this->bounceback['check'][0], $this->bounceback['check'][1])) && (isset($this->bounceback['bounce']) && method_exists($this->bounceback['bounce'][0], $this->bounceback['bounce'][1]))) && !$this->_viewExists(array("name" => $this->viewToLoad, "checkmethod" => true))) {
			$keys = array_keys(Reg::get('URI.working'));
			$values = array_values(Reg::get('URI.working'));
			$controllerPos = array_search('controller', $keys);
			if ($controllerPos === false) {
				$controllerPos = 0;
			}
			$this->params = array_combine($keys, array_slice(array_merge(array_slice($values, 0, ($controllerPos+1)), array($this->bounceback['bounce'][1]), array_slice($values, $controllerPos+1)), 0, count($keys)));
			Reg::set('Param', $this->params);
			$this->viewToLoad = Config::uriToMethod($this->params['view']);
			
			if ($this->_viewExists(array("name" => $this->viewToLoad, "checkmethod" => true)) !== true) {
				$return = false;
			} else if (is_callable($this->bounceback['check']) && call_user_func($this->bounceback['check']) === false) {
				$return = false;
			} else {
				$return = true;
			}
		} else {
			$return = false;
		}
		
		// call hook
		Hook::call('Controller.runBounceBack.after', array(&$return));
		
		return $return;
	}
	
	/**
	 * Returns the generated view content.
	 * 
	 * @access protected
	 * @final
	 * @return string
	 */
	final protected function &_getViewContent() {
		// call hook
		Hook::call('Controller.getViewContent', array(&$this->viewContent));
		
		return $this->viewContent;
	}
	
	/**
	 * Sets the view content.
	 * 
	 * @access protected
	 * @final
	 * @param string &$content The view content
	 */
	final protected function _setViewContent(&$content) {
		// call hook
		Hook::call('Controller.setViewContent', array(&$content));
		
		$this->viewContent = $content;
	}
	
	/**
	 * Get the full page generated content.
	 * 
	 * @access protected
	 * @final
	 * @return string
	 */
	final protected function &_getFullPageContent() {
		// call hook
		Hook::call('Controller.getFullPageContent', array(&$this->fullPageContent));
		
		return $this->fullPageContent;
	}
	
	/**
	 * Sets the full page content.
	 * 
	 * @access protected
	 * @final
	 * @param string &$content The full page content
	 */
	final protected function _setFullPageContent(&$content) {
		// call hook
		Hook::call('Controller.setFullPageContent', array(&$content));
		
		$this->fullPageContent = $content;
	}
	
	/**
	 * Callback function for the designer fix preg_replace_callback
	 * Automatically adjusts paths if they have .. in them
	 * 
	 * @access private
	 * @final
	 * @param array $match An array of the found items from the regular expression
	 * @return string
	 */
	final private function _designerFixCallback($match) {	
		// if this isn't empty then it is a link
		$link = !empty($match[2]);

		// get the tag
		$tag = $match[1];
		
		// set the default to return the full string that was matched
		$return = $match[0];

		$custom = false;

		// do the replacement
		switch ($tag) {
			case "[current]":
				$return = Reg::get("Path.current");
			break;
			
			case "[site]":
				$return = Reg::get("Path.site");
			break;
			
			case "[skin]":
				$return = Reg::get("Path.skin");
			break;
			
			case "[root]":
				$return = Reg::get("Path.root");
			break;
			
			case "[branch.site]":
				$return = Reg::get("Path.branch");
			break;
			
			case "[branch.skin]":
				$return = Reg::get("Path.branchSkin");
			break;
			
			case "[branch.root]":
				$return = Reg::get("Path.branchRoot");
			break;
			
			default:
				// see if it is a php variable. Quick way to echo variables from $this
				if (strpos($tag, '$') === 1) {
					$custom = true;
					
					$var = str_replace(array('[', ']', '$', 'this->'), '', $tag);
					
					// if it is in an object then we need to go down the objects pulling out the variables
					// not pretty
					if (strpos($var, '->') !== false) {
						$parts = explode('->', $var);
						
						$failed = false;
						$var = $this->{array_shift($parts)};
						foreach($parts as $part) {
							if (!isset($var->$part)) {
								// variable isn't set so it failed
								$failed = true;
								break;
							}
							$var = $var->$part;
						}
						
						if ($failed === false) {
							$return = $var;
						}
					} else {
						// it is a variable that isn't an object
						if (isset($this->$var)) {
							$return = $this->$var;
						}
					}
					
					// the variable didn't exist so send nothing back to the page
					// don't want variable names to sneak in
					if ($return == $tag) {
						$return = '';
					}
				} else if (Reg::has(str_replace(array('[', ']'), '', $tag))) {
					$custom = true;
					
					// get the variable from the registry
					$return = Reg::get(str_replace(array('[', ']'), '', $tag));
				} else {
					// look for the tag within the working URI
					$working_uri = Reg::get("URI.working");
					
					if (Reg::hasVal("Branch.name")) {
						$working_uri = array_merge(array("branch"=>Reg::get("Branch.name")), $working_uri);
					}
					
					foreach($working_uri as $key => $item) {
						$tmp_key = "[".$key."]";	
						if ($tag == $tmp_key && Reg::has('Path.' . $key)) {
							$return = Reg::get('Path.' . $key);
							
							break 1;
						}
					}
				}
			break;
		}
		
		// if it is a link then we need to do some more processing
		if ($link === true && $custom === false) {
			// remove any ../ from the url so that they are clean
			$link_arr = explode("/", $match[2]);
			$up_link_count = count(array_keys(array_slice($link_arr, 1), ".."));
			
			$return = explode('/', $return);
			$return = implode("/", (($up_link_count) ? array_slice($return, 0, -1 * $up_link_count) : $return)) . implode("/", array_pad(array_slice($link_arr, $up_link_count+1), -(count(array_slice($link_arr, $up_link_count+1))+1), ""));
			
			// if mod_rewrite isn't being used then need to make sure the URL is valid by turning any extra ? into &
			if (Reg::get("URI.useModRewrite") != true && !empty($return)) {
				if (substr_count($return, "?", 0) > 1) {
					$return = strrev(preg_replace("/\?/i", "&", strrev($return), (substr_count($return, "?", 0) - 1)));
				}
	
			}
		} else if (!empty($match[2])) {
			$return .= $match[2];
		}
		
		// call hook
		Hook::call('Controller.designerFixCallback', array(&$match, &$return));
		
		return $return;
	}
	
	/**
	 * Runs the fix for the designer tags
	 * 
	 * @access public
	 * @final
	 * @param string &$content The content to run the fix on
	 */
	final public function _designerFix (&$content) {		
		// matches all [tags] and everything after it that is before a space, <, >, ", ', .
		$content = preg_replace_callback('#(\[[\w\.\$\-\>\:]+\])(.*?)(?=(?:"|\'|\>|\<|\s|\[|\]))#i', array($this, '_designerFixCallback'), $content);
	}

}
?>