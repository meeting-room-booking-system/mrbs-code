<?php
declare(strict_types=1);
namespace MRBS\Auth;

use MRBS\DBFactory;
use MRBS\User;
use ValueError;

class AuthDbExt extends Auth
{
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

    // Take our own copies of the settings
    $vars = array(
        'db_table',
        'password_format',
        'column_name_username',
        'column_name_display_name',
        'column_name_password',
        'column_name_email',
        'column_name_level'
      );

    foreach ($vars as $var)
    {
      $this->$var = $auth['db_ext'][$var] ?? null;
    }

    // Backwards compatibility setting
    if (!isset($this->password_format) && !empty($auth['db_ext']['use_md5_passwords']))
    {
      $this->password_format = 'md5';
    }
  }


  /* validateUser($user, $pass)
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
  public function validateUser(
    #[\SensitiveParameter]
    ?string $user,
    #[\SensitiveParameter]
    ?string $pass)
  {
    // syntax_casesensitive_equals() modifies our SQL params array for us.   We need an exact match -
    // MySQL allows trailing spaces when using an '=' comparison, eg 'john' = 'john '

    $sql_params = array();

    $query = "SELECT " . $this->connection()->quote($this->column_name_password) .
               "FROM " . $this->connection()->quote($this->db_table) .
              "WHERE " . $this->connection()->syntax_casesensitive_equals($this->column_name_username,
                                                                          $user,
                                                                          $sql_params);

    $stmt = $this->connection()->query($query, $sql_params);

    // Check whether (a) there's just one result and (b) that password matches
    return (($stmt->count() === 1) && $this->password_check($pass, $stmt->next_row()[0])) ? $user : false;
  }


  // Checks that a password matches a hash
  protected function password_check(string $password, string $hash) : bool
  {
    switch ($this->password_format)
    {
      case 'crypt':
      case 'password_hash':
        // Don't call password_needs_rehash() as (a) we may not have UPDATE rights on the external
        // database and (b) whether the password needs to be updated will depend on the PHP version
        // on the external system, not this one.
        return (password_verify($password, $hash));
        break;
      case 'plaintext':
        return hash_equals($hash, $password);
        break;
      default:
        // Check we've got a valid hashing algorithm.  From PHP 8.0.0 hash() will do this.
        if ((version_compare(PHP_VERSION, '8.0.0') < 0) &&
            !in_array($this->password_format, hash_algos()))
        {
          throw new ValueError("'$this->password_format' is not a valid hashing algorithm");
        }
        return hash_equals($hash, hash($this->password_format, $password));
        break;
    }
  }


  protected function getUserFresh(string $username) : ?User
  {
    $sql_params = array();

    // Only retrieve the columns we need (a) to minimise the query and (b) to avoid
    // sending unnecessary information unencrypted over the internet (Remote SQL is
    // usually unencrypted).
    $columns = array();

    $properties = array(
        'column_name_display_name',
        'column_name_email',
        'column_name_level'
      );

    foreach ($properties as $property)
    {
      if (isset($this->$property))
      {
        $columns[] = $this->$property;
      }
    }

    $sql = "SELECT " . implode(', ', array_map(array($this->connection(), 'quote'), $columns)) . "
              FROM " . $this->connection()->quote($this->db_table) . "
             WHERE " . $this->connection()->syntax_casesensitive_equals($this->column_name_username,
                                                                       $username,
                                                                       $sql_params) . "
             LIMIT 1";

    $stmt = $this->connection()->query($sql, $sql_params);

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
    // First get the default level.  Any admins defined in the config
    // file override settings in the external database.
    $user->level = $this->getDefaultLevel($username);

    // Then if they are not an admin get their level from the external db
    if ($user->level < 2)
    {
      // If there's can entry in the db, then use that
      if (isset($this->column_name_level) &&
          ($this->column_name_level !== '') &&
          isset($data[$this->column_name_level]))
      {
        $user->level = $data[$this->column_name_level];
      }
    }

    // Then set the remaining properties. (We don't set all the properties from
    // $data initially because we want to preserve the default values if we don't
    // have data for the four important properties.)
    // (Note that normally there won't be any extra properties because we have
    // specified above the columns that we want, but this code is here so that extra
    // columns can be added if required.)
    foreach ($data as $key => $value)
    {
      if (!property_exists($user, $key))
      {
        $user->$key = $value;
      }
    }

    return $user;
  }


  // Return an array of users, indexed by 'username' and 'display_name'
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

    $sql = "SELECT " . $this->connection()->quote($this->column_name_username) . " AS username, ".
                       $this->connection()->quote($display_name_column) . " AS display_name
              FROM " . $this->connection()->quote($this->db_table) . " ORDER BY display_name";

    $res = $this->connection()->query($sql);

    $users =  $res->all_rows_keyed();
    self::sortUsers($users);

    return $users;
  }


  // Returns a database connection
  // The connection isn't established in the constructor, but here only when it's really
  // needed as it can be expensive establishing a remote connection.  For example
  // method_exists(auth(), 'method') will end up calling the constructor, but a connection
  // isn't needed just for method_exists().
  protected function connection()
  {
    global $auth;

    static $connection = null;

    if (!isset($connection))
    {
      if (empty($auth['db_ext']['db_system']))
      {
        $auth['db_ext']['db_system'] = 'mysql';
      }

      // Establish a connection
      $port = isset($auth['db_ext']['db_port']) ? (int) $auth['db_ext']['db_port'] : null;

      $connection = DBFactory::create(
          $auth['db_ext']['db_system'],
          $auth['db_ext']['db_host'],
          $auth['db_ext']['db_username'],
          $auth['db_ext']['db_password'],
          $auth['db_ext']['db_name'],
          false,
          $port
        );
    }

    return $connection;
  }
}
