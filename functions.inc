<?php



// determine which browser
// currently supported return values:
//	Mozilla (open source Mozilla 5.0) = "Mozilla/5"
//	Netscape (3.X, 4.X) = "Mozilla/[3,4]"
//	MSIE (4.X) = "MSIE 4"
function get_web_browser () {
  if ( ereg ( "MSIE [0-9]", getenv ( "HTTP_USER_AGENT" ) ) )
    return "MSIE";
  if ( ereg ( "Mozilla/[234]", getenv ( "HTTP_USER_AGENT" ) ) )
    return "Netscape";
  if ( ereg ( "Mozilla/5", getenv ( "HTTP_USER_AGENT" ) ) )
    return "Mozilla";
  return "Uknown";
}


function load_user_preferences () {
  global $login, $browser;

  $browser = get_web_browser ();

  // Note: default values are set in config.inc
  $res = mysql_query (
    "SELECT setting, value FROM cal_user_pref WHERE login = '$login'" );
  if ( $res ) {
    while ( $row = mysql_fetch_array ( $res ) ) {
      $cmd = "\$GLOBALS[" . $row[0] . "] = \"" . $row[1] . "\";";
      eval ( $cmd );
    }
    mysql_free_result ( $res );
  }
}

function get_sunday_before ( $year, $month, $day ) {
  $weekday = date ( "w", mktime ( 0, 0, 0, $month, $day, $year ) );
  $newdate = mktime ( 0, 0, 0, $month, $day - $weekday, $year );
  return $newdate;
}

