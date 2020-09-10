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
    $this->username = $username;
    // Set some default properties
    $this->auth_type = $auth['type'];
    $this->display_name = $username;
    $this->setDefaultEmail();
    $this->level = 0; // Play it safe
  }


  public static function getById($id)
  {
    // TODO: there's no doubt a faster way of doing this using a single SQL
    // query, though it needs to work for both MySQL and PostgreSQL.
    $user = parent::getById($id);

    if (isset($user))
    {
      $user->roles = self::getRolesByUserId($id);
    }

    return $user;
  }


  public static function getByName($username, $auth_type)
  {
    return self::getByColumns(array(
        'name'      => $username,
        'auth_type' => $auth_type
      ));
  }


  private static function getRolesByUserId($id)
  {
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
