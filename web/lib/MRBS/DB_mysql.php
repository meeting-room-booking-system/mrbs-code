<?php

namespace MRBS;

use PDO;
use PDOException;

//
class DB_mysql extends DB
{
  const DB_DEFAULT_PORT = 3306;
  const DB_DBO_DRIVER = "mysql";

  
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

  
  // Begin a transaction, if the database supports it. This is used to
  // improve performance for multiple insert/delete/updates.
  public function begin()
  {
    parent::begin();
    $result = $this->command("START TRANSACTION");
  }

  
  // Acquire a mutual-exclusion lock on the named table. For portability:
  // This will not lock out SELECTs.
  // It may lock out DELETE/UPDATE/INSERT or not, depending on the implementation.
  // It will lock out other callers of this routine with the same name argument.
  // It may timeout in 20 seconds and return 0, or may wait forever.
  // It returns 1 when the lock has been acquired.
  // Caller must release the lock with mutex_unlock().
  // Caller must not have more than one mutex at any time.
  // Do not mix this with begin()/end() calls.
  //
  // In MySQL, we avoid table locks, and use low-level locks instead.
  public function mutex_lock($name)
  {
    // GET_LOCK returns 1 if the lock was obtained successfully, 0 if the attempt
    // timed out (for example, because another client has previously locked the name),
    // or NULL if an error occurred (such as running out of memory or the thread was
    // killed with mysqladmin kill)
    try
    {
      $stmt = $this->query("SELECT GET_LOCK(?, 20)", array($name));
    }
    catch (DBException $e)
    {
      trigger_error($e->getMessage(), E_USER_WARNING);
      return FALSE;
    }

    if (($stmt->count() != 1) || 
        ($stmt->num_fields() != 1) ||
        (($row = $stmt->row(0)) === NULL))
    {
      return FALSE;
    }
    else
    {
      $result = $row[0];
    }
  
    if ($result == 1)
    {
      $this->mutex_lock_name = $name;
    }

    return $result;
  }


  // Release a mutual-exclusion lock on the named table. See mutex_unlock.
  public function mutex_unlock($name)
  {
    // Detect unlocking a mutex which is different from the stored mutex?
    $this->query1("SELECT RELEASE_LOCK(?)", array($name));
    $this->mutex_lock_name = NULL;
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
  
    return ($res == -1) ? FALSE : TRUE;
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
    $nature_map = array('bigint'    => 'integer',
                        'char'      => 'character',
                        'double'    => 'real',
                        'float'     => 'real',
                        'int'       => 'integer',
                        'mediumint' => 'integer',
                        'smallint'  => 'integer',
                        'text'      => 'character',
                        'tinyint'   => 'integer',
                        'tinytext'  => 'character',
                        'varchar'   => 'character');
  
    // Length in bytes of MySQL integer types                                        
    $int_bytes = array('bigint'    => 8, // bytes
                       'int'       => 4,
                       'mediumint' => 3,
                       'smallint'  => 2,
                       'tinyint'   => 1);
  
    $stmt = $this->query("SHOW COLUMNS FROM $table", array());

    $fields = array();
    for ($i = 0; ($row = $stmt->row_keyed($i)); $i++)
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
      elseif ($nature == 'character')
      {
        // if it's a character type then use the length that was in parentheses
        // eg if it was a varchar(25), we want the 25
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
        $length = NULL;
      }
      // Convert the is_nullable field to a boolean
      $is_nullable = (utf8_strtolower($row['Null']) == 'yes') ? TRUE : FALSE;
    
      $fields[$i]['name'] = $name;
      $fields[$i]['type'] = $type;
      $fields[$i]['nature'] = $nature;
      $fields[$i]['length'] = $length;
      $fields[$i]['is_nullable'] = $is_nullable;
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
  // take account of training spaces.  (The '=' comparison in MySQL allows
  // trailing spaces, eg 'john' = 'john ').
  public function syntax_casesensitive_equals($fieldname, $string, &$params)
  {
    $params[] = $string;

    return " BINARY " . $this->quote($fieldname) . "=?";
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
}
