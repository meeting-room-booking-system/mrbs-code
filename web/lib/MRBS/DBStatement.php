<?php

namespace MRBS;

use PDO;
use PDOStatement;
use PDOException;


//
class DBStatement extends PDOStatement
{
  protected $db_object = null;
  protected $statement = null;

  //
  public function __construct($db_obj, $sth)
  {
    $this->db_object = $db_obj;
    $this->statement = $sth;
  }


  // Return a row from a statement. The first row is 0.
  // The row is returned as an array with index 0=first column, etc.
  // When called with i >= number of rows in the statement, cleans up from
  // the query and returns 0.
  // Typical usage: $i = 0; while ((a = $statement_obj->row($r, $i++))) { ... }
  public function row ($i)
  {
    if ($i >= $this->count())
    {
      $this->statement->closeCursor();
      return 0;
    }
    return $this->statement->fetch(PDO::FETCH_NUM, PDO::FETCH_ORI_ABS, $i);
  }


  // Return a row from a statement as an associative array keyed by field name.
  // The first row is 0.
  // This is actually upward compatible with row() since the underlying
  // routing also stores the data under number indexes.
  // When called with i >= number of rows in the statement, cleans up from
  // the query and returns 0.
  public function row_keyed ($i)
  {
    if ($i >= $this->count())
    {
      $this->statement->closeCursor();
      return 0;
    }
    return $this->statement->fetch(PDO::FETCH_ASSOC, PDO::FETCH_ORI_ABS, $i);
  }

  // Return all the rows from a statement object, as an array of arrays
  // keyed on the column name
  public function all_rows_keyed()
  {
    $result = array();

    for ($i=0; $row = $this->row_keyed($i); $i++)
    {
      $result[] = $row;
    }

    return $result;
  }

  // Return the number of rows returned by a statement from query().
  public function count()
  {
    return $this->statement->rowCount();
  }

  // Free a statement. You need not call this if you call row() or
  // row_keyed() until the row returns 0, since those methods free the 
  // statement when you finish reading the rows.
  public function free ()
  {
    $this->statement->closeCursor();
  }


  // Returns the number of fields in a statement.
  public function num_fields()
  {
    return $this->statement->columnCount();
  }
}
