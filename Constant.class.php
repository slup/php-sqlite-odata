<?php
class Constant {
	static $DATATYPE_MAPPING = array(
			"INTEGER" => "Edm.Int32",
			"TEXT" => "Edm.String",
			"BOOL" => "Edm.Boolean",
			"DATETIME" => "Edm.DateTime"
		);
}

class DataConverter {
    private function __construct() {}
	
	public static function database2OData($column, $value) {
		if (!is_array($column) || !array_key_exists('type', $column)) {
			return null;
		}
		
		if ($column['type'] == 'BOOL') {
			return DataConverter::booleanDB2OData($value);
		}
		else if($column['type'] == 'DATETIME') {
			return DataConverter::dateTimeDB2OData($value);
		}
		else {
			return $value;
		}
	}
	
	public static function odata2Database($column, $value) {
		if (!is_array($column) || !array_key_exists('type', $column)) {
			return null;
		}
		if ($column['type'] == 'BOOL') {
			return DataConverter::booleanOData2DB($value);
		}
		else if($column['type'] == 'DATETIME') {
			DataConverter::dateTimeOData2DB($value);
		}		
		else {
			return $value;
		}
	}
	
	public static function booleanDB2OData($db_value) {
		return ($db_value === 1) ? "true" : "false";
	}
	
	public static function dateTimeDB2OData($db_value) {
		return gmdate('c', strtotime($db_value));
	}
	
	public static function booleanOData2DB($odata_value) {
		return ($odata_value && strtolower($odata_value) === "true") ? 1 : 0;
	}
	
	public static function dateTimeOData2DB($odata_value) {
		return str_replace($db_value, "T", " ");
	}
}
?>
