<?php
/*****************************************************************************\
*									      *
*   File name       session_php.inc					      *
*									      *
*   Description	    Use PHP built-in sessions handling                        *
*									      *
*   Notes	    To use this authentication scheme, set in config.inc.php: *
*			$auth["session"]  = "php";			      *
*									      *
*   History								      *
*    2003/11/09 JFL Created this file					      *
*									      *
\*****************************************************************************/

// $Id$

session_start();

/*
  Target of the form with sets the URL argument "Action=SetName".
  Will eventually return to URL argument "TargetURL=whatever".
*/
if (isset($Action) && ($Action == "SetName"))
{
    /* First make sure the password is valid */
    if (($NewUserName != "") && (!authValidateUser($NewUserName, $NewUserPassword)))
    {
        print_header(0, 0, 0, 0);
        echo "<P>".get_vocab('unknown_user')."</P>\n";
        printLoginForm($TargetURL);
        exit();
    }

    if (isset($_SESSION))
    {
        $_SESSION["UserName"] = $NewUserName;
        $_SESSION["UserPassword"] = $NewUserPassword;
    }
    else
    {
        global $HTTP_SESSION_VARS;
        $HTTP_SESSION_VARS["UserName"] = $NewUserName;
        $HTTP_SESSION_VARS["UserPassword"] = $NewUserPassword;
    }

    header ("Location: $TargetURL"); /* Redirect browser to initial page */
    /* Note HTTP 1.1 mandates an absolute URL. Most modern browsers support relative URLs,
        which allows to work around problems with DNS inconsistencies in the server name.
        Anyway, if the browser cannot redirect automatically, the manual link below will work. */
    print_header(0, 0, 0, 0);
    echo "<br />\n";
    echo "<p>Please click <a href=\"$TargetURL\">here</a> if you're not redirected automatically to the page you requested.</p>\n";
    echo "</body>\n";
    echo "</html>\n";
    exit();
}

/*
  Display the login form. Used by two routines below.
  Will eventually return to $TargetURL.
*/
function printLoginForm($TargetURL)
{
    global $PHP_SELF;
?>
<p>
  <?php echo get_vocab("please_login") ?>
</p>
<form method="post" action="<?php echo basename($PHP_SELF) ?>">
  <table>
    <tr>
      <td align="right"><?php echo get_vocab("user_name") ?></td>
      <td><input type="text" name="NewUserName" /></td>
    </tr>
    <tr>
      <td align="right"><?php echo get_vocab("user_password") ?></td>
      <td><input type="password" name="NewUserPassword" /></td>
    </tr>
  </table>
  <input type="hidden" name="TargetURL" value="<?php echo $TargetURL ?>" /> <br />
  <input type="hidden" name="Action" value="SetName" /> <br />
  <input type="submit" value=" <?php echo get_vocab('login') ?> " /> <br />
</form>
</body>
</html>
<?php
}

/*
  Target of the form with sets the URL argument "Action=QueryName".
  Will eventually return to URL argument "TargetURL=whatever".
*/
if (isset($Action) && ($Action == "QueryName"))
{
    print_header(0, 0, 0, 0);
    printLoginForm($TargetURL);
    exit();
}

/* authGet()
 * 
 * Request the user name/password
 * 
 * Returns: Nothing
 */
function authGet()
{
    global $PHP_SELF, $QUERY_STRING;

    print_header(0, 0, 0, 0);

    echo "<p>".get_vocab("norights")."</p>\n";

    $TargetURL = basename($PHP_SELF);
    if (isset($QUERY_STRING)) $TargetURL = $TargetURL . "?" . $QUERY_STRING;
    printLoginForm($TargetURL);

    exit();
}

function getUserName()
{
    if (isset($_SESSION) && isset($_SESSION["UserName"]) && ($_SESSION["UserName"] != ""))
    {
        return $_SESSION["UserName"];
    }
    else
    {
        global $HTTP_SESSION_VARS;
        if (isset($HTTP_SESSION_VARS["UserName"]) && ($HTTP_SESSION_VARS["UserName"] != ""))
            return $HTTP_SESSION_VARS["UserName"];
    }
}

function getUserPassword()
{
    if (isset($_SESSION) && isset($_SESSION["UserPassword"]) && ($_SESSION["UserPassword"] != ""))
    {
        return $_SESSION["UserPassword"];
    }
    else
    {
        global $HTTP_SESSION_VARS;
        if (isset($HTTP_SESSION_VARS["UserPassword"]) && ($HTTP_SESSION_VARS["UserPassword"] != ""))
            return $HTTP_SESSION_VARS["UserPassword"];
    }
}

// Print the logon entry on the top banner.
function PrintLogonBox()
{
    global $PHP_SELF, $QUERY_STRING, $user_list_link, $user_link;

    $TargetURL = basename($PHP_SELF);
    if (isset($url_base) && ($url_base != "")) $TargetURL = $url_base . '/' . $TargetURL;
    if (isset($QUERY_STRING)) $TargetURL = $TargetURL . "?" . $QUERY_STRING;
    if (!isset($user_link)) $user_link = "";
    $user=getUserName();
    if (isset($user))
    {
?>
	<TD CLASS="banner" BGCOLOR="#C0E0FF" ALIGN=CENTER>
	  <A name="logonBox" href="<?php echo "$user_link\">" . get_vocab('you_are')." ".$user ?></A><br>
          <FORM METHOD=POST ACTION="admin.php">
	    <input type="hidden" name="TargetURL" value="<?php echo $TargetURL ?>" />
	    <input type="hidden" name="Action" value="SetName" />
	    <input type="hidden" name="NewUserName" value="" />
	    <input type="hidden" name="NewUserPassword" value="" />
	    <input type="submit" value=" <?php echo get_vocab('logoff') ?> " />
	  </FORM>
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