<?php

class News_DeveloperController extends BranchController
{
	var $layout = "index";
	
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