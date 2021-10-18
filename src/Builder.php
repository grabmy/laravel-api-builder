<?php

namespace Laravel\Api\Builder;

class Builder extends BaseBuilder
{
  /**
   * The json file to load
   */
  private $file;

  /**
   * The entire structure of the json parsed
   */
  private $json;

  /**
   * The model part of the json
   */
  private $model;

  /**
   * The tables part of the model
   */
  private $tables;

  /**
   * API part of the json
   */
  private $api;

  /**
   * API part of the json
   */
  private $requests;

  /**
   * Current version of the API
   */
  static private $version = "0.3";

  /**
   * Load the JSON file
   *
   * @param string $file
   * @return array
   */
  public function load($file) {
    $this->file = $file;
    $this->json = null;
    $this->tables = [];

    $this->log('comment', 'Opening JSON API configuration file "'.$file.'" ...', 'v');

    if (!file_exists($file)) {
      $this->log('error', 'File "'.$file.'" does not exist');
      return;
    }

    $this->json = json_decode(file_get_contents($file), true);
    if (empty($this->json)) {
      $this->log('error', 'Could not parse JSON in file "'.$file.'"');
      return;
    }
    $this->log('success', 'JSON is loaded successfully', 'v');

    // Checking version number
    if (!isset($this->json['version'])) {
      $this->log('warning', 'No version provided in the JSON file');
    } else {
      if ($this->json['version'] !== $this::$version) {
        $this->log('error', 'Wrong version number "'.$this->json['version'].'" in JSON file. Expected "'.$this::$version.'"');
        return;
      }
      $this->log('comment', 'JSON version "'.$this->json['version'].'"', 'v');
    }

    // Extracting model from JSON
    if (!isset($this->json['model'])) {
      $this->log('warning', 'No model loaded in JSON');
    } else {
      $this->model = $this->json['model'];

      if (!isset($this->model['tables']) || count($this->model['tables']) == 0) {
        $this->log('warning', 'No tables loaded in JSON');
        return;
      }
  
      if (count($this->model['tables']) > 1) {
        $this->log('comment', 'Parsing '.count($this->model['tables']).' tables ...', 'v');
      } else {
        $this->log('comment', 'Parsing 1 table ...', 'v');
      }
  
      $tableIndex = 0;
      foreach ($this->model['tables'] as $tableName => $tableOptions) {
        $this->log('comment', 'Parsing table "'.$tableName.'"', 'v');
  
        if (!isset($tableOptions['sort'])) {
          $tableOptions['sort'] = $tableIndex + 1;
        }
  
        $table = new BuilderTable($this->command, $tableName, $tableOptions);
        if ($table->hasError()) {
          $this->log('error', 'Table "'.$tableName.'" has errors');
        } else {
          $this->tables[$tableName] = $table;
        }
        $tableIndex++;
      }
    }

    // Extracting API
    if (!isset($this->json['api'])) {
      $this->log('warning', 'No API loaded in JSON');
    } else {
      $this->api = $this->json['api'];

      if (!isset($this->api['requests']) == 0) {
        $this->log('warning', 'No requests loaded in JSON');
        return;
      }
  
      if (count($this->model['tables']) > 1) {
        $this->log('comment', 'Parsing '.count($this->model['tables']).' tables ...', 'v');
      } else {
        $this->log('comment', 'Parsing 1 table ...', 'v');
      }
  
      $tableIndex = 0;
      foreach ($this->model['tables'] as $tableName => $tableOptions) {
        $this->log('comment', 'Parsing table "'.$tableName.'"', 'v');
  
        if (!isset($tableOptions['sort'])) {
          $tableOptions['sort'] = $tableIndex + 1;
        }
  
        $table = new BuilderTable($this->command, $tableName, $tableOptions);
        if ($table->hasError()) {
          $this->log('error', 'Table "'.$tableName.'" has errors');
        } else {
          $this->tables[$tableName] = $table;
        }
        $tableIndex++;
      }
    }
  }

  public function createMigration() {
    $migration = new CreateMigration($this->command, $this->tables);
    $migration->create();
  }

  public function createModel() {
    $model = new CreateModel($this->command, $this->tables);
    $model->create();
  }

  public function createController() {
    if (isset($this->api) && $this->api !== null)
    {
      $controller = new CreateController($this->command, $this->tables, $this->api);
      $controller->create();
    }
  }

  public function createRoute() {
    if (isset($this->api) && $this->api !== null)
    {
      $routes = new CreateRoute($this->command, $this->tables, $this->api);
      $routes->create();
    }
  }

}


