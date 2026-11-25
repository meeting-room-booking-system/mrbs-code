<?php
declare(strict_types=1);
namespace MRBS\Auth;

use MRBS\GoogleLogin;
use function MRBS\_tbl;

class AuthGoogle extends AuthDb
{
  public function __construct()
  {
    // Die with a clear message if $auth['session'] is not 'google'
    $this->checkSessionMatchesType();
    parent::__construct();
  }

  /**
   * Validate a username/email and password pair, excluding users restricted to Google sign-in.
   *
   * @param string|null $user Username or email address
   * @param string|null $pass Password
   * @return string|false
   */
  public function validateUser(
    #[\SensitiveParameter]
    ?string $user,
    #[\SensitiveParameter]
    ?string $pass)
  {
    if (!isset($user) || !isset($pass))
    {
      return false;
    }

    $valid_usernames = [];

    if (($valid_username = $this->validateUsername($user, $pass)) !== false)
    {
      $valid_usernames[] = $valid_username;
    }

    $valid_usernames = array_merge($valid_usernames, $this->validateEmail($user, $pass));
    $valid_usernames = array_unique($valid_usernames);

    // Exclude accounts that can only sign in via Google
    $valid_usernames = array_values(array_filter($valid_usernames, function ($username) {
      return !GoogleLogin::isGoogleOnlyUser($username);
    }));

    if (count($valid_usernames) === 1)
    {
      $result = $valid_usernames[0];
      $sql = "UPDATE " . _tbl('users') . "
                 SET last_login=?, timestamp=timestamp
               WHERE name=?";
      $this->connection()->command($sql, [time(), $result]);
      return $result;
    }

    return false;
  }

  /**
   * Prevent sending a password reset link if the account (or all accounts matching
   * the email address) is restricted to Google sign-in.
   */
  public function requestPassword(?string $login) : bool
  {
    if (!isset($login) || ($login === ''))
    {
      return false;
    }

    $candidate_usernames = [];

    // 1. Lookup by username
    $user = $this->getUserFresh($login);
    if (isset($user))
    {
      $candidate_usernames[] = $user->username;
    }

    // 2. Lookup by email (if enabled)
    if ($this->canValidateByEmail())
    {
      foreach ($this->getUsersByEmail($login) as $row)
      {
        if (isset($row['name']))
        {
          $candidate_usernames[] = $row['name'];
        }
      }
    }

    $candidate_usernames = array_unique($candidate_usernames);

    // Block password reset if all matching candidates are Google-only users
    if (!empty($candidate_usernames))
    {
      $non_google_users = array_filter($candidate_usernames, function ($username) {
        return !GoogleLogin::isGoogleOnlyUser($username);
      });

      if (empty($non_google_users))
      {
        return false;
      }
    }

    // Delegate token generation and email sending to AuthDb
    return parent::requestPassword($login);
  }

  /**
   * Invalidate password reset tokens for Google-only users.
   */
  public function isValidReset(?string $user, ?string $key) : bool
  {
    if (!isset($user) || ($user === '') || GoogleLogin::isGoogleOnlyUser($user))
    {
      return false;
    }

    return parent::isValidReset($user, $key);
  }
}
