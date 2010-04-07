<?php

/**
* database class
*
* handles database connections and queries
*/
class DB {

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
		self::$pdo = new PDO('mysql:host=' . Config::read('Database.host') . ';dbname=' .  Config::read('Database.database'), Config::read('Database.username'), Config::read('Database.password'));

		// load the driver
        $specific_driver = Config::read("Database.driver");
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
	public static function update($key, $values, $table) {
		$data = $values;
		unset($data[$key]);

		$query = self::$driver->update($key, array_keys($data), $table);
		
		return self::execute($query, array_merge(array_values($data), array($values[$key])));
	}

	/**
	* Deletes a row. Helper function.
	*/
	public static function delete($key, $value, $table) {
		$query = self::$driver->delete($key, $value, $table);
		
		return self::execute($query, array($value));
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
		
		// prepare the statement and get it ready to be executed
		$statement = self::$pdo->prepare($query);

		// execute the query
		if ($statement->execute($values) === false) {
			// handle the error
			var_dump($statement->errorInfo());
		}

		// store the query
		self::$queries[] = array($query, $values);

		// set the default fetch mode for the query
		$statement->setFetchMode(PDO::FETCH_ASSOC);

		return $statement;
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
}


/**
* interface for DB drivers
*/
interface DBDriverInterface {
	public function select($fields, $table, $options);
	public function insert($fields, $table);
	public function update($key, $fields, $table);
	public function delete($key, $value, $table);
}