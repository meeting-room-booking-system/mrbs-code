<?php
namespace MRBS\Session;

class SessionPhp extends SessionWithLogin
{
  
  public static function getUsername()
  {
    if (isset($_SESSION['UserName']) && ($_SESSION['UserName'] !== ''))
    {
      return $_SESSION['UserName'];
    }

    return null;
  }
  
  
  public function logoffUser()
  {
    // Unset the session variables
    session_unset();
    session_destroy();
  }
}
