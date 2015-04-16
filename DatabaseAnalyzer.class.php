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
	
	public function table_update($table_name, $new_values) {
		$query = "";
		if ($this->table_exists($table_name)) {
			$columns = $this->get_columns($table_name);
			
			$pk_column = "";
			foreach ($columns as $column) {
				if (1 == $column['pk']) {
					$pk_column = $column['name'];
					break;
				}
			}
			
			if (!array_key_exists($pk_column, $new_values)) {
				echo "ERROR: No primary key";
				return;
			}
			
			$query = "UPDATE ${table_name} SET ";
			$update_values = array();
			
			foreach($columns as $column) {
				$name = $column['name'];
				if ($name === $pk_column) {
					continue;
				}
				
				if (array_key_exists($name, $new_values)) {
					$query .= "${name} = ?, ";
					if ($column['type'] == 'BOOL') {
						$update_values[] = $this->parseBoolean($new_values[$name]); 
					} else {
						$update_values[] = $new_values[$name];
					}
				} else {
					continue;
				}
			}
			
			$query = rtrim($query, ', ');
			
			$query .= " WHERE ${pk_column} = ?";
			$update_values[] = $new_values[$pk_column];
			
			$result = $this->database->prepare($query);
			$result->execute($update_values);
			
			http_response_code(204);
		}
	}
	
	private function parseBoolean($string) {
	   return ($string && strtolower($string) === "true") ? 1 : 0;
	}
}

?>