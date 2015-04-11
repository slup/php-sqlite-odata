<?php

class DatabaseAnalyzer {

	protected $database_name = '';

	/**
	  * Creates DatabaseAnalyzer for specific sqlite database.
	  *
	  * @param string $database_name
	  */
	public function __construct($database_name = '') {
		$this->database_name = $database_name;
		$this->database = new PDO('sqlite:'.$this->database_name);
		$this->database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}
	
	public function __destruct() {
		$this->database = null;
	}
	
	public function get_tables() {
		$result = $this->database->prepare("SELECT tbl_name FROM sqlite_master WHERE type = 'table' AND tbl_name NOT LIKE 'sqlite_%'");
		$result->execute();
		$tables = $result->fetchAll(PDO::FETCH_ASSOC);
		
		return $tables;
	}
	
	public function get_columns($table_name) {
		$result = $this->database->prepare("PRAGMA table_info(${table_name})");
		$result->execute();
		$columns = $result->fetchAll(PDO::FETCH_ASSOC);
		
		return $columns;
	}
	
	public function table_exists($table_name) {
		$result = $this->database->prepare("SELECT tbl_name FROM sqlite_master WHERE type = 'table' AND tbl_name NOT LIKE 'sqlite_%'");
		$result->execute();
		$tables = $result->fetchAll(PDO::FETCH_COLUMN, 0);
		
		return in_array($table_name, $tables);
	}
	
	public function table_get_rows($table_name) {
		if ($this->table_exists($table_name)) {
			$result = $this->database->prepare("SELECT * FROM ${table_name}");
			$result->execute();
			$rows = $result->fetchAll(PDO::FETCH_ASSOC);
			return $rows;
		}
	}
	
	public function table_get_entry($table_name, $pk_column, $id) {
		if ($this->table_exists($table_name)) {
			$result = $this->database->prepare("SELECT * FROM ${table_name} WHERE ${pk_column} = ?");
			$result->execute(array($id));
			$row = $result->fetch(PDO::FETCH_ASSOC);
			return $row;
		}
	}
	
	public function table_row_count($table_name) {
		if ($this->table_exists($table_name)) {
			$result = $this->database->prepare("SELECT COUNT(*) FROM ${table_name}");
			$result->execute();
			
			$count = $result->fetch(PDO::FETCH_COLUMN, 0);
			return $count;
		}
	}
}

?>