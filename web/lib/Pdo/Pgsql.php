<?php
declare(strict_types=1);
namespace Pdo;

use PDO;

// An emulation of the standard PHP class Pdo\Pgsql. This emulation will only be loaded by
// the autoloader if the standard class doesn't exist.  It wasn't introduced until PHP 8.4,
// but in PHP 8.5 the PDO::PGSQL_*constants were deprecated.
class Pgsql
{
  public const ATTR_DISABLE_PREPARES = PDO::PGSQL_ATTR_DISABLE_PREPARES;
}
