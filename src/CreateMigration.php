<?php

namespace Laravel\Api\Builder;

class CreateMigration extends BaseBuilder
{

  private $tables;

  private static $baseDir = './database/migrations/';

  private static $extends = '\\Illuminate\\Database\\Migrations\\Migration';

  public function __construct($command, $tables) {
    parent::__construct($command);
    
    $this->tables = $tables;
  }

  private static function getMigrationFilename($table) {
    $number = '';

    switch (true) {
      case $table->getSort() < 10:
        $number = '00'.$table->getSort();
        break;
      case $table->getSort() < 100:
        $number = '0'.$table->getSort();
        break;
      default:
        $number = $table->getSort();
        break;
    }

    return self::$baseDir.'0'.$number.'____create_'.$table->getName().'_table.php';
  }

  private static function getConstraintFilename($table) {
    $number = '';

    switch (true) {
      case $table->getSort() < 10:
        $number = '00'.$table->getSort();
        break;
      case $table->getSort() < 100:
        $number = '0'.$table->getSort();
        break;
      default:
        $number = $table->getSort();
        break;
    }

    return self::$baseDir.'9'.$number.'____create_'.$table->getName().'_constraint.php';
  }

  public function create() {
    $this->log('comment', '--------------------------------------------', 'v');
    $this->log('comment', 'Creating migration files', 'v');

    $count_file_created = $this->createTables();

    if ($count_file_created > 1) {
      $this->log('comment', $count_file_created . ' files created', 'v');
    } else {
      $this->log('comment', $count_file_created . ' file created', 'v');
    }

    if ($count_file_created > 0) {
      $this->log('comment', 'Run the following command to execute migration:');
      $this->log('info', '    php artisan migrate:fresh');
    }
  }

  public function createTables() {
    $count_file_created = 0;

    foreach ($this->tables as $tableName => $table) {
      $file = self::getMigrationFilename($table);
      $content = $this->getMigrationContent($table);
      $success = file_put_contents($file, $content);
      if ($success == false) {
        $this->log('error', 'Could not write file "'.$file.'"');
      } else  {
        $this->log('success', 'File "'.$file.'" created');
        $count_file_created++;
      }

      // table contraint
      $file = self::getConstraintFilename($table);
      $content = $this->getConstraintContent($table);
      if ($content && trim($content) != '') {
        $success = file_put_contents($file, $content);
        if ($success == false) {
          $this->log('error', 'Could not write file "'.$file.'"');
        } else  {
          $this->log('success', 'File "'.$file.'" created');
          $count_file_created++;
        }
      } else {
        $this->log('comment', 'No constraint for table "' . $tableName . '"', 'v');
      }

      // check for many-to-many field
      $linkTables = [];
      foreach ($table->getFields() as $field) {
        if ($field->getType() == 'many-to-many') {
          // add link table to array
          $option = $field->getOption('many-to-many');
          $sourceFieldName = $table->getName().'_id';
          $sourceFieldOptions = $table->getPrimary()->getType().'|index';

          $targetTable = $this->findTable($option[0]);
          if (!$targetTable) {
            $this->log('warning', 'Could not find table "' . $option[0] . '" in table definitions');
            continue;
          }

          $targetPrimary = $targetTable->getPrimary();
          if (!$targetPrimary) {
            $this->log('warning', 'Could not find primary field of table "' . $option[0] . '" in table definitions');
            continue;
          }
          $targetFieldName = $option[0] . '_id';
          $targetFieldOptions = $targetPrimary->getType() . '|index';

          $tableName = $table->getName() . '_' . $targetTable->getName() . '_link';
          $tableOptions = array(
            'fields' => array(
              $sourceFieldName => $sourceFieldOptions,
              $targetFieldName => $sourceFieldOptions,
            ),
            'order' => $table->getSort()
          );
          $this->log('comment', 'Adding table "' . $tableName . '" as a link table', 'v');
          $linkTables[$tableName] = new BuilderTable($this->command, $tableName, $tableOptions);
        }
      }

      if (count($linkTables) > 0) {
        $this->log('comment', count($linkTables). ' additional tables to create', 'v');
        $migration = new CreateMigration($this->command, $linkTables);
        $count_file_created += $migration->createTables();
      }
    }

    return $count_file_created;
  }

  private function findTable($name) {
    foreach ($this->tables as $table) {
      if ($table->getName() == $name) {
        return $table;
      }
    }
    return null;
  }

