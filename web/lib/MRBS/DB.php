<?php

namespace MRBS;

use PDO;
use PDOException;


//
class DB
{
  const DB_DEFAULT_PORT = null;
  const DB_DBO_DRIVER = null;
  protected $dbh = null;
  protected $mutex_lock_name;
  static private $default_db_obj;

  //
  public function __construct($db_host, $db_username, $db_password,
                              $db_name, $persist = 0, $db_port = null)
  {

    // Early error handling, could be in constructor instead?
    if (is_null(static::DB_DBO_DRIVER) ||
        is_null(static::DB_DEFAULT_PORT))
    {
      print "Encountered a fatal bug in DB abstraction code!\n";
      return null;
    }

    // If no port has been provided, set a SQL variant dependent default
    if (empty($db_port))
    {
      $db_port = static::DB_DEFAULT_PORT;
      //print "Setting default port to $db_port\n";
    }

    // Establish a database connection.

    // On connection error, the message will be output without a proper HTML
    // header. There is no way I can see around this; if track_errors isn't on
    // there seems to be no way to supress the automatic error message output and
    // still be able to access the error text.

    try
    {
      $this->dbh = new PDO(static::DB_DBO_DRIVER.":host=$db_host;port=$db_port;dbname=$db_name",
                           $db_username,
                           $db_password,
                           array(PDO::ATTR_PERSISTENT => ($persist ? true : false),
                                 PDO::ATTR_ERRMODE    => PDO::ERRMODE_EXCEPTION));
      $this->command("SET NAMES 'UTF8'");
    }
    catch (PDOException $e)
    {
      trigger_error($e->getMessage(), E_USER_WARNING);
      if ($e->getCode() == 2054)
      {
        $message = "It looks like you have an old style MySQL password stored, which cannot be " .
                   "used with PDO (though it is possible that mysqli may have accepted it).  Try " .
                   "deleting the MySQL user and recreating it with the same password.";
        trigger_error($message, E_USER_WARNING);
      }
      echo "\n<p>\n" . get_vocab("failed_connect_db") . "\n</p>\n";
      exit;
    }
  }

  
  // Static function to return a DB object for the default MRBS database connection,
  // will make the connection if it hasn't been made yet.
  static public function default_db()
  {
    if (is_null(self::$default_db_obj))
    {
      global $db_persist, $db_host, $db_login, $db_password,
             $db_database, $db_port, $dbsys;

      self::$default_db_obj = DBFactory::create($dbsys, $db_host, $db_login, $db_password,
                                                $db_database, $db_persist, $db_port);
    }
    return self::$default_db_obj;
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
  // Returns -1 on error; use error() to get the error message.
  public function command($sql, $params = array())
  {
    try
    {
      $sth = $this->dbh->prepare($sql);
      $sth->execute($params);
    }
    catch (PDOException $e)
    {
      trigger_error($e->getMessage(), E_USER_WARNING);
      return -1;
    }
  
    return $sth->rowCount();
  }

  
  // Execute an SQL query which should return a single non-negative number value.
  // This is a lightweight alternative to query(), good for use with count(*)
  // and similar queries. It returns -1 on error or if the query did not return
  // exactly one value, so error checking is somewhat limited.
  // It also returns -1 if the query returns a single NULL value, such as from
  // a MIN or MAX aggregate function applied over no rows.
  function query1($sql, $params = array())
  {
    $sth = $this->dbh->prepare($sql);
    if (!$sth)
    {
      trigger_error($sql." ".$this->error(), E_USER_WARNING);
      return -1;
    }
    $sth->execute($params);

    if (($sth->rowCount() != 1) || ($sth->columnCount() != 1) ||
        (($row = $sth->fetch(PDO::FETCH_NUM)) == NULL))
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
  // no results, or FALSE if there's an error
  public function query_array($sql, $params = null)
  {
    $stmt = $this->query($sql, $params);

    if ($stmt === FALSE)
    {
      return FALSE;
    }
    else
    {
      $result = array();
      for ($i = 0; ($row = $stmt->row($i)); $i++)
      {
        $result[] = $row[0];
      }
      return $result;
    }
  }

  
  // Execute an SQL query. Returns a result handle, which should be passed
  // back to row() or row_keyed() to get the results.
  // Returns FALSE on error; use error() to get the error message.
  public function query ($sql, $params = array())
  {
    $sth = $this->dbh->prepare($sql);
    $sth->execute($params);
  
    return new DBStatement($this, $sth);
  }

  
  // Commit (end) a transaction. See begin().
  function commit()
  {
    $result = $this->command("COMMIT");
  
    if ($result < 0)
    {
      trigger_error ($this->error(), E_USER_WARNING);
    }
  }

  
  // Commit (end) a transaction. See begin().
  function rollback()
  {
    $result = $this->command("ROLLBACK", array());
  
    if ($result < 0)
    {
      trigger_error ($this->error(), E_USER_WARNING);
    }
  }


  // Return a string identifying the database version
  function version()
  {
    return $this->query1("SELECT VERSION()");
  }

}
