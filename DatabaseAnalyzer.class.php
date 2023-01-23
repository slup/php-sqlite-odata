<?php
require_once 'Constant.class.php';
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
    
    public function table_get_rows_filtered($table_name, $filter) {
		return $this->table_query_filtered("SELECT * FROM ${table_name} WHERE", $table_name, $filter);
	}
	
    public function table_get_pk_column($table_name) {
        if ($this->table_exists($table_name)) {
            $pk_column = '';
            $columns = $this->get_columns($table_name);
            foreach ($columns as $column) {
				if (1 == $column['pk']) {
					$pk_column = $column['name'];
				}
			}
            
            if ($pk_column === '') {
                return FALSE;
            } else {
                return $pk_column;
            }
        }
    }
    
	public function table_get_entry($table_name, $id) {
		if ($this->table_exists($table_name)) {
            $pk_column = $this->table_get_pk_column($table_name);
            
            if ($pk_column === FALSE) {
                return array();
            }
            
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
	
	public function table_row_count_filtered($table_name, $filter) {
			$count_array = $this->table_query_filtered("SELECT COUNT(*) AS count FROM ${table_name} WHERE", $table_name, $filter);
			if ($count_array) {
				return $count_array[0]['count'];
			} else {
				return 0;
			}
	}
	
	public function table_query_filtered($base_query, $table_name, $filter) {
		if ($this->table_exists($table_name)) {
			$columns = $this->get_columns($table_name);
            $column_names = array();
            foreach ($columns as $column) {
                $column_names[] = $column['name'];
            }
			
			$query = $base_query;
            $filter_values = array();
            foreach ($filter as $filter_column => $filter_value) {
                if (!in_array($filter_column, $column_names)) {
                    // ignoring non existing columns
                    continue; 
                }
                // first element does not need "AND"
                if ($filter_value !== reset($filter)) { 
                    $query .= " AND ";
                }
                $query .= " ${filter_column} = ? ";
                $filter_values[] = $filter_value;
            }
            
            if (strpos($query, '?') === false) {
                // if no valid filter value is given, nothing can be found
                return array(); 
            }
            
			$result = $this->database->prepare($query);
			$result->execute($filter_values);
			return $result->fetchAll(PDO::FETCH_ASSOC);
		}
	}
	
	private function get_table_pk_column($table_name) {
		if ($this->table_exists($table_name)) {
			$columns = $this->get_columns($table_name);
			
			foreach ($columns as $column) {
				if (1 == $column['pk']) {
					return $column['name'];
				}
			}
		}
	}
	
	public function entry_update($table_name, $new_values) {
		$query = "";
		if (!$this->table_exists($table_name)) {
			return false;
		}
		
		$columns = $this->get_columns($table_name);
		
		$pk_column = $this->get_table_pk_column($table_name);
		
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
				$update_values[] = DataConverter::odata2Database($column, $new_values[$name]);
			} else {
				continue;
			}
		}
		
		$query = rtrim($query, ', ');
		
		$query .= " WHERE ${pk_column} = ?";
		$update_values[] = $new_values[$pk_column];
		
		$result = $this->database->prepare($query);
		$result->execute($update_values);
		
		return true;
	}
	
	public function entry_create($table_name, $new_values) {
		$query = "";
		if (!$this->table_exists($table_name)) {
			return false;
		}
		
		$columns = $this->get_columns($table_name);
		
		$pk_column = $this->get_table_pk_column($table_name);
		
		if (!array_key_exists($pk_column, $new_values) 
				|| strlen($new_values[$pk_column]) < 1) {
			$new_values[$pk_column] = $this->table_get_next_key($table_name, $pk_column);
		}
		
		$insert_values = array();
		$query = "INSERT INTO ${table_name} ( ";
		
		foreach($columns as $column) {
			$name = $column['name'];
			
			if (array_key_exists($name, $new_values)) {
				$query .= "${name}, ";
				$insert_values[] = DataConverter::odata2Database($column, $new_values[$name]);
			} else {
				continue;
			}
		}
		
		$query = rtrim($query, ', ');
		$query .= " ) VALUES ( ";
		foreach($columns as $column) {
			$query .= "?, ";
		}
		$query = rtrim($query, ', ');
		$query .= " )";
		
		$result = $this->database->prepare($query);
		$result->execute($insert_values);
		
		return $new_values[$pk_column];
	}
	
	public function entry_delete($table_name, $id) {
		if (!$this->table_exists($table_name)) {
			return false;
		}
		$pk_column = $this->get_table_pk_column($table_name);
		
		$result = $this->database->prepare("DELETE FROM ${table_name} WHERE ${pk_column} = ?");
		$result->execute(array($id));
		return true;
	}
	
	private function table_get_next_key($table_name, $pk_column) {
		if ($this->table_exists($table_name)) {
			$result = $this->database->prepare("SELECT MAX(${pk_column}) FROM ${table_name}");
			$result->execute();
			
			$count = $result->fetch(PDO::FETCH_COLUMN, 0);
			return ($count + 1);
		}	
	}
    
    public function table_get_foreign_keys($table_name) {
		if ($this->table_exists($table_name)) {
			$result = $this->database->prepare("PRAGMA foreign_key_list(${table_name})");
			$result->execute();
			
			$foreign_keys = $result->fetchAll(PDO::FETCH_ASSOC);
			return $foreign_keys;
		}	
	}
    
    public function table_get_relationships($table_name) {
        if ($this->table_exists($table_name)) {
            $relationships = array();
            $tables = $this->get_tables();
            foreach ($tables as $table) {
                $foreign_keys = $this->table_get_foreign_keys($table['tbl_name']);
                
                if ($table['tbl_name'] === $table_name) {
                    foreach ($foreign_keys as $foreign_key) {
                        $foreign_key['type'] = 'entry';
                        $relationships[] = $foreign_key;
                    }
                    continue;
                }
                foreach ($foreign_keys as $foreign_key) {
                    if ($foreign_key['table'] === $table_name) {
                        $relationships[] = array(
                                'table' => $table['tbl_name'],
                                'type' => 'feed'
                            );
                    }
                }
			}
            
            return $relationships;
        }
    }
    
    public function table_get_relationships_between($table_name, $related_table_name) {
        $table_foreign_keys = $this->table_get_foreign_keys($table_name);
        $related_table_foreign_keys = $this->table_get_foreign_keys($related_table_name);
        
        $relationships = array();
        
        foreach ($table_foreign_keys as $foreign_key) {
            // if related table is part of a foreign key of table
            /* Only 1:n is supported, foreign keys are always stored under the n-table
            * thus follows that: table is n and related table is 1, the relationship
            * from table to related table is of type entry
            */
            if($foreign_key['table'] === $related_table_name) {
                $relationships[$table_name] = array(
                        'fromTable' => $table_name,
                        'fromColumn' => $foreign_key['from'],
                        'toTable' => $related_table_name,
                        'toColumn' => $foreign_key['to'],
                        'name' => 'FK_'.$table_name.'_'.$related_table_name,
                        'type' => 'entry'
                    );
                    
                $relationships[$related_table_name] = array(
                        'fromTable' => $related_table_name,
                        'fromColumn' => $foreign_key['to'],
                        'toTable' => $table_name,
                        'toColumn' => $foreign_key['from'],
                        'name' => 'FK_'.$table_name.'_'.$related_table_name,
                        'type' => 'feed'
                    );
                    
                return $relationships;
            }
        }
        
        foreach ($related_table_foreign_keys as $foreign_key) {
            // opposite of above
            if($foreign_key['table'] === $table_name) {
                $relationships[$related_table_name] = array(
                        'fromTable' => $related_table_name,
                        'fromColumn' => $foreign_key['from'],
                        'toTable' => $table_name,
                        'toColumn' => $foreign_key['to'],
                        'name' => 'FK_'.$related_table_name.'_'.$table_name,
                        'type' => 'entry'
                    );
                    
                $relationships[$table_name] = array(
                        'fromTable' => $table_name,
                        'fromColumn' => $foreign_key['to'],
                        'toTable' => $related_table_name,
                        'toColumn' => $foreign_key['from'],
                        'name' => 'FK_'.$related_table_name.'_'.$table_name,
                        'type' => 'feed'
                    );
                    
                return $relationships;
            }
        }
        
        return $relationships;
    
    }
}
?>
