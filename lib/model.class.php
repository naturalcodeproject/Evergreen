<?php

class ModelField {
    public $name;
    public $key;
    public $required;
    public $unique;

    public function __construct() {
        $this->name = '';
        $this->key = false;
        $this->required = false;
        $this->unique = false;
    }
}

class Model {
    private $db_driver;
    private $table_name;
    private $fields;

    const KEY = 'key';
    const REQUIRED = 'required';
    const UNIQUE = 'unique';

    public function __construct() {
        $this->db_driver = null;
        $this->table_name = '';
        $this->fields = array();
    }

    public function setTableName($table_name) {
        $this->table_name = $table_name;
    }

    public function addField($name, $options=false) {
        $field = new ModelField();
        $field->name = $name;

        if ($options) {
            if (in_array(Model::KEY, $options)) {
                $field->key = true;
            }

            if (in_array(Model::REQUIRED, $options)) {
                $field->required = true;
            }

            if (in_array(Model::UNIQUE, $options)) {
                $field->unique = true;
            }
        }

        $this->fields[] = $field;
    }

    private function setup_driver() {
        if (!$this->db_driver) {
            $settings = Factory::get_config()->get_database_info();
            $specific_driver = $settings['database-type'];
            $driver_name = "DB_Driver_$specific_driver";

            require_once('drivers/db.driver.' . strtolower($specific_driver) . '.class.php');

            $this->db_driver = new $driver_name($this->table_name, get_class($this), $this->fields, $this);
        }
    }

    public function db() {
        $this->setup_driver();
        return $this->db_driver->db();
    }

    public function db_driver() {
        $this->setup_driver();
        return $this->db_driver;
    }

    //$this->set_column_operations('password', FL_CREATE|FL_RETRIEVE);
    public function setColumnOperations($column, $flags) {
        $this->setup_driver();
        $this->db_driver->set_column_operations($column, $flags);
    }

    public function create() {
        $this->setup_driver();
        $this->preCreate();
        $result = $this->db_driver->create();
        $this->postCreate();
        return $result;
    }

    public function retrieve($id=false) {
        $this->setup_driver();
        $this->preRetrieve();
        $result = $this->db_driver->retrieve($id);
        $this->postRetrieve();
        return $result;
    }

    public function update() {
        $this->setup_driver();
        $this->preUpdate();
        $result = $this->db_driver->update();
        $this->postUpdate();
        return $result;
    }

    public function delete() {
        $this->setup_driver();
        $this->preDelete();
        $result = $this->db_driver->delete();
        $this->postDelete();
        return $result;
    }

    public function save() {
        $this->setup_driver();
        return $this->db_driver->save();
    }

    public function find($arg1=null, $arg2=null) {
        $this->setup_driver();
        return $this->db_driver->find($arg1, $arg2);
    }

    public function get_found_rows_count() {
        $this->setup_driver();
        return $this->db_driver->get_found_rows_count();
    }

    public function get($property) {
        $this->setup_driver();
        return $this->db_driver->get($property);
    }

    public function set($property, $value) {
        $this->setup_driver();
        $this->db_driver->set($property, $value);
    }

    public function set_properties($properties) {
        $this->setup_driver();
        $this->db_driver->set_properties($properties);
    }

    protected function preCreate() {}
    protected function preRetrieve() {}
    protected function preUpdate() {}
    protected function preDelete() {}

    protected function postCreate() {}
    protected function postRetrieve() {}
    protected function postUpdate() {}
    protected function postDelete() {}

    protected function hasOne($rel_class_name, array $refs) {
        $this->setup_driver();
        $this->db_driver->has_one($rel_class_name, $refs);
    }

    protected function hasMany($rel_class_name, array $refs) {
        $this->setup_driver();
        $this->db_driver->has_many($rel_class_name, $refs);
    }
}

?>