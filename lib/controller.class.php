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
	private $filters = array();
	private $overriddenView = false;
	private	$overriddenViewToLoad = array();
	private $bounceback = array('check' => '', 'bounce' => '');
	
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
			$this->_runBounceBack();
			
			ob_start();
				$this->_runFilters('View.before');
				if (is_callable(array($this, $this->viewToLoad)) && call_user_func(array($this, $this->viewToLoad)) === false) {
					Error::trigger("VIEW_NOT_FOUND");
				}
				if ($this->overriddenView) {
					$this->_getView($this->overriddenViewToLoad);
				} else {
					$this->_getView($this->viewToLoad);
				}
				$this->_runFilters('View.after');
			$this->content_for_layout = ob_get_clean();
			$this->_runFilters('View.afterProcessing');
		} else {
			$error = true;
			Error::trigger("VIEW_NOT_FOUND");
		}
		
		$this->_runFilters('Layout.before');
		if(!empty($this->layout) && !$error) {
			$this->_renderLayout($this->layout);
		} else {
			echo $this->content_for_layout;
		}
		$this->_runFilters('Layout.after');
		
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
	
	final protected function _addFilterAll($filter, $schedule) {
		$path = explode('.', $schedule);
		$filter_holder =& $this->filters;
		foreach($path as $i => $path_key) {
			if ($i == (count($path) - 1)) {
				if (!isset($filter_holder[$path_key][$filter])) {
					$filter_holder[$path_key][$filter] = array();
				}
				$filter_holder[$path_key][$filter] = array(
					'type' => 'except',
					'methods' => array()
				);
				return true;
			} else {
				if (!isset($filter_holder[$path_key])) {
					$filter_holder[$path_key] = array();
				}
				$filter_holder =& $filter_holder[$path_key];
			}
		}
		return false;
	} 
	
	final protected function _addFilterOn($filter, $methods, $schedule) {
		$methods = (array)$methods;
		$path = explode('.', $schedule);
		$filter_holder =& $this->filters;
		foreach($path as $i => $path_key) {
			if ($i == (count($path) - 1)) {
				if (!isset($filter_holder[$path_key][$filter])) {
					$filter_holder[$path_key][$filter] = array(
						'type' => 'only',
						'methods' => array()
					);
				}
				if ($filter_holder[$path_key][$filter]['type'] == 'except') {
					foreach($filter_holder[$path_key][$filter]['methods'] as $key => $method) {
						if (in_array($method, $methods)) {
							unset($filter_holder[$path_key][$filter]['methods'][$key]);
						}
					}
				} else if ($filter_holder[$path_key][$filter]['type'] == 'only') {
					foreach($methods as $key => $method) {
						if (!in_array($method, $filter_holder[$path_key][$filter]['methods'])) {
							$filter_holder[$path_key][$filter]['methods'][] = $method;
						}
					}
				}
				return true;
			} else {
				if (!isset($filter_holder[$path_key])) {
					$filter_holder[$path_key] = array();
				}
				$filter_holder =& $filter_holder[$path_key];
			}
		}
		return false;
	}
	
	final protected function _addFilterExcept($filter, $methods, $schedule) {
		$methods = (array)$methods;
		$path = explode('.', $schedule);
		$filter_holder =& $this->filters;
		foreach($path as $i => $path_key) {
			if ($i == (count($path) - 1)) {
				if (!isset($filter_holder[$path_key][$filter])) {
					$filter_holder[$path_key][$filter] = array(
						'type' => 'only',
						'methods' => array()
					);
				}
				if ($filter_holder[$path_key][$filter]['type'] == 'except') {
					foreach($methods as $key => $method) {
						if (!in_array($method, $filter_holder[$path_key][$filter]['methods'])) {
							$filter_holder[$path_key][$filter]['methods'][] = $method;
						}
					}
				} else if ($filter_holder[$path_key][$filter]['type'] == 'only') {
					$filter_holder[$path_key][$filter] = array(
						'type' => 'except',
						'methods' => $methods
					);
				}
				return true;
			} else {
				if (!isset($filter_holder[$path_key])) {
					$filter_holder[$path_key] = array();
				}
				$filter_holder =& $filter_holder[$path_key];
			}
		}
		return false;
	}

	final protected function _removeFilterOn($filter, $methods, $schedule) {
		$methods = (array)$methods;
		$path = explode('.', $schedule);
		$filter_holder =& $this->filters;
		foreach($path as $i => $path_key) {
			if ($i == (count($path) - 1)) {
				if (!isset($filter_holder[$path_key][$filter])) {
					return true;
				}
				if ($filter_holder[$path_key][$filter]['type'] == 'except') {
					foreach($methods as $key => $method) {
						if (!in_array($method, $filter_holder[$path_key][$filter]['methods'])) {
							$filter_holder[$path_key][$filter]['methods'][] = $method;
						}
					}
					return true;
				} else if ($filter_holder[$path_key][$filter]['type'] == 'only') {
					foreach($filter_holder[$path_key][$filter]['methods'] as $key => $method) {
						if (in_array($method, $methods)) {
							unset($filter_holder[$path_key][$filter]['methods'][$key]);
						}
					}
					
					if (count($filter_holder[$path_key][$filter]['methods']) == 0) {
						$this->_removeFilter($filter);
					}
					return true;
				}
				return false;
			} else {
				if (!isset($filter_holder[$path_key])) {
					$filter_holder[$path_key] = array();
				}
				$filter_holder =& $filter_holder[$path_key];
			}
		}
		return false;
	}
	
	final protected function _removeFilter($filter, $schedule) {
		$path = explode('.', $schedule);
		$filter_holder =& $this->filters;
		foreach($path as $i => $path_key) {
			if ($i == (count($path) - 1)) {
				if (isset($filter_holder[$path_key][$filter])) {
					unset($filter_holder[$path_key][$filter]);
				}
				return true;
			} else {
				if (!isset($filter_holder[$path_key])) {
					$filter_holder[$path_key] = array();
				}
				$filter_holder =& $filter_holder[$path_key];
			}
		}
		return false;
	}
	
	final private function _runFilters($schedule) {
		$path = explode('.', $schedule);
		$filter_holder =& $this->filters;
		foreach($path as $i => $path_key) {
			if ($i == (count($path) - 1)) {
				if (isset($filter_holder[$path_key])) {
					foreach($filter_holder[$path_key] as $filter => $attributes) {
						if ($attributes['type'] == 'except') {
							if (!in_array($this->viewToLoad, $attributes['methods'])) {
								call_user_func(array($this, $filter));
							}
						} else if ($attributes['type'] == 'only') {
							if (in_array($this->viewToLoad, $attributes['methods'])) {
								call_user_func(array($this, $filter));
							}
						}
					}
				}
				return true;
			} else {
				if (!isset($filter_holder[$path_key])) {
					$filter_holder[$path_key] = array();
				}
				$filter_holder =& $filter_holder[$path_key];
			}
		}
		return false;
	}
	
	final protected function _setBounceBack($check, $bounce) {
		$this->bounceback['check'] = $check;
		$this->bounceback['bounce'] = $bounce;
	}
	
	final protected function _removeBounceBack() {
		$this->bounceback = array('check' => '', 'bounce' => '');
	}
	
	final private function _runBounceBack() {
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
	}
}
?>