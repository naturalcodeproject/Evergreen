<?php
class Model {
	private $child_name;
	private $primary_id;
	private $key;
	
	private $columns;
	private $column_names;
	private $column_funcs;
	
	private $table_name;
	
	private $order_by;
	
	protected $db;

	function __consturct($child_name, $table = NULL, $columns = NULL, $num_keys = NULL, $primary_id = NULL) {
		$this->db = DBFactory::get();
		$this->child_name = $child_name;
		$this->primary_id = $primary_id;
		
		$this->table_name = $table;
		
		$this->order_by = array();
		
		$this->column_funcs = array();
		foreach($columns as $key=>$column) {
			if ($pos = strpos($column, ":") !== FALSE) {
				$props = explode(":", $column);
				unset($columns[$key]);
				$this->column_funcs[$props[0]] = $props[1];
			}
		}
		
		$this->columns = $columns;
		$this->column_names = implode(',', $this->columns);
		foreach($this->column_funcs as $key=>$value) {
			$this->columns[] = $key;
		}

		if ($num_keys == 1) {
			$this->key = $this->columns[0];
		} else {
			$this->key = array_slice($this->columns, 0, $num_keys);
		}

		if ($primary_id && !is_array($primary_id)) {		
			if (!is_array($this->key))
				$this->set_property("set_{$this->key}", $this->primary_id);
			else
				$this->set_property("set_{$this->key[0]}", $this->primary_id);
		} else {
			for ($i = 0; $i < count($primary_id); $i++) {
				$this->set_property("set_{$this->key[$i]}", $this->primary_id[$i]);
			}
		}
		//if ($primary_id) $this->populate();
	}

	final private function set_property($method, $value) { 
		$this->$method($value); 
	}
	
	public function set_properties(array $properties) {		
		$class = new ReflectionObject($this);

		foreach ($properties as $property=>$value) {
			$method = "set_" . $property;
			
			if ($class->hasProperty($property)) {
				$this->set_property($method, $value);
				unset($properties[$property]);
			}
		}
	}
	
	public function get_properties() { 
		$class = new ReflectionClass($this->child_name);
		$props = $class->getProperties();
		
		$properties = array();
		
		for ($i = 0; $i< count($props); $i++) {
			$name = $props[$i]->getName();

			$method = "get_" . $name;
			if ($class->hasProperty($name)) {
				$value = $this->$name;
				$properties[$name] = $value;
			}
		}
		
		unset($properties['db']);
		return $properties; 
	}

	public function __call($method, $params) {
		$count = array();

		if (strpos($method, 'set_') === 0) {
			$property = str_replace('set_', '', $method, $count);
			
			if ($count > 1)
				$property = replaceOnce('set_', '', $method);
				
			$this->{$property} = $params[0];
			
			if ($this->table_name == "trip_types" && array_key_exists($property, $_SESSION['replicator']))
			{
				$this->{$property} = $_SESSION['replicator'][$property];
			}
		} else if (strpos($method, 'get_') === 0) {
			$property = str_replace('get_', '', $method, $count);

			if ($count > 1)
				$property = replaceOnce('get_', '', $method);

			return $this->{$property}; 
		}
	}
	
	final public function get_table_name() {
		return $this->table_name;
	}
	
	final public function get_columns() {
		return $this->columns;
	}
	
	final public function get_column_names() {
		return $this->column_names;
	}
	
	final public function get_key() {
		return $this->key;
	}
	
	final public function get_order_by() {
		return $this->order_by;
	}
	
	public function populate() {
		$method;

		if (is_array($this->key)) {
			$method = array();
			foreach($this->key as $key) {
				$method[] = 'get_' . $key;
			}
		} else {
			$method = 'get_' . $this->key;
		}
		
		$query = 'SELECT ' . $this->column_names . ' ' .
				 'FROM ' . $this->table_name . ' ' .
				 'WHERE ';
				 
		$num_keys = 0;
		if (is_array($method)) {
			for($i = 0; $i < count($method); $i++) {
				$key_method = $method[$i];
				$value = $this->$key_method();
				
				if (!empty($value)) {
					if ($num_keys != 0)
						$query .= ' AND ';

					$num_keys++;
					if (!is_numeric($value))
						$value = "'$value'";
					
					$query .= $this->key[$i] . ' = ' . $value;
				}
			}
		} else {
			$value = $this->$method();
			if (!is_numeric($value))
				$value = "'$value'";
						
			$query .= $this->key . ' = ' . $value;
		}
		
	//	echo "$query<BR>";

		try {
			$result = $this->db->getClassObject($query, $this);
			return $result;	
		} catch (Exception $e) {
			trigger_error("Model Populate Failed: " . $e->getMessage());
			return FALSE;
		}
	}
	
