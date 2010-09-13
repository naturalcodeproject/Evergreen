<?php
/**
 * DB Class
 *
 * This class handles the abstract database functionality for the models including
 * the interaction with the driver and with PDO. This class also does alot with the
 * queries that are passed to it to make the queries more abstract and make talking to
 * the database easier.
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
 * DB Class
 *
 * This class handles the abstract database functionality for the models including
 * the interaction with the driver and with PDO. This class also does alot with the
 * queries that are passed to it to make the queries more abstract and make talking to
 * the database easier.
 *
 * @package       evergreen
 * @subpackage    lib
 */
class DB {
	/**
	* Operator constants
	*/
	const EQUALS 				= '=';
    const NOT_EQUALS			= '!=';
    const STARTS_WITH 			= 'startsWith';
    const ENDS_WITH 			= 'endsWith';
    const CONTAINS 				= 'contains';
    const GREATER_THAN 			= '>';
    const GREATER_THAN_OR_EQUAL = '>=';
    const LESS_THAN 			= '<';
    const LESS_THAN_OR_EQUAL 	= '<=';
    const AND_THIS				= '&&';
    const OR_THIS				= '||';

	/**
	 * Holds the PDO connection object.
	 *
	 * @access private
	 * @static
	 * @var object
	 */
	private static $pdo;

	/**
	 * Holds the DB driver object.
	 *
	 * @access private
	 * @static
	 * @var object
	 */
	private static $driver;

	/**
	 * Holds the queries that have been executed. Useful when debugging.
	 *
	 * @access private
	 * @static
	 * @var array
	 */
	private static $queries = array();

	/**
	 * Holds a count of the executed queries.
	 *
	 * @access private
	 * @static
	 * @var array
	 */
	private static $queryCount = 0;

	/**
	 * Sets up the driver and the PDO connection.
	 *
	 * @access public
	 * @static
	 */
	public static function setup() {
		// connect to the DB
		self::$pdo = new PDO(strtolower(Reg::get("Database.driver")).':host=' . Reg::get('Database.host') . ';dbname=' .  Reg::get('Database.database'), Reg::get('Database.username'), Reg::get('Database.password'));

		// load the driver
        $specific_driver = Reg::get("Database.driver");
        $driver_name = "DB_{$specific_driver}_Driver";
        self::$driver = new $driver_name();
	}

	/**
	 * Helper function to select data from a table.
	 *
	 * @access public
	 * @static
	 * @param array $fields An array of the fields registered in the model
	 * @param string $table The name of the target table
	 * @param array $options Optional The options that define the find
	 * @return array
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
	 * Helper function to insert a row into the table.
	 *
	 * @access public
	 * @static
	 * @param array $values The array with the values that need to be inserted with the field names as keys
	 * @param string $table The name of the target table
	 * @return integer
	 */
	public static function insert($values, $table) {
		$query = self::$driver->insert(array_keys($values), $table);

		self::execute($query, array_values($values));

		return self::$pdo->lastInsertId();
	}

	/**
	 * Helper function to update a row in a table.
	 *
	 * @access public
	 * @static
	 * @param array $keys An array populated with the defined model's keys
	 * @param array $values The array with the row's values with the field names as keys
	 * @param string $table The name of the target table
	 * @return array
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
	 * Helper function to delete a row in a table.
	 *
	 * @access public
	 * @static
	 * @param string $table The name of the target table
	 * @param array $options The options for the delete e.g. where, limit
	 * @return array
	 */
	public static function delete($table, $options) {
		$query = self::$driver->delete($table, $options);

		// get the values out of the where
		$values = array();
		if (isset($options['where']) && is_array($options['where'])) {
			$values = array_slice($options['where'], 1);
		}

		return self::execute($query, (array)$values);
	}

	/**
	 * Helper function to truncate a table.
	 *
	 * @access public
	 * @static
	 * @param string $table The name of the target table
	 * @return array
	 */
	public static function truncate($table) {
		$query = self::$driver->truncate($table);

		return self::execute($query);
	}

