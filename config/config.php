<?php
	## URI Map ##
	$uri['controller'] = "main";
	$uri['view'] = "index";
	$uri['action'] = "";
	$uri['id'] = "";
	
	## URI Type ##
	$config['uri_type'] = "REWRITE";
	
	## Error Logs ##
	$config['error_log'] = "public/log";
	
	## Errors Setup ##
	$config['errors']['404'] = "/error404";
	
	// Throw exceptions bool
	
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
	
	/*
	Error::throw('MODEL_DB_FAILED');
	Error::thorw("custom error message", array("url"=>"asdasd"));
	
	// Look at Zend
	
	Hey you got and error and this is the message:
	echo Error::getMessage();
	*/
?>