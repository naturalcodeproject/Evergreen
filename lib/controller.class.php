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
	private $viewToLoad = null;
	private $formhandler = null;
	private $designer = null;
	private $layout = null;
	private $params = array();
	private $filters = array();
	private $overriddenView = false;
	private	$overriddenViewToLoad = array();
	private $bounceback = null;
	
	private $viewContent = null;
	private $fullPageContent = null;
	
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
		$this->_loadView();
		
		## Full Page Content Holder
		$fullPage = $this->_getFullPageContent();
		
		## First Designer Fix
		$this->designer->doFixes($$fullPage);
		
		## Form Fix
		$this->formhandler->decode($fullPage);
		
		## Second Designer Fix
		$this->designer->doFixes($fullPage);
		
		## Set Full Page Content After Fixes
		$this->_setFullPageContent($fullPage);
		
		## Output Page
		$this->_runFilters('Page.output.before');
		echo $this->_getFullPageContent();
		$this->_runFilters('Page.output.after');
	}
	
	final private function _loadView() {
		ob_start();
		$error = false;
		
		if ((is_callable(array($this, $this->viewToLoad)) && $this->_viewExists(array("name" => $this->viewToLoad, "checkmethod" => true))) || (!$this->_viewExists(array("name" => $this->viewToLoad, "checkmethod" => true)) && ($this->_runBounceBack()))) {
			$this->_runFilters('Page.before');
			$this->_runFilters('View.before');
			ob_start();
				$this->_runFilters('View.content.before');
				if (is_callable(array($this, $this->viewToLoad)) && call_user_func(array($this, $this->viewToLoad)) === false) {
					Error::trigger("VIEW_NOT_FOUND");
				}
				if ($this->overriddenView) {
					$this->_getView($this->overriddenViewToLoad);
				} else {
					$this->_getView($this->viewToLoad);
				}
				$this->_runFilters('View.content.after');
			$this->_setViewContent(ob_get_clean());
			$this->_runFilters('View.after');
		} else {
			$error = true;
			Error::trigger("VIEW_NOT_FOUND");
		}
		
		$this->_runFilters('Layout.before');
		if(!$this->_renderLayout() && !$error) {
			echo $this->_getViewContent();
		}
		$this->_runFilters('Layout.after');
		
		$this->_setFullPageContent(ob_get_clean());
		$this->_runFilters('Page.after');
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
	
	final private function _renderLayout() {
		$layout = array_merge(array('name'=>'', 'branch'=>''), (array)$this->layout);
		if (empty($layout['name'])) {
			return false;
		}
		if (($layout['branch'] == Config::read('System.rootIdentifier')) || (!strlen(Config::read("Branch.name")) && empty($layout['branch']))) {
			if ((file_exists(Config::read("Path.physical")."/views/layouts/{$layout['name']}.php") && (include(Config::read("Path.physical")."/views/layouts/{$layout['name']}.php")) == true) || (file_exists(Config::read("Path.physical")."/views/layouts/".str_replace("_", "-", $layout['name']).".php") && (include(Config::read("Path.physical")."/views/layouts/".str_replace("_", "-", $layout['name']).".php")) == true)) {
				return true;
			} else {
				return false;
			}
		} else if ((strlen(Config::read("Branch.name")) && empty($layout['branch'])) || !empty($layout['branch'])) {
			if (!empty($layout['branch'])) {
				$branchToUse = $layout['branch'];
			} else {
				$branchToUse = Config::read("Branch.name");
			}
			if ((file_exists(Config::read("Path.physical")."/branches/".$branchToUse."/views/layouts/{$layout['name']}.php") && (include(Config::read("Path.physical")."/branches/".$branchToUse."/views/layouts/{$layout['name']}.php")) == true) || (file_exists(Config::read("Path.physical")."/branches/".$branchToUse."/views/layouts/".str_replace("_", "-", $layout['name']).".php") && (include(Config::read("Path.physical")."/branches/".$branchToUse."/views/layouts/".str_replace("_", "-", $layout['name']).".php")) == true)) {
				return true;
			} else {
				return false;
			}
		}
		return false;
	}
	
	final protected function _addFilterAll($filter, $schedule) {
		if (!isset($this->filters[$schedule])) {
			$this->filters[$schedule] = array();
		}
		$this->filters[$schedule][$filter] = array(
			'type' => 'except',
			'methods' => array()
		);
		return true;
	} 
	
	final protected function _addFilterOn($filter, $methods, $schedule) {
		$methods = (array)$methods;
		if (!isset($this->filters[$schedule])) {
			$this->filters[$schedule] = array();
		}
		if (!isset($this->filters[$schedule][$filter])) {
			$this->filters[$schedule][$filter] = array(
				'type' => 'only',
				'methods' => array()
			);
		}
		if ($this->filters[$schedule][$filter]['type'] == 'except') {
			foreach($this->filters[$schedule][$filter]['methods'] as $key => $method) {
				if (in_array($method, $methods)) {
					unset($this->filters[$schedule][$filter]['methods'][$key]);
				}
			}
		} else if ($this->filters[$schedule][$filter]['type'] == 'only') {
			foreach($methods as $key => $method) {
				if (!in_array($method, $this->filters[$schedule][$filter]['methods'])) {
					$this->filters[$schedule][$filter]['methods'][] = $method;
				}
			}
		} else {
			return false;
		}
		return true;
	}
	
	final protected function _addFilterExcept($filter, $methods, $schedule) {
		$methods = (array)$methods;
		if (!isset($this->filters[$schedule])) {
			$this->filters[$schedule] = array();
		}
		if (!isset($this->filters[$schedule][$filter])) {
			$this->filters[$schedule][$filter] = array(
				'type' => 'except',
				'methods' => array()
			);
		}
		if ($this->filters[$schedule][$filter]['type'] == 'except') {
			foreach($methods as $key => $method) {
				if (!in_array($method, $this->filters[$schedule][$filter]['methods'])) {
					$this->filters[$schedule][$filter]['methods'][] = $method;
				}
			}
		} else if ($this->filters[$schedule][$filter]['type'] == 'only') {
			$this->filters[$schedule][$filter] = array(
				'type' => 'except',
				'methods' => $methods
			);
		} else {
			return false;
		}
		return true;
	}

	final protected function _removeFilterOn($filter, $methods, $schedule) {
		$methods = (array)$methods;
		if (!isset($this->filters[$schedule][$filter])) {
			return true;
		}
		if ($this->filters[$schedule][$filter]['type'] == 'except') {
			foreach($methods as $key => $method) {
				if (!in_array($method, $this->filters[$schedule][$filter]['methods'])) {
					$this->filters[$schedule][$filter]['methods'][] = $method;
				}
			}
			return true;
		} else if ($this->filters[$schedule][$filter]['type'] == 'only') {
			foreach($this->filters[$schedule][$filter]['methods'] as $key => $method) {
				if (in_array($method, $methods)) {
					unset($this->filters[$schedule][$filter]['methods'][$key]);
				}
			}
			
			if (count($this->filters[$schedule][$filter]['methods']) == 0) {
				$this->_removeFilter($filter);
			}
			return true;
		}
		return false;
	}
	
	final protected function _removeFilter($filter, $schedule) {
		if (isset($this->filters[$schedule][$filter])) {
			unset($this->filters[$schedule][$filter]);
		}
		return true;
	}
	
	final private function _runFilters($schedule) {
		if (isset($this->filters[$schedule])) {
			foreach($this->filters[$schedule] as $filter => $attributes) {
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
			return true;
		}
		return false;
	}
	
	final protected function _setBounceBack($check, $bounce) {
		$this->bounceback = array(
			'check' => $check,
			'bounce' => $bounce
		);
		return true;
	}
	
	final protected function _removeBounceBack() {
		$this->bounceback = null;
		return true;
	}
	
	final private function _runBounceBack() {
		if (((isset($this->bounceback['check']) && method_exists($this, $this->bounceback['check'])) && (isset($this->bounceback['bounce']) && method_exists($this, $this->bounceback['bounce']))) && !$this->_viewExists(array("name" => $this->viewToLoad, "checkmethod" => true))) {
			$values = array_values(Config::read('URI.working'));
			$this->params = array_combine(array_keys(Config::read('URI.working')), array_slice(array_merge(array($values[0]), array($this->bounceback['bounce']),array_slice($values, 1)), 0, count(array_keys(Config::read('URI.working')))));
			Config::register('Param', $this->params);
			$this->params = Config::loadableURI($this->params);
			$this->viewToLoad = $this->params[reset(array_slice(array_keys($this->params), 1, 1))];
			
			if (!$this->_viewExists(array("name" => $this->viewToLoad, "checkmethod" => true))) {
				return false;
			}
			
			if (is_callable(array($this, $this->bounceback['check'])) && call_user_func(array($this, $this->bounceback['check'])) === false) {
				return false;
			}
			return true;
		}
		return false;
	}
	
	final protected function _setLayout($name, $branch = '') {
		$layout = array('name' => $name, 'branch' => $branch);
		if (($layout['branch'] == Config::read('System.rootIdentifier')) || (!strlen(Config::read("Branch.name")) && empty($layout['branch']))) {
			if ((file_exists(Config::read("Path.physical")."/views/layouts/{$layout['name']}.php")) || (file_exists(Config::read("Path.physical")."/views/layouts/".str_replace("_", "-", $layout['name']).".php"))) {
				$this->layout = $layout;
				return true;
			} else {
				return false;
			}
		} else if ((strlen(Config::read("Branch.name")) && empty($layout['branch'])) || !empty($layout['branch'])) {
			if (!empty($layout['branch'])) {
				$branchToUse = $layout['branch'];
			} else {
				$branchToUse = Config::read("Branch.name");
			}
			if ((file_exists(Config::read("Path.physical")."/branches/".$branchToUse."/views/layouts/{$layout['name']}.php")) || (file_exists(Config::read("Path.physical")."/branches/".$branchToUse."/views/layouts/".str_replace("_", "-", $layout['name']).".php"))) {
				$this->layout = $layout;
				return true;
			} else {
				return false;
			}
		}
		return false;
	}
	
	final protected function _removeLayout() {
		$this->layout = null;
		return true;
	}
	
	final protected function _getViewContent() {
		return $this->viewContent;
	}
	
	final protected function _setViewContent($content) {
		$this->viewContent = $content;
	}
	
	final protected function _getFullPageContent() {
		return $this->fullPageContent;
	}
	
	final protected function _setFullPageContent($content) {
		$this->fullPageContent = $content;
	}
}
?>