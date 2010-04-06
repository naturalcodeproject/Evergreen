<?php

class Main_Developer_Controller extends ApplicationController_Developer_Controller
{
	public function __construct() {
		$this->_setLayout('index', Config::read('System.rootIdentifier'));
	}
	
	public function index()
	{
		//$hookhelper = System::load_helper("somethingelse");
		//$hookhelper->aFunction();
		echo "<br />";
		//$hookhelper = System::from_branch()->load_helper("somethingelse");
		echo Config::read('Path.skin');
		echo "<br />";
		//$hookhelper = System::helper('somethingelse')->from_branch()->load();
		//$hookhelper->aFunction();
		
		//$errors = error_get_last();
		//if ($errors) var_dump($errors);
		//$hookhelper = System::load_branch_helper("hookhelper");
		//$hookhelper->aFunction();
		
		var_dump(Config::read("Branch"));
		
		echo "<p>Hello World, <a href=\"[site]/developer/foobar\">click here</a></p>";
		
		echo "<p/>";
		echo "Current ='[current]' - <a href='[current]'>Test</a><br/>";
		echo "Site ='[site]' - <a href='[site]'>Test</a><br/>";
		echo "View ='[view]' - <a href='[view]'>Test</a><br/>";
		echo "Controller ='[controller]' - <a href='[controller]'>Test</a><br/>";
		echo "Branch ='[branch]' - <a href='[branch]'>Test</a><br/>";
		echo "Branch Skin ='[branch.skin]' - <a href='[branch]'>Test</a><br/>";
		echo "Branch Root ='[branch.root]' - <a href='[branch]'>Test</a><br/>";
		
		echo "<br /><br />";
		var_dump(Config::read("URI.working"));
	}
	
	public function formtest()
	{
		
	}
	
	public function foobar()
	{
		echo "<p>This is the next page.</p>";
		
		//var_dump(Factory::get_config()->get_branch_uri_map());
		
		echo "<p/>";
		
		//var_dump(Factory::get_config()->get_branch_current_route_uri());
		
		echo "<p/>";
		
		//var_dump(Factory::get_config()->get_current_route_uri());
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
		
		//var_dump(Factory::get_config()->get_branch_current_route_uri());
	}
	
	public function oranges()
	{
		echo "<p>Oranges!!</p>";
		
		echo "<p/>";
		echo "Current ='[current]' - <a href='[current]'>Test</a><br/>";
		echo "Root ='[root]' - <a href='[root]'>Test</a><br/>";
		
		echo "<p/>";
		
		//var_dump(Factory::get_config()->get_branch_current_route_uri());
		
		echo "<p/>";
		
		//var_dump(Factory::get_config()->branch_current_route_exact);
	}
}

?>