<?php
namespace MRBS\Session;

use \MRBS\JFactory;

require_once MRBS_ROOT . '/auth/cms/joomla.inc';


class SessionJoomla extends SessionWithLogin
{
  
  public function getUsername()
  {
    return JFactory::getUser()->username;
  }
}
