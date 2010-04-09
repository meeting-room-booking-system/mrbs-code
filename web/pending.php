<?php
// $Id$

require_once "defaultincludes.inc";

function display_buttons($row, $is_series)
{
  global $PHP_SELF;
  global $user, $remind_interval;
  
  $last_reminded = (empty($row['reminded'])) ? $row['last_updated'] : $row['reminded'];
  $returl = $PHP_SELF;
                                    
  $target_id = ($is_series) ? $row['repeat_id'] : $row['id'];

  // When we're going to view_entry.php we need to pass the id and series
  // in a query string rather than as hidden inputs.   That's because some
  // pages called by view_entry use HTTP_REFERER to form a return URL, and
  // view_entry needs to have a valid id.
  $query_string = "id=$target_id";
  $query_string .= ($is_series) ? "&amp;series=1" : "";
  
  if (auth_book_admin($user, $row['room_id']))
  {
    // accept
    echo "<form action=\"confirm_entry_handler.php\" method=\"post\">\n";
    echo "<div>\n";
    echo "<input type=\"hidden\" name=\"action\" value=\"accept\">\n";
    echo "<input type=\"hidden\" name=\"id\" value=\"$target_id\">\n";
    echo "<input type=\"hidden\" name=\"series\" value=\"$is_series\">\n";
    echo "<input type=\"hidden\" name=\"returl\" value=\"" . htmlspecialchars($returl) . "\">\n";
    echo "<input type=\"submit\" value=\"" . get_vocab("accept") . "\">\n";
    echo "</div>\n";
    echo "</form>\n";
    // reject
    echo "<form action=\"view_entry.php?$query_string\" method=\"post\">\n";
    echo "<div>\n";
    echo "<input type=\"hidden\" name=\"action\" value=\"reject\">\n";
    echo "<input type=\"hidden\" name=\"returl\" value=\"" . htmlspecialchars($returl) . "\">\n";
    echo "<input type=\"submit\" value=\"" . get_vocab("reject") . "\">\n";
    echo "</div>\n";
    echo "</form>\n";
    // more info
    echo "<form action=\"view_entry.php?$query_string\" method=\"post\">\n";
    echo "<div>\n";
    echo "<input type=\"hidden\" name=\"action\" value=\"more_info\">\n";
    echo "<input type=\"hidden\" name=\"returl\" value=\"" . htmlspecialchars($returl) . "\">\n";
    echo "<input type=\"submit\" value=\"" . get_vocab("more_info") . "\">\n";
    echo "</div>\n";
    echo "</form>\n";
  }
  else
  {
    // Work out whether enough time has elapsed since the last reminder
    $not_yet_time = working_time_diff(time(), $last_reminded) < $remind_interval;

    // if enough time has passed since the last reminder
    // output a "Remind Admin" button, otherwise nothing
    if ($not_yet_time)
    {
      echo "&nbsp";
    }
    else
    {
      echo "<form action=\"confirm_entry_handler.php\" method=\"post\">\n";
      echo "<div>\n";
      echo "<input type=\"hidden\" name=\"action\" value=\"remind_admin\">\n";
      echo "<input type=\"hidden\" name=\"id\" value=\"" . $row['id'] . "\">\n";
      echo "<input type=\"hidden\" name=\"returl\" value=\"" . htmlspecialchars($returl) . "\">\n";
      echo "<input type=\"submit\" value=\"" . get_vocab("remind_admin") . "\">\n";
      echo "</div>\n";
      echo "</form>\n";
    }
  }
}

// display the header row for a series
function display_series_header($row, $table_id)
{  
  echo "<tr>";  // no \n so as not to create another child in the DOM
  echo "<th class=\"control\" onClick=\"toggle_table('$table_id')\">&nbsp;</th>\n";
  // reservation name, with a link to the view_entry page
  echo "<th class=\"header_name\"><a href=\"view_entry.php?id=".$row['repeat_id']."&amp;series=1\">" . htmlspecialchars($row['name']) ."</a></th>\n";
  
  // create_by, area and room names
  echo "<th class=\"header_create\">" . htmlspecialchars($row['create_by']) . "</th>\n";
  echo "<th class=\"header_area\">"   . htmlspecialchars($row['area_name']) . "</th>\n";
  echo "<th class=\"header_room\">"   . htmlspecialchars($row['room_name']) . "</th>\n";
  
  echo "<th class=\"header_start_time\">" . get_vocab("series") . "</th>\n";
  
  echo "<th class=\"header_action\">\n";
  display_buttons($row, TRUE);
  echo "</th>\n";
  echo "</tr>\n";
}

