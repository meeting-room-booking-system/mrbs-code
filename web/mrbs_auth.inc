<?php
namespace MRBS;

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
require_once "auth/auth_$auth[type].inc";
require_once "session/session_$auth[session].inc";


// Gets the minimum user level required to access a page
function get_page_level($page)
{
  global $auth, $max_level;
  
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
 * $level - The access level required
 * 
 * Returns:
 *   false - The user does not have the required access
 *   true  - The user has the required access
 */
function getAuthorised($level)
{
  // If the minimum level is zero (or not set) then they are
  // authorised, whoever they are
  if (empty($level))
  {
    return true;
  }

  // Otherwise we need to check who they are
  $user = getUserName();
  if(isset($user) == false)
  {
    authGet();
    return false;
  }

  return authGetUserLevel($user) >= $level;
}


/* checkAuthorised()
 *
 * Checks to see that a user is authorised to access a page.
 * If they are not, then shows an Access Denied message and exits.
 *
 */
function checkAuthorised($page, $just_check=false)
{
  global $view, $year, $month, $day, $area, $room;
  
  // Get the minimum authorisation level for this page
  $required_level = get_page_level($page);
  
  if ($just_check)
  {
    if ($required_level == 0)
    {
      return true;
    }
    
    $user = getUserName();
    return (isset($user)) ? (authGetUserLevel($user) >= $required_level) : false;
  }
  
  // Check that the user has this level
  if (getAuthorised($required_level))
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
  
  showAccessDenied($view, $year, $month, $day, $area, isset($room) ? $room : null);
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
  $user = getUserName();
  if (strcasecmp($creator, $user) === 0)
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
function showAccessDenied($view=null, $year=null, $month=null, $day=null, $area=null, $room=null)
{
  global $server;
  
  print_header($view, $year, $month, $day, $area, isset($room) ? $room : null);

  echo "<h1>" . get_vocab("accessdenied") . "</h1>\n";
  echo "<p>" . get_vocab("norights") . "</p>\n";
  echo "<p>\n";
  echo "<a href=\"" . htmlspecialchars($server['HTTP_REFERER']) . "\">\n" . get_vocab("returnprev") . "</a>\n";
  echo "</p>\n";

  // Print footer and exit
  print_footer(true);
}


// Checks whether the current user has admin rights
function is_admin()
{
  global $max_level;
  
  $user = getUserName();
  $required_level = (isset($max_level) ? $max_level : 2);
  
  return (authGetUserLevel($user) >= $required_level);
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

  $user = getUserName();
  
  return (authGetUserLevel($user) >= 2);
}


// Checks whether the current user has user editing rights
function is_user_admin()
{
  global $min_user_editing_level;
  
  $user = getUserName();

  return (authGetUserLevel($user) >= $min_user_editing_level);
}


// Checks whether a room is visible to the current user
// Doesn't do anything at the moment, but allows for customisation or future development
function is_visible($room)
{
  return true;
}
