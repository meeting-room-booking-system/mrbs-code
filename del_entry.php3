<?php

include "config.inc";
include "functions.inc";
include "validate.inc";
include "connect.inc";

if ( $id > 0 ) {
  $res = mysql_query ( "SELECT date FROM cal_entry WHERE id = $id" );
  if ( $res ) {
    // MySQL date format is '1999-12-31'
    $row = mysql_fetch_array ( $res );
    $list = split ( "-", $row[0] );
    $thisyear = $list[0];
    $thismonth = $list[1];
  }
  mysql_query ( "DELETE FROM cal_entry WHERE id = $id" );
  mysql_query ( "DELETE FROM cal_entry_user WHERE id = $id" );
}

Header ( "Location: day.php3?day=$day&month=$month&year=$year");
#  ( $thisyear > 0 ? "?year=$thisyear&month=$thismonth" : "" ) );
exit;