	/**
	 * Executes a SQL query and returns the result set.
	 *
	 * Values for the query can either be an array as the second argument or
	 * multiple arguments in the method.
	 *
	 * @access public
	 * @static
	 * @param string|array $query The query to be run or an array with the query and the values in it
	 * @param array $values Optional The values of the query
	 * @return array
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
	 * Executes a SQL query and returns the result set as an object. If the class is a model then it will populate
	 * the model with the data that is returned
	 *
	 * @access public
	 * @static
	 * @param string $query The query to be run
	 * @param array $values Optional The values of the query
	 * @param string $obj_name Optional The name of the class to populate the found data with
	 * @return array
	 */
	public static function queryObject($query, $values = array(), $obj_name = 'stdClass') {
		$result = self::execute($query, $values);

		$objects = new $obj_name;
		if (is_a($objects, 'Model')) {
			$objects = $objects->populate($result);
		} else {
			$objects = array();
			while($row = self::fetchObject($result, $obj_name)) {
				$objects[] = $row;
			}
		}

		return $objects;
	}

	/**
	 * Executes a SQL query.
	 *
	 * Values for the query can either be an array as the second argument or
	 * multiple arguments in the method.
	 *
	 * @access public
	 * @static
	 * @param string $query The query to be run
	 * @param array $values Optional The values of the query
	 * @return mixed
	 */
	public static function execute($query, $values = array()) {

		if (!is_array($values)) {
			$values = func_get_args();
			array_shift($values); // first argument is the sql query
		}

		// fix operators in query
		$query = self::fixOperators($query, $values);

		// prepare the statement and get it ready to be executed
		$statement = self::$pdo->prepare($query);

		if (Reg::get('Database.viewQueries')) {
			echo '<pre>';
			var_dump(array($query, $values));
			echo '</pre>';
		}

		// execute the query
		if ($statement->execute($values) === false) {
			// handle the error
			$error = $statement->errorInfo();
			throw new EvergreenException('MODEL_DB_FAILURE', array(
				'trace' => $error,
				'errorMessage' => end($error),
				'errorId' => (isset($error[1]) ? $error[1] : 0),
				'query' => $query,
				'queryValues' => $values
			));
		} else {
			// store a count of the queries
			self::$queryCount += 1;

			if (Reg::get('Database.storeQueries') == true) {
				// store the query
				self::$queries[] = array($query, $values);
			}

			// set the default fetch mode for the query
			$statement->setFetchMode(PDO::FETCH_ASSOC);

			return $statement;
		}

		return false;
	}

