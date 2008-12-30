<?php

class Main_DeveloperController extends BranchController
{
	var $layout = "index";
	
	public function index()
	{
		//$hookhelper = System::load_helper("somethingelse");
		//$hookhelper->aFunction();
		echo "<br />";
		//$hookhelper = System::from_branch()->load_helper("somethingelse");
		echo URI_SKIN;
		echo "<br />";
		$hookhelper = System::helper('somethingelse')->from_branch()->load();
		$hookhelper->aFunction();
		
		//$errors = error_get_last();
		//if ($errors) var_dump($errors);
		//$hookhelper = System::load_branch_helper("hookhelper");
		//$hookhelper->aFunction();
		
		echo "<p>Hello World, <a href=\"[site]/developer/foobar\">click here</a></p>";
		
		echo "<p/>";
		echo "Current ='[current]' - <a href='[current]'>Test</a><br/>";
		echo "Site ='[site]' - <a href='[site]'>Test</a><br/>";
		echo "View ='[view]' - <a href='[view]'>Test</a><br/>";
		echo "Controller ='[controller]' - <a href='[controller]'>Test</a><br/>";
		echo "Branch ='[branch]' - <a href='[branch]'>Test</a><br/>";
		
		echo "<br /><br />";
		var_dump(Factory::get_config()->get_working_uri());
	}
	
	public function formtest()
	{
		
	}
	
	public function foobar()
	{
		echo "<p>This is the next page.</p>";
		
		var_dump(Factory::get_config()->get_branch_uri_map());
		
		echo "<p/>";
		
		var_dump(Factory::get_config()->get_branch_current_route_uri());
		
		echo "<p/>";
		
		var_dump(Factory::get_config()->get_current_route_uri());
	}
	
	public function pickles()
	{
		echo "<p>Pickles!!</p>";
		
		echo "<p/>";
		echo "Current ='[current]' - <a href='[current]'>Test</a><br/>";
		echo "Site ='[site]' - <a href='[site]'>Test</a><br/>";
		echo "View ='[view]' - <a href='[view]'>Test</a><br/>";
		echo "Controller ='[controller]' - <a href='[controller]'>Test</a><br/>";
		echo "Branch ='[branch]' - <a href='[branch]'>Test</a><br/>";
		
		echo "<p/>";
		
		var_dump(Factory::get_config()->get_branch_current_route_uri());
	}
	
	public function oranges()
	{
		echo "<p>Oranges!!</p>";
		
		echo "<p/>";
		echo "Current ='[current]' - <a href='[current]'>Test</a><br/>";
		
		echo "<p/>";
		
		var_dump(Factory::get_config()->get_branch_current_route_uri());
		
		echo "<p/>";
		
		var_dump(Factory::get_config()->brnach_current_route_exact);
	}
}

?>