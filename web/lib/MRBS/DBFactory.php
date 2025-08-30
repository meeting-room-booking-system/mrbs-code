<?php
declare(strict_types=1);
namespace MRBS;


// A helper class to build a DB object, dependent on the database type required
use Throwable;

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
    self::checkExtensionEnabled($db_system);
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


  // Check that the appropriate PDO extension is enabled.  This can't always be
  // done in the constructor of the class itself because the class can refer to a
  // driver-specific constant.
  private static function checkExtensionEnabled(string $db_system) : void
  {
    // Check for the existence of a driver-specific constant
    switch ($db_system)
    {
      case 'mysql':
      case 'mysqli':
        $constant_name = 'Pdo\Mysql::ATTR_FOUND_ROWS';
        $extension = 'pdo_mysql';
        break;

      case 'pgsql':
        $constant_name = 'Pdo\Pgsql::ATTR_DISABLE_PREPARES';
        $extension = 'pdo_pgsql';
        break;

      default:
        return;
    }

    // We have to test for the constant in a try/catch block, because if we are using the Pdo\Mysql or
    // Pdo\Pgsql emulations (ie we are not running PHP 8.4 or later) then the emulations will throw
    // an error.
    try
    {
      if (!defined($constant_name))
      {
        throw new Exception("Undefined constant $constant_name.");
      }
    }
    catch (Throwable $e)
    {
      $message = "Undefined constant $constant_name.  Check that the $extension extension is enabled " .
        "in your php.ini file.";
      throw new Exception($message);
    }

  }

  private static function getClassName(string $db_system) : string
  {
    switch ($db_system)
    {
      case 'mysql':
      case 'mysqli':
        $class_name = 'DB_mysql';
        break;

      case 'pgsql':
        $class_name = 'DB_pgsql';
        break;

      default:
        throw new Exception("Unsupported database driver '$db_system'");
        break;
    }

    return __NAMESPACE__ . '\\' . $class_name;
  }

}
