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
		$this->params = Config::read("URI.working");
		if (!strlen($this->params['view'])) {
			$this->params['view'] = Config::read("URI.map.view");
		}
		
		$this->viewToLoad = Config::uriToMethod($this->params['view']);
		
		$this->formhandler = new Formhandler($this);
	}
	
	final private function _controllerDestruct() {
		unset($this->viewContent);
		unset($this->fullPageContent);
		unset($this->formhandler);
	}
	
	final public function _showView() {
		## Run the controller's Setup
		$this->_controllerSetup();
		## Set up the actual page
		$this->_loadView();
		
		## First Designer Fix
		$this->_designerFix($this->fullPageContent);
		
		## Form Fix
		$this->formhandler->decode($this->fullPageContent);
		
		## Second Designer Fix
		//$this->_designerFix($fullPage);
		
		## Output Page
		$this->_runFilters('Page.output.before');
		echo $this->fullPageContent;
		$this->_runFilters('Page.output.after');
		$this->_controllerDestruct();
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
			$this->viewContent = ob_get_clean();
			$this->_runFilters('View.after');
		} else {
			$error = true;
			Error::trigger("VIEW_NOT_FOUND");
		}
		
		$this->_runFilters('Layout.before');
		if(!$this->_renderLayout() && !$error) {
			echo $this->viewContent;
		}
		unset($this->viewContent);
		$this->_runFilters('Layout.after');
		
		$this->fullPageContent = ob_get_clean();
		$this->_runFilters('Page.after');
	}
	
	final protected function _getView($args, $controller="", $override = false) {
		if (!is_array($args)) {
			$args = array(
				'name' => $args,
				'controller' => $controller,
				'override' => $override
			);
		}
		if (empty($args['name'])) {
			return false;
		}
		if (empty($args['controller'])) {
			$args['controller'] = $this->params['controller'];
		}
		if ($this->overriddenView == false && $args['override'] == true) {
			$this->overriddenView = $args['override'];
			unset($args['override']);
			$this->overriddenViewToLoad = $args;
			return true;
		}
		if ((strlen(Config::read("Branch.name")) == 0)) {
			$path = Config::read("Path.physical")."/views/".Config::uriToFile(Config::classToFile($args['controller']))."/".Config::uriToFile(Config::methodToFile($args['name'])).".php";
			if (((file_exists($path) && (include($path)) == true))) {
				return true;
			}
			unset($path);
		} else {
			$path = Config::read("Path.physical")."/branches/".Config::uriToFile(Config::classToFile(Config::read("Branch.name")))."/views/".Config::uriToFile(Config::classToFile($args['controller']))."/".Config::uriToFile(Config::methodToFile($args['name'])).".php";
			if (((file_exists($path) && (include($path)) == true))) {
				return true;
			}
			unset($path);
		}
		return false;
	}
	
	final protected function _viewExists($args, $controller="", $checkmethod = false) {
		if (!is_array($args)) {
			$args = array(
				'name' => $args,
				'controller' => $controller,
				'override' => $override
			);
		}
		if (empty($args['name'])) {
			return false;
		}
		if (empty($args['controller'])) {
			$args['controller'] = $this->params['controller'];
		}
		if (($args['name'][0] != '_' && (!isset($this->bounceback['check']) || $this->bounceback['check'] != $args['controller']) && !in_array($args['controller'], $this->notAView))) {
			if ($args['checkmethod'] === true) {
				if (is_callable(array($this, $args['name'])) && method_exists($this, $args['name'])) {
					return true;
				} else {
					return false;
				}
			} else {
				$path = Config::read("Path.physical").((strlen(Config::read("Branch.name"))) ? "/branches/".Config::uriToFile(Config::classToFile(Config::read("Branch.name"))) : "")."/views/".Config::uriToFile(Config::classToFile($args['controller']))."/".Config::uriToFile(Config::methodToFile($args['name'])).".php";
				if (file_exists($path)) {
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
				unset($path);
			}
		} else {
			return false;
		}
	}
	
	final protected function _setLayout($name, $branch = '') {
		$layout = array('name' => $name, 'branch' => $branch);
		if (($layout['branch'] == Config::read('System.rootIdentifier')) || (!strlen(Config::read("Branch.name")) && empty($layout['branch']))) {
			$path = Config::read("Path.physical")."/views/layouts/".Config::uriToFile(Config::methodToFile($layout['name'])).".php";
			if (file_exists($path)) {
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
			$path = Config::read("Path.physical")."/branches/".Config::uriToFile(Config::classToFile($branchToUse))."/views/layouts/".Config::uriToFile(Config::methodToFile($layout['name'])).".php";
			if (file_exists($path)) {
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
	
	final private function _renderLayout() {
		$layout = array_merge(array('name'=>'', 'branch'=>''), (array)$this->layout);
		if (empty($layout['name'])) {
			return false;
		}
		if (($layout['branch'] == Config::read('System.rootIdentifier')) || (!strlen(Config::read("Branch.name")) && empty($layout['branch']))) {
			$path = Config::read("Path.physical")."/views/layouts/".Config::uriToFile(Config::methodToFile($layout['name'])).".php";
			if ((file_exists($path) && (include($path)) == true)) {
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
			$path = Config::read("Path.physical")."/branches/".Config::uriToFile(Config::classToFile($branchToUse))."/views/layouts/".Config::uriToFile(Config::methodToFile($layout['name'])).".php";
			if ((file_exists($path) && (include($path)) == true)) {
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
			$this->viewToLoad = Config::uriToMethod($this->params['view']);
			
			if ($this->_viewExists(array("name" => $this->viewToLoad, "checkmethod" => true)) !== true) {
				return false;
			}
			
			if (is_callable(array($this, $this->bounceback['check'])) && call_user_func(array($this, $this->bounceback['check'])) === false) {
				return false;
			}
			return true;
		}
		return false;
	}
	
	final protected function &_getViewContent() {
		return $this->viewContent;
	}
	
	final protected function _setViewContent(&$content) {
		$this->viewContent = $content;
	}
	
	final protected function &_getFullPageContent() {
		return $this->fullPageContent;
	}
	
	final protected function _setFullPageContent(&$content) {
		$this->fullPageContent = $content;
	}
	
	public function _designerFixCallback($link) {
		
		$link_arr = explode("/", $link[2]);
		$up_link_count = count(array_keys(array_slice($link_arr, 1), ".."));
		
		$return = '';
		switch ($link_arr[0]) {
			case "[current]":
				$new_base = explode("/", Config::read("Path.current"));
				$return = implode("/", (($up_link_count) ? array_slice($new_base, 0, -$up_link_count) : $new_base)) . implode("/", array_pad(array_slice($link_arr, $up_link_count+1), -(count(array_slice($link_arr, $up_link_count+1))+1), ""));
			break;
			
			case "[site]":
				$new_base = explode("/", Config::read("Path.site"));
				$return = implode("/", $new_base) . implode("/", array_pad(array_slice($link_arr, 1), -(count(array_slice($link_arr, 1))+1), ""));
			break;
			
			case "[skin]":
				$new_base = explode("/", Config::read("Path.skin"));
				$return = implode("/", $new_base) . implode("/", array_pad(array_slice($link_arr, 1), -(count(array_slice($link_arr, 1))+1), ""));
			break;
			
			case "[root]":
				$new_base = explode("/", Config::read("Path.root"));
				$return = implode("/", $new_base) . implode("/", array_pad(array_slice($link_arr, 1), -(count(array_slice($link_arr, 1))+1), ""));
			break;
			
			case "[branch.site]":
				$new_base = explode("/", Config::read("Path.branch"));
				$return = implode("/", $new_base) . implode("/", array_pad(array_slice($link_arr, 1), -(count(array_slice($link_arr, 1))+1), ""));
			break;
			
			case "[branch.skin]":
				$new_base = explode("/", Config::read("Path.branchSkin"));
				$return = implode("/", $new_base) . implode("/", array_pad(array_slice($link_arr, 1), -(count(array_slice($link_arr, 1))+1), ""));
			break;
			
			case "[branch.root]":
				$new_base = explode("/", Config::read("Path.branchRoot"));
				$return = implode("/", $new_base) . implode("/", array_pad(array_slice($link_arr, 1), -(count(array_slice($link_arr, 1))+1), ""));
			break;
			
			default:
				$working_uri = Config::read("URI.working");
				
				if (strlen(Config::read("Branch.name"))) {
					$working_uri = array_merge(array("branch"=>Config::read("Branch.name")), $working_uri);
				}
				
				foreach($working_uri as $key => $item) {
					$tmp_key = "[".$key."]";
					
					if ($link_arr[0] == $tmp_key) {
						$position = array_search($key, array_keys($working_uri));
						$new_base = explode("/", Config::read("Path.root"));
						
						$new_url = array_merge( array_merge($new_base, array_slice($working_uri, 0, ($position+1))), array_pad(array_slice($link_arr, $up_link_count+1), -(count(array_slice($link_arr, $up_link_count+1))), "") );
						
						$return = implode("/",  $new_url );
						break 1;
					}
				}
			break;
		}
		
		$return = str_replace("//", "/", $return);
		
		if (!Config::read("URI.useModRewrite") && !empty($return)) {
			if (substr_count($return, "?", 0) > 1) {
				$return = strrev(preg_replace("/\?/i", "&", strrev($return), (substr_count($return, "?", 0) - 1)));
			}

		}
		
		return $link[1].((!empty($return)) ? $return : $link[2]);
	}
	
	public function _designerFix (&$content) {
		$content = preg_replace_callback("/(=\"|=\'|=)([\[\]][^(\"|\'|[:space:]|>)]+)/", array($this, "_designerFixCallback"), $content);
	}

}
?>