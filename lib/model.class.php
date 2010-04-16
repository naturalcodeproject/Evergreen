<?php

/**
* model class
* @todo Error Handling!
*/
abstract class Model implements Iterator, Countable, arrayaccess {
	/**
	* name of the database table
	*/
	protected $table_name = '';

	/**
	* all of the fields for the model
	*/
	private $fields = array();

	/**
	* holds all of the relationship information for the model
	*/
	private $relationships = array();

	/**
	* all errors generated from validation methods
	*/
	private $errors = array();

	/**
	* holds the data for the row
	*/
	private $data = array();

	/**
	* holds the identifier for the current data set
	*/
	private $current_row = 0;

	/**
	* sets the table name for the model
	*/
	public function setTableName($name) {
		$this->table_name = $name;

		return true;
	}

	/**
	* gets the table name for the model
	*/
	public function getTableName() {
		return $this->table_name;
	}

	/**
	* adds a field to the model
	*
	* array(
	*	'name'			=> name of field,
	*	'key'		=> true|false, (default: false)
	*	'validate'	=> array(
	*		'function1'	=> 'message',
	*		...),
	*	'format'		=> see self::$valid_formats
	* );
	*
	* @todo possibly make these their own objects
	*/
	protected function addField($name, $options = array()) {
		$errors = array();

		// set defaults for the field
		$field_data = array(
			'key'		=> false,
			'validate'	=> array(),
			'format'	=> Model_Format::getDefault(),
		);

		// check primary key
		if (in_array('key', $options) || isset($field_data['key']) && $field_data['key'] == true) {
			$field_data['key'] = true;
		}

		// check if required
		if (in_array('required', $options) || array_key_exists('required', $options)) {
			$field_data['required'] = (!empty($options['required'])) ? $options['required'] : '';
		}

		// validate the validate methods
		if (isset($options['validate'])) {
			if (is_array($options['validate'])) {
				// if validate is an array then go through each one
				foreach($options['validate'] as $function => $message) {
					if (is_numeric($function)) {
						$function = $message;
						$message = null;
					}
					// make sure the method exists
					if (method_exists($this, $function) && is_callable(array($this, $function))) {
						$field_data['validate'][$function] = $message;
					} else {
						$errors[] = 'Invalid validation method: ' . $function;
					}
				}
			} else {
				// it is a string for one method
				if (method_exists($this, $options['validate']) && is_callable(array($this, $options['validate']))) {
					$field_data['validate'][$options['validate']] = null;
				} else {
					$errors[] = 'Invalidate validation method: ' . $options['validate'];
				}
			}
		}

		// set the format
		if (!empty($options['format'])) {
			if (Model_Format::isValid($options['format'])) {
				$field_data['format'] = $options['format'];
			} else {
				$errors[] = 'Invalid field format: ' . $options['format'];
			}
		}

		if (!empty($errors)) {
			$this->fields[$name] = $field_data;
			return $errors;
		} else {
			$this->fields[$name] = $field_data;

			return true;
		}
	}

	/**
	* gets the fields for a model
	*/
	public function getFields() {
		return $this->fields;
	}

	/**
	* gets the field names
	*/
	public function getFieldNames($table = true) {
		$names = array_keys($this->fields);

		if ($table == true) {
			array_walk($names, create_function('&$value, $key', '$value = "' . $this->getTableName() . '." . $value;'));
		}

		return $names;
	}

	/**
	* adds an one-to-one relationship
	*
	* array(
	*	'local'		=> 'column in local model',
	*	'foreign'	=> 'column in foreign table',
	*	'alias'		=> 'alias for the foriegn table',
	* );
	*/
	protected function hasOne($class_name, array $options) {
		if (!isset($options['local']) || !isset($options['foreign']) || !isset($options['alias']) || isset($this->fields[$options['alias']])) {
			return false;
		}
		
		$this->relationships[$options['alias']] = array(
			'class_name' => $class_name,
			'type' => 'one',
			'options' => $options
		);
		
		return true;
	}

