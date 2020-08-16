<?php
namespace MRBS;

use MRBS\Auth\AuthFactory;
use MRBS\Session\SessionFactory;


if (!isset($auth['session']))
{
  switch ($auth['type'])
  {
    case 'joomla':
    case 'saml':
    case 'wordpress':
      $auth['session'] = $auth['type'];
      break;
    default:
      $auth['session'] = 'php';
      break;
  }
}

// Need to be in this order
if (is_readable("auth/auth_$auth[type].inc"))
{
  require_once "auth/auth_$auth[type].inc";
}

if (method_exists(session(), 'processForm'))
{
  session()->processForm();
}


// Convenience wrapper function to provide access to an Auth object
function auth()
{
  global $auth;

  static $auth_obj = null;

  if (is_null($auth_obj))
  {
    $auth_obj = AuthFactory::create($auth['type']);
  }

  return $auth_obj;
}


// Convenience wrapper function to provide access to a Session object
function session()
{
  global $auth;

  static $session_obj = null;

  if (is_null($session_obj))
  {
    $session_obj = SessionFactory::create($auth['session']);
  }

  return $session_obj;
}


// Gets the minimum user level required to access a page
function get_page_level($page)
{
  global $auth, $max_level;

  // If you're resetting your password you won't be logged in and $auth['deny_public_access']
  // should not apply.
  if (in_array($page, array('reset_password.php', 'reset_password_handler.php')))
  {
    return 0;
  }

  // Otherwise ...
  switch ($page)
  {
    // These pages are open to the public by default as they only contain
    // read features.
    case 'help.php':
    case 'index.php':
      $result = 0;
      break;

    // These pages reveal usernames, which could be of assistance to someone trying to
    // break into the system, so users are required to be logged in before viewing them.
    case 'search.php':
    case 'view_entry.php':
      $result = 1;
      break;

    // These pages are set to have a minimum access level of 1 as ordinary users
    // should be able to access them because they will have read access and in some
    // cases write access for their own entries.   Where necessary further checks are
    // made within the page to prevent ordinary users gaining access to admin features.
    case 'admin.php':
    case 'approve_entry_handler.php':   // Ordinary users are allowed to remind admins
    case 'edit_room.php':               // Ordinary users can view room details
    case 'edit_users.php':              // Ordinary users can edit their own details
    case 'pending.php':                 // Ordinary users can view their own entries
    case 'registration_handler.php':    // Ordinary users can register for an event
      $result = 1;
      break;

    // These pages allow users to create and delete entries
    case 'check_slot.php':              // Ajax page used by edit_entry.php
    case 'del_entry.php':
    case 'edit_entry.php':
    case 'edit_entry_handler.php':
      $result = ($auth['only_admin_can_book']) ? 2 : 1;
      break;

    // These pages only contain admin features
    case 'add.php':                     // Adds a room
    case 'del.php':                     // Deletes a room
    case 'del_entries.php':             // Ajax page for bulk deletion of entries
    case 'edit_area.php':
    case 'edit_area_handler.php':
    case 'edit_room_handler.php':
    case 'import.php':
    case 'report.php':
    case 'usernames.php':               // Ajax page for getting a list of users
      $result = 2;
      break;

    default:
      $result = (isset($max_level)) ? $max_level : 2;
      break;
  }

  if ($auth['deny_public_access'])
  {
    $result = max($result, 1);
  }

  return $result;
}


/* getAuthorised($level)
 *
 * Check to see if the current user has a certain level of rights
 *
 * $level  - The access level required
 * $returl - The URL to return to eventually
 *
 * Returns:
 *   false - The user does not have the required access
 *   true  - The user has the required access
 */
function getAuthorised($level, $returl)
{
  // If the minimum level is zero (or not set) then they are
  // authorised, whoever they are
  if (empty($level))
  {
    return true;
  }

  // Otherwise we need to check who they are
  $mrbs_user = session()->getCurrentUser();

  if(!isset($mrbs_user))
  {
    // Ask them to authenticate, if the session scheme supports it
    if (method_exists(session(), 'authGet'))
    {
      session()->authGet(null, $returl);
    }
    return false;
  }

  return ($mrbs_user->level >= $level);
}


/* checkAuthorised()
 *
 * Checks to see that a user is authorised to access a page.
 * If they are not, then shows an Access Denied message and exits.
 *
 */
function checkAuthorised($page, $just_check=false)
{
  global $view, $view_all, $year, $month, $day, $area, $room;
  global $returl;

  // Get the minimum authorisation level for this page
  $required_level = get_page_level($page);

  if ($just_check)
  {
    if ($required_level == 0)
    {
      return true;
    }

    $mrbs_user = session()->getCurrentUser();
    return (isset($mrbs_user) && ($mrbs_user->level >= $required_level));
  }

  // Check that the user has this level
  if (getAuthorised($required_level, $returl))
  {
    return true;
  }

  // If we don't know the right date then use today's
  if (!isset($day) or !isset($month) or !isset($year))
  {
    $day   = date('d');
    $month = date('m');
    $year  = date('Y');
  }

  if (empty($area))
  {
    $area = get_default_area();
  }

  showAccessDenied($view, $view_all, $year, $month, $day, $area, isset($room) ? $room : null);
  exit();
}


