<?php
declare(strict_types=1);
namespace MRBS\DB;

use Error;
use PDO;
use Pdo\Mysql;
use PDOException;

//
class DB_mysql extends DB
{
  const DB_DEFAULT_PORT = 3306;
  const DB_DBO_DRIVER = "mysql";
  const DB_CHARSET = "utf8mb4";

  const DB_MARIADB = 0;
  const DB_MYSQL   = 1;
  const DB_PERCONA = 2;
  const DB_OTHER   = 3;

  // For a full list of error codes see https://mariadb.com/kb/en/mariadb-error-codes/
  // (That page doesn't list codes only used by MySQL)
  const ER_CON_COUNT_ERROR            = 1040; // Too many connections
  const ER_TOO_MANY_USER_CONNECTIONS  = 1203; // User %s already has more than 'max_user_connections' active connections
  const ER_USER_LIMIT_REACHED         = 1226; // User '%s' has exceeded the '%s' resource (current value: %ld)

  private const OPTIONS = [
    Mysql::ATTR_FOUND_ROWS => true  // Return the number of found (matched) rows, not the number of changed rows.
  ];

  private const MIN_VERSIONS = array(
      self::DB_MARIADB => '5.5.3', // '10.0.2' recommended for multiple lock support
      self::DB_MYSQL   => '5.5.3', // '5.7.5' recommended for multiple lock support
      self::DB_PERCONA => '5.5.3'  // '5.7.5' recommended for multiple lock support
    );

  private const DB_NAMES = array(
    self::DB_MARIADB => 'MariaDB',
    self::DB_MYSQL   => 'MySQL',
    self::DB_PERCONA => 'Percona'
  );

  private $db_type = null;
  private $returns_native_types = null;
  private $supports_multiple_locks = null;
  private $version_comment = null;

  // The SensitiveParameter attribute needs to be on a separate line for PHP 7.
  // The attribute is only recognised by PHP 8.2 and later.
  public function __construct(
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
    global $db_retries, $db_delay;

    $driver_options =  self::siteOptions() + self::OPTIONS;

    // We allow retries if the connection fails due to a resource constraint, possibly because
    // this database user already has max_user_connections open (through other instances of users
    // accessing MRBS) or other database users on the same server have reached the maximum number of
    // connections for the database.
    $attempts_left = max(1, $db_retries + 1);

    while ($attempts_left > 0)
    {
      try
      {
        $this->connect(
            $db_host,
            $db_username,
            $db_password,
            $db_name,
            $persist,
            $db_port,
            $driver_options
          );
        // Set $attempts_left to zero as we won't have got here if an exception has been thrown
        $attempts_left = 0;
        $this->checkVersion();
        // Turn off ONLY_FULL_GROUP_BY mode (which is the default in MySQL 5.7.5 and later) to prevent SQL
        // errors of the type "Syntax error or access violation: 1055 'mrbs.E.start_time' isn't in GROUP BY".
        // TODO: However the proper solution is probably to rewrite the offending queries.
        $this->command("SET sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");
        // Set STRICT_TRANS_TABLES so that we can detect invalid values being inserted in the database
        $this->command("SET SESSION sql_mode = 'STRICT_TRANS_TABLES'");
      }
      catch (PDOException $e)
      {
        $code = $e->getCode();
        $message = $e->getMessage();

        if (in_array($code, array(
                self::ER_CON_COUNT_ERROR,
                self::ER_TOO_MANY_USER_CONNECTIONS,
                self::ER_USER_LIMIT_REACHED
              )))
        {
          $attempts_left--;
        }
        else
        {
          // It's some kind of error other than a resource error, so retrying won't help
          $attempts_left = 0;
          if ($code == 2054) // The server requested authentication method unknown to the client [MySQL specific]
          {
            $message .= ".\n[MRBS note] It looks like you may have an old style MySQL password stored, which cannot be " .
                        "used with PDO (though it is possible that mysqli may have accepted it).  Try " .
                        "deleting the MySQL user and recreating it with the same password.";
          }
        }

        if ($attempts_left > 0)
        {
          trigger_error($message . ". Retrying ...", E_USER_NOTICE);
          usleep($db_delay * 1000);
        }
        else
        {
          throw new DBException($message);
        }
      }
    }
  }


