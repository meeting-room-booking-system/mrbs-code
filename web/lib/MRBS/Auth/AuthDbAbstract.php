<?php
declare(strict_types=1);
namespace MRBS\Auth;

use MRBS\DB\DB;

abstract class AuthDbAbstract extends Auth
{
  protected $db_table;
  protected $column_name_username;
  protected $column_name_display_name;

  /**
   * Returns a database connection.
   *
   * The connection isn't established in the constructor, but here only when it's really necessary, as it can be
   * expensive establishing a connection when the server is remote.  For example, `method_exists(auth(), 'method')`
   * will end up calling the constructor, but a connection isn't needed just for method_exists().
   */
  abstract protected function connection() : ?DB;


  /**
   * Return an array of users, indexed by 'username' and 'display_name'.
   */
  public function getUsernames() : array
  {
    if (isset($this->column_name_display_name) && ($this->column_name_display_name !== ''))
    {
      $display_name_column = $this->column_name_display_name;
    }
    else
    {
      $display_name_column = $this->column_name_username;
    }

    $quoted_column_name_display_name = $this->connection()->quote($display_name_column);
    $quoted_column_name_username = $this->connection()->quote($this->column_name_username);

    $sql = "SELECT $quoted_column_name_username AS username,
                   CASE
                       WHEN $quoted_column_name_display_name IS NOT NULL AND $quoted_column_name_display_name!='' THEN $quoted_column_name_display_name
                       ELSE $quoted_column_name_username
                   END AS display_name
              FROM " . $this->connection()->quote($this->db_table) . "
             WHERE $quoted_column_name_username IS NOT NULL
          ORDER BY display_name";

    $res = $this->connection()->query($sql);

    $users =  $res->all_rows_keyed();
    // Although the users may already be sorted, we sort them again because MRBS
    // offers an option for sorting by first or last name.
    self::sortUsers($users);

    return $users;
  }

}
