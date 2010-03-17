<?php

class ApplicationController extends Controller {
	protected $filter = 'is_user_admin';
	
	public function __construct() {
		$this->_addFilterAll('another_default');
		$this->_addFilterExcept('another_default', array('custom_error'));
	}
	
	public function is_user_admin() {
		$this->newView = "wibble";
	}

}
?>