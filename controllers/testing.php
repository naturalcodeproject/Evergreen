<?php

class Testing_Controller extends ApplicationController {
	var $layout = "index";
	
	public function index() {
		echo "Hello";
	}
	
	public function run_code() {
		$this->number = 5;
	}
}
?>