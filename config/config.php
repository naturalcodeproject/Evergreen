<?php
	## System Setup ##
	Config::register("System.mode", "development");
	
	## URI Setup ##
	Config::register(array(
		"URI.useModRewrite" => true,
		"URI.useDashes" => true,
		"URI.forceDashes" => true
	));
	
	Config::register(array("URI.map" => array(
		"controller"=>"main",
		"view"=>"index",
		"action"=>"",
		"id"=>""
	)));
	
	//Config::register("URI.prependIdentifier", "another");
	
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
	Config::registerRoute("/pickles/*", array(
		"branch" => "developer",
		"view" => "pickles"
	));
	
	Config::registerRoute("/oranges/*", array(
		"branch" => "developer",
		"view" => "oranges"
	));
	
	Config::registerRoute("/oranges2/:action/*", array(
		"branch" => "developer",
		"view" => "oranges",
		"action" => "list",
		"id" => "hello"
	), array(
		"action" => "(edit|delete|create)"
	));
?>