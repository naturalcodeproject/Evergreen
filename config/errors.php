<?php
	Error::register('VIEW_NOT_FOUND', array("message"=>"You got an error while using the registered messages", "code"=>404));
	Error::register('MODEL_DB_FAILED', "This is a custom message");
	
	//Error::load('VIEW_NOT_FOUND');
	//Error::load('VIEW_NT_FOUND');
	
	//System::URI_ROOT
	
	//Evergreen::URI_ROOT
	
	//Config::URI_ROOT
	
	//add and fyi this message is not registered to the end of the error message if the message isnt a registered one.
?>