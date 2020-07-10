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
