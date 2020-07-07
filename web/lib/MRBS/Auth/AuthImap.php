<?php
namespace MRBS\Auth;

use MRBS\User;

class AuthImap extends Auth
{
  /*
   * Authentication scheme that uses IMAP as the source for user
   * authentication.
   *
   * To use this authentication scheme set the following
   * things in config.inc.php:
   *
   * $auth["realm"] = "MRBS";    // Or any other string
   * $auth["type"]  = "imap";
   *
   * Then, you may configure admin users:
   *
   * $auth["admin"][] = "imapuser1";
   * $auth["admin"][] = "imapuser2";
   */
  public function validateUser($user, $pass)
  {
    global $imap_host, $imap_port;

    $all_imap_hosts = array();
    $all_imap_ports = array();

    // Check if we do not have a username/password
    if (!isset($user) || !isset($pass) || strlen($pass)==0)
    {
      return false;
    }

    // Check that if there is an array of hosts and an array of ports
    // then the number of each is the same
    if (is_array( $imap_host ) &&
        is_array( $imap_port ) &&
        (count($imap_port) != count($imap_host)) )
    {
      return false;
    }

    // Transfer the list of imap hosts to a new value to ensure that
    // an array is always used.
    // If a single value is passed then turn it into an array
    if (is_array( $imap_host ) )
    {
      $all_imap_hosts = $imap_host;
    }
    else
    {
      $all_imap_hosts = array($imap_host);
    }

    // create an array of the port numbers to match the number of
    // hosts if a single port number has been passed.
    if (is_array( $imap_port ) )
    {
      $all_imap_ports = $imap_port;
    }
    else
    {
      foreach($all_imap_hosts as $value)
      {
        $all_imap_ports[] = $imap_port;
      }
    }

    // iterate over all hosts and return if you get a successful login
    foreach( $all_imap_hosts as $idx => $host)
    {
      $error_number = "";
      $error_string = "";

      // Connect to IMAP-server
      $stream = fsockopen( $host, $all_imap_ports[$idx], $error_number,
        $error_string, 15 );
      if ( $stream )
      {
        $response = fgets( $stream, 1024 );
        $logon_str = "a001 LOGIN \"" . self::quote_imap( $user ) . "\" \"" . self::quote_imap( $pass ) . "\"\r\n";
        fputs( $stream, $logon_str );
        $response = fgets( $stream, 1024 );
        if ( substr( $response, 5, 2 ) == 'OK' )
        {
          fputs( $stream, "a002 LOGOUT\r\n" );
          $response = fgets( $stream, 1024 );
          fclose( $stream );
          return $user;
        }
        fputs( $stream, "a002 LOGOUT\r\n" );
        fclose( $stream );
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


  /* quote_imap($str)
   *
   * quote char's into valid IMAP string
   *
   * $str - String to be quoted
   *
   * Returns:
   *   quoted string
   */
  private static function quote_imap($str)
  {
    return preg_replace('/(["\\\\])/', '\\$1', $str);
  }

}
