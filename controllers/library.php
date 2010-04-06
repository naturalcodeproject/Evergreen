<?php

class Library_Controller extends ApplicationController_Controller {
	public function __construct() {
		parent::__construct();
		$this->_setLayout('index');
	}
	
	public function index() {
		echo "This is the library";
	}
	
	public function run_code() {
		$this->number = 5;
	}
}
?>