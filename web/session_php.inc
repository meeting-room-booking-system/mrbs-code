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

// -- HTTP_HOST --
if (!empty($_SERVER) && isset($_SERVER['HTTP_HOST']))
    $HTTP_HOST = $_SERVER['HTTP_HOST'];
else if (!empty($HTTP_SERVER_VARS) && isset($HTTP_SERVER_VARS['HTTP_HOST']))
    $HTTP_HOST = $HTTP_SERVER_VARS['HTTP_HOST'];

    header ("Location: http://$HTTP_HOST$TargetURL"); /* Redirect browser to initial page */
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
?>
<p>
  <?php echo get_vocab("please_login") ?>
</p>
<form method="post" action="<?php echo $PHP_SELF ?>">
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
  <input type="submit" value=" Log in " /> <br />
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
    global $PHP_SELF, $QUERY_STRING;

    print_header(0, 0, 0, 0);
    printLoginForm($TargetURL);
    exit();
}

/* getAuth($realm)
 * 
 * Request that the username/password be given for the specified realm
 * 
 * $realm - Which username/password do we want.
 * 
 * Nothing
 */
function authGet($realm)
{
    global $PHP_SELF, $QUERY_STRING;

    print_header(0, 0, 0, 0);

    echo "<p>".get_vocab("norights")."</p>\n";

    $TargetURL = $PHP_SELF;
    if (isset($QUERY_STRING)) $TargetURL = $TargetURL . "?" . $QUERY_STRING;
    printLoginForm($TargetURL);

    exit();
}

function getUserName()
{
    if (isset($_SESSION) && isset($_SESSION["UserName"]) && ($_SESSION["UserName"] != ""))
        return $_SESSION["UserName"];
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
        return $_SESSION["UserPassword"];
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
    global $PHP_SELF, $QUERY_STRING;

    $TargetURL = $PHP_SELF;
    if (isset($QUERY_STRING)) $TargetURL = $TargetURL . "?" . $QUERY_STRING;
    $user=getUserName();
    if (isset($user))
    {
?>
	<TD CLASS="banner" BGCOLOR="#C0E0FF" ALIGN=CENTER>
	  <A name="logonBox" href="#logonBox"><?php echo get_vocab('you_are')." ".$user ?></A>
          <FORM METHOD=POST ACTION="admin.php">
	    <input type="hidden" name="TargetURL" value="<?php echo $TargetURL ?>" />
	    <input type="hidden" name="Action" value="SetName" />
	    <input type="hidden" name="NewUserName" value="" />
	    <input type="hidden" name="NewUserPassword" value="" />
	    <input type="submit" value=" <?php echo get_vocab('logoff') ?> " />
	  </FORM>
	</TD>
<?php
    }
else
    {
?>
	<TD CLASS="banner" BGCOLOR="#C0E0FF" ALIGN=CENTER>
	  <?php echo get_vocab('unknown_user'); ?><br>
          <FORM METHOD=POST ACTION="admin.php">
	    <input type="hidden" name="TargetURL" value="<?php echo $TargetURL ?>" />
	    <input type="hidden" name="Action" value="QueryName" />
	    <input type="submit" value=" <?php echo get_vocab('login') ?> " />
	  </FORM>
	</TD>
<?php
    }
}
?>
