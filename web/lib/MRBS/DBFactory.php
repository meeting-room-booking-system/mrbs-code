<?php

namespace MRBS;


// A helper class to build a DB object, dependent on the database type required
class DBFactory
{
  // The SensitiveParameter attribute needs to be on a separate line for PHP 7.
  // The attribute is only recognised by PHP 8.2 and later.
  public static function create(
    $db_system,
    $db_host,
    #[SensitiveParameter]
    $db_username,
    #[SensitiveParameter]
    $db_password,
    #[SensitiveParameter]
    $db_name,
    $persist=false,
    $db_port=null)
  {
    switch ($db_system)
    {
      case 'mysql':
      case 'mysqli':
        return new DB_mysql($db_host, $db_username, $db_password, $db_name, $persist, $db_port);
        break;
      case 'pgsql':
        return new DB_pgsql($db_host, $db_username, $db_password, $db_name, $persist, $db_port);
        break;
      default:
        throw new Exception("Unsupported database driver '$db_system'");
        break;
    }
  }
}
