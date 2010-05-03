<?php

class Language_Model extends Model {

	public function __construct() {
		$this->setTableName('languages');
        
        $this->addField('alias', array('key'));
        $this->addField('description');
	}

}

?>