<?php
namespace MRBS;

require_once MRBS_ROOT . '/auth/cms/joomla.inc';

/* authGetUserLevel($user)
 * 
 * Determines the users access level
 * 
 * $user - The user name
 *
 * Returns:
 *   The users access level
 */
function authGetUserLevel($username)
{
  global $auth;
  
  // User not logged in, user level '0'
  if (!isset($username))
  {
    return 0;
  }
  
  // Otherwise get the user's access levels
  $user = JFactory::getUser($username);
  $authorised_levels = $user->getAuthorisedViewLevels();
  
  // Check if they have manager access
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


// Gets the user's email address.   Returns an empty
// string if one can't be found
function authGetUserEmail($username)
{
  if (!isset($username) || $username === '')
  {
    return '';
  }
  
  $user = JFactory::getUser($username);
  $email = '"' . $user->name . '"<' . $user->email . '>';
  return $email;
}

