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
    public $code;
    public $validator;

    // Errors must be handled by user code
    const TYPE_INVALID_FIELD = 0;
    const TYPE_REQUIRED_FIELD_MISSING = 1;
    const TYPE_KEY_MISSING = 2;
    const TYPE_DUPLICATE_UNIQUE = 3; // NOT SUPPORTED YET
    
    // Errors will throw exception and stop execution
    const TYPE_DB_OPERATION_FAILED = 4;

    public function __construct() {
        $this->type = -1;
        $this->field = null;
        $this->msg = '';
        $this->trace = '';
        $this->code = null;
        $this->validator = '';
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
    const UNIQUE = 'unique';

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

            if (array_key_exists(Model::VALIDATE, $options)) {  
                if (is_array($options[Model::VALIDATE])) {
                    foreach ($options[Model::VALIDATE] as $option) {
                        $field->validators[] = $option;
                    }
                } else {
                    $field->validators[] = $options[Model::VALIDATE];
                }
            }
        }

        $this->fields[] = $field;
    }

    public function addError($field=null, $msg='', $type=ModelError::TYPE_INVALID_FIELD, $trace='', $code=null) {
        $modelError = new ModelError();
        $modelError->type = $type;
        $modelError->field = $field;
        $modelError->msg = $msg;
        $modelError->trace = $trace;
        $modelError->code = $code;
        
        $this->errors[] = $modelError;
        
        if ($type == ModelError::TYPE_DB_OPERATION_FAILED) {
            $params = array();
            $params['db_message'] = $msg;
            $params['db_trace'] = $trace;
            $params['db_model'] = get_class($this);
            Error::trigger("MODEL_DB_FAILURE", $params);
        }
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
    
    public function getErrorMessages($field=false) {
        $messages = array();
        if (isset($this->errors)) {
            foreach ($this->errors as $error) {
                if (!$field || ($field && $field == $error->field)) {
                    $messages[] = $error->msg;
                }
            }
        }
        return $messages;
    }
    
    public function getErrorMessage($field, $validator) {
        if (isset($this->errors)) {
            if (isset($this->errors)) {
                foreach ($this->errors as $error) {
                    if ($error->field == $field && $error->validator == $validator) {
                        return $error->msg;
                    }
                }
            }
        }
        
        return null;
    }
    
    public function validateFailed($field=false, $validator=false) {
        if (isset($this->errors)) {
            foreach ($this->errors as $error) {
                if ((!$field || ($field && $error->field == $field)) && (!$validator || $validator == $error->validator)) {
                    return true;
                }
            }   
        }
        
        return false;
    }
    
    public function requiredFailed($field=false) {
        if (isset($this->errors)) {
            foreach ($this->errors as $error) {
                if ($error->type == ModelError::TYPE_REQUIRED_FIELD_MISSING) {
                    if (!$field || ($field && $error->field == $field)) {
                        return true;
                    }
                }
            }
        }
        
        return false;
    }
    
    public function keyFailed($field=false) {
        if (isset($this->errors)) {
            foreach ($this->errors as $error) {
                if ($error->type == ModelError::TYPE_KEY_MISSING) {
                    if (!$field || ($field && $error->field == $field)) {
                        return true;
                    }
                }
            }
        }
        
        return false;
    }

    private function setup_driver() {
        if (!$this->db_driver) {
            $specific_driver = Config::read("Database.driver");
            $driver_name = "DB_Driver_{$specific_driver}";
            $driver_path = '/config/drivers/db.driver.' . strtolower($specific_driver) . '.class.php';
            
            if (Config::read("Branch.name") != "" && file_exists(Config::read('Path.physical') . '/branches/' . Config::read("Branch.name") . $driver_path)) {
            	require_once(Config::read("Path.physical") . '/branches/' . Config::read("Branch.name") . $driver_path);
			} else if (file_exists(Config::read('Path.physical') . $driver_path)) {
				require_once(Config::read("Path.physical") . $driver_path);
			} else {
				Error::trigger('MODEL_DRIVER_NOT_FOUND');
			}
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

    public function getProperties() {
        $this->setup_driver();
        return $this->db_driver->get_properties();
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
            if ($field->required && (!property_exists($this, $field->name) || empty($this->{$field->name}))) {
                $this->addError($field->name, '', ModelError::TYPE_REQUIRED_FIELD_MISSING);
                
                $errors = $this->errors;
                $curError = array_pop($errors);
                $curError->validator = self::REQUIRED;
            }
        }
    }

    private function checkKeys() {
        foreach ($this->fields as $field) {
            if ($field->key && !property_exists($this, $field->name)) {
                $this->addError($field->name, '', ModelError::TYPE_KEY_MISSING);
                
                $errors = $this->errors;
                $curError = array_pop($errors);
                $curError->validator = self::KEY;
            }
        }
    }

    private function checkValidators() {
        foreach ($this->fields as $field) {
            if (count($field->validators)) {
                foreach ($field->validators as $validator) {
                    $prop = $field->name;
                    $result = $this->$validator($prop, $this->$prop);
                    
                    if ($result) {
                        $this->addError($prop, $result);
                        
                        $errors = $this->errors;
                        $curError = array_pop($errors);
                        $curError->validator = $validator;
                    }
                }
            }
        }
    }
}

?>