<?php
namespace MRBS;


class Group extends Table
{
  const TABLE_NAME = 'group';

  protected static $unique_columns = array('name');


  public function __construct($name=null)
  {
    global $auth;

    parent::__construct();
    $this->name = $name;
    // Set some default properties
    $this->auth_type = $auth['type'];
    $this->roles = array();
  }


  public static function getById($id)
  {
    // TODO: there's no doubt a faster way of doing this using a single SQL
    // TODO: query, though it needs to work for both MySQL and PostgreSQL.
    $group = parent::getById($id);

    if (isset($group))
    {
      $group->roles = self::getRolesByGroupId($id);
    }

    return $group;
  }


  public static function getByName($name)
  {
    // TODO: add in roles
    return self::getByColumn('name', $name);
  }


  private static function getRolesByGroupId($id)
  {
    if (!isset($id))
    {
      return array();
    }

    $sql = "SELECT role_id
              FROM " . _tbl('group_role') . "
             WHERE group_id=:group_id";
    $sql_params = array(':group_id' => $id);
    return db()->query_array($sql, $sql_params);
  }

}
