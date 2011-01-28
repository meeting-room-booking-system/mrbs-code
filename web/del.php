<?php
// $Id$

require_once "defaultincludes.inc";

// Get non-standard form variables
$type = get_form_var('type', 'string');
$confirm = get_form_var('confirm', 'string');


// Check the user is authorised for this page
checkAuthorised();

// This is gonna blast away something. We want them to be really
// really sure that this is what they want to do.

if ($type == "room")
{
  // We are supposed to delete a room
  if (isset($confirm))
  {
    // They have confirmed it already, so go blast!
    sql_begin();
    // First take out all appointments for this room
    sql_command("delete from $tbl_entry where room_id=$room");
    sql_command("delete from $tbl_repeat where room_id=$room");
   
    // Now take out the room itself
    sql_command("delete from $tbl_room where id=$room");
    sql_commit();
   
    // Go back to the admin page
    Header("Location: admin.php?area=$area");
  }
  else
  {
    print_header($day, $month, $year, $area, isset($room) ? $room : "");
   
    // We tell them how bad what theyre about to do is
    // Find out how many appointments would be deleted
   
    $sql = "select name, start_time, end_time from $tbl_entry where room_id=$room";
    $res = sql_query($sql);
    if (! $res)
    {
      echo sql_error();
    }
    else if (sql_count($res) > 0)
    {
      echo "<p>\n";
      echo get_vocab("deletefollowing") . ":\n";
      echo "</p>\n";
      
      echo "<ul>\n";
      
      for ($i = 0; ($row = sql_row_keyed($res, $i)); $i++)
      {
        echo "<li>".htmlspecialchars($row['name'])." (";
        echo time_date_string($row['start_time']) . " -> ";
        echo time_date_string($row['end_time']) . ")</li>\n";
      }
      
      echo "</ul>\n";
    }
   
    echo "<div id=\"del_room_confirm\">\n";
    echo "<p>" .  get_vocab("sure") . "</p>\n";
    echo "<div id=\"del_room_confirm_links\">\n";
    echo "<a href=\"del.php?type=room&amp;area=$area&amp;room=$room&amp;confirm=Y\"><span id=\"del_yes\">" . get_vocab("YES") . "!</span></a>\n";
    echo "<a href=\"admin.php\"><span id=\"del_no\">" . get_vocab("NO") . "!</span></a>\n";
    echo "</div>\n";
    echo "</div>\n";
    require_once "trailer.inc";
  }
}

if ($type == "area")
{
  // We are only going to let them delete an area if there are
  // no rooms. its easier
  $n = sql_query1("select count(*) from $tbl_room where area_id=$area");
  if ($n == 0)
  {
    // OK, nothing there, lets blast it away
    sql_command("delete from $tbl_area where id=$area");
   
    // Redirect back to the admin page
    header("Location: admin.php");
  }
  else
  {
    // There are rooms left in the area
    print_header($day, $month, $year, $area, isset($room) ? $room : "");
    echo "<p>\n";
    echo get_vocab("delarea");
    echo "<a href=\"admin.php\">" . get_vocab("backadmin") . "</a>";
    echo "</p>\n";
    require_once "trailer.inc";
  }
}

?>
