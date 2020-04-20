<?php
namespace MRBS\Session;

/*
 * Session management scheme that uses IP addresses to identify users.
 * Anyone who can access the server can make bookings.
 * Administrators are also identified by their IP address.
 *
 * To use this authentication scheme set the following
 * things in config.inc.php:
 *
 * $auth['type']    = 'none';
 * $auth['session'] = 'ip';
 *
 * Then, you may configure admin users:
 *
 * $auth['admin'][] = '127.0.0.1'; // Local host = the server you're running on
 * $auth['admin'][] = '192.168.0.1';
 */
 
 
class SessionIp extends SessionWithoutLogin
{

  // No need to prompt for a name - IP address always there
  public function getCurrentUser()
  {
    global $server;
    
    if ((!isset($server['REMOTE_ADDR'])) ||
        (!is_string($server['REMOTE_ADDR'])) ||
        (($server['REMOTE_ADDR'] === '')))
    {
      return null;
    } 

    return \MRBS\auth()->getUser($server['REMOTE_ADDR']);
  }
  
}
