<?php 
/*****************************************************************************\
*									      *
*   File name       auth_http.inc					      *
*									      *
*   Description	    Get user identity using the HTTP basic authentication.    *
*									      *
*   Notes	    To use this session scheme, set in config.inc.php:        *
*			$auth["session"]  = "http";			      *
*									      *
*   History								      *
*    2003/11/12 JFL Created this file					      *
*    Remaining history in ChangeLog and CVS logs                              *
*									      *
\*****************************************************************************/

// $Id$

/* authGet()
 * 
 * Request the user name/password
 * 
 * Returns: Nothing
 */
function authGet()
{
    global $auth;
    header("WWW-Authenticate: Basic realm=\"$auth[realm]\"");
    Header("HTTP/1.0 401 Unauthorized");
}

function getAuthPassword()
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

function getUserName()
{
    global $PHP_AUTH_USER;
    if (isset($PHP_AUTH_USER))
    {
        $user = $PHP_AUTH_USER;
        if (get_magic_quotes_gpc())
        {
            $user = stripslashes($user);
        }

        if (authValidateUser($user,getAuthPassword()))
        {
            return $user;
        }
        else
        {
            return null;
        }
    }
    else
    {
        return null;
    }
}

// Print the logon entry on the top banner.
function PrintLogonBox()
{
	global $user_list_link;
  
	$user = getUserName();

	if (isset($user))
	{
		// words 'you are xxxx' becomes a link to the
        // report page with only entries created by xxx. Past entries are not
        // displayed but this can be changed
       	$search_string = "report.php?From_day=$day&From_month=$month&".
          "From_year=$year&To_day=1&To_month=12&To_year=2030&areamatch=&".
          "roommatch=&namematch=&descrmatch=&summarize=1&sortby=r&display=d&".
          "sumby=d&creatormatch=$user"; ?>

    <TD CLASS="banner" BGCOLOR="#C0E0FF" ALIGN=CENTER>
      <A name="logonBox" href="<?php echo "$search_string\" title=\""
         . get_vocab('show_my_entries') . "\">" . get_vocab('you_are')." "
         .$user ?></A><br>
<?php if (isset($user_list_link)) print "	  <br>\n	  " .
	    "<A href='$user_list_link'>" . get_vocab('user_list') . "</A><br>\n" ;
?>
    </TD>
<?php
    }
    else
    {
?>
    <TD CLASS="banner" BGCOLOR="#C0E0FF" ALIGN=CENTER>
       <A name="logonBox" href=""><?php echo get_vocab('unknown_user'); ?></A><br>
          <FORM METHOD=POST ACTION="admin.php">
	    <input type="hidden" name="TargetURL" value="<?php echo $TargetURL ?>" />
	    <input type="hidden" name="Action" value="QueryName" />
	    <input type="submit" value=" <?php echo get_vocab('login') ?> " />
	  </FORM>
<?php if (isset($user_list_link)) print "	  <br>\n	  " .
	    "<A href=\"$user_list_link\">" . get_vocab('user_list') . "</A><br>\n" ;
?>
	</TD>
<?php
    }
}

?>
