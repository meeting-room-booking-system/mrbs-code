<?php

include "config.inc";
include "functions.inc";
include "validate.inc";
include "connect.inc";

load_user_preferences ();

$duration = $duration * 60;

function add_duration ( $time, $duration ) {
  $list = split ( ":", $time );
  $hour = $list[0];
  $min = $list[1];
  $minutes = $hour * 60 + $min + $duration;
  $h = $minutes / 60;
  $m = $minutes % 60;
  $ret = sprintf ( "%d:%02d", $h, $m );
  //echo "add_duration ( $time, $duration ) = $ret <BR>";
  return $ret;
}

// check to see if two events overlap
function times_overlap ( $time1, $duration1, $time2, $duration2 ) {
  //echo "times_overlap ( $time1, $duration1, $time2, $duration2 )<BR>";
  $list1 = split ( ":", $time1 );
  $hour1 = $list1[0];
  $min1 = $list1[1];
  $list2 = split ( ":", $time2 );
  $hour2 = $list2[0];
  $min2 = $list2[1];
  // convert to minutes since midnight
  $tmins1start = ($hour1 * 60 + $min1) * 60;
  $tmins1end = $tmins1start + ($duration1 * 60) - 1;
  $tmins2start = ($hour2 * 60 + $min2) * 60;
  $tmins2end = $tmins2start + ($duration2 * 60) - 1;
  //echo "tmins1start=$tmins1start, tmins1end=$tmins1end, tmins2start=$tmins2start, tmins2end=$tmins2end<BR>";
  if ( $tmins1start >= $tmins2start && $tmins1start <= $tmins2end )
    return true;
  if ( $tmins1end >= $tmins2start && $tmins1end <= $tmins2end )
    return true;
  if ( $tmins2start >= $tmins1start && $tmins2start <= $tmins1end )
    return true;
  if ( $tmins2end >= $tmins1start && $tmins2end <= $tmins1end )
    return true;
  return false;
}


// first check for any schedule conflicts
if ( strlen ( $hour ) > 0 ) {
  $date = mktime ( 0, 0, 0, $month, $day, $year );
  if ( $TIME_FORMAT == "12" ) {
    $hour %= 12;
    if ( $ampm == "pm" )
      $hour += 12;
  }
  $sql .= sprintf ( "'%02d:%02d:00', ", $hour, $minute );
  $sql = "SELECT cal_entry_user.login, cal_entry.time," .
    " cal_entry.duration, cal_entry.name, cal_entry.id, cal_entry.access" .
    " FROM cal_entry, cal_entry_user" .
    " WHERE cal_entry.id = cal_entry_user.id" .
    " AND cal_entry.date = '" . date ( "Y-m-d", $date ) . "'" .
    " AND ( ";
  if ( strlen ( $single_user_login ) ) {
    $participants[0] = $single_user_login;
  }
  for ( $i = 0; $i < count ( $participants ); $i++ ) {
    if ( $i ) $sql .= " OR ";
    $sql .= " cal_entry_user.login = '" . $participants[$i] . "'";
  }
  $sql .= " )";
  //echo "SQL: $sql<P>";
  $res = mysql_query ( $sql );
  if ( $res ) {
    $time1 = sprintf ( "%d:%02d", $hour, $minute );
    $duration1 = sprintf ( "%d", $duration );
    while ( $row = mysql_fetch_array ( $res ) ) {
      // see if either event overlaps one another
      if ( $row[4] != $id ) {
        $time2 = $row[1];
        $duration2 = $row[2];
        if ( times_overlap ( $time1, $duration1, $time2, $duration2 ) ) {
          $overlap .= "<LI>";
          if ( ! strlen ( $single_user_login ) )
            $overlap .= "$row[0]: ";
          if ( $row[5] == 'R' && $row[0] != $login )
            $overlap .=  "(PRIVATE)";
          else {
            $overlap .=  "<A HREF=\"view_entry.php3?id=$row[4]";
            if ( $user != $login )
              $overlap .= "&user=$user";
            $overlap .= "\">$row[3]</A>";
          }
          $overlap .= " (" . display_time ( $time2 );
          if ( $duration2 > 0 )
            $overlap .= "-" .
              display_time ( add_duration ( $time2, $duration2 ) );
          $overlap .= ")";
        }
      }
    }
    mysql_free_result ( $res );
  }
}

