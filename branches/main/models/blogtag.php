<?php

class Blogtag_Model extends Model {
    
	public function __construct() {
        $this->setTableName('blog_tags');
        
        $this->addField('id', array('key'));
        $this->addField('name', array('required'));
	}
}

?>