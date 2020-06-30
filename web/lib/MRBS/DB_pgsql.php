<?php

namespace MRBS;

use PDO;
use PDOException;

//
class DB_pgsql extends DB
{
  const DB_DEFAULT_PORT = 5432;
  const DB_DBO_DRIVER = "pgsql";


  // A small utility function (not part of the DB abstraction API) to
  // resolve a qualified table name into its schema and table components.
  // Returns an an array indexed by 'table_schema' and 'table_name'.
  // 'table_schema' can be NULL
  private static function resolve_table($table)
  {
    if (strpos($table, '.') === false)
    {
      $table_schema = null;
      $table_name = $table;
    }
    else
    {
      list($table_schema, $table_name) = explode('.', $table, 2);
    }

    return array('table_schema' => $table_schema,
                 'table_name' => $table_name);
  }


  // Quote a table or column name (which could be a qualified identifier, eg 'table.column')

  // NOTE:  We fold the identifier to lower case here even though it is quoted.   Unlike MySQL,
  // PostgreSQL folds identifiers to lower case, unless they are quoted.  However in MRBS we
  // normally want to quote an identifier in case it has characters such as spaces in it, as
  // could be the case with user generated column names for custom fields.  But if we were also
  // to quote the table name, then queries such as 'SELECT * FROM mrbs_entry E WHERE "E"."id"=2'
  // would fail because the alias 'E' is folded to 'e', but the WHERE clause gives 'E.id'.
  // This means that we won't be able to distinguish in PostgreSQL between column names that just
  // differ in case.  But having column names differing in case would be confusing anyway and so
  // should be discouraged.   And a PostgreSQL user generating custom fields would expect them to
  // be folded to lower case anyway, so presumably wouldn't try and create column names differing
  // only in case.
  public function quote($identifier)
  {
    $quote_char = '"';
    $parts = explode('.', strtolower($identifier));
    return $quote_char . implode($quote_char . '.' . $quote_char, $parts) . $quote_char;
  }


  // Return the value of an autoincrement field from the last insert.
  // For PostgreSQL, this must be a SERIAL type field.
  public function insert_id($table, $field)
  {
    $seq_name = $table . "_" . $field . "_seq";
    return $this->dbh->lastInsertId($seq_name);
  }


  // Acquire a mutual-exclusion lock on the named table. For portability:
  // This will not lock out SELECTs.
  // It may lock out DELETE/UPDATE/INSERT or not, depending on the implementation.
  // It will lock out other callers of this routine with the same name argument.
  // It may timeout and return false, or may wait forever.
  // It returns true when the lock has been acquired.
  // Caller must release the lock with mutex_unlock().
  // Caller must not have more than one mutex at any time.
  // Do not mix this with begin()/end() calls.
  //
  // In PostgreSQL, the EXCLUSIVE mode lock excludes all but SELECT.
  // It does not timeout, but waits forever for the lock.
  public function mutex_lock($name)
  {
    try
    {
      // LOCK TABLE can only be used in transaction blocks
      if (!$this->dbh->inTransaction())
      {
        $this->begin();
      }
      $this->command("LOCK TABLE $name IN EXCLUSIVE MODE");
    }
    catch (DBException $e)
    {
      return false;
    }

    $this->mutex_lock_name = $name;
    return true;
  }


  // Release a mutual-exclusion lock on the named table. See mutex_lock().
  // In PostgreSQL, all locks are released by closing the transaction; there
  // is no other way.
  // Returns true if the lock is released successfully, otherwise false
  public function mutex_unlock($name)
  {
    if ($this->dbh->inTransaction())
    {
      $this->commit();
    }

    $this->mutex_lock_name = null;
    return true;
  }


  // Destructor cleans up the connection
  function __destruct()
  {
    //print "PostgreSQL destructor called\n";

    // Release any forgotten locks
    if (isset($this->mutex_lock_name))
    {
      $this->command("ABORT", array());
    }

    // Rollback any outstanding transactions
    $this->rollback();
  }


  // Check if a table exists
  public function table_exists($table)
  {
    // $table can be a qualified name.  We need to resolve it if necessary into its component
    // parts, the schema and table names
    $table_parts = self::resolve_table($table);

    $sql_params = array();
    $sql = "SELECT COUNT(*)
              FROM information_schema.tables
             WHERE table_name = ?";
    $sql_params[] = $table_parts['table_name'];
    if (isset($table_parts['table_schema']))
    {
      $sql .= " AND table_schema = ?";
      $sql_params[] = $table_parts['table_schema'];
    }

    $res = $this->query1($sql, $sql_params);

    if ($res == 0)
    {
      return false;
    }
    elseif ($res == 1)
    {
      return true;
    }
    elseif (($res > 1) && !isset($table_parts['table_schema']))
    {
      $message = "More than one table called '$table'.  You need to set " . '$db_schema in the config file.';
      throw new DBException($message);
    }
    else
    {
      $message = "Unexpected result from SELECT COUNT(*) query.";
      throw new DBException($message);
    }
  }


