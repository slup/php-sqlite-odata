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
			$relationships = $this->dba->table_get_relationships($table['tbl_name']);
			$tables[$key]['relationships'] = $relationships;
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
					foreach($table['relationships'] as $relationship) {
						$relationships = $this->dba->table_get_relationships_between($table['tbl_name'], $relationship['table']);
						$this->writer->startElement('NavigationProperty');
						$this->writer->writeAttribute('Name', $relationship['table']); //print_r($relationships, TRUE)); 
						$this->writer->writeAttribute('Relationship', $this->model_name.'Model.'.$relationships[$table['tbl_name']]['name']);
						$this->writer->writeAttribute('ToRole', $relationship['table']);
						$this->writer->writeAttribute('FromRole', $table['tbl_name']);
						$this->writer->endElement();
					}
					/*
					<EntityType Name="Territory">
						<Key>
							<PropertyRef Name="TerritoryID"/>
						</Key>
						<Property Name="TerritoryID" Type="Edm.String" Nullable="false" MaxLength="20" FixedLength="false" Unicode="true"/>
						<Property Name="TerritoryDescription" Type="Edm.String" Nullable="false" MaxLength="50" FixedLength="true" Unicode="true"/>
						<Property Name="RegionID" Type="Edm.Int32" Nullable="false"/>
						<NavigationProperty Name="Region" Relationship="NorthwindModel.FK_Territories_Region" ToRole="Region" FromRole="Territories"/>
					</EntityType>
					<EntityType Name="Region">
						<Key>
							<PropertyRef Name="RegionID"/>
						</Key>
						<Property Name="RegionID" Type="Edm.Int32" Nullable="false"/>
						<Property Name="RegionDescription" Type="Edm.String" Nullable="false" MaxLength="50" FixedLength="true" Unicode="true"/>
						<NavigationProperty Name="Territories" Relationship="NorthwindModel.FK_Territories_Region" ToRole="Territories" FromRole="Region"/>
					</EntityType>
					*/
					$this->writer->endElement(); 
				}
				foreach($tables as $table) {
					foreach($table['relationships'] as $relationship) {
						if ($relationship['type'] === 'entry') {
							continue;
						}
						$relationships = $this->dba->table_get_relationships_between($table['tbl_name'], $relationship['table']);
						
						$this->writer->startElement('Association');
						$this->writer->writeAttribute('Name', $relationships[$table['tbl_name']]['name']);
							$this->writer->startElement('End');
							$this->writer->writeAttribute('Type', $this->model_name.'Model.'.$relationships[$table['tbl_name']]['fromTable']);
							$this->writer->writeAttribute('Role', $table['tbl_name'].($relationships[$relationships[$table['tbl_name']]['toTable']]['type'] === 'entry' ? "_1" : "_n"));
							$this->writer->writeAttribute('Multiplicity', $relationships[$relationships[$table['tbl_name']]['toTable']]['type'] === 'entry' ? "1" : "*");
							//$this->writer->writeAttribute('print_r', print_r($relationships, TRUE));
							$this->writer->endElement(); 
							$this->writer->startElement('End');
							$this->writer->writeAttribute('Type', $this->model_name.'Model.'.$relationships[$table['tbl_name']]['toTable']);
							$this->writer->writeAttribute('Role', $relationship['table'].($relationships[$relationships[$table['tbl_name']]['fromTable']]['type'] === 'entry' ? "_1" : "_n"));
							$this->writer->writeAttribute('Multiplicity', $relationships[$relationships[$table['tbl_name']]['fromTable']]['type'] === 'entry' ? "1" : "*");
							//$this->writer->writeAttribute('print_r', print_r($table['relationships'], TRUE));
							$this->writer->endElement(); 
							$this->writer->startElement('ReferentialConstraint');
								$this->writer->startElement('Principal');
								$this->writer->writeAttribute('Role', $table['tbl_name'].($relationships[$relationships[$table['tbl_name']]['toTable']]['type'] === 'entry' ? "_1" : "_n"));
									$this->writer->startElement('PropertyRef');
									$this->writer->writeAttribute('Name', $relationships[$table['tbl_name']]['fromColumn']);
									$this->writer->endElement();
								$this->writer->endElement();
								$this->writer->startElement('Dependent');
								$this->writer->writeAttribute('Role', $relationship['table'].($relationships[$relationships[$table['tbl_name']]['fromTable']]['type'] === 'entry' ? "_1" : "_n"));
									$this->writer->startElement('PropertyRef');
									$this->writer->writeAttribute('Name', $relationships[$table['tbl_name']]['toColumn']);
									$this->writer->endElement();
								$this->writer->endElement();
							$this->writer->endElement();
						$this->writer->endElement(); 
					}
				}
				
				
				$this->writer->endElement(); 
				/*
				<Association Name="FK_Territories_Region">
					<End Type="NorthwindModel.Region" Role="Region" Multiplicity="1"/>
					<End Type="NorthwindModel.Territory" Role="Territories" Multiplicity="*"/>
					<ReferentialConstraint>
						<Principal Role="Region">
							<PropertyRef Name="RegionID"/>
						</Principal>
						<Dependent Role="Territories">
							<PropertyRef Name="RegionID"/>
						</Dependent>
					</ReferentialConstraint>
				</Association>
				*/
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
						$this->writer->writeAttribute('Name', $table['tbl_name'].'_n');
						$this->writer->writeAttribute('EntityType', $this->model_name.'Model.'.$table['tbl_name']);
						//TODO add links between entitysets
						$this->writer->endElement(); 
						
						
						/*
						<EntitySet Name="Regions" EntityType="NorthwindModel.Region"/>
						<EntitySet Name="Territories" EntityType="NorthwindModel.Territory"/>
						*/
					}
					foreach($tables as $table) {
						foreach($table['relationships'] as $relationship) {
							if ($relationship['type'] === 'entry') {
								continue;
							}
							
							$relationships = $this->dba->table_get_relationships_between($table['tbl_name'], $relationship['table']);
							
							$this->writer->startElement('AssociationSet');
								$this->writer->startElement('End');
								$this->writer->writeAttribute('Role', $table['tbl_name'].($relationships[$relationships[$table['tbl_name']]['toTable']]['type'] === 'entry' ? "_1" : "_n"));
								$this->writer->writeAttribute('EntitySet', $this->model_name.'Model.'.$table['tbl_name'].'_n');
								$this->writer->endElement(); 
								$this->writer->startElement('End');
								$this->writer->writeAttribute('Role', $relationship['table'].($relationships[$relationships[$table['tbl_name']]['fromTable']]['type'] === 'entry' ? "_1" : "_n"));
								$this->writer->writeAttribute('EntitySet', $this->model_name.'Model.'.$relationship['table'].'_n');
								$this->writer->endElement(); 
							$this->writer->endElement(); 
						}
						/*
						<AssociationSet Name="FK_Territories_Region" Association="NorthwindModel.FK_Territories_Region">
							<End Role="Region" EntitySet="Regions"/>
							<End Role="Territories" EntitySet="Territories"/>
						</AssociationSet>
						*/
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