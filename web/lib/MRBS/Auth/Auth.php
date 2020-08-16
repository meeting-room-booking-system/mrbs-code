<?php
namespace MRBS\Auth;

use \MRBS\User;


abstract class Auth
{
  public function getUser($username)
  {
    $user = new User($username);
    $user->display_name = $username;
    $user->level = $this->getDefaultLevel($username);
    $user->email = $this->getDefaultEmail($username);

    return $user;
  }


  // Checks whether validation of a user by email address is possible and allowed.
  public function canValidateByEmail()
  {
    return false;
  }


  // Checks whether the method has a password reset facility
  public function canResetPassword()
  {
    return false;
  }


  // Checks whether the password by reset by supplying an email address
  public function canResetByEmail()
  {
    return false;
  }


  // Validates that the password conforms to the password policy
  // (Ideally this function should also be matched by client-side
  // validation, but unfortunately JavaScript's native support for Unicode
  // pattern matching is very limited.   Would need to be implemented using
  // an add-in library).
  function validatePassword($password)
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


  // Gets the level from the $auth['admin'] array in the config file
  protected function getDefaultLevel($username)
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


  // Gets the default email address using config file settings
  protected function getDefaultEmail($username)
  {
    global $mail_settings;

    if (!isset($username) || $username === '')
    {
      return '';
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


  // Sorts an array of users indexed by 'username' and 'display_name', eg the
  // output of getUsernames().   Sorts by display_name then username.
  protected static function sortUsers(array &$users)
  {
    // Obtain a list of columns
    if (function_exists('array_column'))  // PHP >= 5.5.0
    {
      $username     = array_column($users, 'username');
      $display_name = array_column($users, 'display_name');
    }
    else
    {
      $username = array();
      $display_name = array();

      foreach ($users as $key => $user)
      {
        $username[$key]     = $user['username'];
        $display_name[$key] = $user['display_name'];
      }
    }

    // Sort the data with volume descending, edition ascending
    // Add $data as the last parameter, to sort by the common key
    array_multisort($display_name, SORT_ASC, SORT_LOCALE_STRING | SORT_FLAG_CASE,
                    $username, SORT_ASC, SORT_LOCALE_STRING | SORT_FLAG_CASE,
                    $users);
  }

}
