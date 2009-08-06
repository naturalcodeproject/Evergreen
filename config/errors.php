<?php
	$config['errors']['404'] = array("message"=>"The requested view wasn't found", "code"=>"404");
	$config['errors']['MODEL_DB_FAILED'] = array("message"=>"The requested view wasn't found");
	$config['errors']['VIEW_NOT_FOUND'] = array("message"=>"The requested view wasn't found", "url"=>Factory::get_config()->get_error('404'));
	
	Error::add('VIEW_NOT_FOUND', "This is a custom message");
	Error::add('MODEL_DB_FAILED', "This is a custom message");
	
	Error::load('VIEW_NOT_FOUND');
	Error::load('VIEW_NT_FOUND');
	
	System::URI_ROOT
	
	Evergreen::URI_ROOT
	
	Config::URI_ROOT
	
	//add and fyi this message is not registered to the end of the error message if the message isnt a registered one.
?>