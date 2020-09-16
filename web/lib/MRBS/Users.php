<?php
namespace MRBS;


class Users extends TableIterator
{

  private $role_names;


  public function __construct()
  {
    parent::__construct(__NAMESPACE__ . '\\User');
    $roles = new Roles();
    $this->role_names = $roles->getNames();
  }


  public function next()
  {
    $this->cursor++;
    
    if (false !== ($row = $this->res->next_row_keyed()))
    {
      $role_names = array();

      if (isset($row['roles']))
      {
        $row['roles'] = explode(',', $row['roles']);
        foreach ($row['roles'] as $role_id)
        {
          $role_names[] = $this->role_names[$role_id];
        }
      }
      else
      {
        $row['roles'] = array();
      }

      $row['role_names'] = $role_names;

      $this->item = new $this->base_class();
      $this->item->load($row);
    }
  }


  protected function getRes($sort_column = null)
  {
    global $auth;

    // TODO: PostgreSQL equivalent for GROUP_CONCAT

    $class_name = $this->base_class;
    $table_name = _tbl($class_name::TABLE_NAME);
    $sql_params = array(':auth_type' => $auth['type']);
    $sql = "SELECT U.*, GROUP_CONCAT(R.role_id) AS roles
              FROM $table_name U
         LEFT JOIN " . _tbl('user_role') . " R
                ON R.user_id=U.id
             WHERE U.auth_type=:auth_type
          GROUP BY U.name
          ORDER BY U.name";
    $this->res = db()->query($sql, $sql_params);
    $this->cursor = -1;
    $this->item = null;
  }
}