	/**
	* adds an one-to-many relationship
	*
	* array(
	*	'local'		=> 'column in local model',
	*	'foreign'	=> 'column in foreign table',
	*	'alias'		=> 'alias for the foriegn table',
	* );
	*/
	protected function hasMany($class_name, array $options) {
		if (!isset($options['local']) || !isset($options['foreign']) || !isset($options['alias']) || !isset($this->fields[$options['alias']])) {
			return false;
		}
		
		$this->relationships[$options['alias']] = array(
			'class_name' => $class_name,
			'type' => 'many',
			'options' => $options
		);
		
		return true;
	}

	/**
	* retrieve one row using the primary key
	*
	* @return false if there are no primary keys
	*/
	public function retrieve($id) {
		$this->clearData();
		
		$primary = $this->_getPrimaryKeys();
		$ids = func_get_args();
		if (count($ids) != count($primary)) {
			// the row wasn't retrieved because there were to many or to few args. Return false.
			return false;
		}
		
		$where = array();
		foreach($primary as $item) {
			$where[] = $this->table_name . '.' . $item . ' = ?';
		}

		// execute the query
		$results = DB::find($this->getFieldNames(), $this->getTableName(), array(
			'where'	=> array_merge((array)implode(' AND ', $where), $ids),
			'limit'	=> 1,
		));

		// fetch the row
		$results = DB::fetch($results);

		// if the results isn't false and the array is bigger than 0 then populate the object
		if ($results !== false && sizeof($results) > 0) {
			$this->setProperties($results);

			return true;
		}

		// the row wasn't retrieved. Return false.
		return false;
	}

	/*
	* finds multiple rows AKA a SELECT query
	*
	* if the first parameter is a string then that is the alias for a relationship
	* and the function will find within the alias
	*/
	public function find($options = array(), $options2 = array()) {
		$this->clearData();

		$alias = '';

		// if the first option is a string then that is the alias we want to search in
		if (is_string($options)) {
			$alias = $options;
			$options = $options2;
			unset($options);
		}
		$this->_prepareOptions($options);

		$results = DB::find($this->getFieldNames(), $this->getTableName(), $options);
		
		if ($results !== false) {
			// loop through the results and clone the existing object
			while($row = DB::fetch($results)) {
				$this->setProperties($row, true);
			}

			return $this;
		}

		return false;
	}

	/**
	* UPDATE or INSERT a row into the DB
	* calls create() or update()
	*/
	public final function save() {
		$primary = $this->_getPrimaryKeys();
		
		// For multiple primary key models, save will always call create, as update must
        // be called explicitly.  For single primary key models, create will be called if
        // a value has not been set for the primary key, otherwise update will be called.

		if (count($primary) == 1 && !empty($this->data[$this->current_row][$primary[0]])) {
			return $this->update();
		} else {
			return $this->create();
		}
	}

	/**
	* INSERTs a row into the DB
	*/
	public final function create() {
		$this->clearErrors();
		if (method_exists($this, 'preCreate') && is_callable(array($this, 'preCreate'))) {
			$this->preCreate();
		}
        $this->checkRequiredFields();
        $this->checkValidators();
        
        if (!$this->hasErrors()) {
			// prepare the data. This needs to be based on the fields.
			$data = array();
			foreach($this->fields as $name => $options) {
				if (isset($this->data[$this->current_row][$name])) {
					$data[$name] = $this->data[$this->current_row][$name];
				}
			}
	
			// execute the query
			$id = intval(DB::insert($data, $this->getTableName()));
	
			$primary = $this->_getPrimaryKeys();
			if (count($primary) == 1) {
				$this->data[$this->current_row][$primary[0]] = $id;
			} else {
				$id = array();
				foreach($primary as $item) {
					if (isset($this->data[$this->current_row][$item])) {
						$id[$item] =  $this->data[$this->current_row][$item];
					}
				}
			}
			if (method_exists($this, 'postCreate') && is_callable(array($this, 'postCreate'))) {
				$this->postCreate();
			}
			return $id;
		}
		return false;
	}

