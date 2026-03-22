<?php
declare(strict_types=1);
namespace MRBS\SessionHandler;

trait Cookie
{

  /**
   * A wrapper for `setcookie()` that uses the same values for `$path`, `$domain`, `$secure`, `$httponly` and, if
   * applicable, `$samesite` as for the session cookie.
   */
  public function cookieSet(string $name, string $value, int $expires) : bool
  {
    // Use the same cookie params as for the session cookie.
    $cookie_params = session_get_cookie_params();
    assert(version_compare(MRBS_MIN_PHP_VERSION, '7.3.0', '<'), "The else block can be removed.");
    if (version_compare(PHP_VERSION, '7.3.0', '>='))
    {
      // The new way, allowing 'samesite' to be set
      return setcookie($name, $value, [
        'expires' => $expires,
        'path' => $cookie_params['path'],
        'domain' => $cookie_params['domain'],
        'secure' => $cookie_params['secure'],
        'httponly' => $cookie_params['httponly'],
        'samesite' => $cookie_params['samesite']
      ]);
    }

    // The old way.  'samesite' wasn't available until PHP 7.3.0.
    return setcookie(
      $name,
      $value,
      $expires,
      $cookie_params['path'],
      $cookie_params['domain'],
      $cookie_params['secure'],
      $cookie_params['httponly']
    );
  }

}