  // Get information about the columns in a table
  // Returns an array with the following indices for each column
  //
  //  'name'        the column name
  //  'type'        the type as reported by PostgreSQL
  //  'nature'      the type mapped onto one of a generic set of types
  //                (boolean, integer, real, character, binary).   This enables
  //                the nature to be used by MRBS code when deciding how to
  //                display fields, without MRBS having to worry about the
  //                differences between MySQL and PostgreSQL type names.
  //  'length'      the maximum length of the field in bytes, octets or characters
  //                (Note:  this could be null)
  //  'is_nullable' whether the column can be set to NULL (boolean)
  //
  //  NOTE: the type mapping is incomplete and just covers the types commonly
  //  used by MRBS
  public function field_info($table)
  {
    $fields = array();

    // Map PostgreSQL types on to a set of generic types
    $nature_map = array('bigint'            => 'integer',
                        'boolean'           => 'boolean',
                        'bytea'             => 'binary',
                        'character'         => 'character',
                        'character varying' => 'character',
                        'decimal'           => 'decimal',
                        'double precision'  => 'real',
                        'integer'           => 'integer',
                        'numeric'           => 'decimal',
                        'real'              => 'real',
                        'smallint'          => 'integer',
                        'text'              => 'character');

    // $table can be a qualified name.  We need to resolve it if necessary into its component
    // parts, the schema and table names
    $table_parts = self::resolve_table($table);

    $sql_params = array();

    // $table_name and $table_schema should be trusted but escape them anyway for good measure
    $sql = "SELECT column_name, data_type, numeric_precision, numeric_scale, character_maximum_length,
                   character_octet_length, is_nullable
            FROM information_schema.columns
            WHERE table_name = ?";
    $sql_params[] = $table_parts['table_name'];
    if (isset($table_parts['table_schema']))
    {
      $sql .= " AND table_schema = ?";
      $sql_params[] = $table_parts['table_schema'];
    }
    $sql .= "ORDER BY ordinal_position";

    $stmt = $this->query($sql, $sql_params);

    while (false !== ($row = $stmt->next_row_keyed()))
    {
      $name = $row['column_name'];
      $type = $row['data_type'];
      // map the type onto one of the generic natures, if a mapping exists
      $nature = (array_key_exists($type, $nature_map)) ? $nature_map[$type] : $type;
      // Get a length value;  one of these values should be set
      if (isset($row['numeric_precision']))
      {
        if ($nature == 'decimal')
        {
          $length = $row['numeric_precision'] . ',' . $row['numeric_scale'];
        }
        else
        {
          $length = (int) floor($row['numeric_precision'] / 8);  // precision is in bits
        }
      }
      elseif (isset($row['character_maximum_length']))
      {
        $length = $row['character_maximum_length'];
      }
      elseif (isset($row['character_octet_length']))
      {
        $length = $row['character_octet_length'];
      }
      // Convert the is_nullable field to a boolean
      $is_nullable = (utf8_strtolower($row['is_nullable']) == 'yes') ? true : false;

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
    return " LIMIT $count OFFSET $offset ";
  }


  // Generate non-standard SQL to output a TIMESTAMP as a Unix-time:
  public function syntax_timestamp_to_unix($fieldname)
  {
    return " DATE_PART('epoch', $fieldname) ";
  }


  // Returns the syntax for a case sensitive string "equals" function
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

    return " " . $this->quote($fieldname) . "=?";
  }


  // Generate non-standard SQL to match a string anywhere in a field's value
  // in a case insensitive manner. $s is the un-escaped/un-slashed string.
  //
  // Also takes a required pass-by-reference parameter to modify the SQL
  // parameters appropriately.
  //
  // In PostgreSQL, we can do case insensitive regexp with ~*, but not case
  // insensitive LIKE matching.
  // Quotemeta escapes everything we need except for single quotes.
  public function syntax_caseless_contains($fieldname, $string, &$params)
  {
    $params[] = quotemeta($string);

    return " $fieldname ~* ? ";
  }


  // Generate non-standard SQL to specify a column as an auto-incrementing
  // integer while doing a CREATE TABLE
  public function syntax_createtable_autoincrementcolumn()
  {
    return "serial";
  }


  // Returns the syntax for a bitwise XOR operator
  public function syntax_bitwise_xor()
  {
    return "#";
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
      case 2:
        $count = $part;
        break;
      default:
        throw new Exception("Invalid value ($part) given for " . '$part.');
        break;
    }

    $params[] = $delimiter;
    return "SPLIT_PART($fieldname, ?, $count)";
  }

}
