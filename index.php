<?php
  error_reporting(E_ALL);
  header('Content-type: application/xml;charset=utf-8');
  
  require 'AltoRouter.php';
  
  $router = new AltoRouter();
  $router->setBasePath('/ODataTest/');
 
  // Set default timezone
  date_default_timezone_set('UTC');
  
  // --PRAGMA table_info(Todo);
  //SELECT tbl_name FROM sqlite_master WHERE type = 'table' AND tbl_name NOT LIKE 'sqlite_%'
  //--SELECT * FROM sqlite_master

  // print($_SERVER['REMOTE_USER']); 
  try {
    /**************************************
    * Create databases and                *
    * open connections                    *
    **************************************/
 
    // Create (connect to) SQLite database in file
    $file_db = new PDO('sqlite:ODataTest.sqlite');
    // Set errormode to exceptions
    $file_db->setAttribute(PDO::ATTR_ERRMODE, 
                            PDO::ERRMODE_EXCEPTION);
							
	$query = $_GET['request'];
	
	$DATATYPE_MAPPING = array(
			"INTEGER" => "Edm.Int32",
			"TEXT" => "Edm.String",
			"BOOL" => "Edm.Boolean",
		);
	
	if (empty($query)) {
		// show service description
		echo '<?xml version="1.0" encoding="utf-8"?>
			<service xml:base="http://slup.ch/ODataTest/" xmlns="http://www.w3.org/2007/app" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:m="http://docs.oasis-open.org/odata/ns/metadata" m:context="http://slup.ch/ODataTest/$metadata">
				<workspace>
					<atom:title type="text">Default</atom:title>';
					
		$result = $file_db->prepare("SELECT tbl_name FROM sqlite_master WHERE type = 'table' AND tbl_name NOT LIKE 'sqlite_%'");
		$result->execute();
		$tables = $result->fetchAll(PDO::FETCH_ASSOC);

		foreach ($tables as $table) {
			echo "<collection href=\"${table['tbl_name']}\">
					<atom:title type=\"text\">${table['tbl_name']}</atom:title>
				</collection>";
		}

		echo '</workspace>
			</service>';
	} else if ('$metadata' == $query) {
		// show metadata
		
		$path_only = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
		$path_only = str_replace('/'.$query, '', $path_only);
		$path_only = substr($path_only, strrpos($path_only, '/') + 1);
		
		
		echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>
			<edmx:Edmx Version=\"1.0\" xmlns:edmx=\"http://schemas.microsoft.com/ado/2007/06/edmx\">
				<edmx:DataServices xmlns:m=\"http://schemas.microsoft.com/ado/2007/08/dataservices/metadata\" m:DataServiceVersion=\"1.0\">
					<Schema Namespace=\"${path_only}Model\" xmlns=\"http://schemas.microsoft.com/ado/2008/09/edm\">";
			
		
		$result = $file_db->prepare("SELECT tbl_name FROM sqlite_master WHERE type = 'table' AND tbl_name NOT LIKE 'sqlite_%'");
		$result->execute();
		$tables = $result->fetchAll(PDO::FETCH_ASSOC);

		$entity_types = "";
		$entity_sets = "";
		
		foreach ($tables as $table) {
			$result = $file_db->prepare("PRAGMA table_info(${table['tbl_name']})");
			//$result->execute(array($table['tbl_name']));
			$result->execute();
			$columns = $result->fetchAll(PDO::FETCH_ASSOC);
			
			$entity_types = $entity_types."<EntityType Name=\"${table['tbl_name']}\">";
			
			foreach ($columns as $column) {
				if (1 == $column['pk']) {
					$entity_types = $entity_types."<Key>
					<PropertyRef Name=\"${column['name']}\" />
					</Key>";
				}
				
				$nullable = (1 == $column['notnull']) ? 'false' : 'true';
				
				$entity_types = $entity_types."<Property Name=\"${column['name']}\" Type=\"${DATATYPE_MAPPING[$column['type']]}\" Nullable=\"${nullable}\" />";
				
			}
			
			$entity_types = $entity_types."</EntityType>";
			
			$entity_sets = $entity_sets."<EntitySet Name=\"${table['tbl_name']}\" EntityType=\"${path_only}Model.${table['tbl_name']}\" />";
		}
		
		echo $entity_types;
		echo '</Schema>';
		
		echo "<Schema Namespace=\"${path_only}.Model\" xmlns:d=\"http://schemas.microsoft.com/ado/2007/08/dataservices\" xmlns:m=\"http://schemas.microsoft.com/ado/2007/08/dataservices/metadata\" xmlns=\"http://schemas.microsoft.com/ado/2008/09/edm\">
      <EntityContainer Name=\"${path_only}Entities\" m:IsDefaultEntityContainer=\"true\">";
		
		echo $entity_sets;
		echo '</EntityContainer></Schema>
			</edmx:DataServices>
		</edmx:Edmx>';
	} else {
		// execute request
		$current_collection = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
		$current_collection = substr($current_collection, strrpos($current_collection, '/') + 1);
		
		$url_parts = explode('/', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
		
		$current_collection = $url_parts[2];
		$show_collection = true;
		
		
		//$bla = print_r($url_parts,true);
		/*
		if (in_array('$count', $url_parts)) {
			// return the plain entry count
		}
		*/
		
		$pk = "";
		
		if (strpos($current_collection, '(') !== FALSE) {
			// single item!
			$show_collection = false;
			$pk = $current_collection;
			$pk = substr($current_collection, strpos($current_collection, '('));
			$pk = str_replace(array('(', ')'), '', $pk);
			$current_collection = substr($current_collection, 0, strpos($current_collection, '('));
		} 
		
		$service_base_path = "http://slup.ch/${url_parts[1]}/";
		$service_name = $url_parts[1];
		
		
		$result = $file_db->prepare("SELECT tbl_name FROM sqlite_master WHERE type = 'table' AND tbl_name NOT LIKE 'sqlite_%'");
		$result->execute();
		$tables = $result->fetchAll(PDO::FETCH_COLUMN, 0);
		
		$found = in_array($current_collection, $tables);
		if ($found) {
		
			if (in_array('$count', $url_parts)) {
				// return the plain entry count
				$result = $file_db->prepare("SELECT COUNT(*) FROM ${current_collection}");
				$result->execute();
				
				//$bla = in_array('$count', $url_parts);
				$count = $result->fetch(PDO::FETCH_COLUMN, 0);
				
				header('Content-type: text/plain;charset=utf-8');
				echo $count;
				return;
			}
			
			echo '<?xml version="1.0" encoding="utf-8"?>';
			
			if ($show_collection) {
				echo "<feed xml:base=\"${service_base_path}\" xmlns:d=\"http://schemas.microsoft.com/ado/2007/08/dataservices\" xmlns:m=\"http://schemas.microsoft.com/ado/2007/08/dataservices/metadata\" xmlns=\"http://www.w3.org/2005/Atom\">
  <title type=\"text\">${current_collection}</title>
  <id>${service_base_path}$current_collection</id>
  <updated>2015-03-26T21:49:03Z</updated>
  <link rel=\"self\" title=\"${current_collection}\" href=\"${current_collection}\" />";
			}
		
			$result = $file_db->prepare("PRAGMA table_info(${current_collection})");
			$result->execute();
			$columns = $result->fetchAll(PDO::FETCH_ASSOC);
			
			$result_columns = array();
			$pk_column = "";
			foreach ($columns as $column) {
				if (1 == $column['pk']) {
					$pk_column = $column['name'];
				}
				
				$result_columns[$column['name']] = $DATATYPE_MAPPING[$column['type']];
			}
			
			if ($show_collection) {
				$result = $file_db->prepare("SELECT * FROM ${current_collection}");
				$result->execute();
			} else {
				$result = $file_db->prepare("SELECT * FROM ${current_collection} WHERE ${pk_column} = ?");
				$result->execute(array($pk));
			}
			
			$rows = $result->fetchAll(PDO::FETCH_ASSOC);
			
			foreach ($rows as $row) {
				$pk = $row[$pk_column];
				
				if ($show_collection) {
					echo "<entry>";
				} else {
					echo "<entry xml:base=\"${service_base_path}\" xmlns:d=\"http://schemas.microsoft.com/ado/2007/08/dataservices\" xmlns:m=\"http://schemas.microsoft.com/ado/2007/08/dataservices/metadata\" xmlns=\"http://www.w3.org/2005/Atom\">";
				}
				
				echo "<id>${service_base_path}${current_collection}(${pk})</id>
    <title type=\"text\"></title>
    <updated>2015-03-26T21:49:03Z</updated>
    <author>
      <name />
    </author>
    <link rel=\"edit\" title=\"${current_collection}\" href=\"${current_collection}(${pk})\" />
    <category term=\"${service_name}.${current_collection}\" scheme=\"http://schemas.microsoft.com/ado/2007/08/dataservices/scheme\" />
    <content type=\"application/xml\">
      <m:properties>";
	  
				foreach ($result_columns as $column_name => $column_type) {
					$value = "";
					if ("Edm.Boolean" == $column_type) {
						$value = ($row[$column_name]) ? "true" : "false";
					} else {
						$value = $row[$column_name];
					}
					echo "<d:${column_name} m:type=\"${column_type}\">${value}</d:${column_name}>";
				}
				
				echo "</m:properties>
    </content>
  </entry>";
			}
			if ($show_collection) {
				echo "</feed>";
			}
		} else {
			//echo "<element>Nope</element>";
		}
		
		
		if ('$metadata' == $query) {
		}
	}
	
    /**************************************
    * Create tables                       *
    **************************************/
/*
    // Create table messages
    $file_db->exec("CREATE TABLE IF NOT EXISTS Spiel (
                    SpielID INTEGER PRIMARY KEY, 
                    Gegner TEXT, 
                    Datum TEXT,
                    Anzahl INTEGER
                    Rangpunkte BOOL)");
                    
    // Create table messages
    $file_db->exec("CREATE TABLE IF NOT EXISTS Spieler (
                    SpielerID INTEGER PRIMARY KEY, 
                    Name TEXT, 
                    Jahrgang TEXT,
                    LizenzNr TEXT)");
                    
    // Create table messages
    $file_db->exec("CREATE TABLE IF NOT EXISTS Punkt (
                    PunktID INTEGER PRIMARY KEY, 
                    Streiche TEXT, 
                    Total TEXT,
                    Rangpunkte TEXT)");

    // Create table messages
    $file_db->exec("CREATE TABLE IF NOT EXISTS Streich (
                    StreichID INTEGER PRIMARY KEY, 
                    Reihenfolge INTEGER, 
                    Anzahl INTEGER,
                    SpielID INTEGER,
                    SpielerID INTEGER,
                    PunktID INTEGER)");  

    // Prepare INSERT statement to SQLite3 file db
    $insert = "INSERT INTO Spiel (Gegner, Datum, Anzahl, Rangpunkte) 
                VALUES (:gegner, :datum, :anzahl, :rangpunkte)";
    $stmt = $file_db->prepare($insert);
 
    $stmt->bindParam(':gegner', $gegner);
    $stmt->bindParam(':datum', $datum);
    $stmt->bindParam(':anzahl', $anzahl);
    $stmt->bindParam(':rangpunkte', $rangpunkte);

    $spiele = array(
                  array('gegner' => 'HG Mützlenberg-Nesselgraben C',
                        'datum' => '2014-04-19',
                        'anzahl' => 4,
                        'rangpunkte' => 1),
                  array('gegner' => 'HG Wasen-Lugenbach C',
                        'datum' => '2014-04-26',
                        'anzahl' => 4,
                        'rangpunkte' => 0),
                );

    foreach ($spiele as $s) {
      $gegner = $s['gegner'];
      $datum = $s['datum'];
      $anzahl = $s['anzahl'];
      $rangpunkte = $s['Rangpunkte'];
 
      // Execute statement
      $stmt->execute();
    }



    $insert = "INSERT INTO Spieler (Name, Jahrgang, LizenzNr) 
                VALUES (:name, :jahrgang, :lizenz)";
    $stmt = $file_db->prepare($insert);
 
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':jahrgang', $jahrgang);
    $stmt->bindParam(':lizenz', $lizenz);

    $spieler = array(
                 array('name' => 'Hans Muster',
                        'jahrgang' => '84',
                        'lizenz' => '12345'),
                 array('name' => 'Peter Streit',
                        'jahrgang' => '55',
                        'lizenz' => '12346'),
                 array('name' => 'Frank Müller',
                        'jahrgang' => '61',
                        'lizenz' => '12347'),
                );

    foreach ($spieler as $s) {
      $name = $s['name'];
      $jahrgang = $s['jahrgang'];
      $lizenz = $s['lizenz'];
 
      // Execute statement
      $stmt->execute();
    }

     

    $insert = "INSERT INTO Punkt (Streiche, Total, Rangpunkte) 
                VALUES (:streiche, :total, :rangpunkte)";
    $stmt = $file_db->prepare($insert);
 
    $stmt->bindParam(':streiche', $streiche);
    $stmt->bindParam(':total', $total);
    $stmt->bindParam(':rangpunkte', $rangpunkte);

    $punkte = array(
                 array('streiche' => '1,2,3,4',
                        'total' => '10',
                        'rangpunkte' => '0'),
                 array('streiche' => '5,6,7,8',
                        'total' => '26',
                        'rangpunkte' => '0'),
                 array('streiche' => '12,12,13,14',
                        'total' => '51',
                        'rangpunkte' => '6'),
                 array('streiche' => '6,7,13,14',
                        'total' => '40',
                        'rangpunkte' => '1'),
                );

    foreach ($punkte as $p) {
      $streiche = $p['streiche'];
      $total = $p['total'];
      $rangpunkte = $p['rangpunkte'];
 
      // Execute statement
      $stmt->execute();
    }



    $insert = "INSERT INTO Streich (Reihenfolge, Anzahl, SpielID, SpielerID, PunktID) 
                VALUES (:reihenfolge, :anzahl, :spielid, :spielerid, :punktid)";
    $stmt = $file_db->prepare($insert);
 
    $stmt->bindParam(':reihenfolge', $reihenfolge);
    $stmt->bindParam(':anzahl', $anzahl);
    $stmt->bindParam(':spielid', $spielid);
    $stmt->bindParam(':spielerid', $spielerid);
    $stmt->bindParam(':punktid', $punktid);

    $streiche = array(
             array('reihenfolge' => 1,
                    'anzahl' => 4,
                    'spielid' => 1,
                    'spielerid' => 1,
                    'punktid' => 1),
             array('reihenfolge' => 2,
                    'anzahl' => 4,
                    'spielid' => 1,
                    'spielerid' => 2,
                    'punktid' => 2),
             array('reihenfolge' => 3,
                    'anzahl' => 4,
                    'spielid' => 1,
                    'spielerid' => 3,
                    'punktid' => 3),
             array('reihenfolge' => 1,
                    'anzahl' => 4,
                    'spielid' => 2,
                    'spielerid' => 1,
                    'punktid' => 4),          
            );
    
    foreach ($streiche as $s) {
      $reihenfolge = $s['reihenfolge'];
      $anzahl = $s['anzahl'];
      $spielid = $s['spielid'];
      $spielerid = $s['spielerid'];
      $punktid = $s['punktid'];
 
      // Execute statement
      $stmt->execute();
    }

*/

    // Select all data from file db messages table 
/*
    $result = $file_db->prepare("SELECT * FROM Spiel  
                                    LEFT OUTER JOIN Streich ON Streich.SpielID = Spiel.SpielID
                                    LEFT OUTER JOIN Spieler ON Streich.SpielerID = Spieler.SpielerID
                                    LEFT OUTER JOIN Punkt ON Streich.PunktID = Punkt.PunktID");
*/

/*
    $result = $file_db->prepare("SELECT * FROM Spiel");
    $result->execute();
    $spiele = $result->fetchAll(PDO::FETCH_ASSOC);

    foreach ($spiele as $key => $spiel) {
      $result = $file_db->prepare("SELECT * FROM Streich WHERE Streich.SpielID = ?");
      $result->execute(array($spiel['SpielID']));
      $streiche = $result->fetchAll(PDO::FETCH_ASSOC);

      foreach ($streiche as $streich_key => $streich) {
        $result = $file_db->prepare("SELECT * FROM Punkt WHERE Punkt.PunktID = ?");
        $result->execute(array($streich['PunktID']));
        $punkt = $result->fetchAll(PDO::FETCH_ASSOC);
        $streiche[$streich_key]['Punkte'] = $punkt[0];

        $result = $file_db->prepare("SELECT * FROM Spieler WHERE Spieler.SpielerID = ?");
        $result->execute(array($streich['SpielerID']));
        $spieler = $result->fetchAll(PDO::FETCH_ASSOC);
        $streiche[$streich_key]['Spieler'] = $spieler[0];

        $streiche[$streich_key]['Reihenfolge'] = intval($streiche[$streich_key]['Reihenfolge']);
      }

      $spiele[$key]['Streiche'] = $streiche;
    }

    $result = $file_db->prepare("SELECT * FROM Spieler");
    $result->execute();
    $spieler = $result->fetchAll(PDO::FETCH_ASSOC);

    $get_array = array('Spiele' => $spiele,
                    'Spieler' => $spieler);

    $get_data = json_encode($get_array);
    print($get_data);
*/
    /**************************************
    * Drop tables                         *
    **************************************/
/* 
    // Drop table messages from file db
    $file_db->exec("DELETE FROM Spiel");
    $file_db->exec("DELETE FROM Spieler");
    $file_db->exec("DELETE FROM Punkt");
    $file_db->exec("DELETE FROM Streich");
*/
    
    /**************************************
    * Close db connections                *
    **************************************/
 
    // Close file db connection
    //$file_db = null;
  }
  catch(PDOException $e) {
    // Print PDOException message
    echo $e->getMessage();
  }
?>
