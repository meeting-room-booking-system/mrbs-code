<?php
namespace MRBS;


// This is a class for a general MRBS user, regardless of the authentication type.  Once authenticated each
// user is converted into a standard MRBS User object with defined properties.
class User extends Table
{
  const TABLE_NAME = 'user';

  protected static $unique_columns = array('name', 'auth_type');


  public function __construct($username=null)
  {
    global $auth;

    parent::__construct();

    $this->username = $username;
    // Set some default properties
    $this->auth_type = $auth['type'];
    $this->display_name = $username;
    $this->email = self::getDefaultEmail($username);
    $this->level = auth()->getDefaultLevel($username);
    $this->groups = array();
    $this->roles = self::getRolesByUsername($username);
  }


  public function save()
  {
    parent::save();
    $this->saveGroups();
    $this->saveRoles();
  }


  public static function getById($id)
  {
    // TODO: there's no doubt a faster way of doing this using a single SQL
    // TODO: query, though it needs to work for both MySQL and PostgreSQL.
    $user = parent::getById($id);

    if (isset($user))
    {
      $user->username = $user->name;
      $user->groups = self::getGroupsByUserId($user->id);
      $user->roles = self::getRolesByUserId($id);
    }

    return $user;
  }


  public static function getByName($username, $auth_type)
  {
    // TODO: there's no doubt a faster way of doing this using a single SQL
    // TODO: query, though it needs to work for both MySQL and PostgreSQL.
    $user = self::getByColumns(array(
        'name'      => $username,
        'auth_type' => $auth_type
      ));

    if (isset($user))
    {
      $user->username = $user->name;
      $user->groups = self::getGroupsByUserId($user->id);
      $user->roles = self::getRolesByUserId($user->id);
    }

    return $user;
  }


  // Gets the combined individual and group roles for the user
  public function combinedRoles()
  {
    $group_roles = Group::getRoles($this->groups);
    return array_unique(array_merge($this->roles, $group_roles));
  }


  // Returns an array of rules applicable to this user in this
  // room.  $location can be either a Room or Area object.
  // It is a recursive function.  If $for_groups is false
  // then it gets the rules that are applicable to that user.  If
  // true then it just gets the rules applicable to any groups
  // that the user is a member of.
  public function getRules($location, $for_groups = false)
  {
    // Work out whether $location is a room or area
    if ($location instanceof Room)
    {
      $is_room = true;
    }
    elseif ($location instanceof Area)
    {
      $is_room = false;
    }
    else
    {
      throw new \Exception('$location parameter is not an instance of Room or Area.');
    }

    if ($for_groups)
    {
      $roles = Group::getRoles($this->groups);
      if (empty($roles))
      {
        $rule = ($is_room) ? RoomPermission::getDefaultPermission() : AreaPermission::getDefaultPermission();
        return array($rule);
      }
    }
    else
    {
      // Get the individual roles for this user
      $roles = $this->roles;
      if (empty($roles))
      {
        return $this->getRules($location,true);
      }
    }

    // Now we've got the roles, get the rules that apply.
    // If this is a room get any rules for the room.
    if ($is_room)
    {
      $rules = $location->getPermissions($roles);
    }
    // If there are none - either because there were none, or else because the
    // location is an area - check to see if there any rules for the area.
    if (empty($rules))
    {
      $area = ($is_room) ? Area::getById($location->area_id) : $location;
      $rules = $area->getPermissions($roles);
      if (empty($rules))
      {
        // If there are no rules for the area and we're already checking
        // the rules for the user's groups, then there's nothing more that
        // we can do, so return the default rules.
        if ($for_groups)
        {
          $rule = AreaPermission::getDefaultPermission();
          return array($rule);
        }
        // Otherwise, see if there are some rules for the user's groups.
        return $this->getRules($location,true);
      }
    }

    return $rules;
  }


  private function saveGroups()
  {
    $existing = self::getGroupsByUserId($this->id);

    // If there's been no change then don't do anything
    if (array_values_equal($existing, $this->groups))
    {
      return;
    }

    // Otherwise delete the old ones and insert the new ones
    // TODO: add some locking?
    $this->deleteGroups();
    $this->insertGroups();
  }


