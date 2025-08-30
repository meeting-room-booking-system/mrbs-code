<?php
declare(strict_types=1);
namespace Pdo;

use PDO;

// An emulation of the standard PHP class Pdo\Mysql. This emulation will only be loaded by
// the autoloader if the standard class doesn't exist.  It wasn't introduced until PHP 8.4,
// but in PHP 8.5 the PDO::MYSQL_*constants were deprecated.
class Mysql
{
  public const ATTR_FOUND_ROWS =  PDO::MYSQL_ATTR_FOUND_ROWS;
  public const ATTR_SSL_CA = PDO::MYSQL_ATTR_SSL_CA;
  public const ATTR_SSL_CAPATH = PDO::MYSQL_ATTR_SSL_CAPATH;
  public const ATTR_SSL_CERT = PDO::MYSQL_ATTR_SSL_CERT;
  public const ATTR_SSL_CIPHER = PDO::MYSQL_ATTR_SSL_CIPHER;
  public const ATTR_SSL_KEY = PDO::MYSQL_ATTR_SSL_KEY;
  public const ATTR_SSL_VERIFY_SERVER_CERT = PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT;
}
