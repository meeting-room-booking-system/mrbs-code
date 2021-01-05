<?php
namespace MRBS;


class Groups extends Attributes
{

  public function __construct()
  {
    parent::__construct(__NAMESPACE__ . '\\Group');
  }


  public function next()
  {
    $this->cursor++;

    if (false !== ($row = $this->res->next_row_keyed()))
    {
      $this->item = new $this->base_class();
      $this->item->load($row);
    }
  }


  // Returns an array of group names indexed by id.
  public function getNames()
  {
    $result = array();
    foreach ($this as $group)
    {
      $result[$group->id] = $group->name;
    }
    return $result;
  }


  protected function getRes($sort_column = null)
  {
    global $auth;

    $class_name = $this->base_class;
    $table_name = _tbl($class_name::TABLE_NAME);
    $sql_params = array(':auth_type' => $auth['type']);
    $sql = "SELECT G.*, " . db()->syntax_group_array_as_string('R.role_id') . " AS roles
              FROM $table_name G
         LEFT JOIN " . _tbl('group_role') . " R
                ON R.group_id=G.id
             WHERE G.auth_type=:auth_type
          GROUP BY G.id
          ORDER BY G.name";
    $this->res = db()->query($sql, $sql_params);
    $this->cursor = -1;
    $this->item = null;
  }
}
