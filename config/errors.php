<?php
	## General Errors ##
	Error::register('NO_URI_MAP', array("message"=>"The framework could not run because there is either no URI.map variable defined or the one defined isn't sufficient.", "code"=>"GEN"));
	Error::register('NO_PREPEND_IDENTIFIER', array("message"=>"The framework could not run because the option 'URI.useModRewrite' has been set to false and the option 'URI.prependIdentifier' has been overridden with an invalid or null value.", "code"=>"GEN"));
	Error::register('CONTROLLER_NOT_FOUND', array("message"=>"The controller that you were looking for was not found.", "code"=>404));
	Error::register('VIEW_NOT_FOUND', array("message"=>"The view you were looking for was not found.", "code"=>404));
	
	## Branch Errors ##
	Error::register('BRANCH_INACTIVE', array("message"=>"The branch you are trying to load is inactive and cannot be loaded.", "code"=>404));
	Error::register('BRANCH_REQUIRED_SYSTEM_MODE', array("message"=>"The branch you are trying to load has a required system mode set that is not being met by the system.", "code"=>404));
	Error::register('BRANCH_MINIMUM_SYSTEM_VERSION', array("message"=>"The branch you are trying to load has set a minimum system version requirement that is not being met by the system.", "code"=>404));
	Error::register('BRANCH_MAXIMUM_SYSTEM_VERSION', array("message"=>"The branch you are trying to load has set a maximum system version requirement that is not being met by the system.", "code"=>404));
	
	## Loader Errors ##
	Error::register('LOADER_REQUIRED_SYSTEM_MODE', array("message"=>"The helper or plugin you are trying to load has a required system mode set that is not being met by the system.", "code"=>"GEN"));
	Error::register('LOADER_MINIMUM_SYSTEM_VERSION', array("message"=>"The helper or plugin you are trying to load has set a minimum system version requirement that is not being met by the system.", "code"=>"GEN"));
	Error::register('LOADER_MAXIMUM_SYSTEM_VERSION', array("message"=>"The helper or plugin you are trying to load has set a maximum system version requirement that is not being met by the system.", "code"=>"GEN"));

    ## Model Errors ##
    Error::register('MODEL_NOT_FOUND', array("message"=>"The model you are trying to load was not found.", "code"=>"GEN"));
    
    ## Helper Errors ##
    Error::register('HELPER_NOT_FOUND', array("message"=>"The helper you are trying to load was not found.", "code"=>"GEN"));
    
    ## Plugin Errors ##
    Error::register('PLUGIN_NOT_FOUND', array("message"=>"The plugin you are trying to load was not found.", "code"=>"GEN"));
?>
