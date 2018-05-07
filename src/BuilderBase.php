<?php

namespace Laravel\Api\Builder;

class BuilderBase
{

  protected $command;

  protected $error = false;


  public function __construct($command) {
    $this->command = $command;  
  }


  public function hasError() {
    return $this->error;
  }
  

  protected static function removeBetween($start, $end, $string) {
    $startPos = strpos($string, $start);
    $endPos = strpos($string, $end);
    if ($startPos === false || $endPos === false) {
      return $string;
    }
  
    $textToDelete = substr($string, $startPos, ($endPos + strlen($end)) - $startPos);
  
    return str_replace($textToDelete, '', $string);
  }


  /**
   * Log informations to the console
   *
   * @param [type] $type
   * @param [type] $message
   * @return void
   */
  protected function log($type, $message, $verbosity = null) {
    if (!$this->command) {
      return;
    }
    switch ($type) {
      case 'error':
        $this->command->error(' > '.$message.' ');
        $this->error = true;
        break;
      case 'warn':
      case 'warning':
        $this->command->line(' *** '.$message.' *** ', 'fg=yellow;options=bold', $verbosity);
      break;
      case 'info':
        $this->command->line($message, 'fg=blue', $verbosity);
        break;
      case 'success':
        $this->command->info($message, $verbosity);
        break;
      case 'log':
      case 'comment':
      default:
        $this->command->line($message, 'fg=white', $verbosity);
        break;
    }
  }

}


