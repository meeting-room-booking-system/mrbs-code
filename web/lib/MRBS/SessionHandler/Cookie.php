<?php
declare(strict_types=1);
namespace MRBS\SessionHandler;

use MRBS\Utf8\Utf8String;

class Cookie
{

  /**
   * A wrapper for `setcookie()` that uses the same values for `$path`, `$domain`, `$secure`, `$httponly` and, if
   * applicable, `$samesite` as for the session cookie.
   */
  public static function set(string $name, string $value, int $expires) : bool
  {
    // Use the same cookie params as for the session cookie.
    $cookie_params = session_get_cookie_params();
    assert(version_compare(MRBS_MIN_PHP_VERSION, '7.3.0', '<'), "The else block can be removed.");
    if (version_compare(PHP_VERSION, '7.3.0', '>='))
    {
      // The new way, allowing 'samesite' to be set
      $result = setcookie($name, $value, [
        'expires' => $expires,
        'path' => $cookie_params['path'],
        'domain' => $cookie_params['domain'],
        'secure' => $cookie_params['secure'],
        'httponly' => $cookie_params['httponly'],
        'samesite' => $cookie_params['samesite']
      ]);
    }
    else
    {
      // The old way.  'samesite' wasn't available until PHP 7.3.0.
      $result = setcookie(
        $name,
        $value,
        $expires,
        $cookie_params['path'],
        $cookie_params['domain'],
        $cookie_params['secure'],
        $cookie_params['httponly']
      );
    }

    self::checkCookieSizes();
    return $result;
  }


  public static function delete(string $name) : bool
  {
    return self::set($name, '', time() - 42000);
  }


  /**
   * Check the sizes of the cookies that have been set and trigger a warning if they are too large.
   *
   * Browsers will reject cookies that are too large, although `setcookie()` will still set them and return TRUE.
   * What's considered too large depends on the browser. Some browsers just count the length of the name and value
   * of the cookie; others also count the length of the options (expires, path, domain, secure, httponly, samesite).
   * To be on the safe side, the options are included in the calculation.
   */
  private static function checkCookieSizes() : void
  {
    $max_size = 4096;
    $headers = headers_list();

    foreach ($headers as $header)
    {
      if (preg_match('/^Set-Cookie:\s*([^=]*)=(.*$)/', $header, $matches))
      {
        if ((string)(new Utf8String($matches[1] . '=' . $matches[2]))->byteCount() > $max_size)
        {
          trigger_error("Cookie '" . $matches[1] . "' exceeds $max_size bytes", E_USER_WARNING);
        }
      }
    }
  }

}
