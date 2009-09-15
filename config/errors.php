<?php
	Error::register('CONTROLLER_NOT_FOUND', array("message"=>"The controller that you were looking for was not found.", "code"=>404));
	Error::register('VIEW_NOT_FOUND', array("message"=>"The view you were looking for was not found.", "code"=>404));
	Error::register('MODEL_DB_FAILED', "This is a custom message");
?>