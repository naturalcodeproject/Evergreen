<?php

class Main extends ApplicationController {
	var $layout = "index";
	
	public function index () {
		$this->home_selected = "selected";
		
		$this->projax = System::plugin("projax")->load();
		
		//echo "<pre>";
			//print_r(get_loaded_extensions());
			//print_r(get_included_files());
		//echo "</pre>";
		
		//$this->branch_projax = System::plugin("projax")->from_branch("developer")->load();
	
		//$hookhelper = System::load_helper("somethingelse");
		//$hookhelper->aFunction();
	}
	
	
	public function wibble()
	{
		$this->wibblenum = 35;
		
		$this->layout = "";
	}

}
?>