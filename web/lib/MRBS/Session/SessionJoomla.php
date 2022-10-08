<?php
namespace MRBS\Session;

use \MRBS\JFactory;
use MRBS\User;

require_once MRBS_ROOT . '/auth/cms/joomla.inc';


class SessionJoomla extends SessionWithLogin
{

  public function __construct()
  {
    $this->checkTypeMatchesSession();
    parent::__construct();
  }


  public function getCurrentUser() : ?User
  {
    return \MRBS\auth()->getCurrentUser();
  }


  protected function logonUser(string $username) : void
  {
    // Don't need to do anything: the user will have been logged on when the
    // username and password were validated.
  }


  public function logoffUser() : void
  {
    $mainframe = JFactory::getApplication('site');
    $mainframe->logout();
  }
}