  private function saveRoles()
  {
    $existing = self::getRolesByUserId($this->id);

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


  private function deleteGroups()
  {
    $sql = "DELETE FROM " . _tbl('user_group') . "
                  WHERE user_id=:user_id";
    db()->command($sql, array(':user_id' => $this->id));
  }


  private function deleteRoles()
  {
    $sql = "DELETE FROM " . _tbl('user_role') . "
                  WHERE user_id=:user_id";
    db()->command($sql, array(':user_id' => $this->id));
  }


  private function insertGroups()
  {
    // If there aren't any groups then there's no need to do anything
    if (empty($this->groups))
    {
      return;
    }

    // Otherwise insert the groups
    $sql_params = array(':user_id' => $this->id);
    $values = array();
    foreach ($this->groups as $i => $group_id)
    {
      $named_parameter = ":group_id$i";
      $sql_params[$named_parameter] = $group_id;
      $values[] = "(:user_id, $named_parameter)";
    }
    $sql = "INSERT INTO " . _tbl('user_group') . " (user_id, group_id) VALUES ";
    $sql .= implode(', ', $values);
    db()->command($sql, $sql_params);
  }


  private function insertRoles()
  {
    // If there aren't any roles then there's no need to do anything
    if (empty($this->roles))
    {
      return;
    }

    // Otherwise insert the roles
    $sql_params = array(':user_id' => $this->id);
    $values = array();
    foreach ($this->roles as $i => $role_id)
    {
      $named_parameter = ":role_id$i";
      $sql_params[$named_parameter] = $role_id;
      $values[] = "(:user_id, $named_parameter)";
    }
    $sql = "INSERT INTO " . _tbl('user_role') . " (user_id, role_id) VALUES ";
    $sql .= implode(', ', $values);
    db()->command($sql, $sql_params);
  }


  private static function getIdByName($username, $auth_type)
  {
    $sql = "SELECT id FROM " . _tbl(self::TABLE_NAME) ."
             WHERE name=:name
               AND auth_type=:auth_type
             LIMIT 1";

    $sql_params = array(
        ':name' => $username,
        ':auth_type' => $auth_type
      );

    $id = db()->query1($sql, $sql_params);

    return ($id < 0) ? null : $id;
  }


  private static function getGroupsByUserId($id)
  {
    if (!isset($id))
    {
      return array();
    }

    $sql = "SELECT group_id
              FROM " . _tbl('user_group') . "
             WHERE user_id=:user_id";
    $sql_params = array(':user_id' => $id);
    return db()->query_array($sql, $sql_params);
  }


  private static function getRolesByUserId($id)
  {
    if (!isset($id))
    {
      return array();
    }

    $sql = "SELECT role_id
              FROM " . _tbl('user_role') . "
             WHERE user_id=:user_id";
    $sql_params = array(':user_id' => $id);
    return db()->query_array($sql, $sql_params);
  }


  private static function getRolesByUsername($username)
  {
    if (!isset($username) || ($username === ''))
    {
      return array();
    }

    $sql = "SELECT role_id
              FROM " . _tbl('user_role') . " R
         LEFT JOIN " . _tbl(User::TABLE_NAME) . " U
                ON R.user_id=U.id
             WHERE U.name=:username";
    $sql_params = array(':username' => $username);
    return db()->query_array($sql, $sql_params);
  }


  // Gets the default email address for the user (null if one can't be found)
  public static function getDefaultEmail($username)
  {
    global $mail_settings;

    if (!isset($username) || $username === '')
    {
      return null;
    }

    $email = $username;

    // Remove the suffix, if there is one
    if (isset($mail_settings['username_suffix']) && ($mail_settings['username_suffix'] !== ''))
    {
      $suffix = $mail_settings['username_suffix'];
      if (substr($email, -strlen($suffix)) === $suffix)
      {
        $email = substr($email, 0, -strlen($suffix));
      }
    }

    // Add on the domain, if there is one
    if (isset($mail_settings['domain']) && ($mail_settings['domain'] !== ''))
    {
      // Trim any leading '@' character. Older versions of MRBS required the '@' character
      // to be included in $mail_settings['domain'], and we still allow this for backwards
      // compatibility.
      $domain = ltrim($mail_settings['domain'], '@');
      $email .= '@' . $domain;
    }

    return $email;
  }


  // Function to decode any columns that are stored encoded in the database
  protected static function onRead($row)
  {
    $row['username'] = $row['name'];

    return $row;
  }

  // Function to encode any columns that are stored encoded in the database
  protected static function onWrite($row)
  {
    if (!isset($row['name']))
    {
      $row['name'] = $row['username'];
    }

    return $row;
  }

}
