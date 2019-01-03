<?php
namespace MRBS\Session;

class SessionPhp extends SessionWithLogin
{
  
  public function getUsername()
  {
    if (isset($_SESSION['UserName']) && ($_SESSION['UserName'] !== ''))
    {
      return $_SESSION['UserName'];
    }

    return null;
  }
  
  
  protected function logonUser($username)
  {
    // As a defence against session fixation, regenerate
    // the session id and delete the old session.
    session_regenerate_id(true);
    $_SESSION['UserName'] = $username;
    
    // Problems have been reported on Windows IIS with session data not being
    // written out without a call to session_write_close()
    session_write_close();
  }
  
  
  protected function logoffUser()
  {
    // Unset the session variables
    session_unset();
    session_destroy();
    
    // Problems have been reported on Windows IIS with session data not being
    // written out without a call to session_write_close(). [Is this necessary
    // after session_destroy() ??]
    session_write_close();
  }
}
