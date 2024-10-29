<?php

require_once 'DatabaseAnalyzer.class.php';
require_once 'Constant.class.php';
require_once 'documents/ServiceDocument.class.php';
require_once 'documents/Metadata.class.php';
require_once 'documents/Collection.class.php';
require_once 'documents/Entry.class.php';

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
        $sd = new ch\slup\documents\ServiceDocument(new DatabaseAnalyzer($this->database), $this->host, $this->subdir, $this->model_name);
        $sd->create_document();
    }
    
    public function service_metadata() {
        $metadata = new ch\slup\documents\Metadata(new DatabaseAnalyzer($this->database), $this->model_name);
        $metadata->create_document();
    }
    
    public function serve_collection($collection, $query_options) {
        $this->serve_collection_filtered($collection, $query_options, array());
    }
    
    public function serve_collection_filtered($collection, $query_options, $filters) {
        $collectionDocument = new ch\slup\documents\Collection(new DatabaseAnalyzer($this->database), $collection, $this->model_name, $this->service_base_path, $query_options, $filters);
        $collectionDocument->create_document();
    }
    
    public function serve_entry($collection, $id) {
        $entryDocument = new ch\slup\documents\Entry(new DatabaseAnalyzer($this->database), $collection, $this->model_name, $this->service_base_path);
        $entryDocument->create_document($id);
    }
    
    public function serve_related($collection, $id, $related_collection) {
        $dba = new DatabaseAnalyzer($this->database);
        if ($dba->table_exists($collection) && $dba->table_exists($related_collection)) {
        
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
            
            $new_id = $dba->entry_create($collection, $new_properties);
            
            $this->serve_entry($collection, $new_id);
            
            if ($new_id) {
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
                http_response_code(204);
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
    
    public function count_related($collection, $id, $related_collection) {
        $dba = new DatabaseAnalyzer($this->database);
        if ($dba->table_exists($collection) && $dba->table_exists($related_collection)) {
        
            $relationships = $dba->table_get_relationships_between($collection, $related_collection);
            if (array_key_exists($collection, $relationships)) {
                if ($relationships[$collection]['type'] === 'entry') {
                    $row = $dba->table_get_entry($collection, $id);
                    $related_id = $row[$relationships[$collection]['fromColumn']];
                    http_response_code(400); // can not count an entry
                } else {
                    $count = $dba->table_row_count_filtered($related_collection, array($relationships[$collection]['toColumn'] => $id));
                    echo $count;
                }
            }
        }
    }

    public function logout($is_auth_enabled) {
        if ($is_auth_enabled) {
            header('WWW-Authenticate: Basic realm="'.$this->model_name.' Service"');
            header('HTTP/1.1 401 Unauthorized');   
        } else {
            header('Location: '.$this->subdir);
        }
    }

    public function hash($value) {
        header('Content-type: text/plain;charset=utf-8');
        echo password_hash($value, PASSWORD_DEFAULT);
    }
    
}

?>
