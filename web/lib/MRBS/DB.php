<?php

namespace MRBS;

use PDO;
use PDOException;


class DB
{
  const DB_SCHEMA_VERSION = 64;
  const DB_SCHEMA_VERSION_LOCAL = 1;

  const DB_DEFAULT_PORT = null;
  const DB_DBO_DRIVER = null;
  const DB_CHARSET = 'UTF8';

  protected $dbh = null;
  protected $mutex_lock_name;


  public function __construct($db_host, $db_username, $db_password,
                              $db_name, $persist = 0, $db_port = null)
  {

    // Early error handling, could be in constructor instead?
    if (is_null(static::DB_DBO_DRIVER) ||
        is_null(static::DB_DEFAULT_PORT))
    {
      throw new Exception("Encountered a fatal bug in DB abstraction code!");
    }

    // If no port has been provided, set a SQL variant dependent default
    if (empty($db_port))
    {
      $db_port = static::DB_DEFAULT_PORT;
      //print "Setting default port to $db_port\n";
    }

    // Establish a database connection.
    try
    {
      if (!isset($db_host) || ($db_host == ""))
      {
        $hostpart = "";
      }
      else
      {
        $hostpart = "host=$db_host;";
      }
      $this->dbh = new PDO(static::DB_DBO_DRIVER.":${hostpart}port=$db_port;dbname=$db_name",
                           $db_username,
                           $db_password,
                           array(PDO::ATTR_PERSISTENT => ($persist ? true : false),
                                 PDO::ATTR_ERRMODE    => PDO::ERRMODE_EXCEPTION));
      $this->command("SET NAMES '".static::DB_CHARSET."'");
    }
    catch (PDOException $e)
    {
      $message = $e->getMessage();

      // Add in some possible solutions for common problems when migrating to the PDO version of MRBS
      // from an earlier version.
      if ($e->getCode() == 7)
      {
        if (($db_host === '') && (static::DB_DBO_DRIVER === DB_pgsql::DB_DBO_DRIVER))
        {
          $message .= ".\n[MRBS note] Try setting " . '$db_host' . " to '127.0.0.1'.";
        }
      }
      elseif ($e->getCode() == 2054)
      {
        $message .= ".\n[MRBS note] It looks like you may have an old style MySQL password stored, which cannot be " .
                    "used with PDO (though it is possible that mysqli may have accepted it).  Try " .
                    "deleting the MySQL user and recreating it with the same password.";
      }

      throw new DBException($message, 0, $e);
    }
  }


  //
  public function error()
  {
    $error = "No database connection!";

    if ($this->dbh)
    {
      $error_info = $this->dbh->errorInfo();
      $error = $error_info[2];
    }
    return $error;
  }


  // Execute a non-SELECT SQL command (insert/update/delete).
  // Returns the number of tuples affected if OK (a number >= 0).
  // Throws a DBException on error.
  public function command($sql, array $params = array())
  {
    try
    {
      $sth = $this->dbh->prepare($sql);
      $sth->execute($params);
    }
    catch (PDOException $e)
    {
      throw new DBException($e->getMessage(), 0, $e, $sql, $params);
    }

    return $sth->rowCount();
  }


  // Execute an SQL query which should return a single non-negative number value.
  // This is a lightweight alternative to query(), good for use with count(*)
  // and similar queries.
  // It returns -1 if the query returns no result, or a single NULL value, such as from
  // a MIN or MAX aggregate function applied over no rows.
  // Throws a DBException on error.
  function query1($sql, array $params = array())
  {
    try
    {
      $sth = $this->dbh->prepare($sql);
      $sth->execute($params);
    }
    catch (PDOException $e)
    {
      throw new DBException($e->getMessage(), 0, $e, $sql, $params);
    }

    if ($sth->rowCount() > 1)
    {
      throw new DBException("query1() returned more than one row.", 0, null, $sql, $params);
    }

    if ($sth->columnCount() > 1)
    {
      throw new DBException("query1() returned more than one column.", 0, null, $sql, $params);
    }

    if (($row = $sth->fetch(PDO::FETCH_NUM)) == NULL)
    {
      $result = -1;
    }
    else
    {
      $result = $row[0];
    }
    $sth->closeCursor();
    return $result;
  }


  // Run an SQL query that returns a simple one dimensional array of results.
  // The SQL query must select only one column.   Returns an empty array if
  // no results; throws a DBException if there's an error
  public function query_array($sql, array $params = array())
  {
    $stmt = $this->query($sql, $params);

    $result = array();

    while (false !== ($row = $stmt->next_row()))
    {
      $result[] = $row[0];
    }

    return $result;
  }


  // Execute an SQL query. Returns a DBStatement object, a class with a number
  // of methods like row() and row_keyed() to get the results.
  // Throws a DBException on error
  public function query ($sql, array $params = array())
  {
    try
    {
      $sth = $this->dbh->prepare($sql);
      $sth->execute($params);
    }
    catch (PDOException $e)
    {
      throw new DBException($e->getMessage(), 0, $e, $sql, $params);
    }

    return new DBStatement($this, $sth);
  }


  //
  public function begin()
  {
    // Turn off ignore_user_abort until the transaction has been committed or rolled back.
    // See the warning at http://php.net/manual/en/features.persistent-connections.php
    // (Only applies to persistent connections, but we'll do it for all cases to keep
    // things simple)
    mrbs_ignore_user_abort(TRUE);
    if (!$this->dbh->inTransaction())
    {
      $this->dbh->beginTransaction();
    }
  }


  // Commit (end) a transaction. See begin().
  public function commit()
  {
    if ($this->dbh->inTransaction())
    {
      $this->dbh->commit();
    }
    mrbs_ignore_user_abort(FALSE);
  }


  // Roll back a transaction, aborting it. See begin().
  public function rollback()
  {
    if ($this->dbh->inTransaction())
    {
      $this->dbh->rollBack();
    }
    mrbs_ignore_user_abort(FALSE);
  }


  // Checks if inside a transaction
  public function inTransaction()
  {
    return $this->dbh->inTransaction();
  }


  // Return a string identifying the database version
  public function version()
  {
    return $this->query1("SELECT VERSION()");
  }


  // Return a boolean depending on whether $field exists in $table
  public function field_exists($table, $field)
  {
    $rows = $this->field_info($table);
    foreach ($rows as $row)
    {
      if ($row['name'] === $field)
      {
        return true;
      }
    }
    return false;
  }

}