	/**
	* UPDATEs a row in the DB
	*/
	public final function update() {
		$this->clearErrors();
		if (method_exists($this, 'preUpdate') && is_callable(array($this, 'preUpdate'))) {
			$this->preUpdate();
		}
        $this->checkKeys();
        $this->checkRequiredFields();
        $this->checkValidators();
        
        if (!$this->hasErrors()) {
			// prepare the data. This needs to be based on the fields.
			$data = array();
			foreach($this->fields as $name => $options) {
				if (isset($this->data[$this->current_row][$name])) {
					$data[$name] = $this->data[$this->current_row][$name];
				}
			}
	
			// execute the query
			DB::update($this->_getPrimaryKeys(), $data, $this->getTableName());
			
			if (method_exists($this, 'postUpdate') && is_callable(array($this, 'postUpdate'))) {
				$this->postUpdate();
			}
			
			return true;
		}
		return false;
	}

	/**
	* DELETEs a row from the DB
	*/
	public final function delete() {
		$this->clearErrors();
		if (method_exists($this, 'preDelete') && is_callable(array($this, 'preDelete'))) {
			$this->preDelete();
		}
        $this->checkKeys();
		if (!$this->hasErrors()) {
			$keys = $this->_getPrimaryKeys();
			$values = array();
			foreach($keys as $key) {
				$values[] = $this->data[$this->current_row][$key];
			}
			
			DB::delete($keys, $values, $this->getTableName());
			
			if (method_exists($this, 'postDelete') && is_callable(array($this, 'postDelete'))) {
				$this->postDelete();
			}
			
			return true;
		}
		return false;
	}

	/**
	* gets the relationship data
	*/
	public function get($alias) {
		if (!isset($this->relationships[$alias])) {
			return false;
		}
		$relObj = new $this->relationships[$alias]['class_name']();
		$local = $this->relationships[$alias]['options']['local'];
		$options = array(
			"where" => array($this->relationships[$alias]['options']['foreign'].' = ?', $this->$local)
		);
		
		if ($this->relationships[$alias]['type'] == 'one') {
			$options['limit'] = 1;
		}
		
		$relObj->find($options);
		
		if (count($relObj)) {
			return $relObj;
		}
		return false;
	}

	/**
	* populates a model from an array
	*/
	public function setProperties($data = array(), $new = false) {
		// increment the internal counter if forced but don't do it if no data exists
		if ($new === true && sizeof($this->data) != 0) {
			$this->current_row += 1;
		}

		// loop through the fields and populate them
		foreach($data as $key => $value) {
			$this->{$key} = $value;
		}
	}

	/**
	* returns the model properties as an array
	*/
	public function getProperties() {
		$data = array();

		foreach($this->data[$this->current_row] as $key => $value) {
			$data[$key] = $value;
		}

		return $data;
	}

	/**
	* gets the primary key for a table
	*/
	private function _getPrimaryKeys() {
		$return = array();
		foreach($this->fields as $name => $options) {
			if ($options['key'] === true) {
				$return[] = $name;
			}
		}

		return (!empty($return)) ? $return : false;
	}

	/**
	* prepares the options by appending the table name to the front of the columns
	*/
	private function _prepareOptions(&$options) {
		$fields = implode('|', $this->getFieldNames(false));
		$table = $this->getTableName();

		$replace_names = create_function('&$item', '$item = preg_replace("#(' . $fields . ')#i", "' . $table . '.$1", $item);');

		// loop through each option and append the table name to the front of the columns
		// looping so that it doesn't replace keywords such as where, limit, order, etc
		foreach($options as &$item) {
			if (is_array($item)) {
				array_walk_recursive($item, $replace_names);
			} else {
				$replace_names($item);
			}
		}
		
		if (isset($options['limit']) && is_array($options['limit'])) {
			$options['limit'] = trim(implode(', ', $options['limit']), ', ');
		}
		unset($replace_names);
	}

