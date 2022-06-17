<?php
namespace MRBS\Auth;

use \MRBS\User;
use function MRBS\format_compound_name;
use function MRBS\get_registrants;
use function MRBS\get_sortable_name;
use function MRBS\get_vocab;
use function MRBS\in_arrayi;
use function MRBS\session;
use function MRBS\strcasecmp_locale;


abstract class Auth
{

  protected $getDisplayNamesAtOnce = true;

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

    // Use array_key_exists() rather than isset() in case the value is NULL
    if (!array_key_exists($username, $users))
    {
      // Check to see if this is the current user.  If it is, then we
      // can save ourselves a potentially expensive operation.
      // But we can only do this if we are not being called by getCurrentUser(), which
      // some session schemes do, as otherwise we'll end up with an infinite recursion.
      // TODO: is there a better way of handling this??
      $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
      $backtrace_functions = array_column($backtrace, 'function');
      if (!in_array('getCurrentUser', $backtrace_functions))
      {
        $mrbs_user = session()->getCurrentUser();
      }

      if (isset($mrbs_user) && ($mrbs_user->username === $username))
      {
        $user = $mrbs_user;
      }
      else
      {
        $user = $this->getUserFresh($username);
        // Make sure we've got a sensible display name
        if (isset($user) &&
            (!isset($user->display_name) || ($user->display_name === '')))
        {
          $user->display_name = $user->username;
        }
      }
      $users[$username] = $user;
    }

    return $users[$username];
  }


  public function getDisplayName(?string $username) : ?string
  {
    global $get_display_names_all_at_once;

    static $display_names = null;  // Cache for performance

    // Easy case 1: $username is null
    if (!isset($username))
    {
      return null;
    }

    // Easy case 2: it's the current user
    $mrbs_user = session()->getCurrentUser();
    if (isset($mrbs_user) && ($mrbs_user->username === $username))
    {
      return $mrbs_user->display_name;
    }

    // If we can (and want to) then get all the usernames at the same time.  It's
    // much faster than getting them one at a time when they are stored externally.
    if ($get_display_names_all_at_once && method_exists($this, 'getUsernames'))
    {
      if (!isset($display_names))
      {
        $display_names = array_column($this->getUsernames(), 'display_name', 'username');
      }
    }
    // Otherwise just get them one at a time
    else
    {
      if (!isset($display_names[$username]))
      {
        $user = $this->getUser($username);
        $display_names[$username] = (isset($user)) ? $user->display_name : $username;
      }
    }

    if (isset($display_names[$username]) && ($display_names[$username] !== ''))
    {
      return $display_names[$username];
    }

    return $username;
  }


  // Checks whether validation of a user by email address is possible and allowed.
  public function canValidateByEmail() : bool
  {
    return false;
  }


  // Checks whether validation of a user by username is possible and allowed.
  public function canValidateByUsername() : bool
  {
    return true;
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
  public function getRegistrantsDisplayNames (array $entry, bool $with_registered_by=false, bool $with_registrant_username=false) : array
  {
    $display_names = array();

    // Only bother getting the names if we don't already know how many there are,
    // or if we know there are definitely some
    if (!isset($entry['n_registered']) || ($entry['n_registered'] > 0))
    {
      $display_names = $this->getRegistrantsDisplayNamesUnsorted($entry['id'], $with_registered_by, $with_registrant_username);
      usort($display_names, 'MRBS\compare_display_names');
    }

    return $display_names;
  }


  protected function getRegistrantsDisplayNamesUnsorted(int $id, bool $with_registered_by, bool $with_registrant_username) : array
  {
    $display_names = array();
    $registrants = get_registrants($id, false);

    foreach ($registrants as $registrant)
    {
      $display_name = $this->getDisplayName($registrant['username']);
      // Add in the name of the person who registered this user, if required and if different.
      if ($with_registered_by &&
        isset($registrant['create_by']) &&
        ($registrant['create_by'] !== $registrant['username']))
      {
        if ($with_registrant_username)
        {
          $display_names[] = get_vocab("registrant_username_and_registered_by",
                                       $registrant['username'],
                                       $display_name,
                                       $this->getDisplayName($registrant['create_by']));
        }
        else
        {
          $display_names[] = get_vocab("registrant_registered_by",
                                       $display_name,
                                       $this->getDisplayName($registrant['create_by']));
        }
      }
      else
      {
        $display_names[] = ($with_registrant_username) ? format_compound_name($registrant['username'], $display_name) : $display_name;
      }
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

    // Check whether the user is an admin; if not they are level 1.
    return (isset($auth['admin']) && in_arrayi($username, $auth['admin'])) ? 2 : 1;
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


  // Writes the debug message to the error log together with the calling method and line number.
  // It assumes that it has been called by a debug method.
  protected static function logDebugMessage(string $message) : void
  {
    // Need to go three levels back to get the real calling method.
    list( , $called, $caller) = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
    error_log(
        "[MRBS DEBUG] " .
        $caller['class'] . $caller['type'] . $caller['function'] . '(' . $called['line'] . ')' .
        ": $message"
      );
  }
}
