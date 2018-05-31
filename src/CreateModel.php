<?php

namespace Laravel\Api\Builder;

class CreateModel extends BaseBuilder
{
  /**
   * List of tables
   *
   * @var array BuilderTable
   */
  private $tables;

  public static $baseDir = './app/';

  public static $extends = '\\Laravel\\Api\\Builder\\BaseModel';

  public static $namespace = 'App';

  public function __construct($command, $tables) {
    parent::__construct($command);
    
    $this->tables = $tables;
  }

  private static function getModelFilename($table) {
    return self::$baseDir.$table->getPhpName().'.php';
  }


  public function create() {
    $this->log('comment', '--------------------------------------------', 'v');
    $this->log('comment', 'Creating model files', 'v');
    $count_file_created = 0;

    foreach ($this->tables as $tableName => $table) {
      $fields = $table->getFields();
      $file = self::getModelFilename($table);
      $content = $this->getModelContent($table);
      $success = file_put_contents($file, $content);
      if ($success == false) {
        $this->log('error', 'Could not write file "'.$file.'"');
      } else  {
        $this->log('success', 'File "'.$file.'" created');
        $count_file_created++;
      }
    }

    if ($count_file_created > 1) {
      $this->log('comment', $count_file_created . ' files created', 'v');
    } else {
      $this->log('comment', $count_file_created . ' file created', 'v');
    }
  }

  private function getModelContent($table) {
    $tableName = $table->getName();
    $phpName = $table->getPhpName();
    $fields = $table->getFields();
    $api = $table->getApi();
    $fieldsData = $table->getFieldsArray();

    $content = "<?php\n";
    $content .= "\n";
    $content .= "namespace ".self::$namespace.";\n";
    $content .= "\n";
    $content .= "class " . $phpName . " extends ".self::$extends."\n";
    $content .= "{\n";
    $content .= "  \n";
    $content .= "  /**\n";
    $content .= "   * The namespace for all models.\n";
    $content .= "   *\n";
    $content .= "   * @var string\n";
    $content .= "   */\n";
    $content .= "  protected \$modelNamespace = '" . self::$namespace . "';\n";
    $content .= "  \n";
    $content .= "  /**\n";
    $content .= "   * The table associated with the model.\n";
    $content .= "   *\n";
    $content .= "   * @var string\n";
    $content .= "   */\n";
    $content .= "  protected \$table = '" . $tableName . "';\n";
    $content .= "  \n";
    
    // Fillable var
    $fillable = $table->getFillable();
    if (count($fillable) > 0) {
      $content .= "  /**\n";
      $content .= "   * The fillable fields.\n";
      $content .= "   *\n";
      $content .= "   * @var array\n";
      $content .= "   */\n";
      $content .= "  protected \$fillable = ['" . implode("', '", $fillable) . "'];\n";
      $content .= "  \n";
    }

    // primary key field
    $primary = $table->getPrimary();
    if ($primary->getName() != 'id') {
      $content .= "  /**\n";
      $content .= "   * Primary key field name.\n";
      $content .= "   *\n";
      $content .= "   * @var string\n";
      $content .= "   */\n";
      $content .= "  protected \$primaryKey = '" . $primary->getName() . "';\n";
      $content .= "  \n";
    }
    if ($primary->getType() != '') {
      $content .= "  /**\n";
      $content .= "   * Primary key type.\n";
      $content .= "   *\n";
      $content .= "   * @var string\n";
      $content .= "   */\n";
      $content .= "  protected \$keyType = '" . $primary->getType() . "';\n";
      $content .= "  \n";
    }
    if ($primary->getType() != 'increments') {
      $content .= "  /**\n";
      $content .= "   * Primary key incrementing.\n";
      $content .= "   *\n";
      $content .= "   * @var boolean\n";
      $content .= "   */\n";
      $content .= "  public \$incrementing = false;\n";
      $content .= "  \n";
    }

    $content .= "  /**\n";
    $content .= "   * Field data array.\n";
    $content .= "   *\n";
    $content .= "   * @var array\n";
    $content .= "   */\n";

    if (isset($fieldsData) && count($fieldsData) > 0) {
      $content .= "  public \$fieldsData = " . var_export($fieldsData, true) . ";\n";
    } else {
      $content .= "  public \$fieldsData = [];\n";
    }
    $content .= "  \n";

    $content .= "  /**\n";
    $content .= "   * Fetchable array.\n";
    $content .= "   *\n";
    $content .= "   * @var array\n";
    $content .= "   */\n";
    if (isset($api['fetchable'])) {
      $content .= "  public \$fetchable = " . var_export($api['fetchable'], true) . ";\n";
    } else {
      $content .= "  public \$fetchable = true;\n";
    }
    $content .= "  \n";

    $content .= "  public static function staticGetFieldsData() {\n";
    $content .= "    \$entity = new self();\n";
    $content .= "    return \$entity->fieldsData;\n";
    $content .= "  }\n";
    $content .= "  \n";
    $content .= "  public static function staticGetFillable() {\n";
    $content .= "    \$entity = new self();\n";
    $content .= "    return \$entity->fillable;\n";
    $content .= "  }\n";
    $content .= "  \n";
    $content .= "  \n";
    $content .= "}\n";
    $content .= "\n";

    return $content;
  }


}


