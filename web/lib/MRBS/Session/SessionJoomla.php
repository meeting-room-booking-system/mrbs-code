<?php
namespace MRBS\Session;

use \MRBS\JFactory;

require_once MRBS_ROOT . '/auth/cms/joomla.inc';


class SessionJoomla extends SessionWithLogin
{
  
  public static function getUsername()
  {
    return JFactory::getUser()->username;
  }
}
