<?php
function minicals($year, $month, $day, $area) {

if ( ! $friendly ) {
  echo '<TD valign=top ALIGN="left"><TABLE BORDER=1 cellspacing=0>';
  $sun = get_sunday_before ( $year, $month-1, 1 );
  $monthstart = mktime ( 0, 0, 0, $month-1, 1, $year );
  $monthend = mktime ( 0, 0, 0, $month , 0, $year );
  echo "<TR><TD COLSPAN=7 ALIGN=\"middle\"><FONT SIZE=\"-1\">" .
    "<A HREF=\"index.php3?";
  if ( strlen ( $user ) > 0 ) echo "user=$user&";
  echo "year=$year&month=$month\">" .
    strftime ( "%b %Y", $monthstart ) . "</A></FONT></TD></TR>\n";
  for ( $i = $sun; $i < $monthend; $i += ( 24 * 3600 * 7 ) ) {
    print "<TR>\n";
    for ( $j = 0; $j < 7; $j++ ) {
      $date = $i + ( $j * 24 * 3600 );
      if ( $date >= $monthstart && $date <= $monthend ) {
        print "<TD><FONT SIZE=\"-2\">" . "<a href=day.php3?year=" . date("Y",$date) . "&month=" . date("m",$date) . "&day=" .
				date("d",$date) . "&area=$area>" . date ( "d", $date ) . "<a/></FONT></TD>\n";
      } else {
        print "<TD></TD>\n";
      }
    }
    print "</TR>\n";
  }
  echo "</TABLE></TD>\n";
}

?>


<?php


if ( ! $friendly ) {
  echo '<TD valign=top ALIGN="center"><TABLE BORDER=1 cellspacing=0>';
  $sun = get_sunday_before ( $year, $month, 1 );
  $monthstart = mktime ( 0, 0, 0, $month, 1, $year );
  $monthend = mktime ( 0, 0, 0, $month + 1, 0, $year );
  echo "<TR><TD COLSPAN=7 ALIGN=\"middle\"><FONT SIZE=\"-1\">" .
    "<A HREF=\"index.php3?";
  if ( strlen ( $user ) > 0 ) echo "user=$user&";
  echo "year=$year&month=$month\">" .
    strftime ( "%b %Y", $monthstart ) . "</A></FONT></TD></TR>\n";
  for ( $i = $sun; $i < $monthend; $i += ( 24 * 3600 * 7 ) ) {
    print "<TR>\n";
    for ( $j = 0; $j < 7; $j++ ) {
      $date = $i + ( $j * 24 * 3600 );
      if ( $date >= $monthstart && $date <= $monthend ) {
        print "<TD><FONT SIZE=\"-2\">";
				print "<a href=day.php3?year=" . date("Y",$date) . "&month=" . date("m",$date) . "&day=" .
				date("d",$date) . "&area=$area>";
				if (date("Y-m-d",$date) == "$year-$month-$day") { echo "<u><font color=red>";}
				echo date ( "d", $date ) . "</a></FONT></TD>\n";
      } else {
        print "<TD></TD>\n";
      }
    }
    print "</TR>\n";
  }
  echo "</TABLE></TD>\n";
}

?>

<?php


if ( ! $friendly ) {
  echo '<TD valign=top ALIGN="right"><TABLE BORDER=1 cellspacing=0>';
  $sun = get_sunday_before ( $year, $month+1, 1 );
  $monthstart = mktime ( 0, 0, 0, $month+1, 1, $year );
  $monthend = mktime ( 0, 0, 0, $month + 2, 0, $year );
  echo "<TR><TD COLSPAN=7 ALIGN=\"middle\"><FONT SIZE=\"-1\">" .
    "<A HREF=\"index.php3?";
  if ( strlen ( $user ) > 0 ) echo "user=$user&";
  echo "year=$year&month=$month\">" .
    strftime ( "%b %Y", $monthstart ) . "</A></FONT></TD></TR>\n";
  for ( $i = $sun; $i < $monthend; $i += ( 24 * 3600 * 7 ) ) {
    print "<TR>\n";
    for ( $j = 0; $j < 7; $j++ ) {
      $date = $i + ( $j * 24 * 3600 );
      if ( $date >= $monthstart && $date <= $monthend ) {
        print "<TD><FONT SIZE=\"-2\">" . "<a href=day.php3?year=" . date("Y",$date) . "&month=" . date("m",$date) . "&day=" .
				date("d",$date) . "&area=$area>" . date ( "d", $date ) . "<a/></FONT></TD>\n";
      } else {
        print "<TD></TD>\n";
      }
    }
    print "</TR>\n";
  }
  echo "</TABLE></TD>\n";
}

}
?>
