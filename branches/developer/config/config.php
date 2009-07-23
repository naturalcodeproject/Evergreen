<?php
	
	## URI Map ##
	$uri['controller'] = "main";
	$uri['thing'] = "index";
	$uri['action'] = "";
	$uri['id'] = "";
	
	## Error Logs ##
	$config['error_log'] = "public/log";
	
	## Errors Setup ##
	$config['errors_path'] = "public/errors";
	$config['error_404'] = "404.php";
	
	$config['default_layout'] = "index";
	
	## Database Setup ##
	$database['host'] = "localhost_dev";
	$database['username'] = "root_dev";
	$database['password'] = "root_dev";
	$database['database'] = "developer_database";
	$database['database-type'] = "MySQL";
	
	## Routes ##
	$routes['/apples(.*)'] = "/main/foobar/$1";
	
	
?>