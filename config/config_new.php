<?php
	
	
	## URI Map ##
	$uri['controller'] = "main";
	$uri['view'] = "index";
	$uri['action'] = "";
	$uri['id'] = "";
	
	## URI Type ##
	$config['uri_type'] = "AUTO";
	
	## Error Logs ##
	$config['error_log'] = "public/log";
	
	## Errors Setup ##
	$config['errors_path'] = "public/errors";
	$config['error_404'] = "404.php";
	
	## Database Setup ##
	$database['host'] = "localhost";
	$database['username'] = "root";
	$database['password'] = "root";
	$database['database'] = "hooktest";
	$database['database-type'] = "MySQL";
	
	## Routes ##
	$routes['/test(.*)'] = "/testing/look_here/$1";
	$routes['/oranges(.*)'] = "/developer/main/oranges/$1";
	$routes['/pickles(.*)'] = "/developer/main/pickles/$1";
	
	
?>