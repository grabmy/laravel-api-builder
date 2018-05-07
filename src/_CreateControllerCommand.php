<?php

namespace Laravel\Api\Builder;

use Illuminate\Console\Command;
use Exception;
use Laravel\Api\Builder\Builder;

class CreateControllerCommand extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'api:controller {file} {table?}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Create the controller files';



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
      $this->error(' > No file generated due to errors');
    } else {
      $builder->createController();
    }
  }


    
}


