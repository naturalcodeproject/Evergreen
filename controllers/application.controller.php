<?php

class ApplicationController extends Controller {
	protected $filter = 'is_user_admin';
	
	public function is_user_admin() {
		$this->newView = "wibble";
	}

}
?>