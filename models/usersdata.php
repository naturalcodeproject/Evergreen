<?php

class Usersdata_Model extends Model {

	public function __construct() {
		$this->setTableName('userdata');
        
        $this->addField('userid', array('key'));
        $this->addField('userdataid', array('key', 'format' => 'integer'));
        $this->addField('info', array('required', 'format' => array('usersdata_model', 'format_info')));
	}

	public function format_info($value) {
		$value = 'formatted: ' . $value;
		
		return $value;
	}
}

?>