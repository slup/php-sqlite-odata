<?php
namespace ch\slup\documents;

use Constant;

class Entry {

	protected $dba;
	protected $writer;
	protected $collection;
	protected $service_base_path;
	protected $model_name;
	protected $query_options;
	
	public function __construct($db_analyzer, $collection='', $model_name='', $service_base_path='', $query_options=[]) {
		$this->dba = $db_analyzer;
		$this->writer = new \XMLWriter(); 
		$this->collection = $collection;
		$this->service_base_path = $service_base_path;
		$this->model_name = $model_name;
		$this->query_options = $query_options;
	}
	
	public function create_document($id) {
		header('Content-type: application/xml;charset=utf-8');
		
		if (!$this->dba->table_exists($this->collection)
			|| !$this->dba->table_get_entry($this->collection, $id)) {
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
		
		$row = $this->dba->table_get_entry($this->collection, $id);
		
		foreach ($columns as $column) {
			$row[$column['name']] = \DataConverter::database2OData($column, $row[$column['name']]);
		}
		
		$entry = $row;
		$pk = $entry[$pk_column];
		
		$navigation_properties = $this->dba->table_get_relationships($this->collection);
		
		$this->writer->openURI('php://output'); 
		$this->writer->startDocument('1.0', 'utf-8'); 
		$this->writer->startElement('entry');
		$this->writer->writeAttribute('xml:base', $this->service_base_path);
		$this->writer->writeAttribute('xmlns:d', 'http://schemas.microsoft.com/ado/2007/08/dataservices');
		$this->writer->writeAttribute('xmlns:m', 'http://schemas.microsoft.com/ado/2007/08/dataservices/metadata');
		$this->writer->writeAttribute('xmlns', 'http://www.w3.org/2005/Atom');
		$this->write_entry_data($this->writer, $entry, $pk_column, $navigation_properties, $result_columns);
		$this->writer->endElement();
		$this->writer->endDocument();
		
		$this->writer->flush();
		
	}
	
	public function write_entry_data($writer, $entry, $pk_column, $navigation_properties, $result_columns) {
		$writer->startElement('id');
		$pk = $entry[$pk_column];
		$writer->text($this->service_base_path.$this->collection.'('.$pk.')');
		$writer->endElement();
		$writer->startElement('title');
		$writer->writeAttribute('type', 'text');
		$writer->endElement(); 
		$writer->startElement('updated');
		$writer->text(gmdate('c'));
		$writer->endElement(); 
		$writer->startElement('author');
		$writer->writeElement('name');
		$writer->endElement(); 
		$writer->startElement('link');
		$writer->writeAttribute('rel', 'edit');
		$writer->writeAttribute('title', $this->collection);
		$writer->writeAttribute('href', $this->collection.'('.$pk.')');
		$writer->endElement();
		foreach($navigation_properties as $navigation_property) {
			$writer->startElement('link');
			$writer->writeAttribute('rel', 'http://schemas.microsoft.com/ado/2007/08/dataservices/related/'.$navigation_property['table']);
			$writer->writeAttribute('type', 'application/atom+xml;type='.$navigation_property['type']);
			$writer->writeAttribute('title', $navigation_property['table']);
			$writer->writeAttribute('href',  $this->collection.'('.$pk.')/'.$navigation_property['table']);
			$writer->endElement();
		}
		$writer->startElement('category');
		$writer->writeAttribute('term', $this->model_name.'.'.$this->collection);
		$writer->writeAttribute('scheme', 'http://schemas.microsoft.com/ado/2007/08/dataservices/scheme');
		$writer->endElement();
		$writer->startElement('content');
		$writer->writeAttribute('type', 'application/xml');
			$writer->startElementNS('m', 'properties', null);
			foreach($result_columns as $result_column) {
				$writer->startElementNS('d', $result_column['name'], null);
				$writer->writeAttributeNS('m', 'type', null, $result_column['type']);
				$writer->text($entry[$result_column['name']]);
				$writer->endElement();
			}
			$writer->endElement();
		$writer->endElement();
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