// display an entry in a row
function display_entry_row($row)
{
  global $enable_periods;
  
  echo "<tr>\n";
  echo "<td class=\"control\">&nbsp;</td>\n";
    
  // reservation name, with a link to the view_entry page
  echo "<td>";
  echo "<a href=\"view_entry.php?id=".$row['id']."\">" . htmlspecialchars($row['name']) ."</a></td>\n";
    
  // create_by, area and room names
  echo "<td>" . htmlspecialchars($row['create_by']) . "</td>\n";
  echo "<td>" . htmlspecialchars($row['area_name']) . "</td>\n";
  echo "<td>" . htmlspecialchars($row['room_name']) . "</td>\n";
    
  // start date, with a link to the day.php
  $link = getdate($row['start_time']);
  echo "<td>";
  echo "<a href=\"day.php?day=$link[mday]&amp;month=$link[mon]&amp;year=$link[year]&amp;area=".$row['area_id']."\">";
  if(empty($enable_periods))
  {
    $link_str = time_date_string($row['start_time']);
  }
  else
  {
    list(,$link_str) = period_date_string($row['start_time']);
  }
  echo "$link_str</a></td>";
    
  // action buttons
  echo "<td>\n";
  display_buttons($row, FALSE);
  echo "</td>\n";
  echo "</tr>\n";  
}

$user = getUserName();

// Get form variables
$day = get_form_var('day', 'int');
$month = get_form_var('month', 'int');
$year = get_form_var('year', 'int');
$area = get_form_var('area', 'int');
$room = get_form_var('room', 'int');

// If we dont know the right date then make it up
if (!isset($day) or !isset($month) or !isset($year))
{
  $day   = date("d");
  $month = date("m");
  $year  = date("Y");
}
if (empty($area))
{
  $area = get_default_area();
}


if(!getAuthorised(1))
{
  showAccessDenied($day, $month, $year, $area, isset($room) ? $room : "");
  exit;
}
$user = getUserName();
$is_admin = (authGetUserLevel($user) >= 2);

print_header($day, $month, $year, $area, isset($room) ? $room : "");

echo "<h1>" . get_vocab("pending") . "</h1>\n";

// Get a list of all the provisional bookings
// We are only interested in areas where provisional bookings are enabled
$sql = "SELECT E.id, E.name, E.room_id, E.start_time, E.create_by, " .
               sql_syntax_timestamp_to_unix("E.timestamp") . " AS last_updated,
               E.reminded, E.repeat_id,
               R.room_name, R.area_id, A.area_name
        FROM  $tbl_room AS R, $tbl_area AS A, $tbl_entry AS E
        WHERE E.room_id = R.id
          AND R.area_id = A.id
          AND A.provisional_enabled>0
          AND status=" . STATUS_PROVISIONAL;

// Ordinary users can only see their own bookings       
if (!$is_admin)
{
  $sql .= " AND E.create_by='" . addslashes($user) . "'";
}
// We want entries for a series to appear together so that we can display
// them as a separate table below the main entry for the series. 
$sql .= " ORDER BY E.repeat_id, start_time";

$res = sql_query($sql);
if (! $res)
{
  fatal_error(0, sql_error());
}
if (sql_count($res) == 0)
{
  echo "<p>" .get_vocab("none_outstanding") . "</p>\n";
}
else  // display them in a table
{
  echo "<table id=\"pending_list\" class=\"admin_table\">\n";
  echo "<thead>\n";
  echo "<tr>\n";
  $n_cols = 7;
  echo "<th class=\"control\">&nbsp;</th>\n";
  echo "<th class=\"header_name\">" . get_vocab("entry") . "</th>\n";
  echo "<th class=\"header_create\">" . get_vocab("createdby") . "</th>\n";
  echo "<th class=\"header_area\">" . get_vocab("area") . "</th>\n";
  echo "<th class=\"header_room\">" . get_vocab("room") . "</th>\n";
  echo "<th class=\"header_start_time\">" . get_vocab("start_date") . "</th>\n";
  echo "<th class=\"header_action\">" . get_vocab("action") . "</th>\n";
  echo "</tr>\n";
  echo "</thead>\n";
  
  echo "<tbody>\n";
  $last_repeat_id = 0;
  $is_series = FALSE;
  for ($i = 0; ($row = sql_row_keyed($res, $i)); $i++)
  { 
    if ($row['repeat_id'] != $last_repeat_id)
    // there's some kind of change
    {
      $last_repeat_id = $row['repeat_id'];
      if ($is_series)
      {
        // end the last series table if there was one
        $is_series = FALSE;
        echo "</tbody></table></td></tr>\n";
      }
    
      if (!empty($row['repeat_id']))
      {
        // we're starting a new series
        $is_series = TRUE;
        echo "<tr class=\"sub_table\">\n";
        echo "<td class=\"sub_table\" colspan=\"$n_cols\">";
        $table_id = "series" . $row['repeat_id'];
        echo "<table id=\"$table_id\" class=\"maximised\">";  // no \n so as not to create another child in the DOM
        echo "<thead>";  // no \n so as not to create another child in the DOM
        display_series_header($row, $table_id);
        echo "</thead>\n";
        echo "<tbody>\n";
      }      
    }
    display_entry_row($row);
  }
  if ($is_series)
  {
    // if we were in a series, then close the sub-table
    echo "</tbody></table></td></tr>\n";
  }
  echo "</tbody>\n"; 
  echo "</table>\n ";
}

require_once "trailer.inc";
?>
