<?php

namespace Laravel\Api\Builder;

class BuilderTable extends BaseBuilder
{

  private $tableName;

  private $tableOptions;

  private $fields;

  private $api;

  private $primary;

  private $omitted;

  private $indexed;

  private $sort = 0;

  public function __construct($command, $tableName, $tableOptions) {
    parent::__construct($command);
    
    $this->tableName = $tableName;
    $this->tableOptions = $tableOptions;
    $this->fields = [];
    $this->api = [];
    $this->primary = null;
    $this->omitted = [];
    $this->indexed = [];
    
    if (!is_array($tableOptions['fields']) || count($tableOptions['fields']) == 0) {
      $this->log('error', 'No fields in table "'.$tableName.'"');
      return;
    }
    
    foreach ($tableOptions['fields'] as $fieldName => $fieldOptions) {
      $field = new BuilderField($this->command, $fieldName, $fieldOptions);
      if ($field->hasError()) {
        $this->error = true;
      } else {
        $this->fields[$fieldName] = $field;
        if ($field->hasOption('primary')) {
          $this->primary = $field;
          $this->indexed[] = $field->getName();
        }
        if ($field->hasOption('omit')) {
          $this->omitted[] = $field->omitted();
        }
      }
    }

    if (count($this->fields) == 0) {
      $this->log('error', 'No valid fields in table "'.$tableName.'"');
      return;
    }

    if (isset($tableOptions['api'])) {
      $this->api = $tableOptions['api'];
      $this->api['prefix'] = isset($this->api['prefix']) ? $this->api['prefix'] : '';
      $this->api['endpoint'] = isset($this->api['endpoint']) ? $this->api['endpoint'] : strtolower($this->getPhpName());
      $this->api['methods'] = isset($this->api['methods']) ? $this->api['methods'] : ['GET', 'POST', 'PUT', 'DELETE'];
      $this->api['middleware'] = isset($this->api['middleware']) ? $this->api['middleware'] : 'api';
    }

    if (isset($tableOptions['sort'])) {
      $this->sort = intval($tableOptions['sort']);
    }
  }

  public function getSort() {
    return $this->sort;
  }

  public function getApi() {
    return $this->api;
  }

  public function getName() {
    return $this->tableName;
  }

  public function getPrimary() {
    return $this->primary;
  }

  public function getIndexed() {
    return $this->indexed;
  }

  public function getOmitted() {
    return $this->omitted;
  }

  public function getFields() {
    return $this->fields;
  }

  public function getFieldsArray() {
    $result = [];

    foreach ($this->fields as $fieldName => $field) {
      $options = $field->getOptions();

      foreach ($options as $name => $value) {
        if (is_array($value)) {
          if (count($value) == 0) {
            $options[$name] = true;
          } else if (count($value) == 1) {
              $options[$name] = $value[0];
            }
        } else {
          unset($options[$name]);
        }
      }
      $result[$fieldName] = $options;
    }

    return $result;
  }

  public function getPhpName() {
    $arr = explode('_', $this->getName());
    $name = '';

    foreach ($arr as $slug) {
      $name .= ucfirst($slug);
    }

    return $name;
  }

  public function getFillable() {
    $fillable = [];
    foreach ($this->fields as $fieldName => $field) {
      if (isset($options['increments'])) {
        continue;
      }
      if (isset($options['uuid']) && isset($options['primary'])) {
        continue;
      }
      $fillable[] = $fieldName;
    }
    return $fillable;
  }


}