	/**
	* returns the total rows
	*/
	public function count() {
		return count($this->data);
	}

	/**
	* sets a variable
	*/
	public function __set($name, $value) {
		$this->data[$this->current_row][$name] = $value;
	}

	/**
	* gets a variable
	*/
	public function __get($name) {
		if (isset($this->data[$this->current_row][$name])) {
			return $this->data[$this->current_row][$name];
		} else {
			return NULL;
		}
	}

	/**
	* sees if a variable is set
	*/
	public function __isset($name) {
		return isset($this->data[$this->current_row][$name]);
	}

	/**
	* unsets a variable
	*/
	public function __unset($name) {
		unset($this->data[$this->current_row][$name]);
	}

	/**
	* prepares the object to be cloned
	*/
	public function __clone() {
		$currentData = (isset($this->data[$this->current_row])) ? $this->data[$this->current_row] : array();
		$currentErrors = (isset($this->errors[$this->current_row])) ? $this->errors[$this->current_row] : array();
		$this->clearData();
		$this->setProperties($currentData);
		$this->setErrors($currentErrors);
	}
	
	/**
	* turns one row into its own object
	*/
	public function extract($id = null) {
		if ($id == null) {
			$id = $this->current_row;
		}
		
		if (!empty($this->data[$id])) {
			$obj = clone $this;
			$obj->setProperties($this->data[$id]);
			return $obj;
		}

		return false;
	}

	/**
	* turns every row into its own object
	*/
	public function extractAll() {
		$return = array();
		foreach($this->data as $key => $data) {
			$return[] = $this->extract($key);
		}
		unset($key, $data);
		
		return $return;
	}

	/**
	* clears the data in the object
	*/
	private function clearData() {
		$this->data = array();
		$this->current_row = 0;
	}

	/**
	* iterator methods
	*
	* resets the array
	*/
	public function rewind() {
		$this->current_row = 0;
	}

	/**
	* iterator methods
	*
	* Gets the current row which is the object. The current row has already been incremented.
	*/
	public function current() {
		return $this;
	}

	/**
	* iterator methods
	*
	* gets the key for the current row
	*/
	public function key() {
		return $this->current_row;
	}

	/**
	* iterator methods
	*
	* moves to the next row
	*/
	public function next() {
		$this->current_row += 1;
	}

	/**
	* iterator methods
	*
	* sees if the next row is valid
	*/
	public function valid() {
		return isset($this->data[$this->current_row]);
	}
	
	/**
	* arrayaccess method
	*
	* sees if the offset actually exists
	*/
	public function offsetExists($offset) {
		return isset($this->data[$offset]);
	}
	
	/**
	* arrayaccess method
	*
	* gets the row depending on the offset
	*/
	public function offsetGet($offset) {
		$this->current_row = $offset;
		
		return clone $this;
	}
	
	/**
	* arrayaccess method
	*
	* let's you set the value of the array but we don't need that and don't want to allow people to do that
	*/
	public function offsetSet($offset, $value) {
		return false;
	}
	
	/**
	* arrayaccess method
	*
	* unsets a row
	*/
	public function offsetUnset($offset) {
		unset($this->data[$offset]);
	}
	
	public function addError($field=null, $msg='', $validator='', $type=ModelFieldError::TYPE_INVALID_FIELD, $code=null) {
	    $modelError = new ModelFieldError();
	    $modelError->type = $type;
	    $modelError->field = $field;
	    $modelError->msg = $msg;
	    $modelError->code = $code;
		$modelError->validator = $validator;
	    
	    $this->errors[$this->current_row][] = $modelError;
	}
	
	public function clearAllErrors() {
	    unset($this->errors);
	}
	
	public function clearErrors() {
	    unset($this->errors[$this->current_row]);
	}
	
	public function setErrors($errors) {
	    $this->errors[$this->current_row] = (array)$errors;
	}
	
	public function hasErrors() {
	    return (count(((isset($this->errors[$this->current_row])) ? $this->errors[$this->current_row] : array())) > 0);
	}
	
