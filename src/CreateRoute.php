<?php

namespace Laravel\Api\Builder;

class CreateRoute extends BaseBuilder
{

  private $tables;

  private static $filename = './routes/api.php';

  public function __construct($command, array $tables) {
    parent::__construct($command);
    
    $this->tables = $tables;
  }


  public function create() {
    $this->log('comment', '--------------------------------------------', 'v');
    $this->log('comment', 'Modifying route file', 'v');
    $this->log('comment', 'Route file is "' . self::$filename . '"', 'v');

    $this->log('comment', 'Deleting previous lines', 'v');

    $content = file_get_contents(self::$filename);

    if (!$content) {
      $this->log('error', 'Could not open file "' . self::$filename . '"');
      return;
    }

    // Deleting previous lines in api.php
    foreach ($this->tables as $tableName => $table) {
      $this->log('comment', 'Deleting route block for table "' . $tableName . '"', 'v');
      $start = "// #START# RESTful service for table \"" . $tableName . "\"\n";
      $end = "// #END# RESTful service for table \"" . $tableName . "\"\n";
      $previous = $content;
      $content = self::removeBetween($start, $end, $content);
      if (trim($content) == trim($previous)) {
        $this->log('comment', 'No previous route found for "' . $tableName . '"', 'v');
      }
    }

    $content = trim($content);

    $this->log('comment', 'Inserting the new lines', 'v');
    
    foreach ($this->tables as $tableName => $table) {
      $api = $table->getApi();
      $this->log('comment', 'Adding route block for table "' . $tableName . '"', 'v');
      $content .= $this->getApiRoutesContent($table, $api['prefix'], $api['endpoint'], $api['methods'], $api['middleware']);
    }

    $this->log('comment', 'Overwriting the file', 'v');
    $success = file_put_contents(self::$filename, $content);
    if ($success) {
      $this->log('success', 'File "' . self::$filename . '" modified');
    } else {
      $this->log('error', 'Could not overwrite file "' . self::$filename . '"');
    }
    
  }

  private function getApiRoutesContent(BuilderTable $table, string $prefix, string $endPoint, array $methods, string $middleware) {
    $phpName = $table->getPhpName();
    $tableName = $table->getName();

    $content = "\n\n";
    $content .= "// #START# RESTful service for table \"" . $tableName. "\"\n";
    $content .= "// endpoint: \"" . $endPoint . "\"\n";
    $content .= "Route::group(['middleware' => '" . $middleware . "', 'prefix' => '" . $prefix . "'], function () {\n";

    if (isset($methods)) {
      foreach ($methods as $method) {
        switch ($method) {
          case 'GET':
            $content .= "  Route::get('" . $endPoint . "', '" . $phpName . "Controller@index');\n";
            $content .= "  Route::get('" . $endPoint . "/{" . strtolower($phpName) . "}', '" . $phpName . "Controller@show');\n";
            break;
          case 'POST':
            $content .= "  Route::post('" . $endPoint . "', '" . $phpName . "Controller@store');\n";
            break;
          case 'PUT':
            $content .= "  Route::put('" . $endPoint . "/{" . strtolower($phpName) . "}', '" . $phpName . "Controller@update');\n";        
            break;
          case 'DELETE':
            $content .= "  Route::delete('" . $endPoint . "/{" . strtolower($phpName) . "}', '" . $phpName . "Controller@delete');\n";        
            break;
          default:
            break;
        }
      }
    }
    $content .= "});\n";
    $content .= "// #END# RESTful service for table \"" . $tableName. "\"\n\n";

    return $content;    
  }

  

}


