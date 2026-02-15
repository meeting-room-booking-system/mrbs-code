<?php
declare(strict_types=1);
namespace MRBS\DB;

use PDO;
use PDOStatement;


class DBStatement
{
  protected $db_object = null;
  protected $statement = null;


  public function __construct(DB $db_obj, PDOStatement $sth)
  {
    $this->db_object = $db_obj;
    $this->statement = $sth;
  }


  /**
   * Fetch the next row from a result set
   *
   * @return mixed[]|false An array indexed by column number as returned in the result set, starting at column 0,
   * or FALSE if there are no more rows.
   */
  public function next_row()
  {
    return $this->statement->fetch(PDO::FETCH_NUM);
  }


  /**
   * Returns the next row from a statement as an associative array.
   *
   * @return array<string,mixed>|false The next row indexed by column name, or FALSE if there are no more rows.
   */
  public function next_row_keyed()
  {
    return $this->statement->fetch(PDO::FETCH_ASSOC);
  }


  /**
   * Return all the rows from a statement object, as an array of arrays keyed on the column name.
   */
  public function all_rows_keyed() : array
  {
    $result = array();

    while (false !== ($row = $this->next_row_keyed()))
    {
      $result[] = $row;
    }

    return $result;
  }

  /**
   * Returns the number of rows affected by the last SQL statement.
   *
   * For DELETE, INSERT, or UPDATE statements the number of rows affected is returned, though note that this depends
   * on the setting of Pdo\Mysql::ATTR_FOUND_ROWS for MySQL.
   *
   * For statements that produce result sets, such as SELECT, the behaviour is undefined and can be different for each driver.
   */
  public function count() : int
  {
    return $this->statement->rowCount();
  }

  // Returns the number of fields in a statement.
  public function num_fields() : int
  {
    return $this->statement->columnCount();
  }
}
