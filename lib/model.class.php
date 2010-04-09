<?php

/**
* model class
* @todo Error Handling!
*/
abstract class Model implements Iterator, Countable {
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
	
	private $currentItem = null;

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
			$field_data['validate']['notEmpty'] = (!empty($options['required'])) ? $options['required'] : '';
		}

		// validate the validate methods
		if (isset($options['validate'])) {
			if (is_array($options['validate'])) {
				// if validate is an array then go through each one
				foreach($options['validate'] as $function => $message) {
					// if the function is a number then a custom error message wasn't set
					// update the variables appropriately
					if (is_numeric($function)) {
						$function = $message;
						$message = '';
					}

					// make sure the method exists
					if (method_exists($this, $function)) {
						$field_data['validate'][$function] = $message;
					} else {
						$errors[] = 'Invalid validation method: ' . $function;
					}
				}
			} else {
				// it is a string for one method
				if (method_exists($this, $options['validate'])) {
					$field_data['validate'][$options['validate']] = '';
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
	protected function hasOne($class_name, array $options, $branch = '') {

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
	protected function hasMany($class_name, array $options, $branch = '') {

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
			'where'	=> array_merge((array)implode(' && ', $where), $ids),
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
	public function save() {
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
	public function create() {
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

		return $id;
	}

	/**
	* UPDATEs a row in the DB
	*/
	public function update() {
		// prepare the data. This needs to be based on the fields.
		$data = array();
		foreach($this->fields as $name => $options) {
			if (isset($this->data[$this->current_row][$name])) {
				$data[$name] = $this->data[$this->current_row][$name];
			}
		}

		// execute the query
		DB::update($this->_getPrimaryKeys(), $data, $this->getTableName());

		return true;
	}

	/**
	* DELETEs a row from the DB
	*/
	public function delete() {
		$keys = $this->_getPrimaryKeys();
		$values = array();
		foreach($keys as $key) {
			$values[] = $this->data[$this->current_row][$key];
		}
		
		return DB::delete($keys, $values, $this->getTableName());
	}

	/**
	* gets the relationship data
	*/
	public function get($alias) {

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
		return $this->data[$this->current_row][$name];
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
		$currentData = $this->data[$this->current_row];
		$this->clearData();
		$this->setProperties($currentData);
	}

	/**
	* turns every row into its own object
	*/
	public function extractAll() {
		$this->current_row = 0;

		$models = array();
		foreach($this as $row) {
			$models[] = $row;
		}

		return $models;
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
		return clone $this;
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