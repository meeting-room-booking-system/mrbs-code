<?php

namespace MRBS;


// A helper class to build a DB object, dependent on the database type required
class DBFactory
{
  //
  public static function create($db_system, $db_host, $db_username, $db_password, $db_name, $persist = 0, $db_port = null)
  {
    switch ($db_system)
    {
      case 'mysql':
      case 'mysqli':
        $db_system = 'mysql';
        break;
      case 'pgsql':
        break;
      default:
        throw new Exception("Unsupported database driver '$db_system'");
        break;
    }
    $class = "MRBS\DB_${db_system}";
    return new $class($db_host, $db_username, $db_password, $db_name, $persist, $db_port);
  }
}
