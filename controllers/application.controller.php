<?php

class ApplicationController_Controller extends Controller {
	protected $filter = 'is_user_admin';
	
	public function __construct() {
		//$this->_addFilterAll('another_default', 'View.before');
	}
	
	public function is_user_admin() {
		$this->newView = "wibble";
	}

}
?>