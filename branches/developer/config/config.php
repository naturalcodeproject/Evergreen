<?php
	## NEED TO DO SOMETHING WITH THIS ##
	//$config['default_layout'] = "index";
	
	Reg::set("Branch.active", true);
	Reg::set("Branch.requiredSystemMode", "development");
	Reg::set("Branch.minimumSystemVersion", "0.2.0");
	//Reg::set("Branch.maximumSystemVersion", "1.0.0");
	
	/*
Reg::set("URI.map", array(
		"controller"=>"tester",
		"view"=>"index",
		"action"=>"",
		"id"=>""
	));
*/
	
	Reg::set("Errors.404", "/anothererror");
	
	## Database Setup ##
	Reg::set("Database.host", "localhost_dev");
	Reg::set("Database.username", "root_dev");
	Reg::set("Database.password", "root_dev");
	Reg::set("Database.database", "developer_database");
	Reg::set("Database.driver", "MySQL");
	
	## Routes ##
	Config::registerRoute("/apples/*", array(
		"controller" => "main",
		"view" => "foobar"
	));
?>