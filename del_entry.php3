<?

include "config.inc";
include "functions.inc";
include "connect.inc";

if ( $id > 0 ) {
  mysql_query ( "DELETE FROM mrbs_entry WHERE id = $id" );
}

Header ( "Location: day.php3?day=$day&month=$month&year=$year");
