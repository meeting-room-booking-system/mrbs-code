<?php
namespace MRBS;


class Group extends Table
{
  const TABLE_NAME = 'group';

  protected static $unique_columns = array('name', 'auth_type');


  public function __construct($name=null)
  {
    global $auth;

    parent::__construct();
    $this->name = $name;
    // Set some default properties
    $this->auth_type = $auth['type'];
    $this->roles = array();
  }


  public function save()
  {
    parent::save();
    $this->saveRoles();
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


  // Gets the roles assigned to a set of groups
  public static function getRoles(array $groups)
  {
    if (empty($groups))
    {
      return array();
    }

    $sql = "SELECT role_id
              FROM " . _tbl('group_role') . "
             WHERE group_id IN (" . rtrim(str_repeat('?,', count($groups)), ',') . ")";

    return db()->query_array($sql, $groups);
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


  private function saveRoles()
  {
    $existing = self::getRolesByGroupId($this->id);

    // If there's been no change then don't do anything
    if (array_values_equal($existing, $this->roles))
    {
      return;
    }

    // Otherwise delete the old ones and insert the new ones
    // TODO: add some locking?
    $this->deleteRoles();
    $this->insertRoles();
  }


  private function deleteRoles()
  {
    $sql = "DELETE FROM " . _tbl('group_role') . "
                  WHERE group_id=:group_id";
    db()->command($sql, array(':group_id' => $this->id));
  }


  private function insertRoles()
  {
    // If there aren't any roles then there's no need to do anything
    if (empty($this->roles))
    {
      return;
    }

    // Otherwise insert the roles
    $sql_params = array(':group_id' => $this->id);
    $values = array();
    foreach ($this->roles as $i => $role_id)
    {
      $named_parameter = ":role_id$i";
      $sql_params[$named_parameter] = $role_id;
      $values[] = "(:group_id, $named_parameter)";
    }
    $sql = "INSERT INTO " . _tbl('group_role') . " (group_id, role_id) VALUES ";
    $sql .= implode(', ', $values);
    db()->command($sql, $sql_params);
  }

}
