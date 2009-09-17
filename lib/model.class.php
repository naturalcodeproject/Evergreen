<?php

class ModelField {
    public $name;
    public $key;
    public $required;
    public $validators;

    public function __construct() {
        $this->name = '';
        $this->key = false;
        $this->required = false;
        $this->validators = array();
    }
}

class ModelError {
    public $type;
    public $field;
    public $msg;
    public $trace;

    const TYPE_INVALID_FIELD = 0;
    const TYPE_REQUIRED_FIELD_MISSING = 1;
    const TYPE_KEY_MISSING = 2;
    const TYPE_DB_OPERATION_FAILED = 3;

    public function __construct() {
        $this->type = -1;
        $this->field = null;
        $this->msg = '';
        $this->trace = '';
    }
}

class Model {
    private $db_driver;
    private $table_name;
    private $fields;
    private $errors;

    const KEY = 'key';
    const REQUIRED = 'required';
    const VALIDATE = 'validate';

    public function __construct() {
        $this->db_driver = null;
        $this->table_name = '';
        $this->fields = array();
        $this->errors = array();
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

            foreach ($options as $key=>$value) {
                if ($key === Model::VALIDATE) {
                    $field->validators[] = $value;
                }
            }
        }

        $this->fields[] = $field;
    }

    public function addError($field=null, $msg='', $type=ModelError::TYPE_INVALID_FIELD, $trace='') {
        $modelError = new ModelError();
        $modelError->type = $type;
        $modelError->field = $field;
        $modelError->msg = $msg;
        $modelError->trace = $trace;
        $this->errors[] = $modelError;
    }

    public function clearErrors() {
        unset($this->errors);
    }

    public function hasErrors() {
        return (count($this->errors) > 0);
    }

    public function getErrors() {
        return $this->errors;
    }

    private function setup_driver() {
        if (!$this->db_driver) {
            $specific_driver = Config::read("Database.driver");
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

    public function query($statement) {
        $this->clearErrors();
        $this->setup_driver();
        return $this->db_driver->query($statement);
    }

    //$this->set_column_operations('password', FL_CREATE|FL_RETRIEVE);
    public function setColumnOperations($column, $flags) {
        $this->setup_driver();
        $this->db_driver->set_column_operations($column, $flags);
    }

    public function create() {
        $this->clearErrors();
        $this->setup_driver();
        $this->preCreate();

        $this->checkRequiredFields();
        $this->checkValidators();

        if (!$this->hasErrors()) {
            $result = $this->db_driver->create();
            $this->postCreate();
            return $result;
        }

        return false;
    }

    public function retrieve($id=false) {
        $this->clearErrors();
        $this->setup_driver();
        $this->preRetrieve();
        $result = $this->db_driver->retrieve($id);

        if ($result) {
            $this->postRetrieve();
            return $result;
        }

        return false;
    }

    public function update() {
        $this->clearErrors();
        $this->setup_driver();
        $this->preUpdate();

        $this->checkKeys();
        $this->checkRequiredFields();
        $this->checkValidators();

        if (!$this->hasErrors()) {
            $result = $this->db_driver->update();
            $this->postUpdate();
            return $result;
        }

        return false;
    }

    public function delete() {
        $this->clearErrors();
        $this->setup_driver();
        $this->preDelete();
        $result = $this->db_driver->delete();

        if ($result) {
            $this->postDelete();
            return $result;
        }

        return false;
    }

    public function save() {
        $this->clearErrors();
        $this->setup_driver();

        $this->checkRequiredFields();

        if (!$this->hasErrors()) {
            return $this->db_driver->save();
        }

        return false;
    }

    public function find($arg1=null, $arg2=null) {
        $this->clearErrors();
        $this->setup_driver();
        return $this->db_driver->find($arg1, $arg2);
    }

    public function getFoundRowsCount() {
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

    public function setProperties($properties) {
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

    private function checkRequiredFields() {
        // Check for required fields
        foreach ($this->fields as $field) {
            if ($field->required && !property_exists($this, $field->name)) {
                $this->addError($field->name, '', ModelError::TYPE_REQUIRED_FIELD_MISSING);
            }
        }
    }

    private function checkKeys() {
        foreach ($this->fields as $field) {
            if ($field->key && !property_exists($this, $field->name)) {
                $this->addError($field->name, '', ModelError::TYPE_KEY_MISSING);
            }
        }
    }

    private function checkValidators() {
        foreach ($this->fields as $field) {
            if (count($field->validators)) {
                foreach ($field->validators as $validator) {
                    $prop = $field->name;
                    $this->$validator($this->$prop);
                }
            }
        }
    }
}

?>