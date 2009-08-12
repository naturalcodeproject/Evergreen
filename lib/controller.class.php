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
	protected $view_to_load;
	protected $params;
	protected $settings;
	protected $formhandler;
	protected $branch_name;
	protected $config;
	private $view_overridden = false;
	
	final function __construct () {
		## Construct Code
		$this->config = Factory::get_config();
		$this->branch_name = Factory::get_config()->get_branch_name();
		$this->params = Factory::get_config()->get_working_uri();
		if (!strlen(reset(array_slice($this->params, 1, 1)))) $this->params[reset(array_slice(array_keys($this->params), 1, 1))] = reset(array_slice(Factory::get_config()->get_uri_map(), 1, 1));
		
		$this->view_to_load = $this->params[reset(array_slice(array_keys($this->params), 1, 1))];
		
		$this->formhandler = new Formhandler($this);
		$this->designer = new Designer();
		
		## Show View ##
		//$this->show_view();
	}
	
	final public function show_view () {
		## Set up the actual page
		$full_page = $this->run_view();
		
		## First Designer Fix
		$this->designer->do_fixes($full_page);
		
		## Form Fix
		$this->formhandler->decode($full_page);
		
		## Second Designer Fix
		$this->designer->do_fixes($full_page);
		
		## Output Page
		echo $full_page;
	}
	
	final private function run_view() {
		ob_start();
		$error = false;
		
		if ($this->view_exists($this->view_to_load) || (!$this->view_exists($this->view_to_load) && isset($this->bounceback) && method_exists($this, $this->bounceback['check']) && method_exists($this, $this->bounceback['bounce']))) {
			if (isset($this->filter) || isset($this->filter_only) || isset($this->filter_except)) {
				if (isset($this->filter)) {
					if (!empty($this->filter) && !is_array($this->filter)) {
						$this->{$this->filter}();
					}
				}
				
				if (isset($this->filter_only) && is_array($this->filter_only[1]) && in_array($this->view_to_load, $this->filter_only[1])) {
					if (!empty($this->filter_only[0]) && !is_array($this->filter_only[0])) {
						$this->{$this->filter_only[0]}();
					}
				}
				
				if (isset($this->filter_except) && is_array($this->filter_only[1]) && !in_array($this->view_to_load, $this->filter_only[1])) {
					if (!empty($this->filter_except[0]) && !is_array($this->filter_except[0])) {
						$this->{$this->filter_except[0]}();
					}
				}
			}
			
			if (isset($this->bounceback) && !$this->view_exists($this->view_to_load)) {
				$view = $this->view_to_load;
				$values = array_values($this->params);
				$this->params = array_combine(array_keys($this->params), array_slice(array_merge(array($values[0]), array($this->bounceback['bounce']),array_slice($values, 1)), 0, count(array_keys($this->params))));
				
				if (!call_user_func(array($this, $this->bounceback['check']))) {
					if (!$this->view_exists($view)) {
						$error = true;
						Error::load("VIEW_NOT_FOUND");
					}
				}
			}
			
			ob_start();
				call_user_func(array($this, strtolower($this->view_to_load)));
				if (!$this->view_overridden) $this->get_view($this->view_to_load);
			$this->content_for_layout = ob_get_clean();
			
		} else {
			$error = true;
			Error::load("VIEW_NOT_FOUND");
		}
		
		if(!empty($this->layout) && !$error) {
			$this->render_layout($this->layout);
		} else {
			echo $this->content_for_layout;
		}
		
		$full_page = ob_get_clean();
		
		return $full_page;
	}
	
	final protected function get_view ($name, $controller="", $override = false) {
		$this->view_overridden = $override;
		if (empty($controller)) $controller = $this->params[reset(array_slice(array_keys($this->params), 0, 1))];
		if ((empty($this->branch_name) && (@include("views/".strtolower($controller)."/{$name}.php")) == true) || (!empty($this->branch_name) && (@include("branches/{$this->branch_name}/views/".strtolower($controller)."/{$name}.php")) == true)) {
			return true;
		} else {
			return false;
		}
	}
	
	final protected function view_exists ($name, $controller="") {
		if (empty($controller)) $controller = $this->params[reset(array_slice(array_keys($this->params), 0, 1))];
		
		if (method_exists($this, $name)) {
			return true;
		} else {
			Error::load("VIEW_NOT_FOUND");
			return false;
		}
	}
	
	final protected function render_layout ($name) {
		$content_for_layout = $this->content_for_layout;
		if (!empty($this->branch_name) && (@include("branches/{$this->branch_name}/views/layouts/{$name}.php")) == true) {
			return 1;
		} else {
			if ((@include("views/layouts/{$name}.php")) == true) {
				return true;
			} else {
				return false;
			}
		}
	}
}
?>