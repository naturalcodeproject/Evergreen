<?php

class Testing_Controller extends ApplicationController {
	public function __construct() {
		parent::__construct();
		$this->_setLayout('index');
	}
	
	public function index() {
		echo "Hello";
	}
	
	public function run_code() {
		$this->number = 5;
	}
}
?>