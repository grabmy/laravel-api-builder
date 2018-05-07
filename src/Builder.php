<?php

namespace Laravel\Api\Builder;

class Builder extends BaseBuilder
{

  private $file;

  private $json;

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

    if (!isset($this->json['tables']) || count($this->json['tables']) == 0) {
      $this->log('error', 'No tables loaded in JSON');
      return;
    }

    if (count($this->json['tables']) > 1) {
      $this->log('comment', 'Parsing '.count($this->json['tables']).' tables ...', 'v');
    } else {
      $this->log('comment', 'Parsing 1 table ...', 'v');
    }

    $tableIndex = 0;
    foreach ($this->json['tables'] as $tableName => $tableOptions) {
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

  public function createMigration() {
    $migration = new CreateMigration($this->command, $this->tables);
    $migration->create();
  }

  public function createModel() {
    $model = new CreateModel($this->command, $this->tables);
    $model->create();
  }

  public function createController() {
    $model = new CreateController($this->command, $this->tables);
    $model->create();
  }

  public function createRoute() {
    $model = new CreateRoute($this->command, $this->tables);
    $model->create();
  }

}