	public function create() {
		$query = "INSERT INTO {$this->table_name} " .
				 "(" . implode(',', $this->columns) . ") " .
				 " VALUES ( ";
						 
		$first = TRUE;
		foreach ($this->columns as $property) {
			$method = "get_$property";
			$value = $this->db->real_escape_string($this->$method());
					
			if ($property == 'creation_date')
				$value = 'curdate()';
			else if ($property == 'creation_time')
				$value = 'curtime()';
			else if (isset($this->column_funcs[$property]) && $value != '' && $value != NULL)
				$value = $this->column_funcs[$property] . "('$value')";
			else if (!isset($this->column_funcs[$property]) || $value === '')
				$value = "'$value'";
				
			//echo "PROPERTY: $property VALUE: $value<BR>";
					
			if ($value != NULL) {
				if ($first) {
					$first = FALSE;
					$query .= $value;
				} else {
					$query .= ", $value";
				}	
			}
		}
				
		$query .= ')';
		
		//echo "Create Query: $query<BR>";

		try {
			$this->db->query($query);
			$method = 'set_' . $this->key;
			$this->$method($this->db->getSingleElement("select last_insert_id()"));
			$method = 'get_' . $this->key;
			return $this->$method();
		} catch (Exception $e) {
			trigger_error("Model Create Failed: " . $e->getMessage());
			return FALSE;
		}
	}
	
	public function update($field=NULL) {		
		$query = "UPDATE {$this->table_name} " .
				 "SET ";
						 
		$first = TRUE;
		
		if ($field) {
			$method = "get_$field";
			$value = $this->db->real_escape_string($this->$method());
			
			if (isset($this->column_funcs[$field]))
				$value = $this->column_funcs[$field] . "('$value')";
			else
				$value = "'$value'";
				
			$query .= $field . ' = ' . $value;
		} else {
			foreach($this->columns as $property) {
				if (!($property == $this->key || (is_array($this->key) && in_array($property, $this->key))))  {
					$method = "get_$property";
					
					if ($this->$method() !== NULL)
						$value = $this->db->real_escape_string($this->$method());
					else
						$value = NULL;

					//echo "Property: $property VALUE: @$value@<BR>";
					
					if (isset($this->column_funcs[$property]) && $value !== '' && $value !== NULL)
						$value = $this->column_funcs[$property] . "('$value')";
					else if (!isset($this->column_funcs[$property]) || $value === '')
						$value = "'$value'";
						
					if ($value != '' && $value != NULL) {
						if ($first) {
							$first = FALSE;
							$query .= $property . ' = ' . $value;
						} else {
							$query .= ', ' . $property . ' = ' . $value;
						}
					}
				}
			}
		}
				
		if (is_array($this->key)) {
			$method = array();
			foreach($this->key as $key) {
				$method[] = 'get_' . $key;
			}
		} else {
			$method = 'get_' . $this->key;
		}
		
		$query .= ' WHERE ';
		
		if (is_array($method)) {
			for($i = 0; $i < count($method); $i++) {
				if ($i != 0)
					$query .= ' AND ';
		
				$key_method = $method[$i];
				$query .= $this->key[$i] . ' = ' . $this->$key_method();
			}
		} else {
			$query .= $this->key . ' = ' . $this->$method();
		}
		
		$query .= ' LIMIT 1';
		
		//echo 'Update Query: ' . $query . '<BR>';
		
		try {
			$this->db->query($query);
			return TRUE;
		} catch (Exception $e) {
			trigger_error("Model Update Failed: " . $e->getMessage());
			return FALSE;
		}
	}
	
	public function delete($delete = TRUE) {
		$method;
		
		if (is_array($this->key)) {
			$method = array();
			foreach($this->key as $key) {
				$method[] = 'get_' . $key;
			}
		} else {
			$method = 'get_' . $this->key;
		}
				
		if ($delete) {
			$query = 'DELETE FROM ' . $this->table_name . ' ' .
				'WHERE ';
		} else {
			$query = 'UPDATE ' . $this->table_name . ' ' .
				'SET flag_active = 0 WHERE ';
		}
		
		if (is_array($method)) {
			for($i = 0; $i < count($method); $i++) {
				if ($i != 0)
					$query .= ' AND ';
		
				$key_method = $method[$i];
				$query .= $this->key[$i] . ' = ' . $this->$key_method();
			}
		} else {
			$query .= $this->key . ' = ' . $this->$method();
		}
		
		$query .= ' LIMIT 1';
				
		try {
			$this->db->query($query);
			return TRUE;
		} catch (Exception $e) {
			trigger_error("Model Delete Failed: " . $e->getMessage());
			return FALSE;
		}
	}
	
