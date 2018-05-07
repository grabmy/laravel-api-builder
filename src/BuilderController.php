<?php

namespace Laravel\Api\Builder;

class BuilderController extends \App\Http\Controllers\Controller
{
 
  public static function getFilteredAll($collection) {
    $result = [];

    foreach ($collection as $entity) {
      $result[] = $entity->getFiltered();
    }

    return $result;
  }
    
}