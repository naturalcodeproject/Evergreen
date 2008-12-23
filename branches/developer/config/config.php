<?php
	
	## URI Map ##
	$uri['controller'] = "main";
	$uri['view'] = "index";
	$uri['action'] = "";
	$uri['id'] = "";
	
	## Error Logs ##
	$config['error_log'] = "public/log";
	
	## Errors Setup ##
	$config['errors_path'] = "public/errors";
	$config['error_404'] = "404.php";
	
	$config['default_layout'] = "index";
	
	## Database Setup ##
	$database['host'] = "localhost";
	$database['username'] = "root";
	$database['password'] = "root";
	$database['database'] = "hooktest";
	$database['database-type'] = "MySQL";
	
	## Routes ##
	$routes['/apples(.*)'] = "/main/foobar/$1";
	
	
?>