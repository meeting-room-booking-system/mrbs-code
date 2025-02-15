<?php
namespace MRBS;


// This is a class for a general MRBS user, regardless of the authentication type.  Once authenticated each
// user is converted into a standard MRBS User object with defined properties.
use PHPMailer\PHPMailer\PHPMailer;

class User extends Table
{
  const TABLE_NAME = 'user';

  protected static $unique_columns = array('name', 'auth_type');


  public function __construct($username=null)
  {
    parent::__construct();

    $this->username = $username;
    // Set some default properties
    $this->auth_type = auth()->type();
    $this->display_name = $username;
    $this->email = self::getDefaultEmail($username);
    $this->level = auth()::getDefaultLevel($username);
    $this->groups = array();
    $this->roles = self::getRolesByUsername($username);
  }


  public function save() : void
  {
    parent::save();
    $this->saveGroups();
    $this->saveRoles();
  }


  // Checks whether the user appears somewhere in the bookings as (a) the creator
  // of a booking, (b) the modifier of a booking or (c) a registrant.
  public function isInBookings() : bool
  {
    $sql_params = [':username' => $this->username];

    foreach (['entry', 'repeat'] as $table)
    {
      $sql = "SELECT COUNT(id)
                FROM ". _tbl($table) . "
               WHERE (create_by = :username)
                  OR (modified_by = :username)
               LIMIT 1";
      if (db()->query1($sql, $sql_params) > 0)
      {
        return true;
      }
    }

    $sql = "SELECT COUNT(id)
              FROM ". _tbl('participant') . "
             WHERE username = :username
             LIMIT 1";

    return (db()->query1($sql, $sql_params) > 0);
  }


  // Returns an RFC 5322 mailbox address, ie an address in the format
  // "Display name <email address>"
  public function mailbox()
  {
    if (!isset($this->email))
    {
      return null;
    }

    if (!isset($this->display_name) || ($this->display_name === ''))
    {
      return $this->email;
    }

    $mailer = new PHPMailer();
    $mailer->CharSet = get_mail_charset();

    // Note that addrFormat() returns a MIME-encoded address
    return $mailer->addrFormat(array($this->email, $this->display_name));
  }


  // Set the last_login time, where $time is a Unix timestamp (default now)
  public function updateLastLogin($time=null) : void
  {
    global $auth;

    if (!isset($time))
    {
      $time = time();
    }

    $this->last_login = $time;

    // We use a special UPDATE rather than a save() because we want
    // to preserve the timestamp
    $sql = "UPDATE " . \MRBS\_tbl(User::TABLE_NAME) . "
               SET last_login=:last_login, timestamp=timestamp
             WHERE name=:name
               AND auth_type=:auth_type";

    $sql_params = array(
        ':last_login' => $time,
        ':name'       => $this->username,
        ':auth_type'  => $auth['type']
      );

    \MRBS\db()->command($sql, $sql_params);
  }


  public static function getByName($username, $auth_type) : ?User
  {
    return self::getByColumns(array(
        'name'      => $username,
        'auth_type' => $auth_type
      ));
  }


  public function isAdmin() : bool
  {
    global $max_level;

    $required_level = (isset($max_level)) ? $max_level : 2;
    return ($this->level >= $required_level);
  }


  // Checks whether the user is a booking admin, regardless of location.
  // (Note that users below this level may still be granted booking admin
  // rights for particular rooms and areas by using specific rules.)
  public function isBookingAdmin() : bool
  {
    global $min_booking_admin_level;

    $required_level = (isset($min_booking_admin_level)) ? $min_booking_admin_level : 2;
    return ($this->level >= $required_level);
  }