  // Translates $db_options['mysql'] into an array of options indexed by their
  // PDO constants.
  // Note that we cannot declare a constant array to hold this mapping as not all
  // systems support all the PDO constants.
  private static function siteOptions() : array
  {
    global $db_options;

    $result = array();

    foreach ($db_options['mysql'] as $key => $value)
    {
      // Only try and set the option if we need to. Otherwise, we could trigger an
      // 'undefined class constant' error unnecessarily.
      if (isset($value))
      {
        try
        {
          switch ($key)
          {
            case 'ssl_ca':
              $index = Mysql::ATTR_SSL_CA;
              break;
            case 'ssl_capath':
              $index = Mysql::ATTR_SSL_CAPATH;
              break;
            case 'ssl_cert':
              $index = Mysql::ATTR_SSL_CERT;
              break;
            case 'ssl_cipher':
              $index = Mysql::ATTR_SSL_CIPHER;
              break;
            case 'ssl_key':
              $index = Mysql::ATTR_SSL_KEY;
              break;
            case 'ssl_verify_server_cert':
              $index = Mysql::ATTR_SSL_VERIFY_SERVER_CERT;
              break;
            default:
              $index = null;
              trigger_error("Unsupported option '$key'");
              break;
          }
          if (isset($index))
          {
            $result[$index] = $value;
          }
        }
        catch (Error $e)
        {
          $message = $e->getMessage() . ". Try using the 'nd_pdo_mysql' extension instead of 'pdo_mysql'.";
          trigger_error($message, E_USER_WARNING);
          fatal_error(get_vocab("fatal_error"));
        }
      }
    }

    return $result;
  }


  // Quote a table or column name (which could be a qualified identifier, eg 'table.column')
  public function quote(string $identifier) : string
  {
    $quote_char = '`';
    $parts = explode('.', $identifier);
    return $quote_char . implode($quote_char . '.' . $quote_char, $parts) . $quote_char;
  }


  // Return the value of an autoincrement field from the last insert.
  // Must be called right after an insert on that table!
  //
  // For MySQL we don't need to refer to the passed $table or $field
  public function insert_id(string $table, string $field): int
  {
    return (int)$this->dbh->lastInsertId();
  }


  // Checks the attribute PDO::ATTR_STRINGIFY_FETCHES
  private function getStringifyFetches() : bool
  {
    // Not all drivers support PDO::ATTR_STRINGIFY_FETCHES
    try {
      return $this->getAttribute(PDO::ATTR_STRINGIFY_FETCHES);
    }
    catch (PDOException $e) {
      return false;
    }
  }


  // Determines whether the driver returns native types (eg a PHP int
  // for an SQL INT).
  public function returnsNativeTypes() : bool
  {
    if (!isset($this->returns_native_types))
    {
      // MySQL will return native types if PDO::ATTR_STRINGIFY_FETCHES is false
      // and we're using a native driver and (the PHP version is at least 8.1 or
      // PDO::ATTR_EMULATE_PREPARES is false).
      // See https://stackoverflow.com/questions/1197005/how-to-get-numeric-types-from-mysql-using-pdo
      // and https://stackoverflow.com/questions/20079320/how-do-i-return-integer-and-numeric-columns-from-mysql-as-integers-and-numerics
      $this->returns_native_types =
        !$this->getStringifyFetches()&&
        str_contains($this->getAttribute(PDO::ATTR_CLIENT_VERSION), 'mysqlnd') &&
        ((version_compare(PHP_VERSION, '8.1.0') >= 0) || !$this->getAttribute(PDO::ATTR_EMULATE_PREPARES));
    }

    return $this->returns_native_types;
  }

  // Determines whether the database supports multiple locks.
  // This method should not be called for the first time while
  // locks are in place, because it will release them.
  // WARNING! This method should not be used as RELEASE_ALL_LOCKS
  //  is not supported by MariaDB Galera Cluster.
  public function supportsMultipleLocks() : bool
  {
    if (!isset($this->supports_multiple_locks))
    {
      if (!empty($this->mutex_locks))
      {
        throw new Exception(__METHOD__ . " called when there are locks in place.");
      }

      try
      {
        // We could check version numbers, but then we have to test for different
        // version numbers in MySQL and MariaDB, and possibly others.  It's
        // probably cleaner to check for the capability to RELEASE_ALL_LOCKS(), which
        // was introduced at the same time as support for multiple locks.
        $this->query("SELECT RELEASE_ALL_LOCKS()");
        $this->supports_multiple_locks = true;
      }
      catch (DBException $e)
      {
        $this->supports_multiple_locks = false;
      }
    }

    return $this->supports_multiple_locks;
  }


