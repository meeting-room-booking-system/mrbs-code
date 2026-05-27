<?php
declare(strict_types=1);
namespace MRBS\DB;

use PDO;
use Throwable;

/**
 * A helper class to build a DB object, dependent on the database type required
 */
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
    array $db_options=[]) : DB
  {
    // Check that the appropriate PDO extension is enabled
    if (!in_array(self::getDriverName($db_system), PDO::getAvailableDrivers()))
    {
      $driver_name = self::getDriverName($db_system);
      $message = "A PDO driver for $driver_name is not available on this server.  Check that the pdo_$driver_name " .
        "extension is enabled in your php.ini file.";
      throw new DBException($message);
    }
    $class_name = self::getClassName($db_system);
    return new $class_name($db_host, $db_username, $db_password, $db_name, $persist, $db_port, $db_options);
  }


  public static function createDsn(
    string $db_system,
    string $db_host,
    #[\SensitiveParameter]
    string $db_name,
    ?int   $db_port = null
  ) : string
  {
    $class_name = self::getClassName($db_system);
    return $class_name::dsn($db_host, $db_name, $db_port);
  }


  /**
   * Create a driver name from a database system name. 'mysqli' is supported for backwards compatibility
   * and is converted to 'mysql'.
   */
  private static function getDriverName(string $db_system) : string
  {
    switch ($db_system)
    {
      case 'mysql':
      case 'mysqli':
        $driver_name = 'mysql';
        break;

      case 'pgsql':
        $driver_name = 'pgsql';
        break;

      default:
        throw new DBException("Unsupported database driver '$db_system'");
        break;
    }

    return $driver_name;
  }


  /**
   * Get the class name given a database system name.  'mysqli' is supported for backwards compatibility
   * and is converted to 'mysql'.
   */
  private static function getClassName(string $db_system) : string
  {
    $driver_name = self::getDriverName($db_system);
    return __NAMESPACE__ . '\\' . "DB_$driver_name";
  }

}
