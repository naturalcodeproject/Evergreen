<?php
/**
 * Model Class
 *
 * This is the class that all models extend and provides the functionality that
 * makes a model work and coordinates the interaction with the DB class.
 *
 *
 * Copyright 2007-2010, NaturalCodeProject (http://www.naturalcodeproject.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright		Copyright 2007-2010, NaturalCodeProject (http://www.naturalcodeproject.com)
 * @package			evergreen
 * @subpackage		lib
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * Model Class
 *
 * This is the class that all models extend and provides the functionality that
 * makes a model work and coordinates the interaction with the DB class.
 *
 * @package       evergreen
 * @subpackage    lib
 */
abstract class Model implements Iterator, Countable, arrayaccess {
	/**
	 * Name of the database table.
	 *
	 * @access protected
	 * @var string
	 */
	protected $table_name = '';

	/**
	 * All of the fields for the model.
	 *
	 * @access private
	 * @var array
	 */
	private $fields = array();

	/**
	 * Holds all of the relationship information for the model.
	 *
	 * @access private
	 * @var array
	 */
	private $relationships = array();

	/**
	 * All errors generated from validation methods.
	 *
	 * @access private
	 * @var array
	 */
	private $errors = array();

	/**
	 * Holds the data for the row.
	 *
	 * @access private
	 * @var array
	 */
	private $data = array();

	/**
	 * Holds the identifier for the current data set.
	 *
	 * @access private
	 * @var integer
	 */
	private $current_row = 0;

	/**
	 * Sets the table name for the model.
	 *
	 * @access public
	 * @param string $name The name of the table for the model
	 * @return boolean true
	 */
	public function setTableName($name) {
		$this->table_name = $name;

		return true;
	}

	/**
	 * Gets the table name for the model.
	 *
	 * @access public
	 * @return string
	 */
	public function getTableName() {
		return $this->table_name;
	}

