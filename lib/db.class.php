<?php

class DB extends PDO {

	function __construct() {
		$settings = Factory::get_config()->get_database_info();
		
		parent::__construct('mysql:host='.
		  	$settings['host'].';dbname='.
		  	$settings['database'],
		  	$settings['username'],
		  	$settings['password']
	  	);
	  	
	  	$this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}
	
	function query($statement) {
		$result = parent::query($statement);
		
		if ( !$result )
		{
			print_r( $this->errorInfo() );
		}

		return $result;
  	}
  	
  	function getElement($statement) {
  		$stmt = $this->prepare( $statement );
  		$stmt->execute();
  		$result = $stmt->fetchColumn();
  		
  		return $result;
	}
	
	function getRow($statement, $fetch_style=PDO::FETCH_BOTH) {
		$stmt = $this->prepare( $statement );
  		$stmt->execute();
  		$result = $stmt->fetch($fetch_style);
  		
  		return $result;
	}
	
	function getObject($statement, $class) {
		$stmt = $this->prepare( $statement );
  		$stmt->execute();
  		$result = $stmt->fetch(PDO::FETCH_INTO, $class);
  		
  		return $result;
	}
}

?>