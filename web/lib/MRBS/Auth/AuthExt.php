<?php
namespace MRBS\Auth;

use MRBS\System;

/**
 * Authentication scheme that uses an external script as the source for user authentication.
 *
 * To use this authentication scheme, set the following things in config.inc.php:
 *
 *     $auth["realm"] = "MRBS"; // Or any other string
 *     $auth["type"] = "ext";
 *     $auth["prog"] = "authenticationprogram"; // The full path to the external script
 *     $auth["params"] = "<...>"; // Space-separated arguments; #USERNAME# and #PASSWORD#
 *                                // are expanded, e.g. "/etc/htpasswd #USERNAME# #PASSWORD#"
 *     // Alternatively, use an array when arguments contain spaces:
 *     $auth["params"] = ["/etc/htpasswd", "#USERNAME#", "#PASSWORD#"];
 *
 * Then, you may configure admin users:
 *
 *     $auth["admin"][] = "username1";
 *     $auth["admin"][] = "username2";
 *
 */
class AuthExt extends Auth
{

  public function __construct()
  {
    if (System::getServerOSFamily() == "Windows")
    {
      // We don't support PHP < 7.4 because exec() is not safe on Windows.
      // And proc_open() is not safe on Windows when running PHP 8.0 or below, PHP 8.1 before 8.1.29,
      // PHP 8.2 before 8.2.20 or PHP 8.3 before 8.3.8. See https://nvd.nist.gov/vuln/detail/cve-2024-5585.
      if (PHP_VERSION_ID < 80129 ||
          (PHP_VERSION_ID >= 80200 && PHP_VERSION_ID < 80220) ||
          (PHP_VERSION_ID >= 80300 && PHP_VERSION_ID < 80308) )
      {
        $message = 'External authentication on Windows is not supported on this version of PHP (' . PHP_VERSION . ').';
        throw new \Exception($message);
      }
    }

    // There's no parent constructor to call.
  }


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

    $params = $auth["params"] ?? '';
    if (is_string($params))
    {
      $params = (trim($params) === '') ? [] : preg_split('/\s+/', trim($params));
    }
    elseif (!is_array($params))
    {
      throw new \InvalidArgumentException('$auth["params"] must be a string or an array of strings.');
    }

    $username_placeholder = '#USERNAME#';
    $password_placeholder = '#PASSWORD#';

    $arguments = [$auth["prog"]];
    foreach ($params as $param)
    {
      if (!is_string($param))
      {
        throw new \InvalidArgumentException('$auth["params"] must contain only strings.');
      }

      $arguments[] = strtr($param, [
        $username_placeholder => $user,
        $password_placeholder => $pass,
      ]);
    }

    if (PHP_VERSION_ID >= 70400)
    {
      // If we're running Windows, we can only get here on certain versions of PHP.  See the constructor.
      $process = proc_open($arguments, [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
      ], $pipes);

      if (!is_resource($process))
      {
        return false;
      }

      fclose($pipes[0]);
      stream_get_contents($pipes[1]);
      fclose($pipes[1]);
      $ret = proc_close($process);
    }
    else
    {
      // We can't get here at all if we're running Windows - see the constructor.
      $cmd = implode(' ', array_map('escapeshellarg', $arguments));
      exec($cmd, $output, $ret);
    }

    // If it succeeded, return success
    if ($ret == 0)
    {
      return $user;
    }

    // return failure
    return false;
  }

}
