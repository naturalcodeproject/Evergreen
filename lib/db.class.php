<?php

class DB extends PDO {
    private $curException;
    private $curErrorCode;
    
    function __construct() {
        parent::__construct('mysql:host='.
            Config::read('Database.host').';dbname='.
            Config::read('Database.database'),
            Config::read('Database.username'),
            Config::read('Database.password')
        );

        $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->curException = null;
    }

    function query($statement) {
        $this->curException = null;
        
        try {
            $result = parent::query($statement);
            return $result;
        } catch (Exception $e) {
            $this->curException = $e;
            $this->curErrorCode = parent::errorCode();
            return false;
        }
    }

    public function getCurException() {
        return $this->curException;
    }
    
    public function getCurErrorCode() {
        return $this->curErrorCode;
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