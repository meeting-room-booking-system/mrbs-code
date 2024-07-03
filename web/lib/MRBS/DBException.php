<?php
declare(strict_types=1);
namespace MRBS;

class DBException extends \PDOException
{

  public function __construct($message, $code=0, \PDOException $previous=null, $sql=null, $params=null)
  {
    if (isset($sql))
    {
      $message .= "\n" .
        'SQL: ' . str_replace("\n", '', $sql) . "\n" .
        'Params: ' . print_r($params, true);
    }

    parent::__construct($message, $code, $previous);
  }

}
