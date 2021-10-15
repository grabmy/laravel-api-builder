<?php 

namespace Laravel\Api\Builder;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
  /**
   * Bootstrap the application events.
   *
   * @return void
   */
  public function boot() {
    $this->commands([
      MakeApiCommand::class,
      ApiMigrationCommand::class,
    ]);
  }

}