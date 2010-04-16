<?php

/**
* database class
*
* handles database connections and queries
*/
class DB {
	/**
	* operator constants
	*/
	const EQUALS 				= '=';
    const NOT_EQUALS			= '!=';
    const STARTS_WITH 			= '.starts_with';
    const ENDS_WITH 			= '.ends_with';
    const CONTAINS 				= '.contains';
    const GREATER_THAN 			= '>';
    const GREATER_THAN_OR_EQUAL = '>=';
    const LESS_THAN 			= '<';
    const LESS_THAN_OR_EQUAL 	= '<=';
    const AND_THIS				= '&&';
    const OR_THIS				= '||';

	/**
	* holds the PDO connection
	*/
	private static $pdo;

	/**
	* holds the db driver
	*/
	private static $driver;

	/**
	* holds the queries that have been executed. Useful when debugging.
	*/
	public static $queries = array();

	/**
	* setups the DB class
	*/
	public static function setup() {
		// connect to the DB
		self::$pdo = new PDO('mysql:host=' . Reg::get('Database.host') . ';dbname=' .  Reg::get('Database.database'), Reg::get('Database.username'), Reg::get('Database.password'));

		// load the driver
        $specific_driver = Reg::get("Database.driver");
        $driver_name = "DB_{$specific_driver}_Driver";
        self::$driver = new $driver_name();
	}

	/**
	* Select data. Helper function
	*/
	public static function find($fields, $table, $options = array()) {
		// get the query from the driver
		$query = self::$driver->select(implode(',', $fields), $table, $options);

		// get the values out of the where
		$values = array();
		if (isset($options['where']) && is_array($options['where'])) {
			$values = array_slice($options['where'], 1);
		}
		
		// execute the query
		$results = self::execute($query, $values);
		
		return $results;
	}

	/**
	* Insert a row. Helper function.
	*/
	public static function insert($values, $table) {
		$query = self::$driver->insert(array_keys($values), $table);
		
		self::execute($query, array_values($values));

		return self::$pdo->lastInsertId();
	}

	/**
	* Update a row. Helper function
	*/
	public static function update($keys, $values, $table) {
		$data = $values;
		$keyValues = array();
		foreach($keys as $key) {
			if (isset($data[$key])) {
				$keyValues[] = $data[$key];
				unset($data[$key]);
			}
		}

		$query = self::$driver->update($keys, array_keys($data), $table);
		
		return self::execute($query, array_merge(array_values($data), $keyValues));
	}

	/**
	* Deletes a row. Helper function.
	*/
	public static function delete($keys, $values, $table) {
		$query = self::$driver->delete($keys, $values, $table);
		
		return self::execute($query, (array)$values);
	}

	/**
	* executes a SQL query and returns the result set
	*
	* values for the query can either be an array as the second argument or
	* multiple arguments in the method
	*/
	public static function query($query, $values = array()) {
		if (!is_array($values)) {
			$values = func_get_args();
			array_shift($values); // first argument is the sql query
		}

		$result = self::execute($query, $values);

		return self::fetchAll($result);
	}

	/**
	* executes a SQL query and returns the result set as an object
	*
	* this creates a new object for each row. Don't know if the obj is a model or not
	* so can't use the way that stores the data.
	*/
	public static function queryObject($query, $values = array(), $obj_name = 'stdClass') {
		$result = self::execute($query, $values);

		$objects = array();
		while($row = self::fetchObject($result, $obj_name)) {
			$objects[] = $row;
		}

		return $objects;
	}

	/**
	* executes a SQL query
	*
	* values for the query can either be an array as the second argument or
	* multiple arguments in the method
	*/
	public static function execute($query, $values = array()) {
		
		if (!is_array($values)) {
			$values = func_get_args();
			array_shift($values); // first argument is the sql query
		}
		
		// fix operators in query
		$query = self::fixOperators($query);
		
		// prepare the statement and get it ready to be executed
		$statement = self::$pdo->prepare($query);

		// execute the query
		if ($statement->execute($values) === false) {
			// handle the error
			$error = $statement->errorInfo();
			Error::trigger('MODEL_DB_FAILURE', array(
				'trace' => $error,
				'errorMessage' => end($error),
				'errorId' => (isset($error[1]) ? $error[1] : 0),
				'query' => $query,
				'queryValues' => $values
			));
		} else {	
			// store the query
			self::$queries[] = array($query, $values);
	
			// set the default fetch mode for the query
			$statement->setFetchMode(PDO::FETCH_ASSOC);
	
			return $statement;
		}
		
		return false;
	}
	
	/**
	* return the query with the oporators fixed
	*/
	public static function fixOperators($query) {
		$query = str_replace(DB::EQUALS,					self::$driver->equalsOperator(),					$query);
		$query = str_replace(DB::NOT_EQUALS,				self::$driver->notEqualsOperator(),					$query);
		$query = str_replace(DB::STARTS_WITH,				self::$driver->startsWithOperator(),				$query);
		$query = str_replace(DB::ENDS_WITH,					self::$driver->endsWithOperator(),					$query);
		$query = str_replace(DB::CONTAINS,					self::$driver->containsOperator(),					$query);
		$query = str_replace(DB::GREATER_THAN,				self::$driver->greaterThanOperator(),				$query);
		$query = str_replace(DB::GREATER_THAN_OR_EQUAL,		self::$driver->greaterThanOrEqualOperator(),		$query);
		$query = str_replace(DB::LESS_THAN,					self::$driver->lessThanOperator(),					$query);
		$query = str_replace(DB::LESS_THAN_OR_EQUAL,		self::$driver->lessThanOrEqualOperator(),			$query);
		$query = str_replace(DB::AND_THIS,					self::$driver->andOperator(),						$query);
		$query = str_replace(DB::OR_THIS,					self::$driver->orOperator(),						$query);
		return $query;
	}

	/**
	* returns a row from a query that has been executed
	*/
	public static function fetch($statement) {
		return $statement->fetch();
	}

	/**
	* returns all of the rows from a query that has been executed
	*/
	public static function fetchAll($statement) {
		return $statement->fetchAll();
	}

	/**
	* returns a row from a query that has been executed as an object
	*/
	public static function fetchObject($statement, $class_name = 'stdClass') {
		return $statement->fetchObject($class_name);
	}
	
	/**
	* returns a count of all the queries executed on a page
	*/
	public static function queryCount() {
		return count(self::$queries);
	}
}


/**
* interface for DB drivers
*/
interface DBDriverInterface {
	public function select($fields, $table, $options);
	public function insert($fields, $table);
	public function update($key, $fields, $table);
	public function delete($key, $value, $table);
	public function equalsOperator();
	public function notEqualsOperator();
	public function startsWithOperator();
	public function endsWithOperator();
	public function containsOperator();
	public function greaterThanOperator();
	public function greaterThanOrEqualOperator();
	public function lessThanOperator();
	public function lessThanOrEqualOperator();
	public function andOperator();
	public function orOperator();
}