	/**
	 * Return the query with the operators fixed.
	 *
	 * @access public
	 * @static
	 * @param string $query The query to be run
	 * @param array &$values The values of the query
	 * @return string
	 */
	public static function fixOperators($query, &$values) {
		$query = str_replace(DB::AND_THIS, self::$driver->andOperator(), $query);
		$query = str_replace(DB::OR_THIS, self::$driver->orOperator(), $query);

		global $count, $processedValues;
		$count = 0;
		$processedValues = $values;
		$query = preg_replace_callback('/(([^\s]+)[\s]+(?(?=[\(])\(([^\)a-z0-9]*)\)|([\?]+)))|(\?)/is', create_function('$matches', '
			global $count, $processedValues;
			$return = DB::_operatorCallback($matches, $processedValues, $count);
			$count++;
			return $return;
		'), $query);
		$values = $processedValues;
		unset($count, $processedValues);

		return $query;
	}

	/**
	 * Callback for each operator and value pair matched in the query.
	 *
	 * @access public
	 * @static
	 * @param array $found The array of matches
	 * @param array &$values The values of the query
	 * @param integer &$key The current operator found position
	 * @return string
	 */
	public static function _operatorCallback($found, &$values, &$key) {
		$whole = $found[0];
		$operator = $found[2];

		if (strtolower($operator) == 'in') {
			global $total, $current;
			$total = substr_count($whole, "?");
			$current = 0;
			$whole = preg_replace_callback('/(\?)+?/is', create_function('$matches', '
				global $count, $processedValues, $total, $current;
				if (isset($processedValues[$count]) && is_array($processedValues[$count])) {
					$inValCount = count($processedValues[$count]);
					array_splice($processedValues, $count, 1, $processedValues[$count]);
					$count += ($inValCount - 1);
					$return = implode(",", array_pad(array("?"), $inValCount, "?"));
				} else {
					$return = $matches[0];
				}
				if ($total > 1 && $total != ($current+1)) {
					$count++;
				}
				$current++;
				return $return;
			'), $whole);

			unset($total, $current);

			if (substr_count($whole, '?') >= 1 && !preg_match('/[\(]+(.*)[\)]+/is', $whole)) {
				$whole = preg_replace('(\?([^\s\)]*))', '($0)', $whole);
			}
		} else if ($operator == DB::STARTS_WITH) {
			$whole = str_replace(DB::STARTS_WITH, self::$driver->startsWithOperator(), $whole);
			$values[$key] = $values[$key].self::$driver->wildcardOperator();
		} else if ($operator == DB::ENDS_WITH) {
			$whole = str_replace(DB::ENDS_WITH, self::$driver->endsWithOperator(), $whole);
			$values[$key] = self::$driver->wildcardOperator().$values[$key];
		} else if ($operator == DB::CONTAINS) {
			$whole = str_replace(DB::CONTAINS, self::$driver->containsOperator(), $whole);
			$values[$key] = self::$driver->wildcardOperator().$values[$key].self::$driver->wildcardOperator();
		} else if ($operator == DB::EQUALS) {
			$whole = str_replace(DB::EQUALS, self::$driver->equalsOperator(), $whole);
		} else if ($operator == DB::NOT_EQUALS) {
			$whole = str_replace(DB::NOT_EQUALS, self::$driver->notEqualsOperator(), $whole);
		} else if ($operator == DB::GREATER_THAN) {
			$whole = str_replace(DB::GREATER_THAN, self::$driver->greaterThanOperator(), $whole);
		} else if ($operator == DB::GREATER_THAN_OR_EQUAL) {
			$whole = str_replace(DB::GREATER_THAN_OR_EQUAL, self::$driver->greaterThanOrEqualOperator(), $whole);
		} else if ($operator == DB::LESS_THAN) {
			$whole = str_replace(DB::LESS_THAN, self::$driver->lessThanOperator(), $whole);
		} else if ($operator == DB::LESS_THAN_OR_EQUAL) {
			$whole = str_replace(DB::LESS_THAN_OR_EQUAL, self::$driver->lessThanOrEqualOperator(), $whole);
		} else if ($operator == DB::AND_THIS) {
			$whole = str_replace(DB::AND_THIS, self::$driver->andOperator(), $whole);
		} else if ($operator == DB::OR_THIS) {
			$whole = str_replace(DB::OR_THIS, self::$driver->orOperator(), $whole);
		}

		unset($operator);
		return $whole;
	}

	/**
	 * Returns a row from a query that has been executed.
	 *
	 * @access public
	 * @static
	 * @param mixed $statement The result from the PDO execute
	 * @return array
	 */
	public static function fetch($statement) {
		return $statement->fetch();
	}

	/**
	 * Returns all of the rows from a query that has been executed.
	 *
	 * @access public
	 * @static
	 * @param mixed $statement The result from the PDO execute
	 * @return array
	 */
	public static function fetchAll($statement) {
		return $statement->fetchAll();
	}

	/**
	 * Returns a row from a query that has been executed as an object.
	 *
	 * @access public
	 * @static
	 * @param mixed $statement The result from the PDO execute
	 * @param string $class_name The name of the result to fill with the results
	 * @return object
	 */
	public static function fetchObject($statement, $class_name = 'stdClass') {
		return $statement->fetchObject($class_name);
	}

	/**
	 * Returns a count of all the queries executed on a page.
	 *
	 * @access public
	 * @static
	 * @return integer
	 */
	public static function getQueryCount() {
		return self::$queryCount;
	}

	/**
	 * Returns an array of all the executed queries.
	 *
	 * @access public
	 * @static
	 * @return array
	 */
	public static function getQueries() {
		return self::$queries;
	}
}

/**
 * DB Driver Interface Class
 *
 * This is the interface class for the driver used to make sure that the drivers have
 * all of the required fields defined.
 *
 * @package       evergreen
 * @subpackage    lib
 */
interface DBDriverInterface {
	public function select($fields, $table, $options);
	public function insert($fields, $table);
	public function update($key, $fields, $table);
	public function delete($table, $options);
	public function truncate($table);
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
	public function wildcardOperator();
}