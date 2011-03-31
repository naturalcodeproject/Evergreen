<?php
class Test_Model extends Model {
	protected $id;
	protected $name;

	function __construct($id=NULL) {
		$table = 'test';
		$columns = array('id', 'name');
		$num_keys = 1;
		
		parent::__consturct(__CLASS__, $table, $columns, $num_keys, $id);
	}
	
}
?>