  // Since MySQL 5.7.5 lock names are restricted to 64 characters.
  // Truncating them is probably sufficient to ensure uniqueness.
  private static function hash(string $name) : string
  {
    return substr($name, 0, 64);
  }


  // Acquire a mutual-exclusion lock.
  // Returns true if the lock is acquired successfully, otherwise false.
  // WARNING! This method should not be used as GET_LOCK is not supported
  // by MariaDB Galera Cluster.
  public function mutex_lock(string $name) : bool
  {
    $timeout = 20;  // seconds

    if (!$this->supportsMultipleLocks() && !empty($this->mutex_locks))
    {
      $message = "Trying to set lock '$name', but lock '" . $this->mutex_locks[0] .
                 "' already exists.  Only one lock is allowed at any one time.";
      trigger_error($message, E_USER_WARNING);
      return false;
    }

    // GET_LOCK returns 1 if the lock was obtained successfully, 0 if the attempt
    // timed out (for example, because another client has previously locked the name),
    // or NULL if an error occurred (such as running out of memory or the thread was
    // killed with mysqladmin kill)
    try
    {
      $sql_params = array(':str' => self::hash($name),
                          ':timeout' => $timeout);
      $stmt = $this->query("SELECT GET_LOCK(:str, :timeout)", $sql_params);
    }
    catch (DBException $e)
    {
      trigger_error($e->getMessage(), E_USER_WARNING);
      return false;
    }

    if (($stmt->count() != 1) ||
        ($stmt->num_fields() != 1))
    {
      trigger_error("Unexpected number of rows and columns in result", E_USER_WARNING);
      return false;
    }

    $result = $stmt->next_row()[0];

    if ($result == '1')
    {
      $this->mutex_locks[] = $name;
      return true;
    }

    // Otherwise there's been some kind of failure to get a lock
    switch ($result)
    {
      case '0':
        $message = "GET_LOCK timed out after $timeout seconds";
        break;
      case null:
        $message = "GET_LOCK: an error occurred (such as running out of memory " .
                   "or the thread was killed with mysqladmin kill)";
        break;
      default:
        $message = "GET_LOCK: unexpected result '$result'";
        break;
    }

    trigger_error($message, E_USER_WARNING);
    return false;
  }


  // Release a mutual-exclusion lock.
  // Returns true if the lock is released successfully, otherwise false.
  // WARNING! This method should not be used as RELEASE_LOCK
  // is not supported by MariaDB Galera Cluster.
  public function mutex_unlock(string $name) : bool
  {
    // First do some sanity checking before executing the SQL query
    if (!in_array($name, $this->mutex_locks))
    {
      trigger_error("Trying to release a lock ('$name') which hasn't been set", E_USER_WARNING);
      return false;
    }

    // If this request looks OK, then execute the SQL query
    try
    {
      $stmt = $this->query("SELECT RELEASE_LOCK(?)", array(self::hash($name)));
    }
    catch (DBException $e)
    {
      trigger_error($e->getMessage(), E_USER_WARNING);
      return false;
    }

    if (($stmt->count() != 1) ||
        ($stmt->num_fields() != 1))
    {
      trigger_error("Unexpected number of rows and columns in result", E_USER_WARNING);
      return false;
    }

    $result = $stmt->next_row()[0];

    if ($result == '1')
    {
      if (($key = array_search($name, $this->mutex_locks)) !== false)
      {
        unset($this->mutex_locks[$key]);
      }
      return true;
    }

    // Otherwise there's been some kind of failure to release a lock.  These should in theory
    // have been caught by the sanity checking above, but just in case ...
    switch ($result)
    {
      case '0':
        $message = "RELEASE_LOCK: the lock '$name' was not established by this thread and so could not be released";
        break;
      case null:
        $message = "RELEASE_LOCK: the lock '$name' does not exist";
        break;
      default:
        $message = "RELEASE_LOCK: unexpected result '$result'";
        break;
    }

    trigger_error($message, E_USER_WARNING);
    return false;
  }


