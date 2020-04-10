<?php
namespace MRBS\Session;

use \MRBS\JFactory;

require_once MRBS_ROOT . '/auth/cms/joomla.inc';


class SessionJoomla extends SessionWithLogin
{

  public function getCurrentUser()
  {
    return \MRBS\auth()->getUser();
  }


  public function getUsername()
  {
    return JFactory::getUser()->username;
  }


  protected function logonUser($username)
  {
    // Don't need to do anything: the user will have been logged on when the
    // username and password were validated.
  }


  protected function logoffUser()
  {
    $mainframe = JFactory::getApplication('site');
    $mainframe->logout();
  }
}
