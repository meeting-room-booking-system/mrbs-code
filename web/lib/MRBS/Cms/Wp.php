<?php
declare(strict_types=1);
namespace MRBS\Cms;

global $month, $theme, $auth;

use WP_User;
use function apply_filters;
use function get_bloginfo;
use function get_user_by;
use function get_users;
use function is_user_logged_in;
use function is_wp_error;
use function wp_authenticate;
use function wp_get_current_user;
use function wp_logout;
use function wp_signon;



// WordPress changes the default timezone, so save it and then restore it later.
$mrbs_timezone = date_default_timezone_get();

// WordPress shares some global variables with MRBS, so we need to save the MRBS
// ones before loading WP and then restore them afterwards.
$mrbs_month = $month ?? null;
$mrbs_theme = $theme ?? null;

// Load WordPress
define('WP_USE_THEMES', false);
require_once MRBS_ROOT . '/'. $auth['wordpress']['rel_path'] . '/wp-load.php';

// Restore the MRBS settings
$theme = $mrbs_theme;
$month = $mrbs_month;
date_default_timezone_set($mrbs_timezone);


/**
 * A Helper class for WordPress functions that allows the loading of WordPress to be postponed
 * until the functions are actually called.
 */
class Wp
{
  /**
   * Calls the callback functions that have been added to a filter hook.
   *
   * @see https://developer.wordpress.org/reference/functions/apply_filters/
   *
   * @return mixed
   */
  public static function apply_filters(string $hook_name, $value, ...$args)
  {
    return apply_filters($hook_name, $args, $value);
  }


  /**
   * Retrieves information about the current site.
   *
   * @see https://developer.wordpress.org/reference/functions/get_bloginfo/
   */
  public static function get_bloginfo(string $show = '', string $filter = 'raw') : string
  {
    return get_bloginfo($show, $filter);
  }


  /**
   * Retrieves user info by a given field.
   *
   * @see https://developer.wordpress.org/reference/functions/get_user_by/
   *
   * @param int|string $value
   * @return WP_User|false
   */
  public static function get_user_by(string $field, $value)
  {
    return get_user_by($field, $value);
  }


  /**
   * Retrieves list of users matching criteria.
   *
   * @see https://developer.wordpress.org/reference/functions/get_users/
   */
  public static function get_users(array $args = array()) : array
  {
    return get_users($args);
  }


  /**
   * Determines whether the current visitor is a logged in user.
   *
   * @see https://developer.wordpress.org/reference/functions/is_user_logged_in/
   */
  public static function is_user_logged_in(): bool
  {
    return is_user_logged_in();
  }


  /**
   * Checks whether the given variable is a WordPress Error.
   *
   * @see https://developer.wordpress.org/reference/functions/is_wp_error/
   *
   * @param mixed $thing
   * @return bool
   */
  public static function is_wp_error(mixed $thing): bool
  {
    return is_wp_error($thing);
  }


  /**
   * Authenticates a user, confirming the login credentials are valid.
   *
   * @see https://developer.wordpress.org/reference/functions/wp_authenticate/
   *
   * @return WP_User|WP_Error
   */
  public static function wp_authenticate(string $username, string $password) : object
  {
    return wp_authenticate($username, $password);
  }


  /**
   * Retrieves the current user object.
   *
   * @see https://developer.wordpress.org/reference/functions/wp_get_current_user/
   */
  public static function wp_get_current_user(): WP_User
  {
    return wp_get_current_user();
  }


  /**
   * Logs the current user out.
   *
   * @see https://developer.wordpress.org/reference/functions/wp_logout/
   */
  public static function wp_logout()
  {
    return wp_logout();
  }


  /**
   * Authenticates and logs a user in with ‘remember’ capability.
   *
   * @see https://developer.wordpress.org/reference/functions/wp_signon/
   *
   * @param array{user_login: string, user_password: string, remember: bool} $credentials
   * @param string|bool $secure_cookie Whether to use secure cookie.
   * @return WP_User|WP_Error
   */
  public static function wp_signon(array $credentials = array(), $secure_cookie = ''): object
  {
    return wp_signon($credentials, $secure_cookie);
  }

}