  // Release all mutual-exclusion locks.
  // WARNING! This method should not be used as RELEASE_ALL_LOCKS
  // is not supported by MariaDB Galera Cluster.
  public function mutex_unlock_all() : void
  {
    if ($this->supportsMultipleLocks())
    {
      $this->query("SELECT RELEASE_ALL_LOCKS()");
    }
    else
    {
      foreach ($this->mutex_locks as $lock)
      {
        $this->mutex_unlock($lock);
      }
    }
  }


  private function dbType() : ?int
  {
    global $debug;

    if (!isset($this->db_type))
    {
      if ((false !== mb_stripos($this->versionComment(), 'maria')) || (false !== mb_stripos($this->version(), 'maria')))
      {
        $this->db_type = self::DB_MARIADB;
      }
      elseif ((false !== mb_stripos($this->versionComment(), 'mysql')) || (false !== mb_stripos($this->version(), 'mysql')))
      {
        $this->db_type = self::DB_MYSQL;
      }
      // Most Ubuntu packages will identify the database type - see https://github.com/meeting-room-booking-system/mrbs-code/issues/72.
      // But there are some packages that don't seem to include the database type in any of the version information, for example
      // see SF Bugs #545 (https://sourceforge.net/p/mrbs/bugs/545/).  Let's assume that they are MySQL databases, though this isn't
      // necessarily true as it seems Ubuntu can be packaged with either MySQL or MariaDB - see for example https://launchpad.net/ubuntu.
      // However, if we assume MySQL then the required MySQL version number will be less than or equal to the required MariaDB version
      // number and the initial version check will pass, though the code may fail later on when it tries to use an unsupported feature.
      // TODO: something better. Perhaps we could also look at version numbers and then make some assumptions about whether the database
      // TODO: is MySQL or MariaDB, but that could become dangerous in the future. Or perhaps there's some other way.
      elseif ((false !== mb_stripos($this->versionComment(), 'ubuntu')) || (false !== mb_stripos($this->version(), 'ubuntu')))
      {
        $this->db_type = self::DB_MYSQL;
      }
      elseif ((false !== mb_stripos($this->versionComment(), 'percona')) || (false !== mb_stripos($this->version(), 'percona')))
      {
        $this->db_type = self::DB_PERCONA;
      }
      // The Altervista.org hosting platform will give this version comment
      elseif ($this->versionComment() == 'Source distribution')
      {
        $this->db_type = self::DB_MYSQL;
      }
      else
      {
        if ($debug)
        {
          trigger_error("Unknown database type '" . $this->versionComment() . "'");
        }
        $this->db_type = self::DB_OTHER;
      }
    }

    return $this->db_type;
  }


  // Checks that the database version meets the minimum requirement and dies if not
  private function checkVersion() : void
  {
    $db_version = $this->versionNumber();
    $db_type = $this->dbType();

    if (isset(self::MIN_VERSIONS[$db_type]) &&
        (version_compare($db_version, self::MIN_VERSIONS[$db_type]) < 0))
    {
      $this->versionDie(self::DB_NAMES[$db_type], $db_version, self::MIN_VERSIONS[$db_type]);
    }
    // If it's another type of database we'll have to add some minimum version requirements fot it
  }


  // Returns the version_comment variable, eg "MySQL Community Server - GPL"
  // or "MariaDB Server".
  private function versionComment() : string
  {
    if (!isset($this->version_comment))
    {
      $sql = "SHOW variables LIKE 'version_comment'";
      $res = $this->query($sql);
      $row = $res->next_row_keyed();

      $this->version_comment = ($row === false) ? '' : $row['Value'];
    }

    return $this->version_comment;
  }


  // Returns the database version number as a string
  private function versionNumber() : string
  {
    $result = $this->versionString();

    // Extract the version number
    preg_match('/^\d+(\.\d+)+/', $result, $matches);

    return $matches[0];
  }


  // Return a string identifying the database version and type
  public function version() : string
  {
    return $this->versionComment() . ' DB_mysql.php' . $this->versionString();
  }


  // Check if a table exists
  public function table_exists(string $table) : bool
  {
    $res = $this->query("SHOW TABLES LIKE ?", array($table));

    return ($res->count() > 0);
  }


