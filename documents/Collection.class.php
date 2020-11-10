<?php
namespace ch\slup\documents;

use Constant;
use ch\slup\documents;


class Collection {
	
	public function __construct($collection='', $db_analyzer, $model_name='', $service_base_path='', $query_options=[], $filters=[]) {
		$this->dba = $db_analyzer;
		$this->writer = new \XMLWriter(); 
		$this->collection = $collection;
		$this->service_base_path = $service_base_path;
		$this->model_name = $model_name;
		$this->query_options = $query_options;
		$this->filters = $filters;
	}
	
	public function create_document() {
		header('Content-type: application/xml;charset=utf-8');
		
		if (!$this->dba->table_exists($this->collection)) {
			$this->write_error();
			return;
		}
		
		$columns = $this->dba->get_columns($this->collection);
		
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
		
		$this->pk_column = $pk_column;
		$this->result_columns = $result_columns;
		
		
		if ($this->filters) {
			$rows = $this->dba->table_get_rows_filtered($this->collection, $this->filters);
		} else {
			$rows = $this->dba->table_get_rows($this->collection);
		}
		
		
		foreach ($rows as $key => $row) {
			foreach ($columns as $column) {
				$rows[$key][$column['name']] = \DataConverter::database2OData($column, $row[$column['name']]);
			}
		}
		
		$this->entries = $rows;
		
		$this->navigation_properties = $this->dba->table_get_relationships($this->collection);

		
		/*
		$tables = $this->dba->get_tables();
		
		foreach ($tables as $key => $table) {
			$columns = $this->dba->get_columns($table['tbl_name']);
			$tables[$key]['columns'] = $columns;
			$relationships = $this->dba->table_get_relationships($table['tbl_name']);
			$tables[$key]['relationships'] = $relationships;
		}
		*/
		
		$rows = $this->dba->table_get_rows($this->collection);
		
		$this->writer->openURI('php://output'); 
		$this->writer->startDocument('1.0', 'utf-8'); 
		
		$this->writer->startElement('feed');
		$this->writer->writeAttribute('xml:base', $this->service_base_path);
		$this->writer->writeAttribute('xmlns:d', 'http://schemas.microsoft.com/ado/2007/08/dataservices');
		$this->writer->writeAttribute('xmlns:m', 'http://schemas.microsoft.com/ado/2007/08/dataservices/metadata');
		$this->writer->writeAttribute('xmlns', 'http://www.w3.org/2005/Atom');
			if (array_key_exists('$inlinecount', $this->query_options)) {
				$this->writer->writeElementNS('m', 'count', null, count($rows));
			}
			// header
			$this->writer->startElement('title');
			$this->writer->writeAttribute('type', 'text');
			$this->writer->text($this->collection);
			$this->writer->endElement(); 
			$this->writer->startElement('id');
			$this->writer->text($this->service_base_path.$this->collection);
			$this->writer->endElement(); 
			$this->writer->startElement('updated');
			$this->writer->text(gmdate('c'));
			$this->writer->endElement(); 
			$this->writer->startElement('link');
			$this->writer->writeAttribute('rel', 'self');
			$this->writer->writeAttribute('title', $this->collection);
			$this->writer->writeAttribute('href', $this->collection);
			$this->writer->endElement();
			
			$entryDocument = new Entry($this->collection, $this->dba, $this->model_name, $this->service_base_path);
			//elements
			foreach($this->entries as $entry) { 
				$this->writer->startElement('entry');
				$entryDocument->write_entry_data($this->writer, $entry, $pk_column, $this->navigation_properties, $this->result_columns);
				$this->writer->endElement();
			}
		$this->writer->endElement(); 
		
		$this->writer->endDocument();
		
		$this->writer->flush();
		
	}
	
	private function write_error() {
		$this->writer->openURI('php://output'); 
		$this->writer->startDocument('1.0', 'utf-8', 'yes'); 
		$this->writer->startElement('error');
			$this->writer->writeElement('code', '');
			$this->writer->writeElement('message', 'Resource not found for the segment \''.$this->collection.'\'.');
		$this->writer->endElement(); 
		$this->writer->endDocument();
		$this->writer->flush();
	}
}
?>