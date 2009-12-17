<?php
// $Id$

// include the authentification wrappers
require_once "auth_$auth[type].inc";

if (isset($auth['session']))
{
  require_once "session_$auth[session].inc";
}


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
  global $auth;

  $user = getUserName();
  if(isset($user) == FALSE)
  {
    authGet();
    return 0;
  }

  if (isset($auth['only_admin_can_book']) && $auth['only_admin_can_book'])
  {
    $level = 2;
  }

  return authGetUserLevel($user) >= $level;
}

/* getWritable($creator, $user, $room)
 * 
 * Determines if a user is able to modify an entry
 *
 * $creator - The creator of the entry
 * $user    - Who wants to modify it
 * $room    - The id of the room that the entry is in
 *
 * $room is ignored at the moment, but is provided for
 * future enhancements
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

  if(authGetUserLevel($user) >= 2)
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
// and to confirm provisional bookings.
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