  // Get information about the columns in a table
  // Returns an array with the following indices for each column
  //
  //  'name'        the column name
  //  'type'        the type as reported by MySQL
  //  'nature'      the type mapped onto one of a generic set of types
  //                (boolean, integer, real, character, binary).   This enables
  //                the nature to be used by MRBS code when deciding how to
  //                display fields, without MRBS having to worry about the
  //                differences between MySQL and PostgreSQL type names.
  //  'length'      the maximum length of the field in bytes, octets or characters
  //                (Note:  this could be NULL)
  //  'is_nullable' whether the column can be set to NULL (boolean)
  //
  //  NOTE: the type mapping is incomplete and just covers the types commonly
  //  used by MRBS
  public function field_info(string $table) : array
  {
    // Map MySQL types on to a set of generic types
    $nature_map = array(
      'bigint'      => 'integer',
      'blob'        => 'binary',
      'char'        => 'character',
      'date'        => 'timestamp',
      'datetime'    => 'timestamp',
      'decimal'     => 'decimal',
      'double'      => 'real',
      'float'       => 'real',
      'int'         => 'integer',
      'longblob'    => 'binary',
      'longtext'    => 'character',
      'mediumblob'  => 'binary',
      'mediumint'   => 'integer',
      'mediumtext'  => 'character',
      'numeric'     => 'decimal',
      'smallint'    => 'integer',
      'text'        => 'character',
      'time'        => 'timestamp',
      'timestamp'   => 'timestamp',
      'tinyblob'    => 'binary',
      'tinyint'     => 'integer',
      'tinytext'    => 'character',
      'varchar'     => 'character',
      'year'        => 'timestamp'
    );

    // Length in bytes of MySQL integer types
    $int_bytes = array(
      'bigint'    => 8, // bytes
      'int'       => 4,
      'mediumint' => 3,
      'smallint'  => 2,
      'tinyint'   => 1
    );

    $stmt = $this->query("SHOW COLUMNS FROM $table", array());

    $fields = array();

    while (false !== ($row = $stmt->next_row_keyed()))
    {
      $name = $row['Field'];
      $type = $row['Type'];
      $default = $row['Default'];
      // Get the type and optionally length in parentheses, ignoring any attributes.  Note that the
      // length could be of the form (6,2) for a decimal.  Examples that we have to cope with:
      //    tinyint
      //    tinyint unsigned
      //    decimal(6,2)
      //    varchar(255)
      //    mediumint(4) unsigned zerofill
      // The type will be in the first group and the length in the optional second group
      preg_match('/(\w+)[\s(]?([\d,]+)?/', $type, $matches);
      $short_type = $matches[1];
      // map the type onto one of the generic natures, if a mapping exists
      $nature = (array_key_exists($short_type, $nature_map)) ? $nature_map[$short_type] : $short_type;
      // now work out the length
      if ($nature == 'integer')
      {
        // Convert the default to an int (unless it's NULL)
        if (isset($default))
        {
          $default = (int) $default;
        }
        // if it's one of the ints, then look up the length in bytes
        $length = (array_key_exists($short_type, $int_bytes)) ? $int_bytes[$short_type] : 0;
      }
      elseif (($nature == 'character') || ($nature == 'decimal'))
      {
        // if it's a character or decimal type then use the length that was in parentheses
        // eg if it was a varchar(25), we want the 25 and if a decimal(6,2) we want the 6,2
        if (isset($matches[2]))
        {
          $length = $matches[2];
        }
        // otherwise it could be any length (eg if it was a 'text')
        else
        {
          $length = defined('PHP_INT_MAX') ? PHP_INT_MAX : 9999;
        }
      }
      else  // we're only dealing with a few simple cases at the moment
      {
        $length = null;
      }
      // Convert the is_nullable field to a boolean
      $is_nullable = (mb_strtolower($row['Null']) == 'yes');

      $fields[] = array(
        'name' => $name,
        'type' => $type,
        'nature' => $nature,
        'length' => $length,
        'is_nullable' => $is_nullable,
        'default' => $default
      );
    }

    return $fields;
  }

  // Syntax methods

  // Generate non-standard SQL for LIMIT clauses:
  public function syntax_limit(int $count, int $offset) : string
  {
   return "LIMIT $offset,$count";
  }


  // Generate non-standard SQL to output a TIMESTAMP as a Unix-time:
  public function syntax_timestamp_to_unix(string $fieldname) : string
  {
    return "UNIX_TIMESTAMP($fieldname)";
  }


