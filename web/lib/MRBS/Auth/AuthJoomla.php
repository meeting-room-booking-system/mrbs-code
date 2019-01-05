<?php
namespace MRBS\Auth;

use \MRBS\JFactory;
use \MRBS\User;

require_once MRBS_ROOT . '/auth/cms/joomla.inc';


class AuthJoomla extends Auth
{
  
  public function getUser($username)
  {
    $joomla_user = JFactory::getUser($username);
    
    if (!$joomla_user)
    {
      return null;
    }
    
    $user = new User($username);
    $user->display_name = $joomla_user->name;
    $user->email = $joomla_user->email;
    
    return $user;
  }
  
}