<?php

class Main_Controller extends ApplicationController {
	var $layout = "index";
	//var $bounce = array("bounce"=>"loud", "check"=>"soft");
	//var $filter = "";
	//var $filter_only = array("", array("wibble"));
	//var $filter_except = array("", array("wibble"));
	
	public function index () {
		$this->home_selected = "selected";
		
		//$this->projax = System::plugin("projax")->load();
		//$this->render_view('wibble');
		
		//echo "<pre>";
			//print_r(get_loaded_extensions());
			//print_r(get_included_files());
		//echo "</pre>";
		
		//$this->branch_projax = System::plugin("projax")->from_branch("developer")->load();
	
		//$hookhelper = System::load_helper("somethingelse");
		//$hookhelper->aFunction();
		
		// echo "<p/>";
		// 		echo "Current ='[current]' - <a href='[current]'>Test</a><br/>";
		// 		echo "Site ='[site]' - <a href='[site]'>Test</a><br/>";
		// 		echo "View ='[view]' - <a href='[view]'>Test</a><br/>";
		// 		echo "Controller ='[controller]' - <a href='[controller]'>Test</a><br/>";
		// 		echo "Branch ='[branch]' - <a href='[branch]'>Test</a><br/>";
		// 		
		// 		echo "<br /><br />";
		// 		var_dump(Factory::get_config()->get_working_uri());
	}
	
	// public function soft()
	// {
	// 	echo "soft";
	// 	return false;
	// }
	// 
	// public function loud()
	// {
	// 	echo "Loud";
	// }
	
	public function error404()
	{
		echo "This is an error page.";
	}
	
	public function wibble()
	{
		$this->wibblenum = 35;
		
		$this->layout = "";
	}
    
    public function models() {
        $blog_tag_model = System::model('blogtag')->load();
        $this->blog_tags = $blog_tag_model->find();
    }

}
?>