	/**
	 * Adds a field to the model.
	 *
	 * <code>
	 * <?php
	 * array(
	 *	'name'		=> name of field,
	 *	'key'		=> true|false, (default: false)
	 *	'validate'	=> array(
	 *		'function1'	=> 'message',
	 *		...),
	 *	'format'		=> see self::$valid_formats
	 * );
	 * ?>
	 * </code>
	 *
	 * @access protected
	 * @param string $name The name of the field
	 * @param array $options Optional The field options such as the validators and the format
	 * @return boolean true if setup was successful and array of errors if it did not
	 */
	protected function addField($name, $options = array()) {
		$errors = array();

		// set defaults for the field
		$field_data = array(
			'validate'	=> array(),
			'format'		=> array('onGet' => '', 'onSet' => ''),
		);

		// check primary key
		if (in_array('key', $options) || (array_key_exists('key', $options) && $options['key'] != false)) {
			$field_data['key'] = (!empty($options['key']) && $options['key'] != true) ? $options['key'] : '';
		}

		// check if required
		if (in_array('required', $options) || (array_key_exists('required', $options) && $options['required'] != false)) {
			$field_data['required'] = (!empty($options['required']) && $options['required'] != true) ? $options['required'] : '';
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
			if (!is_array($options['format'])) {
				$format = $this->_checkFormatter($options['format']);

				if (is_string($format)) {
					$errors[] = $format;
				} else {
					$field_data['format']['onGet'] = $format;
				}
			} else {
				// see if the user is setting onGet or onSet
				if (isset($options['format']['onGet']) || isset($options['format']['onSet'])) {
					// onGet
					if (isset($options['format']['onGet'])) {
						$format = $this->_checkFormatter($options['format']['onGet']);

						// if a string was returned then there was an error
						if (is_string($format)) {
							$errors[] = $format;
						} else {
							$field_data['format']['onGet'] = $format;
						}
					}

					// onSet
					if (isset($options['format']['onSet'])) {
						$format = $this->_checkFormatter($options['format']['onSet']);

						// if a string was returned then there was an error
						if (is_string($format)) {
							$errors[] = $format;
						} else {
							$field_data['format']['onSet'] = $format;
						}
					}
				} else {
					// user is providing a class and method for the formatter for onGet
					if (sizeof($options['format']) != 2) {
						$errors[] = "Invalid field format. Format needs to be in the form of array('class', 'method')";
					} else {
						// make sure the class and method exists
						if (!is_object($options['format'][0]) && (!class_exists($options['format'][0]) || !method_exists($options['format'][0], $options['format'][1]))) {
							$errors[] = 'Invalid field format. Class/function does not exists: ' . $options['format'][0] . '::' . $options['format'][1];
						} else {
							$field_data['format']['onGet'] = $options['format'];
						}
					}
				}
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
	* checks to see if a format is valid
	*
	* @access private
	* @param mixed Either a string or array of the format that is being checked
	* @return mixed an array of the class and model of the formatter or a string of the error
	*/
	private function _checkFormatter($format) {
		// user is using a provided formatter
		if (!is_array($format)) {
			if (method_exists('ModelFieldFormat', $format)) {
				// it is one of the provided formatters
				return array('ModelFieldFormat', $format);
			} else if (method_exists(get_class($this), $format)) {
				// the method is within the current class
				return array(get_class($this), $format);
			} else {
				// formatter doesn't exist
				return 'Invalid field format: ' . $format;
			}
		} else {
			// if the array doesn't have two indexes then it is in the wrong format
			if (sizeof($format) != 2) {
				return "Invalid field format. Format needs to be in the form of array('class', 'method')";
			} else {
				// make sure the class and method exists
				if (!is_object($format[0]) && (!class_exists($format[0]) || !method_exists($format[0], $format[1]))) {
					return 'Invalid field format. Class/function does not exists: ' . $format[0] . '::' . $format[1];
				} else {
					 return $format;
				}
			}
		}
	}

	/**
	 * Gets the fields for a model.
	 *
	 * @access public
	 * @return array
	 */
	public function getFields() {
		return $this->fields;
	}

	/**
	 * Gets the field names.
	 *
	 * @access public
	 * @return array
	 */
	public function getFieldNames($table = true) {
		$names = array_keys($this->fields);

		if ($table == true) {
			array_walk($names, create_function('&$value, $key', '$value = "' . $this->getTableName() . '." . $value;'));
		}

		return $names;
	}

	/**
	 * Adds an one-to-one relationship.
	 *
	 * <code>
	 * <?php
	 * array(
	 *	'local'		=> 'column in local model',
	 *	'foreign'	=> 'column in foreign table',
	 *	'alias'		=> 'alias for the foreign table',
	 * );
	 * ?>
	 * </code>
	 *
	 * @access protected
	 * @return boolean true if setup correctly and boolean false if setup failed
	 */
	protected function hasOne($class_name, array $options) {
		if (!isset($options['local']) || !isset($options['foreign']) || !isset($options['alias']) || isset($this->fields[$options['alias']]) || !isset($this->fields[$options['local']])) {
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
	 * Adds an one-to-many relationship.
	 *
	 * <code>
	 * <?php
	 * array(
	 *	'local'		=> 'column in local model',
	 *	'foreign'	=> 'column in foreign table',
	 *	'alias'		=> 'alias for the foreign table',
	 * );
	 * ?>
	 * </code>
	 *
	 * @access protected
	 * @return boolean true if setup correctly and boolean false if setup failed
	 */
	protected function hasMany($class_name, array $options) {
		if (!isset($options['local']) || !isset($options['foreign']) || !isset($options['alias']) || isset($this->fields[$options['alias']]) || !isset($this->fields[$options['local']])) {
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
	 * Retrieve one row using the primary key.
	 *
	 * @access public
	 * @return boolean true if results found and setup correctly and boolean false if no results were found
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
			$this->setProperties($results, false, false);

			return true;
		}

		// the row wasn't retrieved. Return false.
		return false;
	}

	/**
	 * Finds multiple rows AKA a SELECT query.
	 *
	 * If the first parameter is a string then that is the alias for a relationship
	 * and the function will find within the alias.
	 *
	 * @access public
	 * @param array $options Used to set the alias of a relationship find or used for the where of a quick find
	 * @param array $options2 Optional Used to set the options for a relationship find or used to set the values on a quick find
	 * @param boolean $autoExtract Optional Defaults to false but when set to true will return an array with each row of data as a separate object
	 * @return object a reference to the current or relationship object with all the data filled in and an array of objects if $autoExtract is set to true
	 */
	public function find($options = array(), $options2 = array(), $autoExtract = null) {
		$alias = $this->_determineOptions($options, $options2);

		if (isset($options['autoExtract']) && $options['autoExtract'] == true) {
			$autoExtract = true;
		} else if (isset($options['autoExtract']) && $options['autoExtract'] == false) {
			$autoExtract = false;
		} else if (!isset($options['autoExtract']) && $autoExtract == null && Reg::get('Database.autoExtract') == true) {
			$autoExtract = true;
		} else {
			$autoExtract = false;
		}

		if (!empty($this->relationships[$alias])) {
			return $this->get($alias, $options);
		} else {
			if ($autoExtract == false) {
				$this->clearData();
			}
		}

		unset($options['autoExtract']);

		$this->_prepareOptions($options);

		$results = DB::find($this->getFieldNames(), $this->getTableName(), $options);
		
		return $this->populate($results, $autoExtract);
	}

	/**
	 * UPDATE or INSERT a row into the DB. calls create() or update()
	 *
	 * @access public
	 * @final
	 * @return mixed
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
	 *
	 * @access public
	 * @final
	 * @return mixed
	 */
	public final function create() {
		$this->clearErrors();
		if (method_exists($this, 'preSave') && is_callable(array($this, 'preSave'))) {
			$this->preSave('create');
		}
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
				if ($id == 0 && !empty($this->data[$this->current_row][$primary[0]])) {
					$id = $this->data[$this->current_row][$primary[0]];
				} else {
					$this->data[$this->current_row][$primary[0]] = $id;
				}
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
			if (method_exists($this, 'postSave') && is_callable(array($this, 'postSave'))) {
				$this->postSave('create');
			}
			return $id;
		}
		return false;
	}

	/**
	 * UPDATEs a row in the DB
	 *
	 * @access public
	 * @final
	 * @return boolean false if there were errors and boolean true if the update was successful
	 */
	public final function update() {
		$this->clearErrors();
		if (method_exists($this, 'preSave') && is_callable(array($this, 'preSave'))) {
			$this->preSave('update');
		}
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
			if (method_exists($this, 'postSave') && is_callable(array($this, 'postSave'))) {
				$this->postSave('update');
			}

			return true;
		}
		return false;
	}

	/**
	 * DELETEs a row from the DB
	 *
	 * @access public
	 * @final
	 * @param array $options Optional Used to set the relationship alias to do the delete on or the options to do a mass delete or the where for a quick delete
	 * @param array $options2 Optional Used to set the options for the relationship delete or used to set the values or a quick delete
	 * @return boolean false if there were errors and boolean true if the delete was successful
	 */
	public final function delete($options = array(), $options2 = array()) {
		$this->clearErrors();
		if (method_exists($this, 'preDelete') && is_callable(array($this, 'preDelete'))) {
			$this->preDelete();
		}
		if (empty($options)) {
	        $this->checkKeys();
			if (!$this->hasErrors()) {
				$keys = $this->_getPrimaryKeys();
				$values = array();
				$columns = array();
				foreach($keys as $key) {
					$columns[] = "{$key} = ?";
					$values[] = $this->data[$this->current_row][$key];
				}

				$options = array(
					"where" => array_merge((array)implode(' && ', $columns), $values)
				);

				$this->_prepareOptions($options);

				DB::delete($this->getTableName(), $options);

				if (method_exists($this, 'postDelete') && is_callable(array($this, 'postDelete'))) {
					$this->postDelete();
				}

				return true;
			}
		} else {
			$alias = $this->_determineOptions($options, $options2);

			if (isset($this->relationships[$alias])) {
				return $this->_relationshipDelete($alias, $options);
			}

			if (!isset($options['where'])) {
				return false;
			}

			$this->_prepareOptions($options);

			DB::delete($this->getTableName(), $options);
			
			if (method_exists($this, 'postDelete') && is_callable(array($this, 'postDelete'))) {
				$this->postDelete();
			}
			
			return true;
		}
		return false;
	}

	/**
	 * Gets the relationship data.
	 *
	 * @access public
	 * @param string $alias The relationship alias
	 * @param array $options Optional Used to set the options for the relationship find
	 * @return mixed
	 */
	public function get($alias, $options = array()) {
		if (!isset($this->relationships[$alias])) {
			return false;
		}
		$relObj = new $this->relationships[$alias]['class_name']();
		$local = $this->relationships[$alias]['options']['local'];

		if (!empty($options['where'])) {
			$query = '('.implode('', array_slice((array)$options['where'], 0, 1)).') && ('.$this->relationships[$alias]['options']['foreign'].' = ?)';
			$values = array_slice((array)$options['where'], 1);
			array_splice($values, count($values), 0, (array)$this->data[$this->current_row][$local]);
			$options['where'] = array_merge((array)$query, $values);
		} else {
			$options['where'] = array($this->relationships[$alias]['options']['foreign'].' = ?', $this->data[$this->current_row][$local]);
		}

		if ($this->relationships[$alias]['type'] == 'one') {
			$options['limit'] = 1;
		}

		return $relObj->find($options);
	}

	/**
	 * Populates the current object based on a custom query
	 *
	 * @access public
	 * @param string $query the query to execute
	 * @param array $values Optional The values of the query
	 * @param mixed $autoExtract Optional Defaults to false but when set to true will return an array with each row of data as a separate object
	 * @return object a reference to the current or relationship object with all the data filled in and an array of objects if $autoExtract is set to true
	 */
	public function query($query, $values = array(), $autoExtract = null) {
		$result = DB::execute($query, $values);

		return $this->populate($result, $autoExtract);
	}

	/**
	 * Processes a relationship delete.
	 *
	 * @access private
	 * @param string $alias The relationship alias
	 * @param array $options Optional Used to set the options for the relationship delete
	 * @return boolean true if the delete was successful and boolean false if not
	 */
	private function _relationshipDelete($alias, $options = array()) {
		if (!isset($this->relationships[$alias])) {
			return false;
		}
		$relObj = new $this->relationships[$alias]['class_name']();
		$local = $this->relationships[$alias]['options']['local'];

		if (!empty($options['where'])) {
			$query = '('.implode('', array_slice((array)$options['where'], 0, 1)).') && ('.$this->relationships[$alias]['options']['foreign'].' = ?)';
			$values = array_slice((array)$options['where'], 1);
			array_splice($values, count($values), 0, (array)$this->data[$this->current_row][$local]);
			$options['where'] = array_merge((array)$query, $values);
		} else {
			$options['where'] = array($this->relationships[$alias]['options']['foreign'].' = ?', $this->data[$this->current_row][$local]);
		}

		if ($this->relationships[$alias]['type'] == 'one') {
			$options['limit'] = 1;
		}

		if ($relObj->delete($options)) {
			return true;
		}

		return false;
	}

	/**
	 * populates a model from a PDO result
	 *
	 * @see DB::execute
	 * @access public
	 * @param mixed $results The PDO result set or false
	 * @param bool $autoExtract Optional Defaults to false but when set to true will return an array with each row of data as a separate object
	 * @return object a reference to the current or relationship object with all the data filled in and an array of objects if $autoExtract is set to true
	 */
	public function populate($results, $autoExtract = null) {
		if ($autoExtract === null && Reg::get('Database.autoExtract') == true) {
			$autoExtract = true;
		} else {
			if ($autoExtract === null) {
				$autoExtract = false;
			} else {
				$autoExtract = (bool)$autoExtract;
			}
		}
		
		if ($results !== false) {
			// loop through the results and clone the existing object
			$models = array();
			while($row = DB::fetch($results)) {
				$this->setProperties($row, true, false);
			}
			if ($autoExtract == true) {
				return $this->extractAll();
			} else {
				return $this;
			}
		}

		return false;
	}

	/**
	 * Populates a model from an array.
	 *
	 * @access public
	 * @param array $data An array of data with the fields as the keys to populate the model with
	 * @param boolean $new Optional When set to true the function creates a new data row in the model for iteration rather than replacing the current row's data
	 */
	public function setProperties($data = array(), $new = false, $filters = true) {
		// increment the internal counter if forced but don't do it if no data exists
		if ($new === true && sizeof($this->data) != 0) {
			$this->current_row += 1;
		}

		// loop through the fields and populate them
		foreach($data as $key => $value) {
			if ($filters === true && $this->isField($key) === true && !empty($this->fields[$key]['format']['onSet'])) {
				$value = ModelFieldFormat::format($this->fields[$key]['format']['onSet'], $value);
			}

			$this->data[$this->current_row][$key] = $value;
		}
	}

	/**
	 * Returns the model properties as an array.
	 *
	 * @access public
	 * @return array
	 */
	public function getProperties() {
		$data = array();

		foreach($this->data[$this->current_row] as $key => $value) {
			// apply the formatter for the field
			if ($this->isField($key) === true && !empty($this->fields[$key]['format']['onGet'])) {
				$value = ModelFieldFormat::format($this->fields[$key]['format']['onGet'], $value);
			}

			$data[$key] = $value;
		}

		return $data;
	}

	/**
	 * Checks to see if a field is part of the current object or not.
	 *
	 * @access public
	 * @param string $name The field name that is to be checked
	 * @return boolean true if field exists and boolean false if not
	 */
	public function isField($name) {
		return isset($this->fields[$name]);
	}

	/**
	 * Gets the primary keys for a table.
	 *
	 * @access private
	 * @param string $name The field name that is to be checked
	 * @return array if there are keys and boolean false if there are none
	 */
	private function _getPrimaryKeys() {
		$return = array();
		foreach($this->fields as $name => $options) {
			if (isset($options['key'])) {
				$return[] = $name;
			}
		}

		return (!empty($return)) ? $return : false;
	}

	/**
	 * Takes the options that are being passed to a function and based on what is being passed determines what the options are and if there is an alias being passed.
	 *
	 * @access private
	 * @param mixed &$options The options that need to be parsed or the alias of a relationship or a where string in the where short hand
	 * @param array $options2 Optional The options for a relationship find/delete or the values for find short hand
	 * @return string if there is an alias defined and boolean true or boolean false if no alias is defined
	 */
	private function _determineOptions(&$options, $options2 = array()) {
		if (is_string($options)) {
			if (isset($this->relationships[$options]) && is_array($options2)) {
				$alias = $options;
				$options = $options2;
				return $alias;
			} else {
				$options = array(
					'where' => array_merge((array)$options, (array)$options2)
				);
				return true;
			}
		} else if(is_array($options)) {
			return true;
		}

		return false;
	}

	/**
	 * Prepares the options by appending the table name to the front of the columns.
	 *
	 * @access private
	 * @param array &$options The options to be prepared to be passed to the DB class
	 */
	private function _prepareOptions(&$options) {
		$fields = implode('|', $this->getFieldNames(false));
		$table = $this->getTableName();

		$replace_names = create_function('&$item', '$item = preg_replace("#(' . $fields . ')\b#i", "' . $table . '.$1", $item);');

		// loop through each option and append the table name to the front of the columns
		// looping so that it doesn't replace keywords such as where, limit, order, etc
		foreach($options as $key => &$item) {
			if (is_array($item)) {
				if ($key != 'where') {
					array_walk_recursive($item, $replace_names);
				}
				else {
					$replace_names($item[0]);
				}
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
	 * Returns the total rows populated in the current object.
	 *
	 * @access public
	 * @return integer
	 */
	public function count() {
		return count($this->data);
	}

	/**
	 * Magic method for setting a field's value.
	 *
	 * @access public
	 */
	public function __set($name, $value) {
		// apply the formatter for the field
		if ($this->isField($name) === true && !empty($this->fields[$name]['format']['onSet'])) {
			$value = ModelFieldFormat::format($this->fields[$name]['format']['onSet'], $value);
		}

		$this->data[$this->current_row][$name] = $value;
	}

	/**
	 * Magic method for getting a field's value or relationship's data.
	 *
	 * @access public
	 * @param string $name The name of the field to get
	 * @return mixed
	 */
	public function __get($name) {
		if (isset($this->data[$this->current_row][$name])) {
			$value = $this->data[$this->current_row][$name];

			// apply the formatter for the field
			if ($this->isField($name) === true && !empty($this->fields[$name]['format']['onGet'])) {
				$value = ModelFieldFormat::format($this->fields[$name]['format']['onGet'], $value);
			}

			return $value;
		} else if (isset($this->relationships[$name])) {
			// it is an alias
			return $this->get($name);
		} else {
			return NULL;
		}
	}

	/**
	 * Magic method to check if a field exists.
	 *
	 * @access public
	 * @param string $name The name of the field to check
	 * @return boolean true if the field exists and boolean false if not
	 */
	public function __isset($name) {
		return isset($this->data[$this->current_row][$name]);
	}

	/**
	 * Magic method to unset a field.
	 *
	 * @access public
	 * @param string $name The name of the field to unset
	 */
	public function __unset($name) {
		unset($this->data[$this->current_row][$name]);
	}

	/**
	 * Magic method that prepares the current object for cloning.
	 *
	 * @access public
	 */
	public function __clone() {
		$currentData = (isset($this->data[$this->current_row])) ? $this->data[$this->current_row] : array();
		$currentErrors = (isset($this->errors[$this->current_row])) ? $this->errors[$this->current_row] : array();
		$this->clearData();
		$this->setProperties($currentData);
		$this->setErrors($currentErrors);
	}
	
	/**
	 * Method to return the current row's array data or the entire result set's data as an array.
	 *
	 * @access public
	 * @param boolean optional $all An option to return all the result set's data versus only the current row.
	 * @return array of the current row's data or all the result set's data, and false if there is no data in the model.
	 */
	public function toArray($all = false) {
		if ($all === false) {
			$key = $this->current_row;
		}
		
		if (!empty($this->data)) {
			if ($all === true) {
				$return = array();
				for($i = 0, $total = count($this->data); $i < $total; $i++) {
					$return[] = $this[$i]->getProperties();
				}
				return $return;
			} elseif ($all === false && !empty($this->data[$key])) {
				return $this[$key]->getProperties();
			}
		}
		
		return false;
	}

	/**
	 * Turns one result set in the current object into its own object.
	 *
	 * @access public
	 * @param integer $key The key or position of the result set to extract, assumes the current result set if empty
	 * @return object if the desired result set is found and boolean false if not
	 */
	public function extract($key = null) {
		if ($key === null) {
			$key = $this->current_row;
		}

		if (!empty($this->data[$key])) {
			$obj = clone $this;
			$obj->setProperties($this->data[$key]);
			return $obj;
		}

		return false;
	}

	/**
	 * Turns every result set in the current object into it's own object.
	 *
	 * @access public
	 * @return array
	 */
	public function extractAll() {
		$return = array();
		for($i = 0, $total = count($this->data); $i < $total; $i++) {
			$return[] = $this->extract($i);
		}

		return $return;
	}

	/**
	 * Clears out all the result sets and errors in the current object and resets the current row count to 0.
	 *
	 * @access public
	 */
	public function clearData() {
		$this->data = array();
		$this->errors = array();
		$this->current_row = 0;
	}

	/**
	 * Iterator method.
	 * Resets the current row pointer.
	 *
	 * @access public
	 */
	public function rewind() {
		$this->current_row = 0;
	}

	/**
	 * Iterator method.
	 * Gets the current row which is the object. The current row has already been incremented.
	 *
	 * @access public
	 * @return object
	 */
	public function current() {
		return $this;
	}

	/**
	 * Iterator method.
	 * Returns the current pointer's value.
	 *
	 * @access public
	 * @return integer
	 */
	public function key() {
		return $this->current_row;
	}

	/**
	 * Iterator method.
	 * Advances the current pointer's value by 1.
	 *
	 * @access public
	 */
	public function next() {
		$this->current_row += 1;
	}

	/**
	 * Iterator method.
	 * Checks if the row the current pointer is targeting is a valid row in the result set.
	 *
	 * @access public
	 * @return boolean true if the current row is a valid row and boolean false if not
	 */
	public function valid() {
		return isset($this->data[$this->current_row]);
	}

	/**
	 * Arrayaccess method.
	 * Checks if the passed in offset exists.
	 *
	 * @access public
	 * @param integer $offset The zero based numeric key of the result set to check
	 * @return boolean true if the result set at the asked for offset exists and boolean false if not
	 */
	public function offsetExists($offset) {
		return isset($this->data[$offset]);
	}

	/**
	 * Arrayaccess method.
	 * Returns an object with the result set data defined by the passed in offset.
	 *
	 * @access public
	 * @param integer $offset The zero based numeric key of the result set to get
	 * @return object
	 */
	public function offsetGet($offset) {
		return $this->extract($offset);
	}

	/**
	 * Arrayaccess method.
	 * Let's you set the value of the array but we don't need that and don't want to allow people to do that.
	 *
	 * @access public
	 * @param integer $offset The zero based numeric key of the result set to set
	 * @param mixed $value The value of the defined offset
	 * @return boolean false
	 */
	public function offsetSet($offset, $value) {
		return false;
	}

	/**
	 * Arrayaccess method.
	 * Unsets a result set row based on the passed in offset.
	 *
	 * @access public
	 * @param integer $offset The zero based numeric key of the result set to unset
	 */
	public function offsetUnset($offset) {
		unset($this->data[$offset]);
	}

	/**
	 * Creates, sets up, and stores an error object for a field error.
	 *
	 * @access public
	 * @param string $field The name of the field that had the error occur
	 * @param string $msg The error message
	 * @param string $validator The name of the validator that failed
	 * @param integer $type The type of failure
	 * @param integer $code Optional Error code that accompanied the error
	 */
	public function addError($field=null, $msg='', $validator='', $type=ModelFieldError::TYPE_INVALID_FIELD, $code=null) {
	    $modelError = new ModelFieldError();
	    $modelError->type = $type;
	    $modelError->field = $field;
	    $modelError->msg = $msg;
	    $modelError->code = $code;
		$modelError->validator = $validator;

	    $this->errors[$this->current_row][] = $modelError;
	}

	/**
	 * Method used to clear all currently set errors for all result sets.
	 *
	 * @access public
	 */
	public function clearAllErrors() {
	    $this->errors = array();
	}

	/**
	 * Method used to clear all errors for the current result set.
	 *
	 * @access public
	 */
	public function clearErrors() {
	    unset($this->errors[$this->current_row]);
	}

	/**
	 * Method used to bulk set errors for the current result set.
	 *
	 * @access public
	 * @param array $errors An array of errors to be set
	 */
	public function setErrors($errors) {
	    $this->errors[$this->current_row] = (array)$errors;
	}

	/**
	 * Checks if the current result set has errors and returns the count.
	 *
	 * @access public
	 * @return integer
	 */
	public function hasErrors() {
	    return (count(((isset($this->errors[$this->current_row])) ? $this->errors[$this->current_row] : array())) > 0);
	}

	/**
	 * Returns an array of all the error objects for the current result set.
	 *
	 * @access public
	 * @return array if there are errors and boolean false if not
	 */
	public function getErrors() {
	    return ((isset($this->errors[$this->current_row])) ? $this->errors[$this->current_row] : false);
	}

	/**
	 * Returns an array of all the error messages for the current result set.
	 * If a field is defined will return all the error messages for the defined field for the current result set.
	 *
	 * @access public
	 * @param string $field Optional Field to get the errors for
	 * @return array
	 */
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

	/**
	 * Returns the error message for the defined field and validator.
	 *
	 * @access public
	 * @param string $field Field to get the errors for
	 * @param string $validator The validator to ge the errors for
	 * @return string if an error is found and null if not
	 */
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

	/**
	 * Validates that all the required fields for the model have a value and sets up and error if not.
	 *
	 * @access private
	 */
	private function checkRequiredFields() {
		foreach ($this->fields as $name => $field) {
			if (isset($field['required']) && !isset($this->data[$this->current_row][$name])) {
				$this->addError($name, (!empty($field['required']) ? $field['required'] : 'The validator \'required\' failed on the \''.$name.'\' field'), 'required', ModelFieldError::TYPE_REQUIRED_FIELD_MISSING);
			}
		}
	}

	/**
	 * Validates that all the primary key fields for the model have a value and sets up and error if not.
	 *
	 * @access private
	 */
	private function checkKeys() {
		foreach ($this->fields as $name => $field) {
			if (isset($field['key']) && !isset($this->data[$this->current_row][$name])) {
				$this->addError($name, (!empty($field['key']) ? $field['key'] : 'The validator \'key\' failed on the \''.$name.'\' field'), 'key', ModelFieldError::TYPE_KEY_MISSING);
			}
		}
	}

	/**
	 * Runs all the defined custom validators on a field and sets up an error if they return a string or false.
	 *
	 * @access private
	 */
	private function checkValidators() {
		foreach ($this->fields as $name => $field) {
			if (count($field['validate'])) {
				foreach ($field['validate'] as $validator => $message) {
					$value = (isset($this->data[$this->current_row][$name])) ? $this->data[$this->current_row][$name] : '';
					$result = call_user_func(array($this, $validator), $name, $value);
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
 * Model Format Class
 *
 * Formats the field types for the model.
 *
 * @package       evergreen
 * @subpackage    lib
 */
class ModelFieldFormat {
	/**
	 * Formats the value for the column and returns the result.
	 *
	 * @access public
	 * @static
	 * @param string $format The format to run
	 * @param mixed $value The value to run the format on
	 * @return mixed
	 */
	public static function format($function, $value) {
		if (!empty($function) && method_exists($function[0], $function[1])) {
			$value = call_user_func($function, $value);
		}

		return $value;
	}

	/**
	 * Integer format.
	 *
	 * @access public
	 * @static
	 * @param mixed $value The value to run the format on
	 * @return integer
	 */
	public static function integer($value) {
		return intval($value);
	}

	/**
	 * Plain text format.
	 *
	 * @access public
	 * @static
	 * @param mixed $value The value to run the format on
	 * @return string
	 */
	public static function plaintext($value) {
		return stripslashes(htmlspecialchars($value));
	}

	/**
	 * HTML text format.
	 *
	 * @access public
	 * @static
	 * @param mixed $value The value to run the format on
	 * @return string
	 */
	public static function htmltext($value) {
		return $value;
	}

	/**
	 * Timestamp format.
	 *
	 * @access public
	 * @static
	 * @param mixed $value The value to run the format on
	 * @return integer
	 */
	public static function timestamp($value) {
		if (is_numeric($value)) {
			return intval($value);
		}

		return strtotime($value);
	}
}

/**
 * Model Field Error Class
 *
 * The class that holds all the information about an error for a field.
 *
 * @package       evergreen
 * @subpackage    lib
 */
class ModelFieldError {
	/**
	 * The error type as defined by the type constants.
	 *
	 * @access public
	 * @var integer
	 */
    public $type;

    /**
	 * The field the error occurred on.
	 *
	 * @access public
	 * @var string
	 */
    public $field;

    /**
	 * The error message.
	 *
	 * @access public
	 * @var string
	 */
    public $msg;

    /**
	 * The error code if any.
	 *
	 * @access public
	 * @var integer
	 */
    public $code;

    /**
	 * The name of the validator that failed.
	 *
	 * @access public
	 * @var string
	 */
    public $validator;

    /**
	 * Error type constants.
	 */
    const TYPE_INVALID_FIELD = 0;
    const TYPE_KEY_MISSING = 1;
    const TYPE_REQUIRED_FIELD_MISSING = 2;
    const TYPE_CUSTOM_VALIDATOR_FAILED = 3;

	/**
	 * Class constructor.
	 *
	 * @access public
	 */
    public function __construct() {
        $this->type = -1;
        $this->field = null;
        $this->msg = '';
        $this->code = null;
        $this->validator = '';
    }
}