if ( strlen ( $overlap ) ) {
  $error = "The following conflicts with the suggested time:<UL>$overlap</UL>";
}


if ( strlen ( $error ) == 0 ) {

  // now add the entries
  if ( $id == 0 ) {
    $res = mysql_query ( "SELECT MAX(id) FROM cal_entry" );
    if ( $res ) {
      $row = mysql_fetch_array ( $res );
      $id = $row[0] + 1;
    } else {
      $id = 1;
    }
  } else {
    mysql_query ( "DELETE FROM cal_entry WHERE id = $id" );
    mysql_query ( "DELETE FROM cal_entry_user WHERE id = $id" );
  }

	if ($login == "guest") {$loginx = $REMOTE_ADDR; } else {$loginx = $login; }
  $sql = "INSERT INTO cal_entry ( id, create_by, date, time, timestamp, duration, priority, access, type, status,
	name, description ) VALUES ( $id, '$loginx', ";

  $date = mktime ( 0, 0, 0, $month, $day, $year );
  $sql .= "'" . date ( "Y-m-d", $date ) . "', ";
  if ( strlen ( $hour ) > 0 ) {
    if ( $TIME_FORMAT == "12" ) {
      $hour %= 12;
      if ( $ampm == "pm" )
        $hour += 12;
    }
    $sql .= sprintf ( "'%02d:%02d:00', ", $hour, $minute );
  } else
    $sql .= "NULL, ";
  $sql .= "'" . date ( "Y-m-d G:i:s" ) . "', ";
  $sql .= sprintf ( "%d, ", $duration );
  $sql .= sprintf ( "%d, ", $priority );
  $sql .= "'$access', ";
  $sql .= "'E', ";
  $sql .= "NULL, ";
  if ( strlen ( $name ) == 0 )
    $name = "Unnamed Event";
  $sql .= "'" . $name .  "', ";
  if ( strlen ( $description ) == 0 )
    $description = $name;
  $sql .= "'" . addslashes($description) . "' )";
  
  $error = "";
  if ( ! mysql_query ( $sql ) )
    $error = "Unable to add entry: " . mysql_error () . "<P><B>SQL:</B> $sql";
  $msg .= "<B>SQL:</B> $sql<P>";
  
  // now add participants
  if ( strlen ( $single_user_login ) ) {
    $participants[0] = $single_user_login;
  }
  for ( $i = 0; $i < count ( $participants ); $i++ ) {
    $status = ( $participants[$i] != $login && $require_approvals ) ? "W" : "A";
    $sql = "INSERT INTO cal_entry_user ( id, login, status ) VALUES ( $id, '" .
      $participants[$i] . "', '$status' )";
    if ( ! mysql_query ( $sql ) ) {
      $error = "Unable to add to cal_entry_user: " . mysql_error () .
        "<P><B>SQL:</B> $sql";
      break;
    }
    $msg .= "<B>SQL:</B> $sql<P>";
  }
}

//print $msg; exit;

if ( strlen ( $error ) == 0 ) {
	if (strlen($returl) == 0) {
		$returl = "index.php3?year=$year&month=$month";
	}
  Header ( "Location: $returl" );
  exit;
}

?>
<HTML>
<HEAD><TITLE>WebCalendar</TITLE>
<?include "style.inc"?>
</HEAD>
<BODY BGCOLOR="<?php echo $BGCOLOR; ?>">

<?php if ( strlen ( $overlap ) ) { ?>
<H2><FONT COLOR="<?php echo $H2COLOR;?>">Scheduling Conflict</H2></FONT>

Your suggested time of <B>
<?php
  $time = sprintf ( "%d:%02d", $hour, $minute );
  echo display_time ( $time );
  if ( $duration > 0 )
    echo "-" . display_time ( add_duration ( $time, $duration ) );
?>
</B> conflicts with the following existing calendar entries:
<UL>
<?php echo $overlap; ?>
</UL>

<?php } else { ?>
<H2><FONT COLOR="<?php echo $H2COLOR;?>">Error</H2></FONT>
<BLOCKQUOTE>
<?php echo $error; ?>
</BLOCKQUOTE>

<?php } 

echo "<a href=$returl>Return to Calendar View</a><p>";

include "trailer.inc"; ?>

</BODY>
</HTML>
