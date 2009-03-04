<?php

class Model {
	private $db_driver;

	public function __construct($table_name) {
		$driver_name = 'DB_Driver_MySQL';
		$this->db_driver = new $driver_name($table_name, get_class($this), get_object_vars($this));
	}
	
	public function db() {
		return $this->db_driver->db();
	}
	
	public function db_driver() {
		return $this->db_driver;
	}
    
    public function set_column_operations($column, $flags) {
		$this->db_driver->set_column_operations($column, $flags);
	}
    
    public function create() {
        $this->pre_create();
        $result = $this->db_driver->create();
        $this->post_create();
        return $result;
    }
    
    public function retrieve($id=false) {
        $this->pre_retrieve();
        $result = $this->db_driver->retrieve($id);
        $this->post_retrieve();
        return $result;
    }
    
    public function update() {
        $this->pre_update();
        $result = $this->db_driver->update();
        $this->post_update();
        return $result;
    }
    
    public function delete() {
        $this->pre_delete();
        $result = $this->db_driver->delete();
        $this->post_delete();
        return $result;
    }
    
    public function save() {
    	return $this->db_driver->save();
	}
    
    public function find($arg1=null, $arg2=null) {
		return $this->db_driver->find($arg1, $arg2);
	}
	
	public function get_found_rows_count() {
		return $this->db_driver->get_found_rows_count();
	}
    
    public function get($property) {
		return $this->db_driver->get($property);
	}
    
    public function set($property, $value) {
		$this->db_driver->set($property, $value);
	}

	public function set_properties($properties) {
		$this->db_driver->set_properties($properties);
	}
    
    protected function pre_create() {}
	protected function pre_retrieve() {}
	protected function pre_update() {}
	protected function pre_delete() {}
	
	protected function post_create() {}
	protected function post_retrieve() {}
	protected function post_update() {}
	protected function post_delete() {}
	
	protected function has_one($rel_class_name, array $refs) {
		$this->db_driver->has_one($rel_class_name, $refs);
	}

	protected function has_many($rel_class_name, array $refs) {
		$this->db_driver->has_many($rel_class_name, $refs);
	}
}

?>