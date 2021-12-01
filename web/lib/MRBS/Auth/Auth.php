<?php
namespace MRBS\Auth;

use \MRBS\User;
use function MRBS\get_registrants;
use function MRBS\get_sortable_name;
use function MRBS\strcasecmp_locale;


abstract class Auth
{
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
  abstract public function validateUser(?string $user, ?string $pass);


  protected function getUserFresh(string $username) : ?User
  {
    global $auth;

    // Check the DB first.  If the user isn't there then create a new one
    $user = User::getByName($username, $auth['type']);

    if (!isset($user))
    {
      $user = new User($username);
    }

    return $user;
  }


  public function getUser(string $username) : ?User
  {
    // Cache results for performance as getting user details in
    // most authentication types is expensive.
    static $users = array();

    if (!isset($users[$username]))
    {
      $users[$username] = $this->getUserFresh($username);
    }

    return $users[$username];
  }


  // Checks whether validation of a user by email address is possible and allowed.
  public function canValidateByEmail() : bool
  {
    return false;
  }


  // Checks whether the method has a password reset facility
  public function canResetPassword() : bool
  {
    return false;
  }


  // Checks whether the password by reset by supplying an email address
  public function canResetByEmail() : bool
  {
    return false;
  }


  // Validates that the password conforms to the password policy
  // (Ideally this function should also be matched by client-side
  // validation, but unfortunately JavaScript's native support for Unicode
  // pattern matching is very limited.   Would need to be implemented using
  // an add-in library).
  public function validatePassword(string $password) : bool
  {
    global $pwd_policy;

    if (isset($pwd_policy))
    {
      // Set up regular expressions.  Use p{Ll} instead of [a-z] etc.
      // to make sure accented characters are included
      $pattern = array('alpha'   => '/\p{L}/',
                       'lower'   => '/\p{Ll}/',
                       'upper'   => '/\p{Lu}/',
                       'numeric' => '/\p{N}/',
                       'special' => '/[^\p{L}|\p{N}]/');
      // Check for conformance to each rule
      foreach($pwd_policy as $rule => $value)
      {
        switch($rule)
        {
          case 'length':
            if (\MRBS\utf8_strlen($password) < $pwd_policy[$rule])
            {
              return false;
            }
            break;
          default:
            // turn on Unicode matching
            $pattern[$rule] .= 'u';

            $n = preg_match_all($pattern[$rule], $password, $matches);
            if (($n === false) || ($n < $pwd_policy[$rule]))
            {
              return false;
            }
            break;
        }
      }
    }

    // Everything is OK
    return true;
  }


  // Returns an array of registrants' display names
  public function getRegistrantsDisplayNames (array $entry) : array
  {
    $display_names = array();

    // Only bother getting the names if we don't already know how many there are,
    // or if we know there are definitely some
    if (!isset($entry['n_registered']) || ($entry['n_registered'] > 0))
    {
      $display_names = $this->getRegistrantsDisplayNamesUnsorted($entry['id']);
      usort($display_names, 'MRBS\compare_display_names');
    }

    return $display_names;
  }


  protected function getRegistrantsDisplayNamesUnsorted(int $id) : array
  {
    $display_names = array();
    $registrants = get_registrants($id, false);

    foreach ($registrants as $registrant)
    {
      $registrant_user = $this->getUser($registrant['username']);
      $display_name = (isset($registrant_user)) ? $registrant_user->display_name : $registrant['username'];
      $display_names[] = $display_name;
    }

    return $display_names;
  }


  // Gets the level from the $auth['admin'] array in the config file
  public static function getDefaultLevel(?string $username) : int
  {
    global $auth;

    // User not logged in, user level '0'
    if(!isset($username))
    {
      return 0;
    }

    // Check whether the user is an admin
    if (isset($auth['admin']))
    {
      foreach ($auth['admin'] as $admin)
      {
        if (strcasecmp($username, $admin) === 0)
        {
          return 2;
        }
      }
    }

    // Everybody else is access level '1'
    return 1;
  }


  // Callback function for comparing two users, indexed by 'username' and 'display_name'.
  // Compares first by 'display_name' and then by 'username'
  private static function compareUsers(array $user1, array $user2) : int
  {
    $display_name1 = get_sortable_name($user1['display_name']);
    $display_name2 = get_sortable_name($user2['display_name']);
    $display_name_comparison = strcasecmp_locale($display_name1, $display_name2);

    if ($display_name_comparison === 0)
    {
      return strcasecmp_locale($user1['username'], $user2['username']);
    }

    return $display_name_comparison;
  }


  // Sorts an array of users indexed by 'username' and 'display_name', eg the
  // output of getUsernames().   Sorts by display_name then username.
  protected static function sortUsers(array &$users) : void
  {
    usort($users, [__CLASS__, 'compareUsers']);
  }


  // Check we've got the right session scheme for the type.
  // To be called for those authentication types which require the same
  // session scheme.
  protected function checkSessionMatchesType()
  {
    global $auth;

    if ($auth['session'] !== $auth['type'])
    {
      $class = get_called_class();
      $message = "MRBS configuration error: $class needs \$auth['session'] set to '" . $auth['type'] . "'";
      die($message);
    }
  }
}
