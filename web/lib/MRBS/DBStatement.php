<?php

namespace MRBS;

use PDO;
use PDOStatement;


//
class DBStatement
{
  protected $db_object = null;
  protected $statement = null;

  //
  public function __construct(DB $db_obj, PDOStatement $sth)
  {
    $this->db_object = $db_obj;
    $this->statement = $sth;
  }


  // Returns the next row from a statement.
  // The row is returned as an array with index 0=first column, etc.
  // Returns FALSE if there are no more rows.
  public function next_row()
  {
    return $this->statement->fetch(PDO::FETCH_NUM);
  }


  // Return a row from a statement as an associative array keyed by field name.
  // Returns FALSE if there are no more rows.
  public function next_row_keyed()
  {
    return $this->statement->fetch(PDO::FETCH_ASSOC);
  }


  // Return all the rows from a statement object, as an array of arrays
  // keyed on the column name
  public function all_rows_keyed()
  {
    $result = array();

    while (false !== ($row = $this->next_row_keyed()))
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

  // Returns the number of fields in a statement.
  public function num_fields()
  {
    return $this->statement->columnCount();
  }
}
