<?php
	Error::register('CONTROLLER_NOT_FOUND', array("message"=>"The controller that you were looking for was not found.", "code"=>404));
	Error::register('VIEW_NOT_FOUND', array("message"=>"The view you were looking for was not found.", "code"=>404));
	Error::register('MODEL_DB_FAILED', "This is a custom message");
	
	
	## Branch Errors ##
	Error::register('BRANCH_INACTIVE', array("message"=>"The view you were looking for was not found.", "code"=>404));
	Error::register('BRANCH_REQUIRED_SYSTEM_MODE', array("message"=>"The view you were looking for was not found.", "code"=>404));
	Error::register('BRANCH_MINIMUM_SYSTEM_VERSION', array("message"=>"The view you were looking for was not found.", "code"=>404));
	Error::register('BRANCH_MAXIMUM_SYSTEM_VERSION', array("message"=>"The view you were looking for was not found.", "code"=>404));
?>