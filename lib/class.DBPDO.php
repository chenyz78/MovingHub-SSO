<?php

class DBPDO {
	public $pdo;
	private $error;

	function __construct($dsn, $user, $pass) {
		$this->connect($dsn, $user, $pass);
	}

	function prep_query($query){
		return $this->pdo->prepare($query);
	}

	function connect($dsn, $user, $pass){
		if(!$this->pdo){
			$dsn      = $dsn;
			$user     = $user;
			$password = $pass;

			try {
				$this->pdo = new PDO($dsn, $user, $password, array(PDO::ATTR_PERSISTENT => true));
				return true;
			} catch (PDOException $e) {
				$this->error = $e->getMessage();
				die($this->error);
				return false;
			}
		}else{
			$this->pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
			return true;
		}
	}

	function table_exists($table_name){
		$stmt = $this->prep_query('SHOW TABLES LIKE ?');
		$stmt->execute(array($table_name));
		return $stmt->rowCount() > 0;
	}

	function execute($query, $values = null){
		if($values == null){
			$values = array();
		}else if(!is_array($values)){
			$values = array($values);
		}
		$stmt = $this->prep_query($query);
		$stmt->execute($values);
		return $stmt;
	}

	function fetch($query, $values = null){
		if($values == null){
			$values = array();
		}else if(!is_array($values)){
			$values = array($values);
		}
		$stmt = $this->execute($query, $values);
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	function fetchAll($query, $values = null, $key = null){
		if($values == null){
			$values = array();
		}else if(!is_array($values)){
			$values = array($values);
		}
		$stmt = $this->execute($query, $values);
		$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

		// Allows the user to retrieve results using a
		// column from the results as a key for the array
		if($key != null && $results[0][$key]){
			$keyed_results = array();
			foreach($results as $result){
				$keyed_results[$result[$key]] = $result;
			}
			$results = $keyed_results;
		}
		return $results;
	}

	function lastInsertId(){
		return $this->pdo->lastInsertId();
	}

}
