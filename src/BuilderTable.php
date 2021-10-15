<?php

namespace Laravel\Api\Builder;

class BuilderTable extends BaseBuilder
{
  /**
   * Name of the table in database
   *
   * @var string
   */
  private $tableName;

  /**
   * Complete table options array
   *
   * @var [type]
   */
  private $tableOptions;

  /**
   * List of fields
   *
   * @var BuilderTableField
   */
  private $fields;

  /**
   * Table API options array
   *
   * @var array
   */
  private $api;

  /**
   * Hook function string
   *
   * @var array
   */
  private $hook;

  /**
   * Primary field
   *
   * @var BuilderTableField
   */
  private $primary;

  /**
   * List of omitted fields in API response
   *
   * @var array
   */
  //private $omitted;

  /**
   * List of indexed fields in table
   *
   * @var array
   */
  private $indexed;

  /**
   * Sort number of the table
   *
   * @var integer
   */
  private $sort = 0;

  /**
   * Constructor
   *
   * @param Command $command
   * @param string $tableName
   * @param array $tableOptions
   * @param array $tables
   */
  public function __construct($command, $tableName, $tableOptions) {
    parent::__construct($command);
    
    // Initialisation
    $this->tableName = $tableName;
    $this->tableOptions = $tableOptions;
    $this->fields = [];
    $this->api = [];
    $this->primary = null;
    //$this->omitted = [];
    $this->indexed = [];
    
    // Stop if no field
    if (!is_array($tableOptions['fields']) || count($tableOptions['fields']) == 0) {
      $this->log('error', 'No fields in table "'.$tableName.'"');
      return;
    }
    
    // Build the fields
    foreach ($tableOptions['fields'] as $fieldName => $fieldOptions) {
      $field = new BuilderTableField($this->command, $fieldName, $fieldOptions);
      if ($field->hasError()) {
        $this->error = true;
      } else {
        $this->fields[$fieldName] = $field;
        if ($field->hasOption('primary')) {
          $this->primary = $field;
          $this->indexed[] = $field->getName();
        }
        /*
        // Removed API feature
        if ($field->hasOption('omit')) {
          $this->omitted[] = $field->omitted();
        }
        */
      }
    }

    // Stop on error
    if (count($this->fields) == 0) {
      $this->log('error', 'No valid fields in table "'.$tableName.'"');
      return;
    }

    // Initialisation of API
    /*
    // Remove API feature
    if (isset($tableOptions['api'])) {
      $this->api = $tableOptions['api'];
      $this->api['prefix'] = isset($this->api['prefix']) ? $this->api['prefix'] : '';
      $this->api['endpoint'] = isset($this->api['endpoint']) ? $this->api['endpoint'] : strtolower($this->getPhpName());
      $this->api['methods'] = isset($this->api['methods']) ? $this->api['methods'] : ['GET', 'POST', 'PUT', 'DELETE'];
      $this->api['middleware'] = isset($this->api['middleware']) ? $this->api['middleware'] : 'api';
    }

    // Initialisation of hook
    if (isset($tableOptions['hook'])) {
      $this->hook = $tableOptions['hook'];
    }

    // Set sort
    if (isset($tableOptions['sort'])) {
      $this->sort = intval($tableOptions['sort']);
    }
    */
  }

  /*
  public function hasHook() {
    return isset($this->hook);
  }

  public function getHook() {
    return $this->hook;
  }

  public function getApi() {
    return $this->api;
  }
  */

  /**
   * Get the sort order for creating tables
   */
  public function getSort() {
    return $this->sort;
  }

  /**
   * Get the name of the table
   */
  public function getName() {
    return $this->tableName;
  }

  /**
   * Get the primary field of the table
   */
  public function getPrimary() {
    return $this->primary;
  }

  /**
   * Get the indexed field of the table
   */
  public function getIndexed() {
    return $this->indexed;
  }

  /*
  public function getOmitted() {
    return $this->omitted;
  }
  */

  /**
   * Get the fields of the table
   */
  public function getFields() {
    return $this->fields;
  }

  /**
   * Get the fields of the table in an array
   */
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
      if ($field->hasOption('increments')) {
        continue;
      }
      if ($field->hasOption('many-to-many') || $field->hasOption('one-to-many')) {
        continue;
      }
      $fillable[] = $fieldName;
    }
    return $fillable;
  }


}


