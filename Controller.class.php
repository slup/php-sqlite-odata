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
	
	public function serve_collection($collection) {
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
				
				$result_columns[$column['name']] = Constant::$DATATYPE_MAPPING[$column['type']];
			}
			
			$template->pk_column = $pk_column;
			$template->result_columns = $result_columns;
			
			$rows = $dba->table_get_rows($collection);
			$template->entries = $rows;
			
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
			$pk_column = "";
			foreach ($columns as $column) {
				if (1 == $column['pk']) {
					$pk_column = $column['name'];
				}
				
				$result_columns[$column['name']] = Constant::$DATATYPE_MAPPING[$column['type']];
			}
			
			$template->pk_column = $pk_column;
			$template->result_columns = $result_columns;
			
			$row = $dba->table_get_entry($collection, $pk_column, $id);
			$template->entry = $row;
			
			echo $template->render('templates/entry.xml');
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
			
			$successful = $dba->table_update($collection, $new_properties);
			if ($successful) {
				http_response_code(204);
			} else {
				http_response_code(400); // general error, for now
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