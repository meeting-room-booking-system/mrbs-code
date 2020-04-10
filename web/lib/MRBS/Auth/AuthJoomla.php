<?php
namespace MRBS\Auth;

use \MRBS\JFactory;
use \MRBS\User;

require_once MRBS_ROOT . '/auth/cms/joomla.inc';


class AuthJoomla extends Auth
{
  /* validateUser($user, $pass)
   *
   * Checks if the specified username/password pair are valid
   *
   * $user  - The user name
   * $pass  - The password
   *
   * Returns:
   *   false    - The pair are invalid or do not exist
   *   true     - The user has been validated and logged in
   */
  public function validateUser($user, $pass)
  {
    $mainframe = JFactory::getApplication('site');

    return $mainframe->login(array('username' => $user,
                                   'password' => $pass));
  }


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


  // Return an array of MRBS users, indexed by 'username' and 'display_name'
  public function getUsernames()
  {
    $result = array();

    // We only want MRBS users, not all the Joomla users
    $groups = self::getMRBSGroups();

    // Get the user ids associated with those groups
    $user_ids = array();

    foreach($groups as $group)
    {
      // Include child groups by doing it recursively
      $user_ids = array_merge($user_ids, \JAccess::getUsersByGroup($group, $recursive=true));
    }

    $user_ids = array_unique($user_ids);

    // No doubt it would be faster to do this with a single SQL query, but then we wouldn't
    // be using the Joomla API abstraction.
    foreach ($user_ids as $user_id)
    {
      $user = JFactory::getUser((int) $user_id);
      $result[] = array('username'     => $user->username,
                        'display_name' => $user->name);
    }

    // Need to sort the users
    self::sortUsers($result);

    return $result;
  }


  // Get an array of Joomla groups that have MRBS user or admin rights
  private static function getMRBSGroups()
  {
    global $auth;

    $result = array();

    // Get all the Joomla access levels that have MRBS user or admin rights
    $mrbs_access_levels = array_merge($auth['joomla']['admin_access_levels'],
                                      $auth['joomla']['user_access_levels']);

    $mrbs_access_levels = array_unique($mrbs_access_levels);

    // There doesn't seem to be a Joomla API to do this, so we'll have to do
    // it with direct access to the database.

    // Get a db connection.
    $db = JFactory::getDbo();

    // Create a new query object.
    $query = $db->getQuery(true);

    // Execute the query
    $query->select($db->quoteName(array('rules')));
    $query->from($db->quoteName('#__viewlevels'));
    $query->where($db->quoteName('id') . ' IN ('. implode(',', $mrbs_access_levels) . ')');
    $db->setQuery($query);
    $column = $db->loadColumn();

    // Process the results into an array
    foreach ($column as $rules)
    {
      $result = array_merge($result, (json_decode($rules)));
    }

    // Remove duplicates
    $result = array_unique($result);

    return $result;
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
