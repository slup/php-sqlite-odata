<?php

  error_reporting(E_ALL);
  ini_set( 'display_errors','1'); 

  require 'Config.class.php';
  require 'AltoRouter.php';
  require 'Controller.class.php';

  if (Config::is_auth_enabled()) {
    if (!isset($_SERVER['PHP_AUTH_USER'])) {
        header('WWW-Authenticate: Basic realm="'.Config::get_model_name().' Service"');
        header('HTTP/1.1 401 Unauthorized');

        echo 'Invalid credentials';
        exit; 
    }

    $ini_array = parse_ini_file("users.ini");

    $user = $_SERVER['PHP_AUTH_USER'];

    if (array_key_exists($user, $ini_array)) {
      $hashed_password = $ini_array[$user];
      $user_supplied_password = $_SERVER['PHP_AUTH_PW'];
      if (password_verify($user_supplied_password, $hashed_password)) {
        // continue with normal processing
      } else {
        echo 'Invalid credentials';
        exit;
      }
    }
  }

  
  $router = new AltoRouter();
  $router->setBasePath(Config::get_subdir());
  
  $controller = new Controller(Config::get_host(), Config::get_subdir(), Config::get_model_name(), Config::get_database_path());
  
  $router->map( 'GET', '/', function() use ($controller) { $controller->service_description(); });
  $router->map( 'GET', '/logout', function() use ($controller) { $controller->logout(Config::is_auth_enabled()); });
  $router->map( 'GET', '/hash/[a:value_to_hash]', function($value_to_hash) use ($controller) { $controller->hash($value_to_hash); });
  $router->map( 'GET', '/[\$metadata:cmd]', function($cmd, $query_string_parameters) use ($controller) { $controller->service_metadata(); });
  $router->map( 'GET', '/[a:collection]', function($collection, $query_string_parameters = array()) use ($controller) { $controller->serve_collection($collection, $query_string_parameters); });
  $router->map( 'GET', '/[a:collection]/', function($collection, $query_string_parameters = array()) use ($controller) { $controller->serve_collection($collection, $query_string_parameters); });
  $router->map( 'GET', '/[a:collection]\([a:id]\)', function($collection, $id, $query_string_parameters = array()) use ($controller) { $controller->serve_entry($collection, $id); });
  $router->map( 'GET', '/[a:collection]\([a:id]\)/[a:related_collection]', function($collection, $id, $related_collection, $query_string_parameters = array()) use ($controller) { $controller->serve_related($collection, $id, $related_collection); });
  $router->map( 'GET', '/[a:collection]/[\$count:count]', function($collection, $count) use ($controller) { $controller->count_collection($collection); });
  $router->map( 'GET', '/[a:collection]\([a:id]\)/[a:related_collection]/[\$count:count]', function($collection, $id, $related_collection, $count) use ($controller) { $controller->count_related($collection, $id, $related_collection); });
  
  $router->map( 'PUT', '/[a:collection]\([a:id]\)', function($collection, $id) use ($controller) { $controller->update_entry($collection, $id); });
  $router->map( 'POST', '/[a:collection]', function($collection) use ($controller) { $controller->create_entry($collection); });
  $router->map( 'POST', '/[a:collection]/', function($collection) use ($controller) { $controller->create_entry($collection); });
  $router->map( 'DELETE', '/[a:collection]\([a:id]\)', function($collection, $id) use ($controller) { $controller->delete_entry($collection, $id); });
  
  $match = $router->match();
  
  if( $match && is_callable( $match['target'] ) ) {
    call_user_func_array( $match['target'], $match['params'] ); 
  }
?>