	public function getErrors() {
	    return ((isset($this->errors[$this->current_row])) ? $this->errors[$this->current_row] : false);
	}
	
	public function getErrorMessages($field=false) {
	    $messages = array();
	    if (isset($this->errors[$this->current_row])) {
	        foreach ($this->errors[$this->current_row] as $error) {
	            if (!$field || ($field && $field == $error->field)) {
	                $messages[] = $error->msg;
	            }
	        }
	    }
	    return $messages;
	}
	
	public function getErrorMessage($field, $validator) {
	    if (isset($this->errors[$this->current_row])) {
			foreach ($this->errors[$this->current_row] as $error) {
				if ($error->field == $field && $error->validator == $validator) {
					return $error->msg;
				}
			}
	    }
	    
	    return null;
	}
	
	private function checkRequiredFields() {
		foreach ($this->fields as $name => $field) {
			if (isset($field['required']) && empty($this->{$name})) {
				$this->addError($name, (!empty($field['required']) ? $field['required'] : 'The validator \'required\' failed on the \''.$name.'\' field'), 'required', ModelFieldError::TYPE_REQUIRED_FIELD_MISSING);
			}
		}
	}
	
	private function checkKeys() {
		foreach ($this->fields as $name => $field) {
			if ($field['key'] && empty($this->{$name})) {
				$this->addError($name, (!empty($field['key']) ? $field['key'] : 'The validator \'key\' failed on the \''.$name.'\' field'), 'key', ModelFieldError::TYPE_KEY_MISSING);
			}
		}
	}
	
	private function checkValidators() {
		foreach ($this->fields as $name => $field) {
			if (count($field['validate'])) {
				foreach ($field['validate'] as $validator => $message) {
					$result = $this->{$validator}($name, $this->$name);
					if ($result === false) {
						$result = (($message != null) ? $message : 'The validator \''.$validator.'\' failed on the \''.$name.'\' field');
					}
					if (!empty($result) && $result !== true) {
						$this->addError($name, $result, $validator, ModelFieldError::TYPE_CUSTOM_VALIDATOR_FAILED);
					}
				}
			}
		}
	}
}

/**
* formats the field types for the model
*/
class Model_Format {
	/**
	* all of the valid data format
	*
	* @todo add a bunch of these
	*/
	private static $valid_formats = array(
		'none'		=> '',
		'plaintext'	=> 'plaintext',
		'htmltext'	=> 'htmltext',
		'integer'	=> 'integer',
		'timestamp'	=> 'integer',
		'datetime'	=> '',
	);

	/**
	* checks to see if it is a valid format
	*/
	public static function isValid($format) {
		if (array_key_exists($format, self::$valid_formats)) {
			return true;
		}

		return false;
	}

	/**
	* gets the default valid format
	*/
	public static function getDefault() {
		return key(self::$valid_formats);
	}

	/**
	* formats the value for the column
	*/
	public static function format($format, $value) {
		$function = self::$valid_formats[$format];

		if (!empty($function) && method_exists('Model_Format', $function)) {
			$value = call_user_func(array('Model_Format', $function), $value);
		}

		return $value;
	}

	/**
	* integer format
	*/
	public static function integer($value) {
		return intval($value);
	}

	/**
	* plain text format
	*/
	public static function plaintext($value) {
		return stripslashes(htmlspecialchars($value));
	}

	/**
	* html text format
	*/
	public static function htmltext($value) {
		return $value;
	}
}

class ModelFieldError {
    public $type;
    public $field;
    public $msg;
    public $code;
    public $validator;

    // Errors must be handled by user code
    const TYPE_INVALID_FIELD = 0;
	const TYPE_KEY_MISSING = 1;
    const TYPE_REQUIRED_FIELD_MISSING = 2;
    const TYPE_CUSTOM_VALIDATOR_FAILED = 3;

    public function __construct() {
        $this->type = -1;
        $this->field = null;
        $this->msg = '';
        $this->code = null;
        $this->validator = '';
    }
}
