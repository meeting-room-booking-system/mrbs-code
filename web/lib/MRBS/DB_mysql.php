<?php

namespace MRBS;

use PDO;
use PDOException;

//
class DB_mysql extends DB
{
  const DB_DEFAULT_PORT = 3306;
  const DB_DBO_DRIVER = "mysql";
  const DB_CHARSET = "utf8mb4";


  // Quote a table or column name (which could be a qualified identifier, eg 'table.column')
  public function quote($identifier)
  {
    $quote_char = '`';
    $parts = explode('.', $identifier);
    return $quote_char . implode($quote_char . '.' . $quote_char, $parts) . $quote_char;
  }


  // Return the value of an autoincrement field from the last insert.
  // Must be called right after an insert on that table!
  //
  // For MySQL we don't need to refer to the passed $table or $field
  public function insert_id($table, $field)
  {
    return $this->dbh->lastInsertId();
  }


  // Acquire a mutual-exclusion lock on the named table. For portability:
  // This will not lock out SELECTs.
  // It may lock out DELETE/UPDATE/INSERT or not, depending on the implementation.
  // It will lock out other callers of this routine with the same name argument.
  // It will timeout in 20 seconds and return false.
  // It returns true when the lock has been acquired.
  // Caller must release the lock with mutex_unlock().
  // Caller must not have more than one mutex at any time.
  // Do not mix this with begin()/end() calls.
  //
  // In MySQL, we avoid table locks, and use low-level locks instead.
  //
  // Note that MySQL 5.7.5 allows multiple locks, but we only allow one in case the
  // MySQL version is earlier than 5.7.5.
  public function mutex_lock($name)
  {
    $timeout = 20;  // seconds

    if (isset($this->mutex_lock_name))
    {
      $message = "Trying to set lock '$name', but lock '" . $this->mutex_lock_name .
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
      $sql_params = array(':str' => $name,
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
      $this->mutex_lock_name = $name;
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


  // Release a mutual-exclusion lock on the named table.
  // Returns true if the lock is released successfully, otherwise false
  public function mutex_unlock($name)
  {
    // First do some sanity checking before executing the SQL query
    if (!isset($this->mutex_lock_name))
    {
      trigger_error("Trying to release a lock ('$name') which hasn't been set", E_USER_WARNING);
      return false;
    }

    if ($this->mutex_lock_name != $name)
    {
      $message = "Trying to release lock '$name' when the lock that has been set is '" .
                 $this->mutex_lock_name . "'";
      trigger_error($message, E_USER_WARNING);
      return false;
    }

    // If this request looks OK, then execute the SQL query
    try
    {
      $stmt = $this->query("SELECT RELEASE_LOCK(?)", array($name));
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
      $this->mutex_lock_name = null;
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


  // Destructor cleans up the connection
  function __destruct()
  {
    //print "MySQL destructor called\n";
     // Release any forgotten locks
    if (isset($this->mutex_lock_name))
    {
      $this->mutex_unlock($this->mutex_lock_name);
    }

    // Rollback any outstanding transactions
    $this->rollback();
  }


  // Return a string identifying the database version
  public function version()
  {
    return "MySQL ".$this->query1("SELECT VERSION()");
  }

  // Check if a table exists
  public function table_exists($table)
  {
    $res = $this->query1("SHOW TABLES LIKE ?", array($table));

    return ($res == -1) ? false : true;
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
  public function field_info($table)
  {
    // Map MySQL types on to a set of generic types
    $nature_map = array(
        'bigint'      => 'integer',
        'char'        => 'character',
        'decimal'     => 'decimal',
        'double'      => 'real',
        'float'       => 'real',
        'int'         => 'integer',
        'longtext'    => 'character',
        'mediumint'   => 'integer',
        'mediumtext'  => 'character',
        'numeric'     => 'decimal',
        'smallint'    => 'integer',
        'text'        => 'character',
        'tinyint'     => 'integer',
        'tinytext'    => 'character',
        'varchar'     => 'character'
      );

    // Length in bytes of MySQL integer types
    $int_bytes = array('bigint'    => 8, // bytes
                       'int'       => 4,
                       'mediumint' => 3,
                       'smallint'  => 2,
                       'tinyint'   => 1);

    $stmt = $this->query("SHOW COLUMNS FROM $table", array());

    $fields = array();

    while (false !== ($row = $stmt->next_row_keyed()))
    {
      $name = $row['Field'];
      $type = $row['Type'];
      // split the type (eg 'varchar(25)') around the opening '('
      $parts = explode('(', $type);
      // map the type onto one of the generic natures, if a mapping exists
      $nature = (array_key_exists($parts[0], $nature_map)) ? $nature_map[$parts[0]] : $parts[0];
      // now work out the length
      if ($nature == 'integer')
      {
        // if it's one of the ints, then look up the length in bytes
        $length = (array_key_exists($parts[0], $int_bytes)) ? $int_bytes[$parts[0]] : 0;
      }
      elseif (($nature == 'character') || ($nature == 'decimal'))
      {
        // if it's a character or decimal type then use the length that was in parentheses
        // eg if it was a varchar(25), we want the 25 and if a decimal(6,2) we want the 6,2
        if (isset($parts[1]))
        {
          $length = preg_replace('/\)/', '', $parts[1]);  // strip off the closing ')'
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
      $is_nullable = (utf8_strtolower($row['Null']) == 'yes') ? true : false;

      $fields[] = array(
          'name' => $name,
          'type' => $type,
          'nature' => $nature,
          'length' => $length,
          'is_nullable' => $is_nullable
        );
    }

    return $fields;
  }

  // Syntax methods

  // Generate non-standard SQL for LIMIT clauses:
  public function syntax_limit($count, $offset)
  {
   return " LIMIT $offset,$count ";
  }


  // Generate non-standard SQL to output a TIMESTAMP as a Unix-time:
  public function syntax_timestamp_to_unix($fieldname)
  {
    return " UNIX_TIMESTAMP($fieldname) ";
  }


  // Returns the syntax for a case sensitive string "equals" function
  // (By default MySQL is case insensitive, so we force a binary comparison)
  //
  // Also takes a required pass-by-reference parameter to modify the SQL
  // parameters appropriately.
  //
  // NB:  This function is also assumed to do a strict comparison, ie
  // take account of trailing spaces.  (The '=' comparison in MySQL allows
  // trailing spaces, eg 'john' = 'john ').
  public function syntax_casesensitive_equals($fieldname, $string, &$params)
  {
    $params[] = $string;

    // We cannot assume that the database column has utf8 collation.  We may for example be
    // authenticating a user against an external database.  See the post at
    // https://stackoverflow.com/questions/5629111/how-can-i-make-sql-case-sensitive-string-comparison-on-mysql#answer-56283818
    // for an explanation of the query.
    return " " . $this->quote($fieldname) . "=CONVERT(? using utf8mb4) COLLATE utf8mb4_bin";
  }

  // Generate non-standard SQL to match a string anywhere in a field's value
  // in a case insensitive manner. $s is the un-escaped/un-slashed string.
  //
  // Also takes a required pass-by-reference parameter to modify the SQL
  // parameters appropriately.
  //
  // In MySQL, REGEXP seems to be case sensitive, so use LIKE instead. But this
  // requires quoting of % and _ in addition to the usual.
  public function syntax_caseless_contains($fieldname, $string, &$params)
  {
    $string = str_replace("\\", "\\\\", $string);
    $string = str_replace("%", "\\%", $string);
    $string = str_replace("_", "\\_", $string);

    $params[] = "%$string%";

    return " $fieldname LIKE ? ";
  }


  // Generate non-standard SQL to add a table column after another specified
  // column
  public function syntax_addcolumn_after($fieldname)
  {
    return "AFTER $fieldname";
  }


  // Generate non-standard SQL to specify a column as an auto-incrementing
  // integer while doing a CREATE TABLE
  public function syntax_createtable_autoincrementcolumn()
  {
    return "int NOT NULL auto_increment";
  }


  // Returns the syntax for a bitwise XOR operator
  public function syntax_bitwise_xor()
  {
    return "^";
  }

  // Returns the syntax for a simple split of a column's value into two
  // parts, separated by a delimiter.  $part can be 1 or 2.
  // Also takes a required pass-by-reference parameter to modify the SQL
  // parameters appropriately.
  public function syntax_simple_split($fieldname, $delimiter, $part, &$params)
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
}
