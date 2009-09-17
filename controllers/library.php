<?php

class Library_Controller extends ApplicationController {
	var $layout = "index";
	
	public function index() {
		echo "This is the library";
	}
	
	public function run_code() {
		$this->number = 5;
	}
}
?>