//
// Print all the calendar entries for the specified user for the
// specified date.  If we are display data from someone other than
// the logged in user, then check the access permission of the entry.
//
function print_date_entries ( $date, $user, $hide_icons ) {
  // removed this layout hack and replaced with css code
  //print "<IMG SRC=\"spacer.gif\" ALIGN=\"right\" WIDTH=1 HEIGHT=75 ALT=\"\">\n";
  //print "<IMG SRC=\"spacer.gif\" WIDTH=75 HEIGHT=1 ALT=\"\"><BR>\n";
  print "<FONT SIZE=\"-1\">";
  if ( ! $hide_icons ) {
    print "<A HREF=\"edit_entry.php3?";
    if ( strcmp ( $user, $GLOBALS["login"] ) )
      print "user=$user&";
    print "year=" . date ( "Y", $date ) .
      "&month=" . date ( "m", $date ) . "&day=" . date ( "d", $date  ) . "\">" .
      "<IMG SRC=\"new.gif\" WIDTH=10 HEIGHT=10 ALT=\"New Entry\" BORDER=0 ALIGN=right>" .
      "</A>";
  }
  print "[" . date ( "d", $date ) . "]";
  print "<BR>\n";
  $sql = "SELECT cal_entry.id, cal_entry.name, cal_entry.priority, " .
    "cal_entry.time, cal_entry_user.status, cal_entry.create_by, " .
    "cal_entry.access " .
    "FROM cal_entry, cal_entry_user " .
    "WHERE cal_entry.id = cal_entry_user.id AND " .
    "cal_entry_user.login = '" . $user . "' AND " .
    "cal_entry.date = '" . date ( "Y-m-d", $date ) . "'";
  if ( $GLOBALS["DISPLAY_UNAPPROVED"] = "N" )
    $sql .= " AND cal_entry_user.status = 'A'";
  $sql .= " ORDER BY cal_entry.time";
  //print "SQL: $sql<BR>";
  $res = mysql_query ( $sql );
  if ( $res ) {
    echo "<NOBR>";
    while ( $row = mysql_fetch_array ( $res ) ) {
      if ( ! $hide_icons ) {
        echo "<A HREF=\"view_entry.php3?id=$row[0]\" onMouseOver=\"window.status='View this entry'; return true;\"><IMG SRC=\"circle.gif\" WIDTH=5 HEIGHT=7 ALT=\"view icon\" BORDER=0></A>";
      }
      if ( $row[4] == "W" ) echo "<FONT COLOR=\"$GLOBALS[UNAPPROVED_COLOR]\">";
      if ( $row[2] == 3 ) echo "<B>";
      if ( strlen ( $row[3] ) ) {
        $list = split ( ":", $row[3] );
        if ( $GLOBALS["TIME_FORMAT"] == "24" ) {
          printf ( "%02d:%02d", $list[0], $list[1] );
        } else {
          $h = $list[0] % 12;
          if ( $h == 0 ) $h = 12;
          echo $h;
          if ( $list[1] > 0 )
            printf ( ":%02d", $list[1] );
          echo $list[0] < 12 ? "am" : "pm";
        }
        echo "&gt;";
      }
      echo "</NOBR>";
      if ( $GLOBALS["login"] != $user && $row[6] == 'R' )
        echo "(PRIVATE)";
      else
        echo htmlentities ( $row[1] );
      //if ( ! $hide_icons ) {
      //  echo "<A HREF=\"view_entry.php3?id=$row[0]\" onMouseOver=\"window.status='View this entry'; return true;\"><IMG SRC=\"view.gif\" WIDTH=10 HEIGHT=10 ALT=\"view icon\" BORDER=0></A>\n";
      //  if ( $row[5] == $GLOBALS["login"] || strlen ( $single_user_login ) > 0
      //    || $GLOBALS["is_admin"] = "Y" )
      //    echo "<A HREF=\"edit_entry.php3?id=$row[0]\" onMouseOver=\"window.status='Edit this entry'; return true;\"><IMG SRC=\"edit.gif\" WIDTH=10 HEIGHT=10 ALT=\"edit icon\" BORDER=0></A>\n";
        //echo "<A HREF=\"del_entry.php3?id=$row[0]\" onMouseOver=\"window.status='Delete this entry'; return true;\" onClick=\"return confirm('Are you sure\\nyou want to\\ndelete this entry?');\"><IMG SRC=\"delete.gif\" WIDTH=10 HEIGHT=10 ALT=\"delete icon\" BORDER=0></A>\n";
      //}
      if ( $row[2] == 3 ) echo "</B>";
      if ( $row[4] == "W" ) echo "</FONT>";
      echo "<BR>";
    }
  }
  mysql_free_result ( $res );
  print "</FONT>";
}


// display a link to any unapproved events
function display_unapproved_events ( $user ) {
  $sql = "SELECT COUNT(id) FROM cal_entry_user " .
    "WHERE status = 'W' AND login = '$user'";
  //print "SQL: $sql<BR>";
  $res = mysql_query ( $sql );
  if ( $res ) {
    if ( $row = mysql_fetch_array ( $res ) ) {
      if ( $row[0] > 0 )
        printf ( "You have <A HREF=\"list_unapproved.php3\">%d unapproved %s</A><BR>\n", $row[0], ( $row[0] > 1 ? "events" : "event" ) );
    }
    mysql_free_result ( $res );
  }
}


// display a time in either 12 or 24 hour format
function display_time ( $time ) {
  $list = split ( ":", $time );
  $hour = $list[0];
  $min = $list[1];
  if ( $GLOBALS["TIME_FORMAT"] == "12" ) {
    $ampm = $hour >= 12 ? "pm" : "am";
    $hour %= 12;
    if ( $hour == 0 )
      $hour = 12;
    $ret = sprintf ( "%d:%02d%s", $hour, $min, $ampm );
  } else {
    $ret = sprintf ( "%d:%02d", $hour, $min );
  }
  return $ret;
}

// convert a date from MySQL date format "1999-12-31" into "31 Dec 1999"
function mysql_date_to_str ( $indate ) {
  if ( strlen ( $indate ) > 0 ) {
    $list = split ( "-", $indate );
    $d = mktime ( 0, 0, 0, $list[1], $list[2], $list[0] );
    return strftime ( "%A, %B %d, %Y", $d );
  } else {
    return strftime ( "%A, %B %d, %Y" );
  }
}

