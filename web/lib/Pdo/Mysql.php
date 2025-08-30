<?php
declare(strict_types=1);
namespace Pdo;

use PDO;

// An emulation of the standard PHP class Pdo\Mysql. This emulation will only be loaded by
// the autoloader if the standard class doesn't exist.  It wasn't introduced until PHP 8.4,
// but in PHP 8.5 the PDO::MYSQL_*constants were deprecated.
class Mysql
{
  public const ATTR_USE_BUFFERED_QUERY = PDO::MYSQL_ATTR_USE_BUFFERED_QUERY;
  public const ATTR_LOCAL_INFILE = PDO::MYSQL_ATTR_LOCAL_INFILE;
  // public const ATTR_LOCAL_INFILE_DIRECTORY = PDO::MYSQL_ATTR_LOCAL_INFILE_DIRECTORY; // Available as of PHP 8.1.0
  public const ATTR_INIT_COMMAND = PDO::MYSQL_ATTR_INIT_COMMAND;
  public const ATTR_READ_DEFAULT_FILE = PDO::MYSQL_ATTR_READ_DEFAULT_FILE;
  public const ATTR_READ_DEFAULT_GROUP = PDO::MYSQL_ATTR_READ_DEFAULT_GROUP;
  public const ATTR_MAX_BUFFER_SIZE = PDO::MYSQL_ATTR_MAX_BUFFER_SIZE;
  public const ATTR_FOUND_ROWS = PDO::MYSQL_ATTR_FOUND_ROWS;
  public const ATTR_IGNORE_SPACE = PDO::MYSQL_ATTR_IGNORE_SPACE;
  public const ATTR_COMPRESS = PDO::MYSQL_ATTR_COMPRESS;
  public const ATTR_SERVER_PUBLIC_KEY = PDO::MYSQL_ATTR_SERVER_PUBLIC_KEY;
  public const ATTR_SSL_CA = PDO::MYSQL_ATTR_SSL_CA;
  public const ATTR_SSL_CAPATH = PDO::MYSQL_ATTR_SSL_CAPATH;
  public const ATTR_SSL_CERT = PDO::MYSQL_ATTR_SSL_CERT;
  public const ATTR_SSL_CIPHER = PDO::MYSQL_ATTR_SSL_CIPHER;
  public const ATTR_SSL_KEY = PDO::MYSQL_ATTR_SSL_KEY;
  public const ATTR_SSL_VERIFY_SERVER_CERT = PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT;  // Available as of PHP 7.0.18 and PHP 7.1.4
  public const ATTR_MULTI_STATEMENTS = PDO::MYSQL_ATTR_MULTI_STATEMENTS;
}
