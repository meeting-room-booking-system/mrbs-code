<?php
namespace MRBS\Session;

require_once MRBS_ROOT . '/auth/cms/joomla.inc';


class SessionJoomla extends Session
{
  
  public static function getUsername()
  {
    return JFactory::getUser()->username;
  }
}
