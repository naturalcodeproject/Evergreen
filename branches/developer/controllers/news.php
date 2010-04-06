<?php

class News_Developer_Controller extends ApplicationController_Developer_Controller
{
	public function __construct() {
		$this->_setLayout('index', Config::read('System.rootIdentifier'));
	}
	
	public function index() {
	
	}
	
	public function oranges() {
		echo "<p>Oranges!! #2</p>";
		
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