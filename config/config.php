<?php
	## System Setup ##
	Config::register("System.mode", "development");
	
	## URI Setup ##
	Config::register(array(
		"URI.useModRewrite" => false,
		"URI.useDashes" => true,
		"URI.forceDashes" => true
	));
	
	Config::register(array("URI.map" => array(
		"controller"=>"main",
		"view"=>"index",
		"action"=>"",
		"id"=>""
	)));
	
	## Errors Setup ##
	//Config::register("Error.generalErrorMessage", "An error occured. Please contact admin@example.com");
	Config::register("Error.logDirectory", "public/log");
	Config::register("Error.404", "/error404");
	//Config::register("Error.404", "https://www.google.com");
	
	## Database Setup ##
	Config::register("Database.host", "localhost");
	Config::register("Database.username", "root");
	Config::register("Database.password", "root");
	Config::register("Database.database", "hooktest");
	Config::register("Database.driver", "MySQL");
	
	## Routes ##
	Config::registerRoute("/test(.*)", "/testing/index/$1");
	Config::registerRoute("/oranges(.*)", "/developer/main/oranges/$1");
	Config::registerRoute("/pickles(.*)", "/developer/main/pickles/$1");
?>