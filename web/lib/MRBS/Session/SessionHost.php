<?php
declare(strict_types=1);
namespace MRBS\Session;

use MRBS\User;
use function MRBS\auth;

/*
 * This is a slight variant of session_ip.
 * Session management scheme that uses the DNS name of the computer
 * to identify users and administrators.
 * Anyone who can access the server can make bookings etc.
 *
 * To use this authentication scheme set the following
 * things in config.inc.php:
 *
 * $auth['type']    = 'none';
 * $auth['session'] = 'host';
 *
 * Then, you may configure admin users:
 *
 * $auth['admin'][] = 'DNSname1';
 * $auth['admin'][] = 'DNSname2';
 */


class SessionHost extends Session
{

  // No need to prompt for a name: if no DNSname is returned, the IP address is used
  public function getCurrentUser() : ?User
  {
    global $server;

    if ((!isset($server['REMOTE_ADDR'])) ||
        (!is_string($server['REMOTE_ADDR'])) ||
        (($server['REMOTE_ADDR'] === '')))
    {
      return parent::getCurrentUser();
    }

    return auth()->getUser(gethostbyaddr($server['REMOTE_ADDR']));
  }

}