  // Gets the combined individual and group roles for the user
  public function combinedRoles() : array
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
  public function getRules($location, $for_groups = false) : array
  {
    // Work out whether $location is a room or area
    $is_room = ($location instanceof Room);

    assert($is_room || ($location instanceof Area),
           '$location is neither an instance of Room nor Area');

    if ($for_groups)
    {
      $roles = Group::getRoles($this->groups);
      if (empty($roles))
      {
        $rule = $location->getDefaultRule($this);
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
      $rules = $location->getRules($roles);
    }
    // If there are none - either because there were none, or else because the
    // location is an area - check to see if there any rules for the area.
    if (empty($rules))
    {
      $area = ($is_room) ? Area::getById($location->area_id) : $location;
      $rules = $area->getRules($roles);
      if (empty($rules))
      {
        // If there are no rules for the area and we're already checking
        // the rules for the user's groups, then there's nothing more that
        // we can do, so return the default rules.
        if ($for_groups)
        {
          $rule = $location->getDefaultRule($this);
          return array($rule);
        }
        // Otherwise, see if there are some rules for the user's groups.
        return $this->getRules($location,true);
      }
    }

    // Merge in the default rule
    $rules[] = $location->getDefaultRule($this);

    return $rules;
  }


  // Returns the HTML for a table of effective permissions for this user
  public function effectivePermissionsHTML() : string
  {
    $html = '';

    $areas = new Areas();
    $permission_options = AreaRule::getPermissionOptions();

    $html .= '<table';
    if (isset($this->id) && ($this->id !== ''))
    {
      $html .= ' data-id="' . escape_html($this->id) . '"';
    }
    $html .= ">\n";
    $html .= "<thead>\n";
    $html .= "<tr>";
    $html .= "<th></th>";
    foreach ($permission_options as $key => $value)
    {
      $html .= "<th>" . escape_html($value) . "</th>";
    }
    $html .= "</tr>\n";
    $html .= "</thead>\n";

    $html .= "<tbody>\n";
    foreach ($areas as $area)
    {
      $html .= '<tr class="area">';
      $html .= "<td>" . escape_html($area->area_name) . "</td>";
      foreach ($permission_options as $key => $value)
      {
        $html .= "<td>";
        // READ is the only permission which can be applied to an area itself
        // as opposed to the rooms within the area.
        if ($key == AreaRule::READ)
        {
          $class = ($area->isAble($key, $this)) ? 'yes' : 'no';
          $html .= '<span class="' . $class . '"></span>';
        }
        $html .= "</td>";
      }
      $html .= "</tr>\n";
      $rooms = new Rooms($area->id);
      foreach ($rooms as $room)
      {
        $html .= '<tr class="room">';
        $html .= "<td>" . escape_html($room->room_name) . "</td>";
        unset($class);
        foreach ($permission_options as $key => $value)
        {
          $html .= "<td>";
          // If the previous class was 'no' then we don't need to do
          // the isAble() test, thus saving some time.  Note that this
          // depends on the permissions being in increasing order.
          if (!isset($class) || ($class == 'yes'))
          {
            $class = ($room->isAble($key, $this)) ? 'yes' : 'no';
          }
          $html .= '<span class="' . $class . '"></span>';
          $html .= "</td>";
        }
        $html .= "</tr>\n";
      }
    }
    $html .= "</tbody>\n";
    $html .= "</table>\n";

    return $html;
  }


  // Determines whether the key properties for the user $new are different.
  // Note that $new is an array rather than an object.
  public function hasChanged(array $new) : bool
  {
    return (($new['display_name'] !== $this->display_name) ||
            ($new['level'] !== $this->level) ||
            (isset($new['email']) && ($new['email'] !== $this->email)) ||
            !array_values_equal($new['groups'], $this->groups));
  }


  private function saveGroups() : void
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


  private function saveRoles() : void
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


  private function deleteGroups() : void
  {
    $sql = "DELETE FROM " . _tbl('user_group') . "
                  WHERE user_id=:user_id";
    db()->command($sql, array(':user_id' => $this->id));
  }


  private function deleteRoles() : void
  {
    $sql = "DELETE FROM " . _tbl('user_role') . "
                  WHERE user_id=:user_id";
    db()->command($sql, array(':user_id' => $this->id));
  }


  private function insertGroups() : void
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


  private function insertRoles() : void
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


  private static function getIdByName($username, $auth_type) : ?int
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


  private static function getGroupsByUserId($id) : array
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


  private static function getRolesByUserId($id) : array
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


  private static function getRolesByUsername($username) : array
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
  public static function getDefaultEmail(?string $username) : ?string
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
  protected static function onRead(array $row) : array
  {
    // TODO:  Simplify things by renaming the 'name' column to 'username'
    $row['username'] = $row['name'];

    if (array_key_exists('groups', $row))
    {
      $row['groups'] = db()->convert_string_to_array($row['groups']);
      $row['group_names'] = Groups::idsToNames($row['groups']);
    }

    if (array_key_exists('roles', $row))
    {
      $row['roles'] = db()->convert_string_to_array($row['roles']);
      $row['role_names'] = Roles::idsToNames($row['roles']);
    }

    // Turn the last_updated column into an int (some MySQL drivers will return a string,
    // and it won't have been caught by load() as it's a derived result).
    if (isset($row['last_updated']))
    {
      $row['last_updated'] = intval($row['last_updated']);
    }

    return $row;
  }

  // Function to encode any columns that are stored encoded in the database
  protected static function onWrite(array $row) : array
  {
    if (!isset($row['name']))
    {
      $row['name'] = $row['username'];
    }

    return $row;
  }


  protected static function getByColumns(array $columns) : ?object
  {
    $conditions = array();
    $sql_params = array();

    foreach ($columns as $name => $value)
    {
      $conditions[] = db()->quote($name) . "=?";
      $sql_params[] = $value;
    }

    $sql = "SELECT U.*, " . db()->syntax_group_array_as_string('R.role_id') . " AS roles,
                        " . db()->syntax_group_array_as_string('G.group_id') . " AS " . db()->quote('groups') . "
              FROM " . _tbl(self::TABLE_NAME) . " U
         LEFT JOIN " . _tbl('user_role') . " R
                ON R.user_id=U.id
         LEFT JOIN " . _tbl('user_group') . " G
                ON G.user_id=U.id
             WHERE " . implode(' AND ', $conditions) . "
          GROUP BY U.id
             LIMIT 1";

    $res = db()->query($sql, $sql_params);

    if ($res->count() == 0)
    {
      $result = null;
    }
    else
    {
      $result = new self();
      $result->load($res->next_row_keyed());
    }

    return $result;
  }

}
