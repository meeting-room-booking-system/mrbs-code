<?php
declare(strict_types=1);
namespace MRBS;

use PDOException;

class DBException extends PDOException
{

  public function __construct(string $message, int $code=0, PDOException $previous=null, string $sql=null, array $params=null)
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
