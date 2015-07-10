<?php

require_once 'DatabaseAnalyzer.class.php';
require_once 'Template.class.php';
require_once 'Constant.class.php';

class Controller {

	private $subdir = '';
	private $host = '';
	private $model_name = '';
	private $database = '';
	private $service_base_path = '';
	

	public function __construct($host='localhost', $subdir='', $model_name='', $database='') {
		$this->host = $host;
		$this->subdir = $this->add_trailings_slash($subdir);
		$this->model_name = $model_name;
		$this->database = $database;
		$this->service_base_path = $this->host . $this->subdir;
		
	}
	
	private function add_trailings_slash($path) {
		if (substr($path, -1) === '/') {
			return $path;
		} else {
			return $path . '/';
		}
	}

	public function service_description() {
		$template = new Template();
		$db_analyzer = new DatabaseAnalyzer($this->database);
		
		$tables = $db_analyzer->get_tables();
		
		$template->tables = $tables;
		$template->host = $this->host;
		$template->subdir = $this->subdir;
		$template->title = $this->model_name;
		echo $template->render('templates/service_description.xml');
	}
	
	public function service_metadata() {
		$template = new Template();
		
		$db_analyzer = new DatabaseAnalyzer($this->database);
		
		$tables = $db_analyzer->get_tables();
		
		foreach ($tables as $key => $table) {
			$columns = $db_analyzer->get_columns($table['tbl_name']);
			$tables[$key]['columns'] = $columns;
		}
		
		$template->tables = $tables;
		$template->model_name = $this->model_name;
		echo $template->render('templates/metadata.xml');
	}
	
	public function serve_collection($collection, $query_options) {
		$this->serve_collection_filtered($collection, $query_options, array());
	}
    
    public function serve_collection_filtered($collection, $query_options, $filter) {
        $template = new Template();
		$dba = new DatabaseAnalyzer($this->database);
		
		if ($dba->table_exists($collection)) {
			$template->current_collection = $collection;
			$template->service_base_path = $this->service_base_path;
			$template->model_name = $this->model_name;
			$template->updated = gmdate('c');
			
			$columns = $dba->get_columns($collection);
			
			$result_columns = array();
			$pk_column = "";
			foreach ($columns as $column) {
				if (1 == $column['pk']) {
					$pk_column = $column['name'];
				}
				$result_columns[] = array(
						'type' => Constant::$DATATYPE_MAPPING[$column['type']],
						'name' => $column['name']
					);
			}
			
			$template->pk_column = $pk_column;
			$template->result_columns = $result_columns;
			
            
            if ($filter) {
                $rows = $dba->table_get_rows_filtered($collection, $filter);
            } else {
                $rows = $dba->table_get_rows($collection);
            }
			
			foreach ($rows as $key => $row) {
				foreach ($columns as $column) {
					$rows[$key][$column['name']] = DataConverter::database2OData($column, $row[$column['name']]);
				}
			}
			
			$template->entries = $rows;
            
            $relationships = $dba->table_get_relationships($collection);
			$template->navigation_properties = $relationships;
            
            if (array_key_exists('$inlinecount', $query_options)) {
                $template->inline_count = count($rows);
            }
			
			echo $template->render('templates/collection.xml');
		}
    }
	
	public function serve_entry($collection, $id) {
		$template = new Template();
		$dba = new DatabaseAnalyzer($this->database);
		
		if ($dba->table_exists($collection)) {
			$template->current_collection = $collection;
			$template->service_base_path = $this->service_base_path;
			$template->model_name = $this->model_name;
			$template->updated = gmdate('c');
			
			$columns = $dba->get_columns($collection);
			
			$result_columns = array();
			foreach ($columns as $column) {
				$result_columns[$column['name']] = Constant::$DATATYPE_MAPPING[$column['type']];
			}
			
			$template->pk_column = $dba->table_get_pk_column($collection);
			$template->result_columns = $result_columns;
			
			$row = $dba->table_get_entry($collection, $id);
			
			foreach ($columns as $column) {
				$row[$column['name']] = DataConverter::database2OData($column, $row[$column['name']]);
			}

			$template->entry = $row;
            
            $relationships = $dba->table_get_relationships($collection);
			$template->navigation_properties = $relationships;
			
			echo $template->render('templates/entry.xml');
		}
	}
    
    public function serve_related($collection, $id, $related_collection) {
        $dba = new DatabaseAnalyzer($this->database);
        if ($dba->table_exists($collection) && $dba->table_exists($related_collection)) {
        
            //print_r($dba->table_get_relationships_between($collection, $related_collection));
            // check if there is a relationship between those tables
            $relationships = $dba->table_get_relationships_between($collection, $related_collection);
            if (array_key_exists($collection, $relationships)) {
                if ($relationships[$collection]['type'] === 'entry') {
                    $row = $dba->table_get_entry($collection, $id);
                    $related_id = $row[$relationships[$collection]['fromColumn']];
                    $this->serve_entry($related_collection, $related_id);
                } else {
                    $this->serve_collection_filtered($related_collection, 
                        array(), array($relationships[$collection]['toColumn'] => $id)); 
                }
            }
        }
    }
	
	public function create_entry($collection) {
		$dba = new DatabaseAnalyzer($this->database);
		
		if ($dba->table_exists($collection)) {
			$body = file_get_contents("php://input");
			$xml = new SimpleXMLElement($body);
			
			$namespaces = $xml->getNamespaces(true);
			$entry = $xml->xpath('//*[local-name() = \'properties\']')[0];
			$new_properties = array();
			foreach($entry->children($namespaces['d']) as $tag => $value) {
				$new_properties[$tag] = $value.'';
			}
			
			$successful = $dba->entry_create($collection, $new_properties);
			
			if ($successful) {
				http_response_code(201);
			} else {
				http_response_code(400); // general error, for now
			}
		}
		
	}
	
	public function update_entry($collection, $id) {
		$dba = new DatabaseAnalyzer($this->database);
		
		if ($dba->table_exists($collection)) {
			$body = file_get_contents("php://input");
			$xml = new SimpleXMLElement($body);
			
			$namespaces = $xml->getNamespaces(true);
			$entry = $xml->xpath('//*[local-name() = \'properties\']')[0];
			$new_properties = array();
			foreach($entry->children($namespaces['d']) as $tag => $value) {
				$new_properties[$tag] = $value.'';
			}
			
			$successful = $dba->entry_update($collection, $new_properties);
			if ($successful) {
				http_response_code(204);
			} else {
				http_response_code(400); // general error, for now
			}
		}
	}
	
	public function delete_entry($collection, $id) {
		$dba = new DatabaseAnalyzer($this->database);
		if ($dba->table_exists($collection)) {
			$successful = $dba->entry_delete($collection, $id);
			if ($successful) {
				http_response_code(200);
			} else {
				http_response_code(400); // general error, for now
			}
		}
	}
	
	public function count_collection($collection) {
		$dba = new DatabaseAnalyzer($this->database);
		
		if ($dba->table_exists($collection)) {
			header('Content-type: text/plain;charset=utf-8');
			$count = $dba->table_row_count($collection);
			echo $count;
			return;
		}
	}
	
}

?>