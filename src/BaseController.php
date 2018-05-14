<?php

namespace Laravel\Api\Builder;

class BaseController extends \App\Http\Controllers\Controller
{

  protected function response($array, $statusCode = 200) {
    return response($array, $statusCode)
      ->header('Content-Type', 'application/json')
      ->header('Access-Control-Allow-Origin', $this->allowedOrigin)
      ->header('Access-Control-Allow-Methods', $this->allowedMethods);
  }

  public static function getFilteredAll($collection) {
    $result = [];

    foreach ($collection as $entity) {
      $result[] = $entity->getFiltered();
    }

    return $result;
  }
    
}