<?php
namespace MRBS;


// This is a class for a general MRBS user, regardless of the authentication type.  Once authenticated each
// user is converted into a standard MRBS user object with defined properties.  (Do not confuse this user
// with a user in the user table: the 'db' authentication method is just one of many that MRBS supports.)
class User extends Table
{
  const TABLE_NAME = 'user';

  public $username;
  public $display_name;
  public $email;
  public $level;
  public $auth_type;

  protected static $unique_columns = array('name', 'auth_type');


  public function __construct($username=null)
  {
    global $auth;

    parent::__construct();

    if (isset($username) && ($username !== ''))
    {
      $this->username = $username;
      // Set some default properties
      $this->auth_type = $auth['type'];
      $this->display_name = $username;
      $this->setDefaultEmail();
      $this->level = 0; // Play it safe
      $this->id = self::getIdByName($username, $this->auth_type);
      $this->roles = self::getRolesByUserId($this->id);
    }
  }


  public function save()
  {
    parent::save();
    $this->saveRoles();
  }


  public static function getById($id)
  {
    // TODO: there's no doubt a faster way of doing this using a single SQL
    // query, though it needs to work for both MySQL and PostgreSQL.
    $user = parent::getById($id);

    if (isset($user))
    {
      $user->username = $user->name;
      $user->roles = self::getRolesByUserId($id);
    }

    return $user;
  }


  public static function getByName($username, $auth_type)
  {
    $user = self::getByColumns(array(
        'name'      => $username,
        'auth_type' => $auth_type
      ));

    if (isset($user))
    {
      $user->username = $user->name;
      $user->roles = self::getRolesByUserId($user->id);
    }

    return $user;
  }


  private function saveRoles()
  {
    $existing = self::getRolesByUserId($this->id);

    // If there's been no change then don't do anything
    if (array_diff($existing, $this->roles) == array_diff($this->roles, $existing))
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
    $sql = "DELETE FROM " . _tbl('user_role') . "
                  WHERE user_id=:user_id";
    db()->command($sql, array(':user_id' => $this->id));
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
        ':name:' => $username,
        ':auth_type' => $auth_type
      );

    $id = db()->query1($sql, $sql_params);

    return ($id < 0) ? null : $id;
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


  // Sets the default email address for the user (null if one can't be found)
  private function setDefaultEmail()
  {
    global $mail_settings;

    if (!isset($this->username) || $this->username === '')
    {
      $this->email = null;
    }
    else
    {
      $this->email = $this->username;

      // Remove the suffix, if there is one
      if (isset($mail_settings['username_suffix']) && ($mail_settings['username_suffix'] !== ''))
      {
        $suffix = $mail_settings['username_suffix'];
        if (substr($this->email, -strlen($suffix)) === $suffix)
        {
          $this->email = substr($this->email, 0, -strlen($suffix));
        }
      }

      // Add on the domain, if there is one
      if (isset($mail_settings['domain']) && ($mail_settings['domain'] !== ''))
      {
        // Trim any leading '@' character. Older versions of MRBS required the '@' character
        // to be included in $mail_settings['domain'], and we still allow this for backwards
        // compatibility.
        $domain = ltrim($mail_settings['domain'], '@');
        $this->email .= '@' . $domain;
      }
    }
  }

}
