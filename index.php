<?php
  require 'AltoRouter.php';
  require 'Controller.class.php';
  
  $router = new AltoRouter();
  $router->setBasePath('/ODataTest');
  
  $controller = new Controller('slup.ch', '/ODataTest/', 'ODataTest', 'ODataTest.sqlite');
  
  $router->map( 'GET', '/', function() use ($controller) { $controller->service_description(); });
  $router->map( 'GET', '/[\$metadata:cmd]', function() use ($controller) { $controller->service_metadata(); });
  $router->map( 'GET', '/[a:collection]', function($collection) use ($controller) { $controller->serve_collection($collection); });
  $router->map( 'GET', '/[a:collection]/', function($collection) use ($controller) { $controller->serve_collection($collection); });
  $router->map( 'GET', '/[a:collection]\([a:id]\)', function($collection, $id) use ($controller) { $controller->serve_entry($collection, $id); });
  $router->map( 'GET', '/[a:collection]/[\$count:count]', function($collection) use ($controller) { $controller->count_collection($collection); });
  
  $match = $router->match();
  
  if( $match && is_callable( $match['target'] ) ) {
    call_user_func_array( $match['target'], $match['params'] ); 
  }
?>
