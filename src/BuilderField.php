<?php

namespace Laravel\Api\Builder;

class BuilderField extends BaseBuilder
{

  private $fieldName;

  private $fieldOptions;

  private $options;

  private $type;
  private $typeParams;

  /**
   * Field type list
   * 
   * uuid : UUID type, auto generated if the field is primary
   * string: String type with optional parameter length
   * int: Integer type
   * integer: Integer type
   * text: Text type
   * date: Date type
   * datetime: Date with time type
   * increments: Integer primary key with auto increments
   * float: Float type
   * desimal: Decimal type
   * bool: Boolean type
   * boolean: Boolean type
   * one-to-many: get a list of linked records from another table. The "one-to-one" option field 
   *   must be set in the definition of the other table. Corresponds to a relationship one-to-many
   *
   * @var array
   */
  protected static $typeList = [
    'uuid' => ['mandatory' => 0, 'optional' => 0],
    'string' => ['mandatory' => 0, 'optional' => 1],
    'int' => ['mandatory' => 0, 'optional' => 0],
    'integer' => ['mandatory' => 0, 'optional' => 0],
    'text' => ['mandatory' => 0, 'optional' => 0],
    'date' => ['mandatory' => 0, 'optional' => 0],
    'datetime' => ['mandatory' => 0, 'optional' => 0],
    'increments' => ['mandatory' => 0, 'optional' => 0],
    'float' => ['mandatory' => 0, 'optional' => 0],
    // 'decimal' => ['mandatory' => 0, 'optional' => 0],
    'bool' => ['mandatory' => 0, 'optional' => 0],
    'boolean' => ['mandatory' => 0, 'optional' => 0],
    'one-to-many' => ['mandatory' => 2, 'optional' => 0, 'list' => ['table', 'field']],
    'many-to-many' => ['mandatory' => 1, 'optional' => 0, 'list' => ['table']],
  ];

  /**
   * Field option list
   * 
   * unique : Make the value unique @TODO check at model level if the value is unique in DB
   * default: Default value if created with Null or omitted
   * min: Minimum value for integer or float, minimum length for string, minimum length for array, 
   *   if not null
   * nullable: Field can be Null
   * primary: Field is a primary key, primary field is required and unique
   * required: Field must not be null or empty at creation and update
   * type: Check the type of the value if not null
   * one-to-one: Create a linked field to bind a record from another table, as a foreign key. 
   *   Corresponds to a relationship one-to-one
   * as: For linked field, the foreign entity is retrieved in a variable by API and omitted
   * omit: Field is not returned by API
   * cascade: @TODO For linked field, cascade the deletion to the foreign table entry
   *
   * @var array
   */
  protected static $optionList = [
    'unique' => ['mandatory' => 0, 'optional' => 0],
    'default' => ['mandatory' => 1, 'optional' => 0, 'list' => ['value']],
    'min' => ['mandatory' => 1, 'optional' => 0, 'list' => ['value']],
    'max' => ['mandatory' => 1, 'optional' => 0, 'list' => ['value']],
    'nullable' => ['mandatory' => 0, 'optional' => 0],
    'primary' => ['mandatory' => 0, 'optional' => 0],
    'required' => ['mandatory' => 0, 'optional' => 0],
    'type' => ['mandatory' => 1, 'optional' => 0, 'list' => ['type']],
    'one-to-one' => ['mandatory' => 2, 'optional' => 0, 'list' => ['table', 'field']],
    'as' => ['mandatory' => 1, 'optional' => 0, 'list' =>['field']],
    'omit' => ['mandatory' => 0, 'optional' => 0],
    'index' => ['mandatory' => 0, 'optional' => 0],
  ];


  public function __construct($command, $fieldName, $fieldOptions) {
    parent::__construct($command);
    
    $this->fieldName = $fieldName;
    $this->fieldOptions = $fieldOptions;
    $this->options = [];
    $this->type = null;
    $this->typeParams = null;

    $this->parseOptions($fieldOptions);
    $this->validate();
  }

  private function parseOptions($optionString) {
    if (trim($optionString) == '') {
      return;
    }

    $list = explode('|', $optionString);
    $this->options = [];

    foreach ($list as $value) {
      $arr = explode(':', $value);
      if (count($arr) == 1) {
        $option = $arr[0];
        $params = [];
      } else {
        $option = $arr[0];
        array_shift($arr);
        $params = $arr;
      }
      $this->options[$option] = $params;
    }
  }

  public function getOptions() {
    return $this->options;
  }

  public function getOption($name) {
    if (!$this->hasOption($name)) {
      return null;
    }
    return $this->options[$name];
  }

  public function getType() {
    return $this->type;
  }

  public function getTypeParams() {
    return $this->typeParams;
  }

  public function getName() {
    return $this->fieldName;
  }

  public function hasOption($name) {
    return isset($this->options[$name]);
  }

  public function translateType($name) {
    if ($name == 'int') {
      return 'integer';
    }
    if ($name == 'bool') {
      return 'boolean';
    }
    return $name;
  }

  private function validate() {
    if (count($this->options) == 0) {
      $this->log('error', 'No options for field "'.$this->fieldName.'"');
      return;
    }

    $types = [];
    foreach ($this->options as $optionName => $optionParams) {
      $this->validateOption($optionName, $optionParams);
      if (isset(self::$typeList[$optionName])) {
        $types[] = $optionName;
      }
    }

    if (count($types) == 0) {
      $this->log('error', 'No types for field "'.$this->fieldName.'"');
      return;
    } else if (count($types) > 1) {
      $this->log('error', 'Too many types for field "'.$this->fieldName.'"');
      return;
    }

    $this->type = $this->translateType($types[0]);
    $this->typeParams = $this->getOption($this->type);
  }

  private function validateOption($optionName, $optionParams) {
    if (isset(self::$optionList[$optionName])) {
      $validation = self::$optionList[$optionName];
    } else if (isset(self::$typeList[$optionName])) {
      $validation = self::$typeList[$optionName];
    } else {
      $this->log('error', 'Unknown option "'.$optionName.'" for field "'.$this->fieldName.'"');
      return false;
    }

    if (count($optionParams) < $validation['mandatory']) {
      $this->log('error', 'Field "'.$this->fieldName.'" option "'.$optionName.'" has not enought parameters. Minimum '.$validation['mandatory'].', found '.count($optionParams));
      $this->log('error', 'Parameters for field "'.$this->fieldName.'":  "'.$this->fieldOptions.'"');
      return false;
    }

    if (count($optionParams) > $validation['mandatory'] + $validation['optional']) {
      $this->log('error', 'Field "'.$this->fieldName.'" option "'.$optionName.'" has too many parameters. Maximum '.($validation['mandatory']+$validation['optional']).', found '.count($optionParams));
      $this->log('error', 'Parameters for field "'.$this->fieldName.'":  "'.$this->fieldOptions.'"');
      return false;
    }

    return true;
  }



}


