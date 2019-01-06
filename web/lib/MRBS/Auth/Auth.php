<?php
namespace MRBS\Auth;

use \MRBS\User;


abstract class Auth
{
  public function getUser($username)
  {
    $user = new User($username);
    $user->display_name = $username;
    $user->email = self::getDefaultEmail($username);
    
    return $user;
  }
  
  
  // Gets the default email address for $username.   Returns null if one can't be found
  private static function getDefaultEmail($username)
  {
    global $mail_settings;
    
    if (!isset($username) || $username === '')
    {
      return null;
    }
    
    $email = $username;
    
    // Remove the suffix, if there is one
    if (isset($mail_settings['username_suffix']) && ($mail_settings['username_suffix'] !== ''))
    {
      $suffix = $mail_settings['username_suffix'];
      if (substr($email, -strlen($suffix)) === $suffix)
      {
        $email = substr($email, 0, -strlen($suffix));
      }
    }
    
    // Add on the domain, if there is one
    if (isset($mail_settings['domain']) && ($mail_settings['domain'] !== ''))
    {
      // Trim any leading '@' character. Older versions of MRBS required the '@' character
      // to be included in $mail_settings['domain'], and we still allow this for backwards
      // compatibility.
      $domain = ltrim($mail_settings['domain'], '@');
      $email .= '@' . $domain;
    }
    
    return $email;
  }
}