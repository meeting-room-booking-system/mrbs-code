<?php
namespace MRBS\Auth;

use \MRBS\JFactory;
use \MRBS\User;

require_once MRBS_ROOT . '/auth/cms/joomla.inc';


class AuthJoomla extends Auth
{

  public function getUser($username=null)
  {
    if ($username === '')
    {
      return null;
    }

    $joomla_user = JFactory::getUser($username);

    if ($joomla_user === false)
    {
      return new User($username);
    }
    
    if ($joomla_user->guest)
    {
      return null;
    }

    $user = new User($joomla_user->username);
    $user->display_name = $joomla_user->name;
    $user->email = $joomla_user->email;
    $user->level = self::getUserLevel($joomla_user);

    return $user;
  }


  private static function getUserLevel(\MRBS\JUser $joomla_user)
  {
    global $auth;

    // User not logged in, user level '0'
    if ($joomla_user->guest)
    {
      return 0;
    }

    // Otherwise get the user's access levels
    $authorised_levels = $joomla_user->getAuthorisedViewLevels();

    // Check if they have admin access
    if (isset($auth['joomla']['admin_access_levels']))
    {
      $admin_levels = (array)$auth['joomla']['admin_access_levels'];
      if (count(array_intersect($authorised_levels, $admin_levels)) > 0)
      {
        return 2;
      }
    }

    // Check if they have user access
    if (isset($auth['joomla']['user_access_levels']))
    {
      $user_levels = (array)$auth['joomla']['user_access_levels'];
      if (count(array_intersect($authorised_levels, $user_levels)) > 0)
      {
        return 1;
      }
    }

    // Everybody else is access level '0'
    return 0;
  }

}
