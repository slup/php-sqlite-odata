<?php

namespace ch\slup\documents;

class ServiceDocument {
	
	public function __construct($host='localhost', $subdir='', $model_name='', $db_analyzer) {
		$this->dba = $db_analyzer;
		$this->writer = new \XMLWriter(); 
		$this->host = $host;
		$this->subdir = $subdir;
		$this->model_name = $model_name;
	}
	
	public function create_document() {
		header('Content-type: application/xml;charset=utf-8');
		
		$tables = $this->dba->get_tables();
		
		$this->writer->openURI('php://output'); 
		$this->writer->startDocument('1.0', 'utf-8'); 
		$this->writer->startElement('service');
		$this->writer->writeAttribute('xmlns', 'http://www.w3.org/2007/app');
		$this->writer->writeAttribute('xmlns:atom', 'http://www.w3.org/2005/Atom');
		$this->writer->writeAttribute('xmlns:m', 'http://docs.oasis-open.org/odata/ns/metadata');
		$this->writer->writeAttribute('xml:base', $this->host.$this->subdir);
		$this->writer->writeAttribute('m:context', $this->host.$this->subdir.'$metadata');
			$this->writer->startElement('workspace');
				$this->writer->writeElementNS('atom', 'title', null, $this->model_name);
				foreach($tables as $table) {
					$this->writer->startElement('collection');
					$this->writer->writeAttribute('href', $table['tbl_name']);
					$this->writer->writeElementNS('atom', 'title', null, $table['tbl_name']);
					$this->writer->endElement(); 
				}
			$this->writer->endElement(); 
		$this->writer->endElement(); 
		
		
		$this->writer->endDocument();

		$this->writer->flush(); 
	}
}
?>