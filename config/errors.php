<?php
	## General Errors ##
	Error::register('NO_URI_MAP', array("message"=>"The framework could not run because there is either no URI.map variable defined or the one defined isn't sufficient.", "code"=>"GEN"));
	Error::register('URI_MAP_INVALID_KEYS', array("message"=>"The framework could not run because the URI.map is missing either a controller or view key in it's array.", "code"=>"GEN"));
	Error::register('NO_PREPEND_IDENTIFIER', array("message"=>"The framework could not run because the option 'URI.useModRewrite' has been set to false and the option 'URI.prependIdentifier' has been overridden with an invalid or null value.", "code"=>"GEN"));
	Error::register('CONTROLLER_NOT_FOUND', array("message"=>"The controller that you were looking for was not found.", "code"=>404));
	Error::register('VIEW_NOT_FOUND', array("message"=>"The view you were looking for was not found.", "code"=>404));
	
	## Branch Errors ##
	Error::register('BRANCH_INACTIVE', array("message"=>"The branch you are trying to load is inactive and cannot be loaded.", "code"=>404));
	Error::register('BRANCH_REQUIRED_SYSTEM_MODE', array("message"=>"The branch you are trying to load has a required system mode set that is not being met by the system.", "code"=>404));
	Error::register('BRANCH_MINIMUM_SYSTEM_VERSION', array("message"=>"The branch you are trying to load has set a minimum system version requirement that is not being met by the system.", "code"=>404));
	Error::register('BRANCH_MAXIMUM_SYSTEM_VERSION', array("message"=>"The branch you are trying to load has set a maximum system version requirement that is not being met by the system.", "code"=>404));
	
    ## Model Errors ##
    Error::register('MODEL_NOT_FOUND', array("message"=>"The model you are trying to load was not found.", "code"=>"GEN"));
    Error::register('MODEL_DRIVER_NOT_FOUND', array("message"=>"The model driver you are trying to load was not found.", "code"=>"GEN"));
    Error::register('MODEL_DB_FAILURE', array("message"=>"Model DB Error", "code"=>"DB"));
    
    ## Helper Errors ##
    Error::register('HELPER_NOT_FOUND', array("message"=>"The helper you are trying to load was not found.", "code"=>"GEN"));
    
    ## Plugin Errors ##
    Error::register('PLUGIN_NOT_FOUND', array("message"=>"The plugin you are trying to load was not found.", "code"=>"GEN"));
    
	
	Error::register('REQUIRED_SYSTEM_MODE', array("message"=>"The %(type)s, %(name)s, you are trying to load has a required system mode set of %(class-required-mode)s that is not being met by the system which is in %(System.mode)s mode.", "messageArgs" => array('type'=>'Class'), "code"=>"GEN"));
	Error::register('MINIMUM_SYSTEM_VERSION', array("message"=>"The %(type)s%(name)s you are trying to load has set a minimum system version requirement of %(class-required-version)s that is not being met by the system which is version %(System.version)s.", "messageArgs" => array('type'=>'Class', 'name'=>''), "code"=>"GEN"));
	Error::register('MAXIMUM_SYSTEM_VERSION', array("message"=>"The %(type)s%(name)s you are trying to load has set a maximum system version requirement of %(class-required-version)s that is not being met by the system which is version %(System.version)s.", "messageArgs" => array('type'=>'Class', 'name'=>''), "code"=>"GEN"));
?>
