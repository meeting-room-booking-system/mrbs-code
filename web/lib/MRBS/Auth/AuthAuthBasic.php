<?php
namespace MRBS\Auth;

use Exception;
use WhiteHat101\Crypt\APR1_MD5;

/**
 * Authentication scheme that uses an Apache "auth basic" password file for user authentication.
 *
 * To use this authentication scheme, set the following things in config.inc.php:
 *
 *     $auth["type"] = "auth_basic";
 *     $auth["auth_basic"]["passwd_file"] = "/etc/httpd/htpasswd"; // Example
 *
 * Then, you may configure admin users:
 *
 *     $auth["admin"][] = "username1";
 *     $auth["admin"][] = "username2";
 */
class AuthAuthBasic extends Auth
{
  private $passwd_file;

  public function __construct()
  {
    global $auth;

    if (!isset($auth['auth_basic']['passwd_file']))
    {
      throw new Exception("auth_basic: passwd file not specified");
    }

    $this->passwd_file = $auth['auth_basic']['passwd_file'];
    if (!is_readable($this->passwd_file))
    {
      throw new Exception("auth_basic: passwd file not readable");
    }
  }


  public function validateUser(
    #[\SensitiveParameter]
    ?string $user,
    #[\SensitiveParameter]
    ?string $pass)
  {
    // Check if we do not have a username/password
    if(!isset($user) || !isset($pass))
    {
      return false;
    }

    // Open the password file
    if (false === ($handle = fopen($this->passwd_file, 'r')))
    {
      // Shouldn't happen because we've already checked that it's readable in the constructor
      trigger_error("Could not open '$this->passwd_file' for reading");
      return false;
    }

    // Find this user and then verify the password against the hash
    while (false !== ($line = fgets($handle)))
    {
      $line = trim($line);  // Get rid of the EOL
      list($username, $hashed) = explode(':', $line);
      if ($username === $user)
      {
        return (self::passwordVerify($pass, $hashed)) ? $user : false;
      }
    }

    return false;
  }


  /**
   * Verifies that a password matches a hash.  Combines support for APR1-MD5 with PHP's standard `password_verify()`.
   */
  private static function passwordVerify(
    #[\SensitiveParameter]
    $password,
    $hash
  ) : bool
  {
    // If the hash is using an algorithm that PHP password_verify() can cope with, then use standard password_verify().
    if (password_get_info($hash)['algo'])
    {
      return password_verify($password, $hash);
    }
    // Otherwise, if it's using Apache's APR1-MD5 algorithm then we can cope with that
    if (str_starts_with($hash, '$apr1$'))
    {
      return APR1_MD5::check($password, $hash);
    }
    trigger_error("Unsupported algorithm in hash '$hash'", E_USER_NOTICE);
    return false;
  }

}
