<?php
	class Sample_Model extends Model {
		public function __construct() {
			$this->setTableName('sample');
			
			$this->addField('id', array('key'));
			$this->addField('name', array('required'));
		}
	}
?>