<?php
namespace MRBS\Session;

require_once MRBS_ROOT . '/auth/cms/wordpress.inc';


class SessionWordpress extends SessionWithLogin
{
  
  public function getUsername()
  {
    if (!is_user_logged_in())
    {
      return null;
    }
    
    $current_user = wp_get_current_user();
    return $current_user->user_login;
  }
  
  
  // Can only return a valid username.  If the username and password are not valid it will ask for new ones.
  protected function getValidUser($username, $password)
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
      \MRBS\print_header();
      $error_message = apply_filters('login_errors', $wp_user->get_error_message());
      // The Wordpress error message contains HTML so don't escape it.
      $this->printLoginForm(\MRBS\this_page(), $this->form['target_url'], $this->form['returl'], $error_message, $raw=true);
      exit();
    }
    
    return $username;
  }
  
  
  public function logonUser($username)
  {
    // Don't need to do anything: the user will have been logged on when the
    // username and password were validated.
  }
  
  
  public function logoffUser()
  {
    wp_logout();
  }
  
}
