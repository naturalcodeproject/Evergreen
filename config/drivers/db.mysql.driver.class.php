<?php

class DB_MySQL_Driver implements DBDriverInterface {
	/**
	* returns a select query
	*
	* @param $fields string of all of the fields to get
	* @param $table string of the table name
	* @param $options array of the where, order and limit part of a query
	*/
	public function select($fields, $table, $options) {
		$query = 'SELECT ' . $fields . ' FROM ' . $table .
			$this->_getJoins($options) .
			$this->_getWhere($options) .
			$this->_getOrder($options) .
			$this->_getLimit($options);

		return $query;
	}

	public function insert($fields, $table) {
		
		$query = 'INSERT INTO ' . $table . ' (' . implode(',', $fields) . ') VALUES (' . rtrim(str_repeat('?,', sizeof($fields)), ',') . ')';
		
		return $query;
	}

	public function update($keys, $fields, $table) {
		foreach($fields as &$fieldValue) {
			$fieldValue .= ' = ?';
		}
		
		foreach($keys as &$keyValue) {
			$keyValue .= ' = ?';
		}
		
		$query = 'UPDATE ' . $table . ' SET ' . implode(', ', $fields) . ' WHERE ' . implode(' AND ', $keys);
		
		return $query;
	}
	
	public function delete($table, $options) {
		$query = 'DELETE FROM ' . $table .
			$this->_getJoins($options) .
			$this->_getWhere($options) .
			$this->_getOrder($options) .
			$this->_getLimit($options);

		return $query;
	}

	/**
	* extracts joins (inner, left and right) out of the options
	*/
	private function _getJoins() {
		return '';
	}

	/**
	* extracts the where part out of the options
	*/
	private function _getWhere($options) {
		if (!empty($options['where'])) {
			// if it is an array then we just want the first one which is the actual where string
			return ' WHERE ' . ((is_array($options['where'])) ? $options['where'][0] : $options['where']);
		}

		return '';
	}

	/**
	* extracts order part out of the options
	*/
	private function _getOrder($options) {
		if (!empty($options['order'])) {
			// if it is an array then it needs to be made into a string
			return ' ORDER BY ' . ((is_array($options['order'])) ? implode(', ', $options['order']) : $options['order']);
		}

		return '';
	}

	/**
	* extracts limit part out of the options
	*/
	private function _getLimit($options) {
		if (!empty($options['limit'])) {
			return ' LIMIT ' . $options['limit'];
		}

		return '';
	}
	
	/**
	* returns operators
	*/
	public function equalsOperator() {
		return '=';
	}
	public function notEqualsOperator() {
		return '!=';
	}
	public function startsWithOperator() {
		return 'like';
	}
	public function endsWithOperator() {
		return 'like';
	}
	public function containsOperator() {
		return 'like';
	}
	public function greaterThanOperator() {
		return '>';
	}
	public function greaterThanOrEqualOperator() {
		return '>=';
	}
	public function lessThanOperator() {
		return '<';
	}
	public function lessThanOrEqualOperator() {
		return '<=';
	}
	public function andOperator() {
		return 'AND';
	}
	public function orOperator() {
		return 'OR';
	}
}

?>