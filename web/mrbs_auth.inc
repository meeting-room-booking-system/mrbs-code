<?php
// $Id$

// include the authentification wrappers
include "auth_$auth[type].inc";

function getAuthUserName()
{
	global $PHP_AUTH_USER;
        if (isset($PHP_AUTH_USER))
        {
            $user = $PHP_AUTH_USER;
            if (get_magic_quotes_gpc())
            {
               $user = stripslashes($user);
            }
            return $user;
        }
        else
        {
            return null;
        }
}

function getAuthUserPassword()
{
	global $PHP_AUTH_PW;

        if (isset($PHP_AUTH_PW))
        {
            $pw = $PHP_AUTH_PW;
            if (get_magic_quotes_gpc())
            {
                $pw = stripslashes($pw);
            }
            return $pw;
        }
        else
        {
            return null;
        }
}

/* getAuthorised($user, $pass, $level)
 * 
 * Check to see if the user name/password is valid
 * 
 * $user  - The user name
 * $pass  - The users password
 * $level - The access level required
 * 
 * Returns:
 *   0        - The user does not have the required access
 *   non-zero - The user has the required access
 */
function getAuthorised($user, $pass, $level)
{
	global $auth;
	
	if(!authValidateUser($user, $pass))
		return 0;
	
	return authGetUserLevel($user, $auth["admin"]) >= $level;
}

/* getWritable($creator, $user)
 * 
 * Determines if a user is able to modify an entry
 *
 * $creator - The creator of the entry
 * $user    - Who wants to modify it
 *
 * Returns:
 *   0        - The user does not have the required access
 *   non-zero - The user has the required access
 */
function getWritable($creator, $user)
{
	global $auth;
	
	// Always allowed to modify your own stuff
	if($creator == $user)
		return 1;
	
	if(authGetUserLevel($user, $auth["admin"]) >= 2)
		return 1;
	
	// Unathorised access
	return 0;
}

/* showAccessDenied()
 * 
 * Displays an appropate message when access has been denied
 * 
 * Retusns: Nothing
 */
function showAccessDenied($day, $month, $year, $area)
{
	global $HTTP_REFERER;
	
	print_header($day, $month, $year, $area);
?>
  <H1><?php echo get_vocab("accessdenied")?></H1>
  <P>
   <?php echo get_vocab("norights")?>
  </P>
  <P>
   <A HREF="<?php echo $HTTP_REFERER; ?>"><?php echo get_vocab("returnprev"); ?></A>
  </P>
 </BODY>
</HTML>
<?php
}
?>
