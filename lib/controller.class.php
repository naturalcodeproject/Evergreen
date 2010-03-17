<?php
/*
*	Copyright (C) 2006-2009 NaturalCodeProject
*	All Rights Reserved
*	
*	@author Daniel Baldwin
*	
*	Description: Controller class for all user controllers.
*
*/

abstract class Controller {
	private $viewToLoad;
	private $formhandler;
	private $designer;
	private $params = array();
	protected $filters = array();
	private $overriddenView = false;
	private	$overriddenViewToLoad = array();
	
	protected $bounceback = array();
	protected $filter = array();
	protected $filter_except = array();
	protected $filter_only = array();
	protected $notAView = array();
	
	final private function _controllerSetup() {
		## Construct Code ##
		$this->params = Config::loadableURI(Config::read("URI.working"));
		if (!strlen(reset(array_slice($this->params, 1, 1)))) {
			$this->params[reset(array_slice(array_keys($this->params), 1, 1))] = reset(array_slice(Config::read("URI.map"), 1, 1));
		}
		
		$this->viewToLoad = $this->params[reset(array_slice(array_keys($this->params), 1, 1))];
		
		$this->formhandler = new Formhandler($this);
		$this->designer = new Designer();
	}
	
	final public function _showView() {
		## Run the controller's Setup
		$this->_controllerSetup();
		## Set up the actual page
		$full_page = $this->_loadView();
		
		## First Designer Fix
		$this->designer->doFixes($full_page);
		
		## Form Fix
		$this->formhandler->decode($full_page);
		
		## Second Designer Fix
		$this->designer->doFixes($full_page);
		
		## Output Page
		echo $full_page;
	}
	
	final private function _loadView() {
		ob_start();
		$error = false;
		
		if ((is_callable(array($this, $this->viewToLoad)) && $this->_viewExists(array("name" => $this->viewToLoad, "checkmethod" => true))) || (!$this->_viewExists(array("name" => $this->viewToLoad, "checkmethod" => true)) && (isset($this->bounceback['check']) && isset($this->bounceback['bounce'])) && method_exists($this, $this->bounceback['check']) && method_exists($this, $this->bounceback['bounce']))) {
			if (!empty($this->filter) || !empty($this->filter_only) || !empty($this->filter_except)) {
				if (isset($this->filter)) {
					if (!empty($this->filter) && !is_array($this->filter)) {
						call_user_func(array($this, $this->filter));
					}
				}
				
				if (isset($this->filter_only) && sizeof($this->filter_only) > 1 && is_array($this->filter_only[1]) && in_array($this->viewToLoad, $this->filter_only[1])) {
					if (!empty($this->filter_only[0]) && !is_array($this->filter_only[0])) {
						call_user_func(array($this, $this->filter_only[0]));
					}
				}
				
				if (isset($this->filter_except) && sizeof($this->filter_except) > 1 && is_array($this->filter_except[1]) && !in_array($this->viewToLoad, $this->filter_except[1])) {
					if (!empty($this->filter_except[0]) && !is_array($this->filter_except[0])) {
						call_user_func(array($this, $this->filter_except[0]));
					}
				}
			}
			
			if ((isset($this->bounceback['check']) && isset($this->bounceback['bounce'])) && !$this->_viewExists(array("name" => $this->viewToLoad, "checkmethod" => true))) {
				$values = array_values(Config::read('URI.working'));
				$this->params = array_combine(array_keys(Config::read('URI.working')), array_slice(array_merge(array($values[0]), array($this->bounceback['bounce']),array_slice($values, 1)), 0, count(array_keys(Config::read('URI.working')))));
				Config::register('Param', $this->params);
				$this->params = Config::loadableURI($this->params);
				$this->viewToLoad = $this->params[reset(array_slice(array_keys($this->params), 1, 1))];
				
				if (!$this->_viewExists(array("name" => $this->viewToLoad, "checkmethod" => true))) {
					$error = true;
					Error::trigger("VIEW_NOT_FOUND");
				}
				
				if (is_callable(array($this, $this->bounceback['check'])) && call_user_func(array($this, $this->bounceback['check'])) === false) {
					$error = true;
					Error::trigger("VIEW_NOT_FOUND");
				}
			}
			
			ob_start();
				if (is_callable(array($this, $this->viewToLoad)) && call_user_func(array($this, $this->viewToLoad)) === false) {
					Error::trigger("VIEW_NOT_FOUND");
				}
				if ($this->overriddenView) {
					$this->_getView($this->overriddenViewToLoad);
				} else {
					$this->_getView($this->viewToLoad);
				}
			$this->content_for_layout = ob_get_clean();
			
		} else {
			$error = true;
			Error::trigger("VIEW_NOT_FOUND");
		}
		
		if(!empty($this->layout) && !$error) {
			$this->_renderLayout($this->layout);
		} else {
			echo $this->content_for_layout;
		}
		
		$full_page = ob_get_clean();
		
		return $full_page;
	}
	
