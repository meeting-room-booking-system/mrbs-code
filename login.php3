<?php

include "config.inc";
include "functions.inc";
include "connect.inc";

$year=date("Y");
$month=date("m");
$day=date("d");

if ( strlen ( $single_user_login ) ) {
  // No login for single-user mode
  Header ( "Location: day.php3?year=$year&month=$month&day=$day" );
} else {
  if ( ! empty ( $login ) && ! empty ( $password ) ) {
    $sql = "SELECT lastname, firstname FROM cal_user WHERE " .
      "login = '" . $login . "' AND passwd = '" . $password . "'";
    $res = mysql_query ( $sql );
    if ( ! $res ) {
      $error = "Invalid login";
    } else {
      $row = mysql_fetch_array ( $res );
      if ( $row ) {
#				echo "yep";
				// set login to expire in 1000 days
        $encoded_login = encode_string ( $login );
        SetCookie ( "webcalendar_session", $encoded_login,
          time() + (3600 * 10) ); # Keep the cookie valid for 10 hours
#        echo "enc1 - $encoded_login";
				// The cookie "webcalendar_login" is provided as a convenience to
        // other apps that may wish to find out what the last calendar
        // login was, so they can use week_ssi.php3 as a server-side include.
        // As such, it's not a security risk to have it un-encoded since it
        // is not used with this app.
        SetCookie ( "webcalendar_login", $login,
          time() + (3600 * 10), "/" );
        Header ( "Location: day.php3?year=$year&month=$month&day=$day" );
        exit;
      }
    }
    $error = "Invalid login";
  }
  // delete current user
  SetCookie ( "webcalendar_session", "" );
}

?>
<HTML>
<HEAD>
<TITLE>WebCalendar: Login</TITLE>

<?include "style.inc"?>

</HEAD>
<BODY BGCOLOR="#C0C0C0">

<H1 class="sitename">Room Booking System</H1>
<img src=meetingroomsystem.gif><br>
<h1>MCUK Meeting Room System</h1>
<?php
if ( strlen ( $error ) > 0 ) {
  print "<FONT COLOR=\"#FF0000\"><B>Error:</B> $error</FONT><P>\n";
}
?>
<FORM ACTION="login.php3" METHOD="POST">

<b>Log in as user "guest", password "guest"</B><p>

<TABLE BORDER=0>
<TR><TD><B>Login:</B></TD>
  <TD><INPUT NAME="login" SIZE=10 VALUE="<?php echo $login;?>"></TD></TR>
<TR><TD><B>Password:</B></TD>
  <TD><INPUT NAME="password" TYPE="password" SIZE=10></TD></TR>
<TR><TD COLSPAN=2><INPUT TYPE="hidden" NAME="remember" VALUE="yes" CHECKED> </TD></TR>
<TR><TD COLSPAN=2><INPUT TYPE="submit" VALUE="Login"></TD></TR>
</TABLE>

</FORM>

If you need more information then please see <a href=http://water/is/docs/RoomBookingSystem.pdf>this document</a> for an introduction.
</BODY>
</HTML>
