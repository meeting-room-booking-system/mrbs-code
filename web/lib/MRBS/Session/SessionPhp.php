<?php
namespace MRBS\Session;


// Uses PHP's built-in session handling

class SessionPhp extends SessionWithLogin
{
  
  public function __construct()
  {
    global $auth;
    
    parent::__construct();
    
    // Check to see if we've been inactive for longer than allowed and if so logout
    // the user
    if (!empty($auth['session_php']['inactivity_expire_time']))
    {
      if (isset($_SESSION['LastActivity']) &&
          ((time() - $_SESSION['LastActivity']) > $auth['session_php']['inactivity_expire_time']))
      {
        $this->logoffUser();
      }
      // Ajax requests don't count as activity, unless it's the special Ajax request used
      // to record client side activity.
      $activity = \MRBS\get_form_var('activity', 'int');
      if ($activity || !\MRBS\is_ajax() || !isset($_SESSION['LastActivity']))
      {
        $_SESSION['LastActivity'] = time();
      }
    }
  }
  
  
  public function getCurrentUser()
  {
    return (isset($_SESSION['user'])) ? $_SESSION['user'] : null;
  }
  
  
  protected function logonUser($username)
  {
    $user = \MRBS\auth()->getUser($username);
    
    // As a defence against session fixation, regenerate
    // the session id and delete the old session.
    session_regenerate_id(true);
    $_SESSION['user'] = $user;
    
    // Problems have been reported on Windows IIS with session data not being
    // written out without a call to session_write_close()
    session_write_close();
  }
  
  
  public function logoffUser()
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
