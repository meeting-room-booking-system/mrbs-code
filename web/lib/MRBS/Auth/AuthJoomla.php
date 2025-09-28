<?php
namespace MRBS\Auth;

use Joomla\CMS\Factory;
use MRBS\Joomla\JFactory;
use MRBS\User;

require_once MRBS_ROOT . '/auth/cms/joomla.inc';


class AuthJoomla extends Auth
{
  public function __construct()
  {
    $this->checkSessionMatchesType();
  }


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
  public function validateUser(
    #[\SensitiveParameter]
    ?string $user,
    #[\SensitiveParameter]
    ?string $pass)
  {
    if (version_compare(JVERSION, '5.0', '<'))
    {
      $mainframe = JFactory::getApplication('site');
    }
    else
    {
      $mainframe = Factory::getApplication('site');
    }

    return $mainframe->login(array('username' => $user, 'password' => $pass)) ? $user : false;
  }


  protected function getUserFresh(?string $username=null) : ?User
  {
    if ($username === '')
    {
      return null;
    }

    if (version_compare(JVERSION, '5.0', '<'))
    {
      $joomla_user = JFactory::getUser($username);
    }
    else
    {
      $joomla_user = Factory::getUser($username);
    }

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


  // TODO: sort out where getCurrentUser belongs.  We have it in both
  // TODO: Auth and Session for Joomla!
  public function getCurrentUser() : ?User
  {
    return $this->getUserFresh();
  }


  // Return an array of MRBS users, indexed by 'username' and 'display_name'
  public function getUsernames() : array
  {
    $result = array();

    // We only want MRBS users, not all the Joomla users
    $groups = self::getMRBSGroups();

    // Get the user ids associated with those groups
    $user_ids = array();

    foreach($groups as $group)
    {
      // Include child groups by doing it recursively
      if (version_compare(JVERSION, '5.0', '<'))
      {
        $user_ids = array_merge($user_ids, \JAccess::getUsersByGroup($group, $recursive = true));
      }
      else
      {
        $user_ids = array_merge($user_ids, \Joomla\CMS\Access\Access::getUsersByGroup($group, $recursive = true));
      }
    }

    $user_ids = array_unique($user_ids);

    // No doubt it would be faster to do this with a single SQL query, but then we wouldn't
    // be using the Joomla API abstraction.
    foreach ($user_ids as $user_id)
    {
      if (version_compare(JVERSION, '5.0', '<'))
      {
        $user = JFactory::getUser((int)$user_id);
      }
      else
      {
        $user = Factory::getUser((int)$user_id);
      }
      // Check to see that the user has a username. The result of getUser() on a user_id that doesn't exist is,
      // strangely, a user object with all properties set to null.  In theory (?) all the user_ids returned by
      // getUsersByGroup() should exist, but there has been a case where this is not so.  See
      // https://github.com/meeting-room-booking-system/mrbs-code/issues/3682 .
      if (isset($user->username))
      {
        $result[] = array(
          'username' => $user->username,
          'display_name' => $user->name
        );
      }
      else
      {
        trigger_error("The Joomla user with id $user_id appears in Joomla groups but not in Joomla users.", E_USER_WARNING);
      }
    }

    // Need to sort the users
    self::sortUsers($result);

    return $result;
  }


  // Get an array of Joomla groups that have MRBS user or admin rights
  private static function getMRBSGroups() : array
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
    if (version_compare(JVERSION, '5.0', '<'))
    {
      $db = JFactory::getDbo();
    }
    else
    {
      $db = Factory::getDbo();
    }


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


  private static function getUserLevel(object $joomla_user) : int
  {
    global $auth;

    $required_class = (version_compare(JVERSION, '5.0', '<')) ? 'MRBS\Joomla\JUser' : 'Joomla\CMS\User\User';
    $actual_class = get_class($joomla_user);
    if ($actual_class !== $required_class)
    {
      $message = 'Argument #1 ($joomla_user) must be of type ' . "$required_class, $actual_class given";
      throw new \TypeError($message);
    }

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
