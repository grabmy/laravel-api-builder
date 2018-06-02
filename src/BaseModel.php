<?php

namespace Laravel\Api\Builder;

use Webpatser\Uuid\Uuid;
use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
  /**
   * The used namespace where all the models can be found
   *
   * @var string
   */
  protected $modelNamespace = '';

  /**
   * Get the list of entities
   *
   * @param array $columns
   * @return array
   */
  public static function all($columns = ['*']) {
    $list = parent::all($columns);

    foreach ($list as $entity) {
      $entity = $entity->fetch();
    }
    
    return $list;
  }

  private static function getPhpName(string $tableName) {
    $arr = explode('_', $tableName);
    $name = '';

    foreach ($arr as $slug) {
      $name .= ucfirst($slug);
    }

    return $name;
  }

  /**
   *  Setup model event hooks
   */
  public static function boot()
  {
    parent::boot();
    self::creating(function ($model) {
      $values = self::initFields();
      foreach ($values as $fieldName => $fieldValue) {
        $model->setAttribute($fieldName, $fieldValue);
      }
    });
  }

  public static function initFields() {
    $values = [];
    $options = self::staticGetFieldsData();
    foreach ($options as $fieldName => $fieldOptions) {
      $newValue = self::initField($fieldName, $fieldOptions);
      if (!is_null($newValue)) {
        $values[$fieldName] = $newValue;
      }
    }
    return $values;
  }

  protected static function initField(string $fieldName, array $fieldOptions) {
    $fieldValue = null;
    if (isset($fieldOptions['uuid']) && isset($fieldOptions['primary'])) {
      $fieldValue = (string) Uuid::generate();
    }
    return $fieldValue;
  }

  /**
   * Undocumented function
   *
   * @param array $values
   * @param boolean $isUpdate
   * @return void
   */
  protected static function validateFields(array $values, $isUpdate = false) {
    $fields = self::staticGetFieldsData();
    $fillable = self::staticGetFillable();
    $errors = [];

    if (count($fields) == 0) {
      return $errors;
    }

    foreach ($fillable as $index => $fieldName) {
      $fieldValue = isset($values[$fieldName]) ? $values[$fieldName] : null;
      $fieldOptions = isset($fields[$fieldName]) ? $fields[$fieldName] : [];
      $errors = array_merge($errors, self::validateField($fieldName, $fieldValue, $fieldOptions, $isUpdate));
    }

    return $errors;
  }

  protected static function validateField(string $fieldName, $fieldValue, array $fieldOptions, $isUpdate = false) {
    $errors = [];
    
    if (!$isUpdate && (isset($fieldOptions['unique']) || isset($fieldOptions['primary']))) {
      if (!self::validUnique($fieldName, $fieldValue)) {
        $errors[] = array(
          'name' => $fieldName,
          'value' => $fieldValue,
          'type' =>'error-not-unique'
        );
      }
    }

    if (isset($fieldOptions['min'])) {
      if (!self::validMin($fieldValue, intval($fieldOptions['min']))) {
        $errors[] = array(
          'name' => $fieldName,
          'value' => $fieldValue,
          'min' => intval($fieldOptions['min']),
          'type' =>'error-minimum-value'
        );
      }
    }

    if (!is_null($fieldValue) && isset($fieldOptions['max'])) {
        if (!self::validMax($fieldValue, intval($fieldOptions['max']))) {
          $errors[] = array(
            'name' => $fieldName,
            'value' => $fieldValue,
            'max' => intval($fieldOptions['max']),
            'type' =>'error-maximum-value'
          );
        }
    }

    if (isset($fieldOptions['required']) || (isset($fieldOptions['primary']) && !isset($fieldOptions['increments']) && !isset($fieldOptions['uuid']) && !$isUpdate)) {
        if (!self::required($fieldValue)) {
          $errors[] = array(
            'name' => $fieldName,
            'value' => $fieldValue,
            'type' =>'error-required'
          );
        }
    }

    if (isset($fieldOptions['uuid']) && !isset($fieldOptions['primary'])) {
      if (is_null($fieldValue) && isset($fieldOptions['nullable'])) {
        // do nothing
      } else {
        if (!self::validUuid($fieldValue)) {
          $errors[] = array(
            'name' => $fieldName,
            'value' => $fieldValue,
            'type' =>'error-invalid-uuid'
          );
        }
      }
    }

    if (!is_null($fieldValue) && isset($fieldOptions['type'])) {
      if (!self::validType($fieldValue, $fieldOptions['type'])) {
        $errors[] = array(
          'name' => $fieldName,
          'value' => $fieldValue,
          'expected' => $fieldOptions['type'],
          'found' => gettype($fieldValue),
          'type' =>'error-invalid-type'
        );
      }
    }

    if (isset($fieldOptions['foreign'])) {
      if (is_null($fieldValue) && isset($fieldOptions['nullable'])) {
        // do nothing
      } else {
        if (!self::validForeign($fieldValue, $fieldOptions['foreign'])) {
          $errors[] = array(
            'name' => $fieldName,
            'value' => $fieldValue,
            'type' =>'error-invalid-foreign-id'
          );
        }
      }
    }

    return $errors;
  }

  /**
   * Generate and return an UUID as a string
   *
   * @return string
   */
  protected static function getUuid() {
      return (string) Uuid::generate();
  }

  /**
   * Validate a unique field by making a request
   *
   * @param [type] $name
   * @param [type] $value
   * @return void
   */
  protected static function validUnique(string $name, $value) {
    if (self::where($name, $value)->count() > 0) {
      return false;
    }
    return true;
  }


  /**
   * Validate minimum value or length of a field value
   * 
   * @param mixed $value The value to check
   * @param integer $min The minimum value
   * @return boolean
   */
  protected static function validMin($value, $min) {
    if (is_string($value) && ($min <= 0 || mb_strlen($value) >= $min)) {
        return true;
    }
    if (is_numeric($value) && ($value >= $min)) {
        return true;
    }
    if (is_array($value) && (count($value) >= $min)) {
        return true;
    }
    return false;
  }

  /**
   * Validate the variable type of value
   * 
   * @param mixed $value The value to check
   * @param integer $min The minimum value
   * @return boolean True if validated
   */
  protected static function validType($value, $type) {
    if ($type === 'string' && is_string($value)) {
        return true;
    }
    if ($type === 'integer' && is_integer($value)) {
      return true;
    }
    if ($type === 'float' && (is_integer($value) || is_float($value))) {
      return true;
    }
    if ($type === 'decimal' && (is_integer($value) || is_float($value))) {
      return true;
    }
    if ($type === 'int' && is_integer($value)) {
        return true;
    }
    if ($type === 'array' && is_array($value)) {
        return true;
    }
    if ($type === 'boolean' && is_bool($value)) {
        return true;
    }
    if ($type === 'bool' && is_bool($value)) {
        return true;
    }
    return false;
  }

  /**
   * Validate minimum value or length of a field value
   * 
   * @param mixed $value The value to check
   * @param integer $min The maximum value
   * @return boolean True if validated
   */
  protected static function validMax($value, $max) {
    if (is_null($value)) {
      return true;
    }
    if (is_string($value) && mb_strlen($value) <= $max) {
      return true;
    }
    if (is_numeric($value) && $value <= $max) {
      return true;
    }
    if (is_array($value) && count($value) <= $max) {
      return true;
    }
    return false;
  }

  /**
   * Validate UUID (no version)
   * 
   * @param string $name The UUID string
   * @return boolean True if validated
   */
  protected static function validUuid($value) {
    $pattern = '/^[0-9A-F]{8}-[0-9A-F]{4}-[0-9A-F]{4}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i';
    if (preg_match($pattern, (string) $value)) {
      return true;
    }
    return false;
  }

  /**
   * Validate required field value
   * 
   * @param mixed $value The value to check
   * @return boolean True if validated
   */
  protected static function required($value) {
    if (is_string($value) && strlen($value) > 0) {
      return true;
    }
    if (is_numeric($value) && ($value > 0)) {
      return true;
    }
    if (is_array($value) && (count($value) > 0)) {
      return true;
    }
    return (boolean) $value;
  }

  /**
   * Validate foreign key
   * 
   * @param mixed $value
   * @param array $options
   * @return boolean True if validated
   */
  protected static function validForeign($value, $options) {
    $class = "\\App\\".self::getPhpName($options[0]);
    $entity = $class::find($value);
    return !is_null($entity);
  }

  protected function isFetchable($name) {
    return ($this->fetchable === true || isset($this->fetchable[$name]) && $this->fetchable[$name] !== false);
  }

  protected function getFetchable($name) {
    return isset($this->fetchable[$name]) ? $this->fetchable[$name] : false;
  }

  public function fetch() {
    if ($this->fetchable === false) {
      return $this;
    }

    foreach ($this->fieldsData as $fieldName => $fieldOptions) {
      if (isset($fieldOptions['one-to-one']) && isset($fieldOptions['as'])) {
        if ($this->isFetchable($fieldOptions['as'])) {
          $this->{$fieldOptions['as']} = $this->getLinked($fieldOptions['one-to-one'][0], $fieldOptions['one-to-one'][1], $this->{$fieldName}, $this->getFetchable($fieldOptions['as']));
        }
      } else if (isset($fieldOptions['one-to-many'])) {
        if ($this->isFetchable($fieldName)) {
          $this->{$fieldName} = $this->getListed($fieldOptions['one-to-many'][0], $fieldOptions['one-to-many'][1], $this->{$this->primaryKey}, $this->getFetchable($fieldName));
        }
      } else if (isset($fieldOptions['many-to-many'])) {
        if ($this->isFetchable($fieldName)) {
          $this->{$fieldName} = $this->getManyToMany($fieldOptions['many-to-many'][0], $fieldOptions['many-to-many'][1], $this->{$this->primaryKey}, $this->getFetchable($fieldName));
        }
      }
    }

    return $this;
  }

  public function getFiltered() {
    $result = $this->toArray();

    foreach ($this->fieldsData as $fieldName => $fieldOptions) {
      if (isset($fieldOptions['omit'])) {
        unset($result[$fieldName]);
      }
      if (isset($fieldOptions['as'])) {
        if ($this->isFetchable($fieldOptions['as'])) {
          unset($result[$fieldName]);
          if (isset($result[$fieldOptions['as']])) {
            $result[$fieldOptions['as']] = $result[$fieldOptions['as']]->getFiltered();
          }
        }
      } else if (isset($fieldOptions['one-to-many'])) {
        if (isset($result[$fieldName])) {
          foreach ($result[$fieldName] as $entity) {
            $entity = $entity->getFiltered();
          }
        }
      }
    }

    return $result;
  }

  private function getLinked($table, $field, $value, $fetchable) {
    // nothing to be found if value is null
    if (is_null($value)) {
      return null;
    }

    // get the model class
    $class = self::getPhpName($table);
    if ($this->modelNamespace != '') {
      $class = '\\'.$this->modelNamespace.'\\'.$class;
    }
    $entity = $class::where($field, '=', $value)->first();

    if (is_null($entity)) {
      return null;
    }
    
    $entity->fetchable = $fetchable;
    if ($fetchable !== false) {
      $entity->fetch();
    }
    return $entity;
  }

  private function getListed($table, $field, $value, $fetchable) {
    // nothing to be found if value is null
    if (is_null($value)) {
      return null;
    }

    // get the model class
    $class = self::getPhpName($table);
    if ($this->modelNamespace != '') {
      $class = '\\'.$this->modelNamespace.'\\'.$class;
    }

    // get the list
    $list = $class::where($field, '=', $value)->get();

    if (is_null($list)) {
      return null;
    }
    
    // fetch each entity
    $result = [];
    foreach ($list as $entity) {
      $entity->fetchable = $fetchable;
      if ($fetchable !== false) {
        $entity->fetch();
      }      $result[] = $entity;
    }
    return $list;
  }

  private function getManyToMany($targetTable, $targetField, $sourceValue, $fetchable) {
    // nothing to be found if value is null
    if (is_null($sourceValue)) {
      return null;
    }

    $linkTable = $this->table . '_' . $targetTable . '_link';
    $linkSourceField = $this->table . '_id';
    $linkTargetField = $targetTable . '_id';

    return \DB::table($linkTable)
      ->select($targetTable.'.*')
      ->where($linkTable.'.'.$linkSourceField, '=', $sourceValue)
      ->join($targetTable, $linkTable.'.'.$linkTargetField, '=', $targetTable.'.'.$targetField)
      ->get();
  }

  private function clearManyToMany($targetTable, $sourceValue) {
    // nothing to be found if value is null
    if (is_null($sourceValue)) {
      return null;
    }

    $linkTable = $this->table . '_' . $targetTable . '_link';
    $linkSourceField = $this->table . '_id';

    return \DB::table($linkTable)
      ->where($linkSourceField, '=', $sourceValue)
      ->delete();
  }

  private function insertManyToMany($fieldName, $targetTable, $targetField, $targetValue, $sourceValue) {
    // nothing to be found if value is null
    if (is_null($sourceValue)) {
      return null;
    }

    if (is_null($targetValue) || empty($targetValue)) {
      return null;
    }

    $linkTable = $this->table . '_' . $targetTable . '_link';
    $linkSourceField = $this->table . '_id';
    $linkTargetField = $targetTable . '_id';

    \DB::table($linkTable)
      ->insert(array(
        $linkSourceField => $sourceValue,
        $linkTargetField => $targetValue
      ));
    
    if (!isset($this->{$fieldName})) {
      $this->{$fieldName} = [];
    }

    $array = $this->{$fieldName};
    array_push($array, \DB::table($targetTable)->where($targetField, '=', $targetValue)->first());
    $this->{$fieldName} = $array;
  }

  protected function updateMany($inputs) {
    foreach ($this->fieldsData as $fieldName => $fieldOptions) {
      if (isset($fieldOptions['many-to-many'])) {
        $this->clearManyToMany($fieldOptions['many-to-many'][0], $this->{$this->primaryKey});

        if (isset($inputs[$fieldName]) && count($inputs[$fieldName]) > 0) {
          foreach ($inputs[$fieldName] as $targetValue) {
            $this->insertManyToMany($fieldName, $fieldOptions['many-to-many'][0], $fieldOptions['many-to-many'][1], $targetValue, $this->{$this->primaryKey});
          }
        }
      }
    }
  }

  public static function create($inputs) {
    $inputsData = $inputs;
    $fieldsData = self::staticGetFieldsData();
    foreach ($fieldsData as $fieldName => $fieldOptions) {
      if (isset($fieldOptions['many-to-many']) || isset($fieldOptions['one-to-many'])) {
        unset($inputsData[$fieldName]);
      }
    }
    $entity = static::query()->create($inputsData);
    $entity->updateMany($inputs);

    return $entity;
  }
  
  public function update(array $attributes = [], array $options = []) {
    $inputsData = $attributes;
    foreach ($this->fieldsData as $fieldName => $fieldOptions) {
      if (isset($fieldOptions['many-to-many']) || isset($fieldOptions['one-to-many'])) {
        unset($inputsData[$fieldName]);
      }
    }
    $success = parent::update($inputsData, $options);
    $this->updateMany($attributes);
    return $success;
  }
  
}

