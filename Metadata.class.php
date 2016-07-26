<?php
namespace ch\slup\documents;

use Constant;

class Metadata {
	
	public function __construct($model_name='', $db_analyzer) {
		$this->dba = $db_analyzer;
		$this->writer = new \XMLWriter(); 
		$this->model_name = $model_name;
	}
	
	public function create_document() {
		header('Content-type: application/xml;charset=utf-8');
		
		$tables = $this->dba->get_tables();
		
		foreach ($tables as $key => $table) {
			$columns = $this->dba->get_columns($table['tbl_name']);
			$tables[$key]['columns'] = $columns;
		}
		
		$this->writer->openURI('php://output'); 
		$this->writer->startDocument('1.0', 'utf-8'); 
		
		$this->writer->startElementNS('edmx', 'Edmx', 'http://schemas.microsoft.com/ado/2007/06/edmx');
		$this->writer->writeAttribute('Version', '1.0');
			$this->writer->startElementNS('edmx', 'DataServices', null);
			$this->writer->writeAttribute('xmlns:m', 'http://schemas.microsoft.com/ado/2007/08/dataservices/metadata');
			$this->writer->writeAttribute('m:DataServiceVersion', '1.0');
				$this->writer->startElement('Schema');
				$this->writer->writeAttribute('Namespace', $this->model_name.'Model');
				$this->writer->writeAttribute('xmlns', 'http://schemas.microsoft.com/ado/2008/09/edm');
				foreach($tables as $table) {
					$this->writer->startElement('EntityType');
					$this->writer->writeAttribute('Name', $table['tbl_name']);
					foreach($table['columns'] as $column) {
						if (1 == $column['pk']) {
							$this->writer->startElement('Key');
								$this->writer->startElement('PropertyRef');
								$this->writer->writeAttribute('Name', $column['name']);
								$this->writer->endElement();
							$this->writer->endElement();
						}
						$nullable = (1 == $column['notnull']) ? 'false' : 'true';
						$type = Constant::$DATATYPE_MAPPING[$column['type']];
						$this->writer->startElement('Property');
						$this->writer->writeAttribute('Name', $column['name']);
						$this->writer->writeAttribute('Type', $type);
						$this->writer->writeAttribute('Nullable', $nullable);
						$this->writer->endElement(); 
					}
					$this->writer->endElement(); 
				}
				$this->writer->endElement(); 
				$this->writer->startElement('Schema');
				$this->writer->writeAttribute('Namespace', $this->model_name.'.Model');
				$this->writer->writeAttribute('xmlns', 'http://schemas.microsoft.com/ado/2008/09/edm');
				$this->writer->writeAttribute('xmlns:m', 'http://schemas.microsoft.com/ado/2007/08/dataservices/metadata');
				$this->writer->writeAttribute('xmlns:d', 'http://schemas.microsoft.com/ado/2007/08/dataservices');
					$this->writer->startElement('EntityContainer');
					$this->writer->writeAttribute('Name', $this->model_name.'Entities');
					$this->writer->writeAttribute('m:IsDefaultEntityContainer', 'true');
					foreach($tables as $table) {
						$this->writer->startElement('EntitySet');
						$this->writer->writeAttribute('Name', $table['tbl_name']);
						$this->writer->writeAttribute('EntityType', $this->model_name.'Model.'.$table['tbl_name']);
						//TODO add links between entitysets
						$this->writer->endElement(); 
					}
					$this->writer->endElement(); 
				$this->writer->endElement();
			$this->writer->endElement(); 
		$this->writer->endElement(); 
		
		$this->writer->endDocument();
		
		$this->writer->flush(); 
	}
}
?>