<?php
namespace MRBS\Auth;

/*
 * Authentication management scheme that delegates everything to a ready
 * configured SimpleSamlPhp instance.  You should use this scheme, along with
 * the session scheme with the same name, if you want your users to
 * authenticate using SAML Single Sign-on.
 *
 * See the session management scheme with the same name for information on
 * how to configure SAML authentication.  This authentication module on its
 * own doesn't work.
 */

use MRBS\User;

class AuthSaml extends Auth
{
  public function __construct()
  {
    $this->checkSessionMatchesType();
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
    $current_username = \MRBS\session()->getUsername();

    if (isset($current_username) && $current_username === $user)
    {
      return $user;
    }

    return false;
  }


  public function getUser($username)
  {
    $user = new User($username);
    $user->level = $this->getLevel($username);
    $user->email = $this->getEmail($username);

    return $user;
  }


  /* getLevel($username)
   *
   * Determines the user's access level
   *
   * It does this by comparing SAML attributes with $auth['saml']['admin']
   * If any attribute matches, the user is considered admin and 2 is returned.
   *
   * If the user is not logged in, or the provided username doesn't match our
   * SAML session, 0 is returned.
   *
   * Otherwise, 1 is returned.
   *
   * $username - The user name
   *
   * Returns:
   *   The user's access level
   */
  private function getLevel($username)
  {
    global $auth;

    $userData = \MRBS\session()->ssp->getAttributes();
    $current_username = \MRBS\session()->getUsername();

    if (isset($current_username) && $current_username === $username)
    {
      foreach ($auth['saml']['admin'] as $attr => $values)
      {
        if (array_key_exists($attr, $userData))
        {
          foreach ($values as $value)
          {
            if (in_array($value, $userData[$attr]))
            {
              return 2;
            }
          }
        }
      }

      return 1;
    }

    return 0;
  }


  // Gets the users e-mail from the SAML attributes.
  // Returns an empty string if no e-mail address was found
  private function getEmail($username)
  {
    global $auth;

    $mailAttr = $auth['saml']['attr']['mail'];
    $userData = \MRBS\session()->ssp->getAttributes();
    $current_username = \MRBS\session()->getUsername();

    if (isset($current_username) && $current_username === $username)
    {
      return array_key_exists($mailAttr, $userData) ? $userData[$mailAttr][0] : '';
    }

    return '';
  }

}