// Define an array to use to jumble up the key
$offsets = array ( 31, 41, 59, 26, 54 );


function hextoint ( $val ) {
  if ( ! strlen ( $val ) )
    return 0;
  switch ( strtoupper ( $val ) ) {
    case "0": return 0;
    case "1": return 1;
    case "2": return 2;
    case "3": return 3;
    case "4": return 4;
    case "5": return 5;
    case "6": return 6;
    case "7": return 7;
    case "8": return 8;
    case "9": return 9;
    case "A": return 10;
    case "B": return 11;
    case "C": return 12;
    case "D": return 13;
    case "E": return 14;
    case "F": return 15;
  }
  return 0;
}

// Extract a user's name from a session id
// This is a lame attempt at security.  Otherwise, users would be
// able to edit their cookies.txt file and set the username in plain
// text.
// $instr is a hex-encoded string. "Hello" would be "48656c6c6f".
function decode_string ( $instr ) {
  $orig = "";
  for ( $i = 0; $i < strlen ( $instr ); $i += 2 ) {
    $ch1 = substr ( $instr, $i, 1 );
    $ch2 = substr ( $instr, $i + 1, 1 );
    $val = hextoint ( $ch1 ) * 16 + hextoint ( $ch2 );
    $j = $i % count ( $offsets );
    $newval = $val + $offsets[$j];
    $newval %= 256;
    $orig .= chr ( $val );
  }
  return $orig;
}

function encode_string ( $instr ) {
  $ret = "";
  for ( $i = 0; $i < strlen ( $instr ); $i++ ) {
    $ch1 = substr ( $instr, $i, 1 );
    $val = ord ( $ch1 );
    $j = $i % count ( $offsets );
    $newval = $val + $offsets[$j];
    $newval %= 256;
    $ret .= bin2hex ( chr ( $newval ) );
  }
  return $ret;
}


function toTimeString(&$dur, &$units)
{
	if($dur >= 60)
	{
		$dur /= 60;
		
		if($dur >= 60)
		{
			$dur /= 60;
			
			if(($dur >= 24) && ($dur % 24 == 0))
			{
				$dur /= 24;
				
				if(($dur >= 7) && ($dur % 7 == 0))
				{
					$dur /= 7;
					
					if(($dur >= 52) && ($dur % 52 == 0))
					{
						$dur  /= 52;
						$units = "years";
					}
					else
						$units = "weeks";
				}
				else
					$units = "days";
			}
			else
				$units = "hours";
		}
		else
			$units = "minutes";
	}
	else
		$units = "seconds";
}

function genDateSelector($prefix, $day, $month, $year)
{
	if($day   == 0) $day = date("d");
	if($month == 0) $month = date("m");
	if($year  == 0) $year = date("Y");
	
	echo "<SELECT NAME=\"${prefix}day\">\n";
	
	for($i = 1; $i <= 31; $i++)
		echo "<OPTION" . ($i == $day ? " SELECTED" : "") . ">$i\n";
	
	echo "</SELECT>\n";
	echo "<SELECT NAME=\"${prefix}month\">\n";
	
	for($i = 1; $i <= 12; $i++)
	{
		$m = strftime("%b", mktime(0, 0, 0, $i, 1, $year));
		
		print "<OPTION VALUE=\"$i\"" . ($i == $month ? " SELECTED" : "") . ">$m\n";
	}
	
	echo "</SELECT>\n";
	echo "<SELECT NAME=\"${prefix}year\">\n";
	
	for($i = -1; $i < 5; $i++)
	{
		$y = $year + $i;
		
		print "<OPTION" . ($y == $year ? " SELECTED" : "") . ">$y\n";
	}
	
	echo "</SELECT>\n";
}

?>
