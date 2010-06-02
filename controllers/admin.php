<?php
class Admin_Controller extends ApplicationController_Controller {
	public function __construct() {
		parent::__construct();
		$this->_setLayout('index');
	}
	
	public function index() {
		echo "Admin controller<pre>";
		var_dump(Reg::get('Param'), Reg::get('URI.map'));
	}
	
}