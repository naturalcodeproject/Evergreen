<?php

class Users_Model extends Model {

	public function __construct() {
		$this->setTableName('users');
        
        $this->addField('id', array('key'));
        $this->addField('firstname', array('required'));
        $this->addField('lastname');
        $this->addField('username');
	}

}

?>