/* getWritable($creator, $room)
 *
 * Determines if the current user is able to modify an entry
 *
 * $creator - The creator of the entry
 * $rooms   - The id(s) of the room(s) that the entries are in. Can
 *            be a scalar or an array.
 * $all     - Whether to check that the creator has write access
 *            for all ($all=true) or just some ($all=false) of the
 *            rooms.
 *
 * Returns:
 *   false  - The user does not have the required access
 *   true   - The user has the required access
 */
function getWritable($creator, $rooms=null, $all=true)
{
  if (is_array($rooms) && (count($rooms) > 0))
  {
    if ($all)
    {
      // We want the user to have write access for all the rooms,
      // so if for any one room they are not, then return false.
      foreach ($rooms as $room)
      {
        if (!getWritable($creator, $room))
        {
          return false;
        }
      }
      return true;
    }
    else
    {
      // We want the user to have write access for at least one room,
      // so if there are no rooms for which they do, then return false.
      foreach ($rooms as $room)
      {
        if (getWritable($creator, $room))
        {
          return true;
        }
      }
      return false;
    }
  }

  if (is_null($rooms) && !$all)
  {
    // Not yet supported.  Could support it but need to decide what $rooms=null means.
    // Does it mean all rooms in the system or just all rooms in the current area?
    throw new \Exception('$rooms===null and $all===false not yet supported.');
  }

  // You can't make bookings in rooms which are invisible
  if (!is_visible($rooms))
  {
    return false;
  }

  // Always allowed to modify your own stuff
  $mrbs_user = session()->getCurrentUser();
  if (isset($mrbs_user) && (strcasecmp($creator, $mrbs_user->username) === 0))
  {
    return true;
  }

  // Otherwise you have to be a (booking) admin for this room
  if (is_book_admin($rooms))
  {
    return true;
  }

  // Unauthorised access
  return false;
}

/* showAccessDenied()
 *
 * Displays an appropriate message when access has been denied
 *
 * Returns: Nothing
 */
function showAccessDenied($view=null, $view_all=null, $year=null, $month=null, $day=null, $area=null, $room=null)
{
  global $server;

  $context = array(
      'view'      => $view,
      'view_all'  => $view_all,
      'year'      => $year,
      'month'     => $month,
      'day'       => $day,
      'area'      => $area,
      'room'      => isset($room) ? $room : null
    );

  print_header($context);

  // Wrap the contents in a <div> to help with styling.  Not a very nice solution, but anyway.
  echo "<div>\n";
  echo "<h1>" . get_vocab("accessdenied") . "</h1>\n";
  echo "<p>" . get_vocab("norights") . "</p>\n";
  if (isset($server['HTTP_REFERER']))
  {
    echo "<p>\n";
    echo "<a href=\"" . htmlspecialchars($server['HTTP_REFERER']) . "\">\n" . get_vocab("returnprev") . "</a>\n";
    echo "</p>\n";
  }
  echo "</div>\n";

  // Print footer and exit
  print_footer(true);
}


// Checks whether the current user has admin rights
function is_admin()
{
  global $max_level;

  $mrbs_user = session()->getCurrentUser();
  $required_level = (isset($max_level) ? $max_level : 2);

  return (isset($mrbs_user) && ($mrbs_user->level >= $required_level));
}


// Checks whether the current user has booking administration rights
// for $rooms - ie is allowed to modify and delete other people's bookings
// and to approve bookings.
//
// $rooms can be either a single scalar value or an array of room ids.  The default
// value for $rooms is all rooms.  (At the moment $room is ignored, but is passed here
// so that later MRBS can be enhanced to provide fine-grained permissions.)
//
// $all specifies whether the user must be a booking for all $rooms, or just some of
// them, ie at least one.
//
// Returns:  TRUE if the user is allowed has booking admin rights for
//           the room(s); otherwise FALSE
function is_book_admin($rooms=null, $all=true)
{
  if (is_array($rooms) && (count($rooms) > 0))
  {
    if ($all)
    {
      // We want the user to be a booking admin for all the rooms,
      // so if for any one room they are not, then return false.
      foreach ($rooms as $room)
      {
        if (!is_book_admin($room))
        {
          return false;
        }
      }
      return true;
    }
    else
    {
      // We want the user to be a booking admin for at least one room,
      // so if there are no rooms for which they are, then return false.
      foreach ($rooms as $room)
      {
        if (is_book_admin($room))
        {
          return true;
        }
      }
      return false;
    }
  }

  if (is_null($rooms) && !$all)
  {
    // Not yet supported.  Could support it but need to decide what $rooms=null means.
    // Does it mean all rooms in the system or just all rooms in the current area?
    throw new \Exception('$rooms===null and $all===false not yet supported.');
  }

  $mrbs_user = session()->getCurrentUser();

  return (isset($mrbs_user) && ($mrbs_user->level >= 2));
}


// Checks whether the current user has user editing rights
function is_user_admin()
{
  global $min_user_editing_level;

  $mrbs_user = session()->getCurrentUser();

  return (isset($mrbs_user) && ($mrbs_user->level >= $min_user_editing_level));
}


// Checks whether a room is visible to the current user
// Doesn't do anything at the moment, but allows for customisation or future development
function is_visible($room)
{
  return true;
}
