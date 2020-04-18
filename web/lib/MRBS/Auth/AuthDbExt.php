<?php
namespace MRBS\Auth;

use MRBS\DBFactory;
use MRBS\User;

class AuthDbExt extends Auth
{
  protected $db_ext_conn;

  protected $db_table;
  protected $password_format;
  protected $column_name_username;
  protected $column_name_display_name;
  protected $column_name_password;
  protected $column_name_email;
  protected $column_name_level;

  public function __construct()
  {
    global $auth;

    if (empty($auth['db_ext']['db_system']))
    {
      $auth['db_ext']['db_system'] = 'mysql';
    }

    // Establish a connection
    $persist = 0;
    $port = isset($auth['db_ext']['db_port']) ? (int)$auth['db_ext']['db_port'] : null;

    $this->db_ext_conn = DBFactory::create(
        $auth['db_ext']['db_system'],
        $auth['db_ext']['db_host'],
        $auth['db_ext']['db_username'],
        $auth['db_ext']['db_password'],
        $auth['db_ext']['db_name'],
        $persist,
        $port
      );

    // Take our own copies of the settings
    $vars = array(
        'db_table',
        'password_format',
        'column_name_username',
        'column_name_display_name',
        'column_name_password',
        'column_name_email',
        'column_name_level',
        'use_md5_passwords'
      );

    foreach ($vars as $var)
    {
      $this->$var = (isset($auth['db_ext'][$var])) ? $auth['db_ext'][$var] : null;
    }

    // Backwards compatibility setting
    if (!isset($this->password_format) && !empty($auth['db_ext']['use_md5_passwords']))
    {
      $this->password_format = 'md5';
    }
  }


  /* authValidateUser($user, $pass)
   *
   * Checks if the specified username/password pair are valid
   *
   * $user  - The user name
   * $pass  - The password
   *
   * Returns:
   *   false    - The pair are invalid or do not exist
   *   string   - The validated username
   */
  public function validateUser($user, $pass)
  {
    $retval = false;

    // syntax_casesensitive_equals() modifies our SQL params array for us.   We need an exact match -
    // MySQL allows trailing spaces when using an '=' comparison, eg 'john' = 'john '

    $sql_params = array();

    $query = "SELECT " . $this->db_ext_conn->quote($this->column_name_password) .
             "FROM " . $this->db_ext_conn->quote($this->db_table) .
             "WHERE " . $this->db_ext_conn->syntax_casesensitive_equals($this->column_name_username,
                                                                        $user,
                                                                        $sql_params);

    $stmt = $this->db_ext_conn->query($query, $sql_params);

    if ($stmt->count() == 1) // force a unique match
    {
      $row = $stmt->next_row();

      switch ($this->password_format)
      {
        case 'md5':
          if (md5($pass) == $row[0])
          {
            $retval = $user;
          }
          break;

        case 'sha1':
          if (sha1($pass) == $row[0])
          {
            $retval = $user;
          }
          break;

        case 'sha256':
          if (hash('sha256', $pass) == $row[0])
          {
            $retval = $user;
          }
          break;

        case 'crypt':
          $recrypt = crypt($pass,$row[0]);
          if ($row[0] == $recrypt)
          {
            $retval = $user;
          }
          break;

        case 'password_hash':
          if (password_verify($pass, $row[0]))
          {
            // Should we call password_needs_rehash() ?
            // Probably not as we may not have UPDATE rights on the external database.
            $retval = $user;
          }
          break;

        default:
          // Otherwise assume plaintext
          if ($pass == $row[0])
          {
            $retval = $user;
          }
          break;
      }
    }

    return $retval;
  }


  public function getUser($username)
  {
    global $auth;

    $sql_params = array();

    $sql = "SELECT *
            FROM " . $this->db_ext_conn->quote($this->db_table) . "
            WHERE " . $this->db_ext_conn->syntax_casesensitive_equals($this->column_name_username,
                                                                      $username,
                                                                      $sql_params) . "
            LIMIT 1";

    $stmt = $this->db_ext_conn->query($sql, $sql_params);

    // The username doesn't exist - return NULL
    if ($stmt->count() === 0)
    {
      return null;
    }

    // The username does exist - return a User object
    $data = $stmt->next_row_keyed();

    $user = new User($username);

    // Set the email address
    if (isset($this->column_name_email) && isset($data[$this->column_name_email]))
    {
      $user->email = $data[$this->column_name_email];
    }

    // Set the display name
    if (isset($this->column_name_display_name) && isset($data[$this->column_name_display_name]))
    {
      $user->display_name = $data[$this->column_name_display_name];
    }

    // Set the level
    // First check whether the user is an admin from the config file
    foreach ($auth['admin'] as $admin)
    {
      if(strcasecmp($username, $admin) === 0)
      {
        $user->level = 2;
        break;
      }
    }

    // If not, check the data from the external db
    if ($user->level != 2)
    {
      // If there's can entry in the db, then use that
      if (isset($this->column_name_level) &&
          ($this->column_name_level !== '') &&
          isset($data[$this->column_name_level]))
      {
        $user->level = $data[$this->column_name_level];
      }
      // Otherwise they're level 1
      else
      {
        $user->level = 1;
      }
    }

    return $user;
  }


  // Return an array of users, indexed by 'username' and 'display_name'
  public function getUsernames()
  {
    if (isset($this->column_name_display_name) && ($this->column_name_display_name !== ''))
    {
      $display_name_column = $this->column_name_display_name;
    }
    else
    {
      $display_name_column = $this->column_name_username;
    }

    $sql = "SELECT " . $this->db_ext_conn->quote($this->column_name_username) . " AS username, ".
                       $this->db_ext_conn->quote($display_name_column) . " AS display_name
            FROM " . $this->db_ext_conn->quote($this->db_table) . " ORDER BY display_name";

    $stmt = $this->db_ext_conn->query($sql);

    return $stmt->all_rows_keyed();
  }
}
