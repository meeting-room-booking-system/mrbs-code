<?php
namespace MRBS\Auth;

/*
 * Authentication scheme that uses POP3 as the source for user
 * authentication.
 *
 * To use this authentication scheme set the following
 * things in config.inc.php:
 *
 * $auth["realm"] = "MRBS";    // Or any other string
 * $auth["type"]  = "pop3";
 *
 * Then, you may configure admin users:
 *
 * $auth["admin"][] = "pop3user1";
 * $auth["admin"][] = "pop3user2";
 */

class AuthPop3 extends Auth
{
  /* authValidateUser($user, $pass)
   *
   * Checks if the specified username/password pair are valid
   *
   * $user  - The user name
   * $pass  - The password
   *
   * Returns:
   *   false    - The pair are invalid or do not exist
   *   string   - The validated username
   */
  public function validateUser($user, $pass)
  {
    global $auth;
    global $pop3_host;
    global $pop3_port;

    $match = array();
    $shared_secret = '';
    $all_pop3_hosts = array();
    $all_pop3_ports = array();

    // Check if we do not have a username/password
    if (!isset($user) || !isset($pass) || strlen($pass)==0)
    {
      return false;
    }

    // Check that if there is an array of hosts and an array of ports
    // then the number of each is the same
    if (is_array($pop3_host) && is_array($pop3_port) &&
        count($pop3_port) != count($pop3_host) )
    {
      return false;
    }

    // Transfer the list of pop3 hosts to an new value to ensure that
    // an array is always used.
    // If a single value is passed then turn it into an array
    if (is_array($pop3_host))
    {
      $all_pop3_hosts = $pop3_host;
    }
    else
    {
      $all_pop3_hosts = array($pop3_host);
    }

    // create an array of the port numbers to match the number of
    // hosts if a single port number has been passed.
    if (is_array($pop3_port))
    {
      $all_pop3_ports = $pop3_port;
    }
    else
    {
      foreach ($all_pop3_hosts as $value)
      {
        $all_pop3_ports[] = $pop3_port;
      }
    }

    // iterate over all hosts and return if you get a successful login
    foreach ($all_pop3_hosts as $idx => $host)
    {
      $error_number = "";
      $error_string = "";

      // Connect to POP3 server
      $stream = fsockopen( $host, $all_pop3_ports[$idx], $error_number,
        $error_string, 15 );
      $response = fgets( $stream, 1024 );

      // first we try to use APOP, and then if that fails we fall back to
      // traditional stuff

      // get the shared secret ( something on the greeting line that looks like <XXXX> )
      if ( preg_match( '/(<[^>]*>)/', $response, $match ) )
      {
        $shared_secret = $match[0];
      }

      // if we have a shared secret then try APOP
      if ($shared_secret)
      {
        $md5_token = md5("$shared_secret$pass");

        if ($stream)
        {
          $auth_string = "APOP $user $md5_token\r\n";
          fputs( $stream, $auth_string );

          // read the response. if it's an OK then we're authenticated
          $response = fgets( $stream, 1024 );
          if( substr( $response, 0, 3 ) == '+OK' )
          {
            fputs( $stream, "QUIT\r\n" );
            return $user;
          }
        }
      } // end shared secret if

      // if we've still not authenticated then try using traditional methods
      // need to reconnect if we tried APOP
      if ($shared_secret)
      {
        $stream = fsockopen( $host, $all_pop3_ports[$idx], $error_number,
          $error_string, 15 );
        $response = fgets( $stream, 1024 );
      }

      // send standard POP3 USER and PASS commands
      if ( $stream )
      {
        fputs( $stream, "USER $user\r\n" );
        $response = fgets( $stream, 1024 );
        if( substr( $response, 0, 3 ) == '+OK' )
        {
          fputs( $stream, "PASS $pass\r\n" );
          $response = fgets( $stream, 1024 );
          if ( substr( $response, 0, 3 ) == '+OK' )
          {
            return $user;
          }
        }
        fputs( $stream, "QUIT\r\n" );
      }
    }

    // return failure
    return false;
  }


  // Checks whether validation of a user by email address is possible and allowed.
  public function canValidateByEmail()
  {
    return true;
  }

}
