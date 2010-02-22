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
	private $view_to_load;
	private $formhandler;
	private $designer;
	private $params;
	private $view_overridden = false;
	
	protected $bounceback = array();
	protected $filter = array();
	protected $filter_except = array();
	protected $filter_only = array();
	protected $not_a_view = array();
	
	final function __construct () {
		## Construct Code ##
		$this->params = Config::loadableURI(Config::read("URI.working"));
		if (!strlen(reset(array_slice($this->params, 1, 1)))) {
			$this->params[reset(array_slice(array_keys($this->params), 1, 1))] = reset(array_slice(Config::read("URI.map"), 1, 1));
		}
		
		$this->view_to_load = $this->params[reset(array_slice(array_keys($this->params), 1, 1))];
		
		$this->formhandler = new Formhandler($this);
		$this->designer = new Designer();
	}
	
	final public function showView () {
		## Set up the actual page
		$full_page = $this->loadView();
		
		## First Designer Fix
		$this->designer->doFixes($full_page);
		
		## Form Fix
		$this->formhandler->decode($full_page);
		
		## Second Designer Fix
		$this->designer->doFixes($full_page);
		
		## Output Page
		echo $full_page;
	}
	
	final private function loadView() {
		ob_start();
		$error = false;
		
		if ((is_callable(array($this, $this->view_to_load)) && $this->viewExists($this->view_to_load)) || (!$this->viewExists($this->view_to_load) && (isset($this->bounceback['check']) && isset($this->bounceback['bounce'])) && method_exists($this, $this->bounceback['check']) && method_exists($this, $this->bounceback['bounce']))) {
			if (!empty($this->filter) || !empty($this->filter_only) || !empty($this->filter_except)) {
				if (isset($this->filter)) {
					if (!empty($this->filter) && !is_array($this->filter)) {
						call_user_func(array($this, $this->filter));
					}
				}
				
				if (isset($this->filter_only) && sizeof($this->filter_only) > 1 && is_array($this->filter_only[1]) && in_array($this->view_to_load, $this->filter_only[1])) {
					if (!empty($this->filter_only[0]) && !is_array($this->filter_only[0])) {
						call_user_func(array($this, $this->filter_only[0]));
					}
				}
				
				if (isset($this->filter_except) && sizeof($this->filter_except) > 1 && is_array($this->filter_except[1]) && !in_array($this->view_to_load, $this->filter_except[1])) {
					if (!empty($this->filter_except[0]) && !is_array($this->filter_except[0])) {
						call_user_func(array($this, $this->filter_except[0]));
					}
				}
			}
			
			if ((isset($this->bounceback['check']) && isset($this->bounceback['bounce'])) && !$this->viewExists($this->view_to_load)) {
				$values = array_values(Config::read('URI.working'));
				$this->params = array_combine(array_keys(Config::read('URI.working')), array_slice(array_merge(array($values[0]), array($this->bounceback['bounce']),array_slice($values, 1)), 0, count(array_keys(Config::read('URI.working')))));
				Config::register('Param', $this->params);
				$this->params = Config::loadableURI($this->params);
				$this->view_to_load = $this->params[reset(array_slice(array_keys($this->params), 1, 1))];
				
				if (!$this->viewExists($this->view_to_load)) {
					$error = true;
					Error::trigger("VIEW_NOT_FOUND");
				}
				
				if (is_callable(array($this, $this->bounceback['check'])) && call_user_func(array($this, $this->bounceback['check'])) === false) {
					$error = true;
					Error::trigger("VIEW_NOT_FOUND");
				}
			}
			
			ob_start();
				if (is_callable(array($this, $this->view_to_load)) && call_user_func(array($this, $this->view_to_load)) === false) {
					Error::trigger("VIEW_NOT_FOUND");
				}
				if (!$this->view_overridden) $this->getView($this->view_to_load);
			$this->content_for_layout = ob_get_clean();
			
		} else {
			$error = true;
			Error::trigger("VIEW_NOT_FOUND");
		}
		
		if(!empty($this->layout) && !$error) {
			$this->renderLayout($this->layout);
		} else {
			echo $this->content_for_layout;
		}
		
		$full_page = ob_get_clean();
		
		return $full_page;
	}
	
	final protected function getView ($name, $controller="", $override = false) {
		if ($this->view_overridden == false && $override == true) {
			$this->view_overridden = $override;
		}
		if (empty($controller)) {
			$controller = $this->params[reset(array_slice(array_keys($this->params), 0, 1))];
		}
		if ((!strlen(Config::read("Branch.name")) && file_exists(Config::read("Path.physical")."/views/".strtolower($controller)."/{$name}.php") && (include(Config::read("Path.physical")."/views/".strtolower($controller)."/{$name}.php")) == true) || (strlen(Config::read("Branch.name")) && file_exists(Config::read("Path.physical")."/branches/".Config::read("Branch.name")."/views/".strtolower($controller)."/{$name}.php") && (include(Config::read("Path.physical")."/branches/".Config::read("Branch.name")."/views/".strtolower($controller)."/{$name}.php")) == true)) {
			return true;
		} else {
			return false;
		}
	}
	
	final protected function viewExists ($name, $controller="") {
		if (empty($controller)) $controller = $this->params[reset(array_slice(array_keys($this->params), 0, 1))];
		
		if (!preg_match('/^_(.*)$/i', $name) && (!isset($this->bounceback['check']) || $this->bounceback['check'] != $name) && !in_array($name, $this->not_a_view) && method_exists($this, $name)) {
			return true;
		} else {
			return false;
		}
	}
	
	final protected function renderLayout ($name) {
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
}
?>