<?php

namespace Laravel\Api\Builder;

use Illuminate\Console\Command;
use Exception;
use Laravel\Api\Builder\Builder;

class MakeApiCommand extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'make:api {file} {table?}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Create the database migration, model, controller files and add the API routes';



  /**
   * Execute the console command.
   *
   * @return mixed
   */
  public function handle() {
    $file = $this->argument('file');

    $builder = new Builder($this);
    $builder->load($file);
    if ($builder->hasError()) {
      $this->error(' > No files generated due to errors');
    } else {
      $builder->createMigration();
      $builder->createModel();
      $builder->createController();
      $builder->createRoute();
    }
  }


    
}