	public function order_search_by($name, $ascending=true) {
		$this->order_by[$name] = (($ascending) ? 'ASC' : 'DESC');
	}
	
	public function search(&$results=FALSE, $limit = 0, $start = 0, $status = ALL) {
		$count = 0;
				
		$start_query = "SELECT {$this->column_names} FROM ";
		$start_query .= $this->table_name . ' ';
		$query .= 'WHERE ';
		
		$nolimit_query = '';
		
		foreach($this->columns as $column) {
			$default = '';
			
		//	if (isset($this->defaults[$column])) {
				//$default = $this->defaults[$column];
		//	}
		
			$this->search_property($query, $count, $column, 'get_'.$column, $default);
		}
		
		if ($count > 0)
			$query = $start_query . $query;
		else
			$query = $start_query;
		
		
		if ($status != ALL) {
			if ($count > 0)
				$query .= 'AND ';
			else
				$query .= 'WHERE ';
				
			$query .= 'flag_active = ' . $status . ' ';
		}
		
		$first = true;
		foreach ($this->order_by as $field=>$order) {
			if ($first) {
				$first = false;
				
				$query .= "ORDER BY $field $order ";
			} else {
				$query .= ", $field $order ";
			}
		}
		
		if ($limit != 0 || $start != 0 || $results === FALSE) {
			$nolimit_query = str_replace($this->column_names, 'count(1)', $query);
			
			if ($limit != 0 || $start != 0)
				$query .= "LIMIT $start, $limit";	
		} 
		
			//echo '<PRE>' . $query . '</PRE>';
			//echo '<PRE>No Limit: ' . $nolimit_query . '</PRE>';
				
		try {
			if ($results !== FALSE)
				$results = $this->db->getClassObjectArray($query, $this);
			
			if ($results === FALSE && empty($nolimit_query))
				$nolimit_query = $query;
				
			return $this->determine_search_total($nolimit_query, $results);
		} catch (Exception $e) {
			trigger_error("Model Search Failed: " . $e->getMessage());
			return FALSE;
		}
	}
	
	public function search_property(&$query, &$count, $field, $method, $default, $method2=false, $default2=false) {
		if (!$method2) {
			// echo "Method: $method Result: " . $this->$method() . "<BR>";
			if ($this->$method() != $default) {
				if ($count > 0) { $query .= "and "; }
					
				$value = $this->$method();
				$compare = 'like';
				$in_search = FALSE;
						
				if ($value[0] == '+' && $value[1] == '+') {
					$value = substr($value, 2);
					$compare = '>';
				} else if ($value[0] == '-' && $value[1] == '-') {
					$value = substr($value, 2);
					$compare = '<';
				} else if ($value[0] == '-' && $value[1] == '=') {
					$value = substr($value, 2);
					$compare = '<=';
				} else if ($value[0] == '+' && $value[1] == '=') {
					$value = substr($value, 2);
					$compare = '>=';
				} else if ($value[0] == '!' && $value[1] == '!') {
					$value = substr($value, 2);
					$compare = '!=';
				} else if (is_array($value)) {
					array_walk($value, array('Model', 'add_apostrophes'));
					$value = implode(',', $value);
					$value = '('.$value.')';
					$compare = 'in';
					$in_search = TRUE;
				}
				
				if (isset($this->column_funcs[$field]))
					$value = $this->column_funcs[$field] . "('{$this->db->real_escape_string($value)}')";
				else if (!$in_search)
					$value = "'{$this->db->real_escape_string($value)}'";
						
				$query .= "$field $compare $value ";
				$count++;
			}
		} else {	
			if ($this->$method() != $default || $this->$method2() != $default2) {
				if ($count > 0) { $query .= "and "; }
				
				$value1;
				$value2;
				
				if (isset($this->column_funcs[$field])) {
					$value1 = $this->column_funcs[$field] . "('{$this->db->real_escape_string($this->$method())}')";
					$value2 = $this->column_funcs[$field] . "('{$this->db->real_escape_string($this->$method2())}')";
				} else {
					$value1 = "'{$this->db->real_escape_string($this->$method())}'";
					$value2 = "'{$this->db->real_escape_string($this->$method2())}'";
				}
				
				$query .= "$field between $value1 and $value2 "; 

				$count++;
			}
		}
	}
	
	public function determine_search_total($nolimit_query, &$results=NULL) {
		if ($nolimit_query == '' && $results !== NULL) {
			return count($results);
		} else if ($results !== NULL) {
			return $this->db->getSingleElement($nolimit_query);	
		} else {
			return 0;
		}
	}
	
	private static function add_apostrophes(&$item, $key) {
		$item = "'$item'";
	}
}
?>