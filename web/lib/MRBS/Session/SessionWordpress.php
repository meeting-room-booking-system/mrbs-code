<?php
declare(strict_types=1);
namespace MRBS\Session;

use MRBS\User;
use function MRBS\auth;

require_once MRBS_ROOT . '/auth/cms/wordpress.inc';


class SessionWordpress extends SessionWithLogin
{

  public function __construct()
  {
    $this->checkTypeMatchesSession();
    parent::__construct();
  }


  public function getCurrentUser() : ?User
  {
    if (!is_user_logged_in())
    {
      return parent::getCurrentUser();
    }

    $mrbs_user = wp_get_current_user();

    return auth()->getUser($mrbs_user->user_login);
  }


  // Can only return a valid username.  If the username and password are not valid it will ask for new ones.
  protected function getValidUser(
    #[\SensitiveParameter]
    ?string $username,
    #[\SensitiveParameter]
    ?string $password) : string
  {
    global $errors; // $errors is a WordPress global

    $credentials = array();
    $credentials['user_login'] = $username;
    $credentials['user_password'] = $password;
    $credentials['remember'] = false;
    $wp_user = wp_signon($credentials);

    if (is_wp_error($wp_user))
    {
      $errors = $wp_user;
      $error_message = apply_filters('login_errors', $wp_user->get_error_message());
      // The WordPress error message contains HTML so don't escape it.
      $this->authGet($this->form['target_url'], $this->form['returl'], $error_message, true);
      exit(); // unnecessary because authGet() exits, but just included for clarity
    }

    return $username;
  }


  protected function logonUser(string $username) : void
  {
    // Don't need to do anything: the user will have been logged on when the
    // username and password were validated.
  }


  public function logoffUser() : void
  {
    wp_logout();
  }

}
