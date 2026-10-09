<?php
declare(strict_types=1);
namespace MRBS\Auth;

use MRBS\Cms\Joomla\Joomla;
use MRBS\User;


class AuthJoomla extends Auth
{
  private $joomla;

  public function __construct()
  {
    $this->checkSessionMatchesType();
    $this->joomla = Joomla::getInstance();
  }


  public function validateUser(
    #[\SensitiveParameter]
    ?string $user,
    #[\SensitiveParameter]
    ?string $pass)
  {
    return $this->joomla->app()->login(array('username' => $user, 'password' => $pass)) ? $user : false;
  }


  public function getUserFresh(?string $username=null) : ?User
  {
    if ($username === '')
    {
      return null;
    }

    $joomla_user = $this->joomla->getUser($username);

    if (empty($joomla_user->id))
    {
      // If the username is set and the Joomla user id is empty then that's because the user has been deleted from
      // Joomla, but we still have their booking in MRBS, so create an MRBS user; or if the username is not set it means
      // that we were trying to get the currently logged-in user and there isn't one, so return NULL.
      return (isset($username)) ? new User($username) : null;
    }

    if ($joomla_user->guest)
    {
      return null;
    }

    $user = new User($joomla_user->username);
    $user->display_name = $joomla_user->name;
    $user->email = $joomla_user->email;
    $user->level = $this->getUserLevel($joomla_user);

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
    $result = [];

    // We only want MRBS users, not all the Joomla users
    $groups = $this->getMRBSGroups();

    // Get the user ids associated with those groups
    $user_ids = [];

    foreach($groups as $group)
    {
      // Include child groups by doing it recursively
      $user_ids = array_merge($user_ids, $this->joomla->getUsersByGroup($group, true));
    }

    $user_ids = array_unique($user_ids);

    // No doubt it would be faster to do this with a single SQL query, but then we wouldn't
    // be using the Joomla API abstraction.
    foreach ($user_ids as $user_id)
    {
      $user = $this->joomla->getUser($user_id);
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
  private function getMRBSGroups() : array
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
    $db = $this->joomla->getDbo();

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


  private function getUserLevel(object $joomla_user) : int
  {
    global $auth;

    $required_class = (version_compare($this->joomla->version, '5.0', '<')) ? 'MRBS\Cms\Joomla\JUser' : 'Joomla\CMS\User\User';
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
