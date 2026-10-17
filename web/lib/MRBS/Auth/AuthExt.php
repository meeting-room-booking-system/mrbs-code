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
      if (\MRBS\System::getServerOSFamily() == "Windows")
      {
        throw new \Exception('External authentication using exec() is not supported on Windows.');
      }

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
