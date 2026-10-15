<?php
namespace MRBS\Auth;

/**
 * Authentication scheme that uses an external script as the source for user authentication.
 *
 * To use this authentication scheme, set the following things in config.inc.php:
 *
 *     $auth["realm"] = "MRBS"; // Or any other string
 *     $auth["type"] = "ext";
 *     $auth["prog"] = "authenticationprogram"; // The full path to the external script
 *     $auth["params"] = "<...>"; // Parameters to pass to the script; #USERNAME# and #PASSWORD#
 *                                // will be expanded to the values typed by the user, e.g.
 *                                // "/etc/htpasswd #USERNAME# #PASSWORD#"
 *
 * Then, you may configure admin users:
 *
 *     $auth["admin"][] = "username1";
 *     $auth["admin"][] = "username2";
 *
 */
class AuthExt extends Auth
{
  public function validateUser(
    #[\SensitiveParameter]
    ?string $user,
    #[\SensitiveParameter]
    ?string $pass)
  {
    global $auth;

    // Check if we do not have a username/password
    if(!isset($user) || !isset($pass))
    {
      return false;
    }

    // Generate the command line
    $cmd = $auth["prog"] . ' ' . $auth["params"];
    $username_placeholder = '#USERNAME#';
    $password_placeholder = '#PASSWORD#';

    // A placeholder inside a quoted region will break argument handling because escapeshellarg()
    // adds its own quotes. Check both quote types, regardless of OS.
    foreach (["'", '"'] as $q)
    {
      $dq = preg_quote($q, '/');
      $up = preg_quote($username_placeholder, '/');
      $pp = preg_quote($password_placeholder, '/');

      if (preg_match("/{$dq}[^{$dq}]*(?:{$up}|{$pp})[^{$dq}]*{$dq}/", $cmd))
      {
        throw new \Exception(
          "A placeholder in \$auth['params'] appears inside a {$q}-quoted string. " .
          "This will produce a broken or exploitable command line."
        );
      }
    }

    // Use a non-recursive substitution, to prevent being attacked by having a placeholder in one of
    // the user-supplied values.
    $cmd = strtr($cmd, [
      $username_placeholder => escapeshellarg($user),
      $password_placeholder => escapeshellarg($pass),
    ]);

    // Run the program
    exec($cmd, $output, $ret);

    // If it succeeded, return success
    if ($ret == 0)
    {
      return $user;
    }

    // return failure
    return false;
  }

}
