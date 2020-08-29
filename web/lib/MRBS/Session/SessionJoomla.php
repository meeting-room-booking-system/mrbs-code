<?php
namespace MRBS\Session;

use \MRBS\JFactory;

require_once MRBS_ROOT . '/auth/cms/joomla.inc';


class SessionJoomla extends SessionWithLogin
{

  public function __construct()
  {
    $this->checkTypeMatchesSession();
    parent::__construct();
  }


  public function getCurrentUser()
  {
    return \MRBS\auth()->getUser();
  }


  protected function logonUser($username)
  {
    // Don't need to do anything: the user will have been logged on when the
    // username and password were validated.
  }


  public function logoffUser()
  {
    $mainframe = JFactory::getApplication('site');
    $mainframe->logout();
  }
}
