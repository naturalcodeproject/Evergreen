<?php

/**
 * DB Wrapper Class with custom exception handling
 * and query methods
 */
class DB extends mysqli {
	// Result Object from query
	private $result;
	
	// Constants for setting the result type array
	const ASSOC = 1;
	const NUM = 2;
	const BOTH = 3;
	
	/**
	 * DB class constructor
	 * @throws ConnectException
	 */
	function __construct() {
		//require('connect.inc');
		$settings = Factory::get_config()->get_database_info();
	  	parent::__construct(
	  	$settings['host'],
	  	$settings['username'],
	  	$settings['password'],
	  	$settings['database']);

       	if (mysqli_connect_errno($this)) {
       		throw new ConnectException(mysqli_connect_error($this), mysqli_connect_errno($this));
       	}
	}
	
	/**
	 * DB class destructor
	 * Closes the DB Connection
	 */
	public function __destruct() {
    	if (!mysqli_connect_errno($this)) {
        	$this->close();
       	}
   	}

   	/**
   	 * Overrides mysqli->query
   	 * Runs the SQL query and returns the result
   	 * @param string $query SQL query string
   	 * @return Result Object
   	 */
	function query($query) {
		$this->result = parent::query($query);
    	if(mysqli_error($this)){
    		throw new QueryException(mysqli_error($this), mysqli_errno($this));
    	}
    
    	return $this->result;
  	}

  	/**
  	 * Runs the SQL query and returns the last element
  	 * from the first row of the result set.  Useful with queries
  	 * that return a single element (ex. 'Select now()')
  	 * @param string $query SQL query string
  	 * @return Single Element from result 
  	 */
  	function getSingleElement($query) {
  		$row = $this->query($query)->fetch_row();
  		
  		return (is_array($row) ? array_pop($row) : $row);
	}

	/**
	 * Runs the SQL query and returns the first row
	 * of the result set.  Useful with queries that
	 * return a single row (ex. 'Select user_id, user_name
	 * from users where user_id = 1')
	 * @param string $query SQL query string
	 * @param const $result_type (optional) sets the array 
	 * result type
	 * @return array First row from result set  
	 */
	function getSingleRow($query, $result_type = DB::BOTH) {
		return $this->query($query)->fetch_array($result_type);
	}
	
	/**
	 * Runs the SQL query and returns the specified row
	 * of the result set.
	 * @param string $query SQL query string
	 * @param int $row_num Row index of result set to return
	 * @param const $result_type (optional) sets the array
	 * result type
	 * @return array Specified row from result set
	 */
	function getSpecificRow($query, $row_num, $result_type = DB::BOTH) {
		$this->result = $this->query($query);
		$this->result->data_seek($row_num);
		$row = $this->result->fetch_array($result_type);
		return $row;
	}
	
	/**
	 * Runs the SQL query and returns a multi-dimensional array
	 * populated with the result set.
	 * @param string $query SQL query string
	 * @param const $result_type (optional) sets the array
	 * result type
	 * @return array Array of Results
	 */
	function getResultsArray($query, $result_type = DB::BOTH) {
		$this->result = $this->query($query);
		
		$rows = $this->resultToArray($result_type);

        $this->result->free_result();

        return $rows;
	}
	
	/**
	 * Converts the Result Set Object into an array
	 * @param const $result_type (optional) sets the array
	 * result type
	 * @return array Array of Results
	 */
	private function resultToArray($result_type = DB::BOTH) {
		$rows = array();
        while($row = $this->result->fetch_array($result_type)) {
            $rows[] = $row;
        }
        
        return $rows;
	}
	
	/**
	 * Overrides mysqli->multi_query
	 * Executes multiple SQL queries, must use
	 * mysqli_use_result() or mysqli_store_result()
	 * to get results.
	 * @param string $query SQL query strings (semicolon delimited)
	 * @return bool False if first query fails
	 */
	function multi_query($query) {
		$success = parent::multi_query($query);
    	if(mysqli_error($this)){
    		throw new QueryException(mysqli_error($this), mysqli_errno($this));
    	}
    	
    	return $success;
	}
	
	/**
	 * Runs multiple SQL queries and returns a multi-dimensional array
	 * of the results from each of the queries.
	 * @param string $query SQL query strings (semicolon delimited)
	 * @param const $result_type (optional) sets the array
	 * result type
	 * @return array Each result set is put into an integer-indexed
	 * multi-dimensional array
	 */
	function getMultiQueryResults($query, $result_type = DB::BOTH) {
		$multiresults = array();
		
		if ($this->multi_query($query)) {
			do {
				if ($this->result = $this->store_result()) {
					$multiresults[] = $this->resultToArray($result_type);
					$this->result->close();
				}
			} while ($this->next_result());
		}
		
		return $multiresults;
	}

	/**
	 * Runs the SQL query and returns an object with properties
	 * to match each column returned in the result set.
	 * @param string $query SQL query string
	 * @return object
	 */
	function getObject($query) {
		$this->result = $this->query($query);
		$object = $this->result->fetch_object();
		
		return $object;
	}
	
	function getClassObject($query, &$data_object) {
		$row = $this->getSingleRow($query, DB::ASSOC);
		if (empty($row))
			return FALSE;

		$properties = array_keys($row);	
		$class = new ReflectionObject($data_object);
		
		//$data_object = $this->buildObjectFromRow($data_object, $row, $class, $properties);
		$this->buildObjectFromRow($data_object, $row, $class, $properties);
		//return $data_object;
		return TRUE;
	}
	
	private function buildObjectFromRow(&$data_object, &$row, &$class, &$properties) {
    	for ($i = 0; $i < count($properties); $i++) {
    		$method = "set_" . $properties[$i];
    		$property = $properties[$i];

    		if ($class->hasProperty($property)) {
  				$data_object->$method($row[$properties[$i]]);
    		}
    	}
    
    	//return (clone $data_object);
	}
	
	function getClassObjectArray($query, $data_object) {
		$this->result = $this->query($query);
		$class = new ReflectionObject($data_object);

		$objects = array();
		$setup_properties = true;
		$properties = array();
		
		while($row = $this->result->fetch_array(DB::ASSOC)) {
			if ($setup_properties) {
				$properties = array_keys($row);
				$setup_properties = false;	
			}
			
			$obj = clone($data_object);
			$this->buildObjectFromRow($obj, $row, $class, $properties);
			$objects[] = $obj;
        }

        return $objects;
	}
	
	/**
	 * Runs the SQL query and returns an array of objects 
	 * with properties to match each column returned 
	 * in the result set.
	 * @param string $query SQL query string
	 * @return array Array of Objects
	 */
	function getObjectArray($query) {
		$this->result = $this->query($query);
		$rows = array();

        while($row = $this->result->fetch_object()) {
            $rows[] = $row;
        }

        return $rows;
	}
	
	/**
	 * Overrides mysqli->prepare
	 * Prepares a SQL statement for execution
	 * @param string $query SQL query string
	 * @return PreparedStatement object
	 */
	function prepare($query) {
		$statement = parent::prepare($query);
    	if(mysqli_error($this)){
    		throw new StatementException(mysqli_error($this), mysqli_errno($this));
    	}
    	
    	return $statement;
  	}
}

/**
 * ConnectException class that is thrown for DB Connection errors
 */
class ConnectException extends Exception {}

/**
 * QueryException class that is thrown for SQL errors
 */
class QueryException extends Exception {}

/**
 * StatementException class that is thrown for preparing statement errors
 */
class StatementException extends Exception {}
?>