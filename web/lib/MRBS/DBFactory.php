<?php

namespace MRBS;


// A helper class to build a DB object, dependent on the database type required
class DBFactory
{
  // The SensitiveParameter attribute needs to be on a separate line for PHP 7.
  // The attribute is only recognised by PHP 8.2 and later.
  public static function create(
    string $db_system,
    string $db_host,
    #[\SensitiveParameter]
    string $db_username,
    #[\SensitiveParameter]
    string $db_password,
    #[\SensitiveParameter]
    string $db_name,
    bool $persist=false,
    ?int $db_port=null,
    array $db_options=[])
  {
    switch ($db_system)
    {
      case 'mysql':
      case 'mysqli':
        try
        {
          return new DB_mysql($db_host, $db_username, $db_password, $db_name, $persist, $db_port, $db_options);
        }
        catch (\Throwable $e)
        {
          $message = $e->getMessage();
          if ($e->getMessage() == "Undefined constant PDO::MYSQL_ATTR_FOUND_ROWS")
          {
            $message .= ".  Check that the PDO MySQL driver is enabled in your php.ini file.";
          }
          throw new Exception($message, $e->getCode());
        }
        break;
      case 'pgsql':
        return new DB_pgsql($db_host, $db_username, $db_password, $db_name, $persist, $db_port, $db_options);
        break;
      default:
        throw new Exception("Unsupported database driver '$db_system'");
        break;
    }
  }
}
