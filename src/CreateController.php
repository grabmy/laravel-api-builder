<?php

namespace Laravel\Api\Builder;

class CreateController extends BaseBuilder
{

  private $tables;

  private static $baseDir = './app/Http/Controllers/';

  private static $extends = '\\Laravel\\Api\\Builder\\BaseController';

  private static $namespace = 'App\\Http\\Controllers';

  public function __construct($command, $tables) {
    parent::__construct($command);
    
    $this->tables = $tables;
  }

  private static function getControllerFilename($table) {
    return self::$baseDir.$table->getPhpName().'Controller.php';
  }


  public function create() {
    $this->log('comment', '--------------------------------------------', 'v');
    $this->log('comment', 'Creating controller files', 'v');
    $count_file_created = 0;

    foreach ($this->tables as $tableName => $table) {
      $fields = $table->getFields();
      $file = self::getControllerFilename($table);
      $content = $this->getControllerContent($table);
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

  private function getControllerContent($table) {
    $tableName = $table->getName();
    $phpName = $table->getPhpName();
    $api = $table->getApi();
    $modelNamespace = CreateModel::$namespace;

    $content = "<?php\n";
    $content .= "/**\n";
    $content .= " * Warning: this file is generated by Laravel API Builder.\n";
    $content .= " * If you modify it and run the generation again, your modification will be overwritten.\n";
    $content .= " */\n";
    $content .= "\n";
    $content .= "namespace ".self::$namespace.";\n";
    $content .= "\n";
    $content .= "use Illuminate\\Http\\Request;\n";
    $content .= "use ".$modelNamespace."\\" . $phpName . ";\n";
    $content .= "\n";
    $content .= "\n";
    $content .= "class " . $phpName . "Controller extends ".self::$extends."\n";
    $content .= "{\n";
    $content .= "  \n";

    $content .= "  /**\n";
    $content .= "   * Domains allowed for the API, \"*\" for everything.\n";
    $content .= "   *\n";
    $content .= "   * @var string\n";
    $content .= "   */\n";
    $content .= "  public \$allowedOrigin = \"*\";\n";
    $content .= "  \n";

    $content .= "  /**\n";
    $content .= "   * Methods allowed for the API, \"*\" for everything.\n";
    $content .= "   *\n";
    $content .= "   * @var string\n";
    $content .= "   */\n";
    $content .= "  public \$allowedMethods = \"*\";\n";
    $content .= "  \n";

    $content .= "  /**\n";
    $content .= "   * Should fetch linked and listed entities in the body result of POST store.\n";
    $content .= "   *\n";
    $content .= "   * @var boolean\n";
    $content .= "   */\n";

    $fetchOnStore = 'false';
    if (isset($api['fetchOnStore']) && $api['fetchOnStore'] == true) {
      $fetchOnStore = 'true';
    }
    $content .= "  public \$fetchOnStore = $fetchOnStore;\n";

    $content .= "  \n";
    $content .= "  /**\n";
    $content .= "   * Should fetch linked and listed entities in the body result of PUT update.\n";
    $content .= "   *\n";
    $content .= "   * @var boolean\n";
    $content .= "   */\n";

    $fetchOnUpdate = 'false';
    if (isset($api['fetchOnUpdate']) && $api['fetchOnUpdate'] == true) {
      $fetchOnUpdate = 'true';
    }
    $content .= "  public \$fetchOnUpdate = $fetchOnUpdate;\n";

    $content .= "  \n";
    $content .= "  \n";
    $content .= "  /**\n";
    $content .= "   * Get the complete list.\n";
    $content .= "   */\n";
    $content .= "  public function index()\n";
    $content .= "  {\n";
    $content .= "    return \$this->response(\$this->getFilteredAll(" . $phpName . "::all()));\n";
    $content .= "  }\n";
    $content .= "  \n";
    $content .= "  /**\n";
    $content .= "   * Get one item.\n";
    $content .= "   */\n";
    $content .= "  public function show(\$id)\n";
    $content .= "  {\n";
    $content .= "    \$entity = " . $phpName . "::find(\$id);\n";
    $content .= "    \n";
    $content .= "    if (\$entity) {\n";
    $content .= "      \$result = \$entity->fetch()->getFiltered();\n";
    if ($table->hasHook()) {
      $content .= "      \$result = ".$table->getHook()."('get', \$id, \$result);\n";
    }
    $content .= "      return \$this->response(\$result, 200);\n";
    $content .= "    } else {\n";
    $content .= "      return \$this->response(null, 404);\n";
    $content .= "    }\n";
    $content .= "  }\n";
    $content .= "  \n";
    $content .= "  /**\n";
    $content .= "   * Create one item.\n";
    $content .= "   */\n";
    $content .= "  public function store(Request \$request)\n";
    $content .= "  {\n";
    $content .= "    // Get the inputs\n";
    $content .= "    \$inputs = \$request->all();\n";
    $content .= "    \n";
    $content .= "    // Check for errors\n";
    $content .= "    \$errors = " . $phpName . "::validateFields(\$inputs);\n";
    $content .= "    \n";
    $content .= "    if (count(\$errors) > 0) {\n";
    $content .= "      return \$this->response(\$errors, 400);\n";
    $content .= "    }\n";
    $content .= "    \n";
    $content .= "    try {\n";
    $content .= "      // Create the entity\n";
    $content .= "      \$entity = " . $phpName . "::create(\$inputs);\n";
    $content .= "    } catch (\\Illuminate\\Database\\QueryException \$e) {\n";
    $content .= "      // Return the SQL error\n";
    $content .= "      if (config('app.APP_DEBUG') === false) {\n";
    $content .= "        return \$this->response(['type' => 'error-sql-query'], 500);\n";
    $content .= "      } else {\n";
    $content .= "        return \$this->response(['type' => 'error-sql-query', 'message' => \$e->getMessage()], 500);\n";
    $content .= "      }\n";
    $content .= "    }\n";
    $content .= "    \n";
    $content .= "    if (\$this->fetchOnStore) {\n";
    $content .= "      \$entity = \$entity->fetch()->getFiltered();\n";
    $content .= "    }\n";
    if ($table->hasHook()) {
      $content .= "    \$entity = ".$table->getHook()."('create', \$inputs, \$entity);\n";
    }
    $content .= "    \n";
    $content .= "    // Success\n";
    $content .= "    return \$this->response(\$entity, 201);\n";
    $content .= "  }\n";
    $content .= "  \n";
    $content .= "  /**\n";
    $content .= "   * Update one item.\n";
    $content .= "   */\n";
    $content .= "  public function update(Request \$request, \$id)\n";
    $content .= "  {\n";
    $content .= "    // Get the inputs\n";
    $content .= "    \$inputs = \$request->all();\n";
    $content .= "    \n";
    $content .= "    // Check for errors\n";
    $content .= "    \$errors = " . $phpName . "::validateFields(\$inputs, true);\n";
    $content .= "    if (count(\$errors) > 0) {\n";
    $content .= "      return \$this->response(\$errors, 400);\n";
    $content .= "    }\n";
    $content .= "    \n";
    $content .= "    // Get the entity\n";
    $content .= "    \$entity = " . $phpName . "::find(\$id);\n";
    $content .= "    \n";
    $content .= "    if (\$entity && \$entity->update(\$inputs)) {\n";
    $content .= "      if (\$this->fetchOnUpdate) {\n";
    $content .= "        \$result = \$entity->fetch()->getFiltered();\n";
    $content .= "      } else {\n";
    $content .= "        \$result = \$entity->getFiltered();\n";
    $content .= "      }\n";
    $content .= "      \n";
    if ($table->hasHook()) {
      $content .= "      \$result = ".$table->getHook()."('update', \$inputs, \$result);\n";
    }
    $content .= "      // Return success\n";
    $content .= "      return \$this->response(\$result, 200);\n";
    $content .= "    } else {\n";
    $content .= "      // Entity not found\n";
    $content .= "      return \$this->response(null, 404);\n";
    $content .= "    }\n";
    $content .= "  }\n";
    $content .= "  \n";
    $content .= "  /**\n";
    $content .= "   * Delete one item.\n";
    $content .= "   */\n";
    $content .= "  public function delete(\$id)\n";
    $content .= "  {\n";
    $content .= "    // Get the entity\n";
    $content .= "    \$entity = " . $phpName . "::find(\$id);\n";
    $content .= "    \n";
    $content .= "    if (\$entity && \$entity->delete()) {\n";
    if ($table->hasHook()) {
      $content .= "      \$entity = ".$table->getHook()."('delete', \$id, \$entity);\n";
    }
    $content .= "      // Return success\n";
    $content .= "      return \$this->response(null, 204);\n";
    $content .= "    } else {\n";
    $content .= "      // Entity not found\n";
    $content .= "      return \$this->response(null, 404);\n";
    $content .= "    }\n";
    $content .= "  }\n";
    $content .= "  \n";
    $content .= "}\n";
    $content .= "\n";

    return $content;
  }


}


