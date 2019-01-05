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

require_once "auth/auth_$auth[type].inc";

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
    case 'del_entry.php':               // Ajax page for bulk deletion of entries
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
  $user = session()->getUsername();
  
  if(!isset($user))
  {
    // Ask them to authenticate, if the session scheme supports it
    if (method_exists(session(), 'authGet'))
    {  
      session()->authGet();
    }
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
    
    $user = session()->getUsername();
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
 * $room    - The id of the room that the entry is in
 *
 * Returns:
 *   false  - The user does not have the required access
 *   true   - The user has the required access
 */
function getWritable($creator, $room)
{
  $user = session()->getUsername();
  
  // Always allowed to modify your own stuff
  if(strcasecmp($creator, $user) === 0)
  {
    return true;
  }
  
  // Otherwise you have to be a (booking) admin for this room
  if (is_book_admin($room))
  {
    return true;
  }
  
  // Unathorised access
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
  print_header($view, $year, $month, $day, $area, isset($room) ? $room : null);

  echo "<h1>" . get_vocab("accessdenied") . "</h1>\n";
  echo "<p>" . get_vocab("norights") . "</p>\n";
  if (isset($_SERVER['HTTP_REFERER']))
  {
    echo "<p>\n";
    echo "<a href=\"" . htmlspecialchars($_SERVER['HTTP_REFERER']) . "\">\n" . get_vocab("returnprev") . "</a>\n";
    echo "</p>\n";
  }

  // Print footer and exit
  print_footer(true);
}


// Checks whether the current user has admin rights
function is_admin()
{
  global $max_level;
  
  $user = session()->getUsername();
  $required_level = (isset($max_level) ? $max_level : 2);
  
  return (authGetUserLevel($user) >= $required_level);
}


// Checks whether the current user has booking administration rights
// for $room - ie is allowed to modify and delete other people's bookings
// and to approve bookings.   The default value for $room is all rooms.
//
// At the moment $room is ignored, but is passed here so that later
// it can be enhanced to provide fine-grained permissions
// 
// Returns:  TRUE if the user is allowed has booking admin rights for
//           the room; otherwise FALSE
function is_book_admin($room=null)
{
  $user = session()->getUsername();
  
  return (authGetUserLevel($user) >= 2);
}


// Checks whether the current user has user editing rights
function is_user_admin()
{
  global $min_user_editing_level;
  
  $user = session()->getUsername();

  return (authGetUserLevel($user) >= $min_user_editing_level);
}