  // Returns the syntax for a case-sensitive string "equals" function
  // (By default MySQL is case-insensitive, so we force a binary comparison)
  //
  // Also takes a required pass-by-reference parameter to modify the SQL
  // parameters appropriately.
  //
  // NB:  This function is also assumed to do a strict comparison, ie
  // take account of trailing spaces.  (The '=' comparison in MySQL allows
  // trailing spaces, eg 'john' = 'john ').
  public function syntax_casesensitive_equals(string $fieldname, string $string, array &$params) : string
  {
    $params[] = $string;

    // We cannot assume that the database column has utf8 collation.  We may for example be
    // authenticating a user against an external database.  See the post at
    // https://stackoverflow.com/questions/5629111/how-can-i-make-sql-case-sensitive-string-comparison-on-mysql#answer-56283818
    // for an explanation of the query.
    return $this->quote($fieldname) . "=CONVERT(? using utf8mb4) COLLATE utf8mb4_bin";
  }

  // Generate non-standard SQL to match a string anywhere in a field's value
  // in a case-insensitive manner. $s is the un-escaped/un-slashed string.
  //
  // Also takes a required pass-by-reference parameter to modify the SQL
  // parameters appropriately.
  //
  // In MySQL, REGEXP seems to be case-sensitive, so use LIKE instead. But this
  // requires quoting of % and _ in addition to the usual.
  public function syntax_caseless_contains(string $fieldname, string $string, array &$params) : string
  {
    $string = str_replace("\\", "\\\\", $string);
    $string = str_replace("%", "\\%", $string);
    $string = str_replace("_", "\\_", $string);

    $params[] = "%$string%";

    return "$fieldname LIKE ?";
  }


  // Generate non-standard SQL to add a table column after another specified
  // column
  public function syntax_addcolumn_after(string $fieldname) : string
  {
    return "AFTER $fieldname";
  }


  // Generate non-standard SQL to specify a column as an auto-incrementing
  // integer while doing a CREATE TABLE
  public function syntax_createtable_autoincrementcolumn() : string
  {
    return "int NOT NULL auto_increment";
  }


  // Returns the syntax for a bitwise XOR operator
  public function syntax_bitwise_xor() : string
  {
    return "^";
  }

  // Returns the syntax for a simple split of a column's value into two
  // parts, separated by a delimiter.  $part can be 1 or 2.
  // Also takes a required pass-by-reference parameter to modify the SQL
  // parameters appropriately.
  public function syntax_simple_split(string $fieldname, string $delimiter, int $part, array &$params) : string
  {
    switch ($part)
    {
      case 1:
        $count = 1;
        break;
      case 2:
        $count = -1;
        break;
      default:
        throw new Exception("Invalid value ($part) given for " . '$part.');
        break;
    }

    $params[] = $delimiter;
    return "SUBSTRING_INDEX($fieldname, ?, $count)";
  }


  // Returns the syntax for aggregating a number of rows as a delimited string
  public function syntax_group_array_as_string(string $fieldname, string $delimiter=',') : string
  {
    // Use DISTINCT to eliminate duplicates which can arise when the query
    // has joins on two or more junction tables.  Maybe a different query
    // would eliminate the duplicates and the need for DISTINCT, and it may
    // or may not be more efficient.
    return "GROUP_CONCAT(DISTINCT $fieldname SEPARATOR '$delimiter')";
  }


  // Returns the syntax for an "upsert" query.  Unfortunately getting the id of the
  // last row differs between MySQL and PostgreSQL.   In PostgreSQL the query will
  // return a row with the id in the 'id' column.  However there isn't a corresponding
  // way of doing this in MySQL, but db()->insert_id() will work, regardless of whether
  // an insert or update was performed.
  //
  //  $conflict_keys     the key(s) which is/are unique; can be a scalar or an array
  //                     (ignored in MySQL)
  //  $assignments       an array of assignments for the UPDATE clause
  //  $has_id_column     whether the table has an id column
  public function syntax_on_duplicate_key_update($conflict_keys, array $assignments, bool $has_id_column=false) : string
  {
    if ($has_id_column)
    {
      // In order to make lastInsertId() work even after an UPDATE
      $assignments[] = "id=LAST_INSERT_ID(id)";
    }

    return "ON DUPLICATE KEY UPDATE " . implode(', ', $assignments);
  }

}
