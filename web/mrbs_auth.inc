<?php
// $Id$

// include the authentification wrappers
require_once "auth/auth_$auth[type].inc";

if (isset($auth['session']))
{
  require_once "session/session_$auth[session].inc";
}

// MINIMUM PAGE ACCESS LEVELS

// These pages are open to the public by default as they only contain
// read features.   However if $auth['deny_public_access'] is TRUE then
// access to the public is denied
$page_level['day.php']                   = ($auth['deny_public_access']) ? 1 : 0;
$page_level['help.php']                  = ($auth['deny_public_access']) ? 1 : 0;
$page_level['month.php']                 = ($auth['deny_public_access']) ? 1 : 0;
$page_level['report.php']                = ($auth['deny_public_access']) ? 1 : 0;
$page_level['search.php']                = ($auth['deny_public_access']) ? 1 : 0;
$page_level['view_entry.php']            = ($auth['deny_public_access']) ? 1 : 0;
$page_level['week.php']                  = ($auth['deny_public_access']) ? 1 : 0;

// These pages are set to have a minimum access level of 1 as ordinary users
// should be able to access them because they will have read access and in some
// cases write access for their own entries.   Where necessary further checks are
// made within the page to prevent ordinary users gaining access to admin features.
$page_level['admin.php']                 = 1;  // Ordinary users can view room details 
$page_level['approve_entry_handler.php'] = 1;  // Ordinary users are allowed to remind admins
$page_level['del_entry.php']             = ($auth['only_admin_can_book']) ? 2 : 1;
$page_level['edit_area_room.php']        = 1;  // Ordinary users can view room details
$page_level['edit_entry.php']            = ($auth['only_admin_can_book']) ? 2 : 1;
$page_level['edit_entry_handler.php']    = ($auth['only_admin_can_book']) ? 2 : 1;
$page_level['edit_users.php']            = 1;  // Ordinary users can edit their own details
$page_level['pending.php']               = 1;  // Ordinary users can view their own entries

// These pages have a minimum access level of 2 as they only contain admin features
$page_level['add.php']                   = 2;
$page_level['del.php']                   = 2;
$page_level['del_entry_ajax.php']        = 2;
$page_level['import.php']                = 2;


/* getAuthorised($level)
 * 
 * Check to see if the current user has a certain level of rights
 * 
 * $level - The access level required
 * 
 * Returns:
 *   0        - The user does not have the required access
 *   non-zero - The user has the required access
 */
function getAuthorised($level)
{
  // If the minimum level is zero (or not set) then they are
  // authorised, whoever they are
  if (empty($level))
  {
    return TRUE;
  }

  // Otherwise we need to check who they are
  $user = getUserName();
  if(isset($user) == FALSE)
  {
    authGet();
    return 0;
  }

  return authGetUserLevel($user) >= $level;
}

/* checkAuthorised()
 *
 * Checks to see that a user is authorised to access the current page.
 * If they are not, then shows an Access Denied message and exits.
 *
 * Required page level authorisations are defined above.
 * If they are not defined then the function plays safe and requires the
 * highest level of authorisation.
 */
function checkAuthorised()
{
  global $page_level, $max_level;
  global $day, $month, $year, $area, $room;
  global $PHP_SELF;
  
  // Get the minimum authorisation level for this page
  if (isset($page_level[basename($PHP_SELF)]))
  {
    $required_level = $page_level[basename($PHP_SELF)];
  }
  elseif (isset($max_level))
  {
    $required_level = $max_level;
  }
  else
  {
    $required_level = 2;
  }
  
  // Check that the user has this level
  if (!getAuthorised($required_level))
  {
    // If we dont know the right date then use today's
    if (!isset($day) or !isset($month) or !isset($year))
    {
      $day   = date("d");
      $month = date("m");
      $year  = date("Y");
    }
    if (empty($area))
    {
      $area = get_default_area();
    }
    showAccessDenied($day, $month, $year, $area, isset($room) ? $room : "");
    exit();
  }
}

/* getWritable($creator, $user, $room)
 * 
 * Determines if a user is able to modify an entry
 *
 * $creator - The creator of the entry
 * $user    - Who wants to modify it
 * $room    - The id of the room that the entry is in
 *
 * Returns:
 *   0        - The user does not have the required access
 *   non-zero - The user has the required access
 */
function getWritable($creator, $user, $room)
{
  // Always allowed to modify your own stuff
  if(strcasecmp($creator, $user) == 0)
  {
    return 1;
  }
  
  // Otherwise you have to be a (booking) admin for this room
  if (auth_book_admin($user, $room))
  {
    return 1;
  }
  
  // Unathorised access
  return 0;
}

/* showAccessDenied()
 * 
 * Displays an appropriate message when access has been denied
 * 
 * Returns: Nothing
 */
function showAccessDenied($day, $month, $year, $area, $room)
{
  global $HTTP_REFERER;

  print_header($day, $month, $year, $area, isset($room) ? $room : "");
?>
  <h1><?php echo get_vocab("accessdenied")?></h1>
  <p>
   <?php echo get_vocab("norights")?>
  </p>
  <p>
    <a href="<?php echo htmlspecialchars($HTTP_REFERER); ?>">
      <?php echo get_vocab("returnprev"); ?>
    </a>
  </p>
<?php
  // Print footer and exit
  print_footer(TRUE);
}

/* auth_can_edit_user($creator, $user)
 * 
 * Determines if a user is able to edit another user's details
 *
 * $user    - Who wants to modify it
 * $target  - The user that is being edited
 *
 * Returns:
 *   0        - The user does not have the required access
 *   non-zero - The user has the required access
 */
function auth_can_edit_user($user, $target)
{
  global $min_user_editing_level;
  
  // Always allowed to modify your own stuff
  if(strcasecmp($user, $target) == 0)
  {
    return 1;
  }

  if(authGetUserLevel($user) >= $min_user_editing_level)
  {
    return 1;
  }

  // Unathorised access
  return 0;
}

// auth_book_admin($user, $room)
//
// Checks whether $user is allowed has booking administration rights
// for $room - ie is allowed to modify and delete other people's bookings
// and to approve bookings.
//
// At the moment $room is ignored, but is passed here so that later
// it can be enhanced to provide fine-grained permissions
// 
// Returns:  TRUE if the user is allowed has booking admin rights for
//           the room; otherwise FALSE
function auth_book_admin($user, $room)
{
  return (authGetUserLevel($user) >= 2);
}
?>
