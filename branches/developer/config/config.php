<?php
	## NEED TO DO SOMETHING WITH THIS ##
	$config['default_layout'] = "index";
	
	## Database Setup ##
	Config::register("Database.host", "localhost_dev");
	Config::register("Database.username", "root_dev");
	Config::register("Database.password", "root_dev");
	Config::register("Database.database", "developer_database");
	Config::register("Database.driver", "MySQL");
	
	## Routes ##
	Config::registerRoute("/apples(.*)", "/main/foobar/$1");
	
	
?>