  private function getMigrationContent(BuilderTable $table) {
    $phpName = $table->getPhpName();
    $tableName = $table->getName();
    $fields = $table->getFields();
    $primary = $table->getPrimary();
    $indexed = $table->getIndexed();

    $content = "<?php\n";
    $content .= "\n";
    $content .= "use Illuminate\Support\Facades\Schema;\n";
    $content .= "use Illuminate\Database\Schema\Blueprint;\n";
    $content .= "\n";
    $content .= "class Create".$phpName."Table extends ".self::$extends."\n";
    $content .= "{\n";
    $content .= "  /**\n";
    $content .= "   * Run the migrations.\n";
    $content .= "   *\n";
    $content .= "\n";
    $content .= "   */\n";
    $content .= "  public function up()\n";
    $content .= "  {\n";
    $content .= "    Schema::create('".$tableName."', function (Blueprint \$table) {\n";

    foreach ($fields as $fieldName => $field) {
      if (!is_null($field->getType())) {
        $content .= $this->getMigrationField($field);
      }
    }

    $content .= "      \$table->timestamps();\n";

    if (!empty($primary)) {
      $content .= "      \n";
      $content .= "      \$table->primary('" . $primary->getName() . "');\n";
    }

    if (count($indexed) > 0) {
      $content .= "      \n";
      $content .= "      \$table->index('" . implode('", "', $indexed) . "');\n";
    }

    $content .= "    });\n";
    $content .= "  }\n";
    $content .= "\n";
    $content .= "  /**\n";
    $content .= "   * Reverse the migrations.\n";
    $content .= "   *\n";
    $content .= "   * @return void\n";
    $content .= "   */\n";
    $content .= "  public function down()\n";
    $content .= "  {\n";
    $content .= "    Schema::disableForeignKeyConstraints();\n";
    $content .= "    Schema::dropIfExists('".$tableName."');\n";
    $content .= "  }\n";
    $content .= "}\n";
    $content .= "\n";

    return $content;
  }

  private function getConstraintContent(BuilderTable $table) {
    $countConstraint = 0;
    $fields = $table->getFields();

    foreach ($fields as $fieldName => $field) {
      if ($field->hasOption('one-to-one')) {
        $countConstraint++;
      }
    }
    if ($countConstraint == 0) {
      return '';
    }

    $phpName = $table->getPhpName();
    $tableName = $table->getName();

    $content = "<?php\n";
    $content .= "\n";
    $content .= "use Illuminate\Support\Facades\Schema;\n";
    $content .= "use Illuminate\Database\Schema\Blueprint;\n";
    $content .= "\n";
    $content .= "class Create".$phpName."Constraint extends ".self::$extends."\n";
    $content .= "{\n";
    $content .= "  /**\n";
    $content .= "   * Run the migrations.\n";
    $content .= "   *\n";
    $content .= "\n";
    $content .= "   */\n";
    $content .= "  public function up()\n";
    $content .= "  {\n";
    $content .= "    Schema::table('".$tableName."', function (Blueprint \$table) {\n";

    foreach ($fields as $fieldName => $field) {
      if ($field->hasOption('one-to-one')) {
        $params = $field->getOption('one-to-one');

        if ($tableName != $params[0]) {
          if (!isset($params[1])) {
            $params[1] = 'id';
          }
          $content .= "      \$table->foreign('".$fieldName."')->references('".$params[1]."')->on('".$params[0]."')";
          if ($field->hasOption('cascade')) {
            $content .= "->onDelete('cascade')";
          }
          $content .= ";\n";
        }
      }
    }

    $content .= "    });\n";
    $content .= "  }\n";
    $content .= "\n";
    $content .= "  /**\n";
    $content .= "   * Reverse the migrations.\n";
    $content .= "   *\n";
    $content .= "   * @return void\n";
    $content .= "   */\n";
    $content .= "  public function down()\n";
    $content .= "  {\n";
    $content .= "  }\n";
    $content .= "}\n";
    $content .= "\n";

    return $content;
  }


  /**
   * Return a PHP string for the migration field line
   *
   * @return string
   */
  public function getMigrationField(BuilderTableField $field) {
    $fieldType = $field->getType();
    if ($fieldType == 'one-to-many' || $fieldType == 'many-to-many') {
      return '';
    }

    $fieldTypeParams= $field->getTypeParams();
    $fieldOptions= $field->getOptions();
    $fieldName = $field->getName();

    $content = '';

    // specific for field type
    if ($fieldType == 'file') {
      $content .= "      \$table->string('".$fieldName."'";
    } else {
      $content .= "      \$table->".$fieldType."('".$fieldName."'";
    }
    
    if (!empty($fieldTypeParams)) {
      foreach ($fieldTypeParams as $param) {
        if (is_string($param)) {
          $content .= ", '" . $param . "'";
        } else {
          $content .= ", " . intval($param) . "";
        }
      }
    }
    $content .= ")";

    foreach ($fieldOptions as $name => $params) {
      switch ($name) {
        case 'nullable':
          $content .= '->nullable()';
          break;
        case 'unique':
          $content .= '->unique()';
          break;
        case 'defaultexp':
          if (isset($params[0])) {
            $content .= '->default(DB::raw(\''.$params[0].'\'));';
          }
          break;
        case 'default':
          if (isset($params[0])) {
            if ($fieldType == 'bool') {
              if ($params[0] == 'true') {
                $params[0] = true;
              } else {
                $params[0] = false;
              }
            } else if ($fieldType == 'integer') {
              $params[0] = intval($params[0]);
            } else if ($fieldType == 'float') {
              $params[0] = intval($params[0]);
            }

            if (is_string($params[0])) {
              $content .= '->default("' . $params[0] . '")';
            } else if (is_bool($params[0])) {
              if ($params[0]) {
                $content .= '->default(true)';
              } else {
                $content .= '->default(false)';
              }
            } else {
              $content .= '->default(' . $params[0] . ')';
            }
          }
          break;
        default:
          break;
      }
    }

    $content .= ";\n";

    return $content;
  }

  

}


