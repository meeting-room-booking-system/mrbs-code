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
    $current_username = \MRBS\session()->getUsername();

    if (isset($current_username) && $current_username === $user)
    {
      return $user;
    }

    return false;
  }


  protected function getUserFresh(string $username) : ?User
  {
    $user = new User($username);
    $user->level = $this->getLevel($username);
    $user->email = $this->getEmail($username);
    $user->display_name = $this->getUserDisplayName($username);

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
  private function getLevel(string $username) : int
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

      if (isset($auth['saml']['user']))
      {
        foreach ($auth['saml']['user'] as $attr => $values)
        {
          if (array_key_exists($attr, $userData))
          {
            foreach ($values as $value)
            {
              if (in_array($value, $userData[$attr]))
              {
                return 1;
              }
            }
          }
        }
        return  0;
      }

      return 1;
    }

    return 0;
  }


  // Gets the users e-mail from the SAML attributes.
  // Returns an empty string if no e-mail address was found
  private function getEmail(string $username) : string
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

  // Gets the users displayname from the SAML attributes.
  // Returns an empty string if no givenName and surname was found
  private function getUserDisplayName(string $username) : string
  {
    global $auth;

    $givenNameAttr = $auth['saml']['attr']['givenName'];
    $surnameAttr = $auth['saml']['attr']['surname'];
    $userData = \MRBS\session()->ssp->getAttributes();
    $current_username = \MRBS\session()->getUsername();

    if (isset($current_username) && $current_username === $username)
    {
      $givenName = array_key_exists($givenNameAttr, $userData) ? $userData[$givenNameAttr][0] : '';
      $surname = array_key_exists($surnameAttr, $userData) ? $userData[$surnameAttr][0] : '';
      return trim($givenName . ' ' . $surname);
    }

    return '';
  }

}
