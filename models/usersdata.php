<?php

class Usersdata_Model extends Model {

	public function __construct() {
		$this->setTableName('userdata');
        
        $this->addField('userid', array('key'));
        $this->addField('userdataid', array('key'));
        $this->addField('info', array('required'));
	}

}

?>