	final protected function _getView($args, $controller="", $override = false) {
		if (!is_array($args)) {
			$args = array(
				'name' => $args
			);
		}
		$args = array_merge(array(
				'name' => $args,
				'controller' => $controller,
				'override' => $override
			), $args);
		if (empty($args['name'])) {
			return false;
		}
		if (empty($args['controller'])) {
			$args['controller'] = $this->params[reset(array_slice(array_keys($this->params), 0, 1))];
		}
		if ($this->overriddenView == false && $args['override'] == true) {
			$this->overriddenView = $args['override'];
			unset($args['override']);
			$this->overriddenViewToLoad = $args;
			return true;
		}
		if ((strlen(Config::read("Branch.name")) == 0)) {
			if (((file_exists(Config::read("Path.physical")."/views/".$args['controller']."/".$args['name'].".php") && (include(Config::read("Path.physical")."/views/".$args['controller']."/".$args['name'].".php")) == true)) || ((file_exists(Config::read("Path.physical")."/views/".$args['controller']."/".str_replace('_', '-', $args['name']).".php") && (include(Config::read("Path.physical")."/views/".$args['controller']."/".str_replace('_', '-', $args['name']).".php")) == true))) {
				return true;
			}
		} else {
			if (((file_exists(Config::read("Path.physical")."/branches/".Config::read("Branch.name")."/views/".$args['controller']."/".$args['name'].".php") && (include(Config::read("Path.physical")."/branches/".Config::read("Branch.name")."/views/".$args['controller']."/".$args['name'].".php")) == true)) || (file_exists(Config::read("Path.physical")."/branches/".Config::read("Branch.name")."/views/".$args['controller']."/".str_replace('_', '-', $args['name']).".php") && (include(Config::read("Path.physical")."/branches/".Config::read("Branch.name")."/views/".$args['controller']."/".str_replace('_', '-', $args['name']).".php")) == true)) {
				return true;
			}
		}
		return false;
	}
	
	final protected function _viewExists($args, $controller="", $checkmethod = false) {
		if (!is_array($args)) {
			$args = array(
				'name' => $args
			);
		}
		$args = array_merge(array(
				'name' => $args,
				'controller' => $controller,
				'checkmethod' => $checkmethod
			), $args);
		if (empty($args['name'])) {
			return false;
		}
		if (empty($args['controller'])) {
			$args['controller'] = $this->params[reset(array_slice(array_keys($this->params), 0, 1))];
		}
		if (($args['name'][0] != '_' && (!isset($this->bounceback['check']) || $this->bounceback['check'] != $args['controller']) && !in_array($args['controller'], $this->notAView))) {
			if ($args['checkmethod'] === true) {
				if (method_exists($this, $args['name'])) {
					return true;
				} else {
					return false;
				}
			} else {
				if (((!strlen(Config::read("Branch.name")) && (file_exists(Config::read("Path.physical")."/views/".$args['controller']."/".$args['name'].".php") || file_exists(Config::read("Path.physical")."/views/".$args['controller']."/".str_replace('_', '-', $args['name']).".php"))) || (strlen(Config::read("Branch.name")) && (file_exists(Config::read("Path.physical")."/branches/".Config::read("Branch.name")."/views/".$args['controller']."/".$args['name'].".php") || file_exists(Config::read("Path.physical")."/branches/".Config::read("Branch.name")."/views/".$args['controller']."/".str_replace('_', '-', $args['name']).".php"))))) {
					if ($args['checkmethod'] == 'both') {
						if (method_exists($this, $args['name'])) {
							return true;
						} else {
							return false;
						}
					}
					return true;
				} else {
					return false;
				}
			}
		} else {
			return false;
		}
	}
	
	final private function _renderLayout($name) {
		$content_for_layout = $this->content_for_layout;
		if (strlen(Config::read("Branch.name")) && file_exists(Config::read("Path.physical")."/branches/".Config::read("Branch.name")."/views/layouts/{$name}.php") && (include(Config::read("Path.physical")."/branches/".Config::read("Branch.name")."/views/layouts/{$name}.php")) == true) {
			return true;
		} else {
			if (file_exists(Config::read("Path.physical")."/views/layouts/{$name}.php") && (include(Config::read("Path.physical")."/views/layouts/{$name}.php")) == true) {
				return true;
			} else {
				return false;
			}
		}
	}
	
	final protected function _addFilter($args, $type = "all", $methods = array(), $schedule = "") {
		if (!is_array($args)) {
			$args = array(
				'filter' => $args
			);
		}
		$args = array_merge(array(
				'filter' => $args,
				'type' => $type,
				'methods' => $methods,
				'schedule' => $schedule
			), $args);
		if (empty($args['schedule'])) {
			$args['schedule'] = "View.before";
		}
		if (empty($args['filter'])) {
			return false;
		}
		$path = explode('.', $args['schedule']);
		$filter_holder =& $this->filters;
		foreach($path as $i => $path_key) {
			if ($i == (count($path) - 1)) {
				if (!isset($filter_holder[$path_key][$args['filter']])) {
					$filter_holder[$path_key][$args['filter']] = array();
				}
				if (isset($filter_holder[$path_key][$args['filter']]['methods']) && $args['type'] != 'all') {
					foreach($filter_holder[$path_key][$args['filter']]['methods'] as $value) {
						if (!in_array($value, (array)$args['methods'])) {
							$args['methods'] = array_merge((array)$args['methods'], (array)$value);
						}
					}
				}
				$filter_holder[$path_key][$args['filter']] = array_merge(array(
					'type' => $args['type'],
					'methods' => $args['methods']
				), $filter_holder[$path_key][$args['filter']]);
				return true;
			} else {
				if (!isset($filter_holder[$path_key])) {
					$filter_holder[$path_key] = array();
				}
				$filter_holder =& $filter_holder[$path_key];
			}
		}
	}
	
	final protected function _addFilterExcept($filter, $methods = array(), $schedule = "") {
		return $this->_addFilter(array(
			'filter' => $filter,
			'type' => 'except',
			'methods' => $methods,
			'schedule' => $schedule
		));
	}
	
	final protected function _addFilterOnly($filter, $methods = array(), $schedule = "") {
		return $this->_addFilter(array(
			'filter' => $filter,
			'type' => 'only',
			'methods' => $methods,
			'schedule' => $schedule
		));
	}
	
	final protected function _addFilterAll($filter, $schedule = "") {
		return $this->_addFilter(array(
			'filter' => $filter,
			'type' => 'all',
			'methods' => array(),
			'schedule' => $schedule
		));
	}
}
?>