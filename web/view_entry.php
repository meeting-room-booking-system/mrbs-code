<?php
// $Id$

require_once "defaultincludes.inc";
require_once "mrbs_sql.inc";

// Generates a single button
function generateButton($form_action, $id, $series, $action_type, $returl, $submit_value, $title='')
{
  global $room_id;
  
  echo "<form action=\"$form_action?id=$id&amp;series=$series\" method=\"post\">\n";
  echo "<fieldset>\n";
  echo "<legend></legend>\n";
  echo "<input type=\"hidden\" name=\"action\" value=\"$action_type\">\n";
  echo "<input type=\"hidden\" name=\"room_id\" value=\"$room_id\">\n";
  echo "<input type=\"hidden\" name=\"returl\" value=\"" . htmlspecialchars($returl) . "\">\n";
  echo "<input type=\"submit\" title=\"" . htmlspecialchars($title) . "\" value=\"$submit_value\">\n";
  echo "</fieldset>\n";
  echo "</form>\n";  
}

// Generates the Accept, Reject and More Info buttons
function generateConfirmButtons($id, $series)
{
  global $returl, $PHP_SELF;
  global $entry_info_time, $entry_info_user, $repeat_info_time, $repeat_info_user;
  
  $info_time = ($series) ? $repeat_info_time : $entry_info_time;
  $info_user = ($series) ? $repeat_info_user : $entry_info_user;
  
  $this_page = basename($PHP_SELF);
  if (empty($info_time))
  {
    $info_title = get_vocab("no_request_yet");
  }
  else
  {
    $info_title = get_vocab("last_request") . ' ' . time_date_string($info_time);
    if (!empty($info_user))
    {
      $info_title .= " " . get_vocab("by") . " $info_user";
    }
  }
  
  echo "<tr>\n";
  echo "<td>" . ($series ? get_vocab("series") : get_vocab("entry")) . ":</td>\n";
  echo "<td>\n";
  generateButton("confirm_entry_handler.php", $id, $series, "accept", $returl, get_vocab("accept"));
  generateButton($this_page, $id, $series, "reject", $returl, get_vocab("reject"));
  generateButton($this_page, $id, $series, "more_info", $returl, get_vocab("more_info"), $info_title);
  echo "</td>\n";
  echo "</tr>\n";
}

function generateOwnerButtons($id, $series)
{
  global $user, $create_by, $status, $area;
  global $PHP_SELF, $reminders_enabled, $last_reminded, $reminder_interval;
  
  $this_page = basename($PHP_SELF);
  
  // Remind button if you're the owner AND there's a provisional
  // booking outstanding AND sufficient time has passed since the last reminder
  // AND we want reminders in the first place
  if (($reminders_enabled) &&
      ($user == $create_by) && 
      ($status == STATUS_PROVISIONAL) &&
      (working_time_diff(time(), $last_reminded) >= $reminder_interval))
  {
    echo "<tr>\n";
    echo "<td>&nbsp;</td>\n";
    echo "<td>\n";
    generateButton("confirm_entry_handler.php", $id, $series, "remind", $this_page . "?id=$id&amp;area=$area", get_vocab("remind_admin"));
    echo "</td>\n";
    echo "</tr>\n";
  } 
}

function generateTextArea($form_action, $id, $series, $action_type, $returl, $submit_value, $caption, $value='')
{
  echo "<tr><td id=\"caption\" colspan=\"2\">$caption:</td></tr>\n";
  echo "<tr>\n";
  echo "<td id=\"note\" colspan=\"2\">\n";
  echo "<form action=\"$form_action\" method=\"post\">\n";
  echo "<fieldset>\n";
  echo "<legend></legend>\n";
  echo "<textarea name=\"note\">" . htmlspecialchars($value) . "</textarea>\n";
  echo "<input type=\"hidden\" name=\"id\" value=\"$id\">\n";
  echo "<input type=\"hidden\" name=\"series\" value=\"$series\">\n";
  echo "<input type=\"hidden\" name=\"returl\" value=\"$returl\">\n";
  echo "<input type=\"hidden\" name=\"action\" value=\"$action_type\">\n";
  echo "<input type=\"submit\" value=\"$submit_value\">\n";
  echo "</fieldset>\n";
  echo "</form>\n";
  echo "</td>\n";
  echo "<tr>\n";
}
    

// Get non-standard form variables
//
// If $series is TRUE, it means that the $id is the id of an 
// entry in the repeat table.  Otherwise it's from the entry table.
$id = get_form_var('id', 'int');
$series = get_form_var('series', 'int');
$action = get_form_var('action', 'string');
$returl = get_form_var('returl', 'string');
$error = get_form_var('error', 'string');

// Check the user is authorised for this page
checkAuthorised();

// Also need to know whether they have admin rights
$user = getUserName();
$is_admin = (authGetUserLevel($user) >= 2);
// You're only allowed to make repeat bookings if you're an admin
// or else if $auth['only_admin_can_book_repeat'] is not set
$repeats_allowed = $is_admin || empty($auth['only_admin_can_book_repeat']);

print_header($day, $month, $year, $area, isset($room) ? $room : "");


// Need to tell all the links where to go back to after an edit or delete
if (!isset($returl))
{
  if (isset($HTTP_REFERER))
  {
    $returl = $HTTP_REFERER;
  }
  // If we haven't got a referer (eg we've come here from an email) then construct
  // a sensible place to go to afterwards
  else
  {
    switch ($default_view)
    {
      case "month":
        $returl = "month.php";
        break;
      case "week":
        $returl = "week.php";
        break;
      default:
        $returl = "day.php";
    }
    $returl .= "?year=$year&month=$month&day=$day&area=$area";
  }
}
$link_returl = urlencode($returl);  // for use in links

if (empty($series))
{
  $series = 0;
}
else
{
  $series = 1;
}

$row = mrbsGetBookingInfo($id, $series);

$custom_fields = array();
foreach ($row as $column => $value)
{
  switch ($column)
  {
    // Don't bother with these columns
    case 'area_id':
    case 'duration':
    case 'repeat_id':
    case 'reminded':
    case 'rep_type':
    case 'rep_enddate':
    case 'rep_opt':
    case 'rep_num_weeks':
    case 'start_time':
    case 'end_time':
      break;

    case 'name':
    case 'description':
    case 'create_by':
    case 'room_name':
    case 'area_name':
      $$column = htmlspecialchars($value);
      break;

    case 'type':
    case 'status':
    case 'private':
    case 'room_id':
    case 'entry_info_time':
    case 'entry_info_user': // HTML escaping done later
    case 'entry_info_text': // HTML escaping done later
    case 'repeat_info_time':
    case 'repeat_info_user': // HTML escaping done later
    case 'repeat_info_text': // HTML escaping done later
      $$column = $row[$column];
      break;

    case 'last_updated':
      $updated = time_date_string($row['last_updated']);
      break;

    default:
      $custom_fields[$column] = $row[$column];
      break;
  }
}

// Very special cases
$last_reminded = (empty($row['reminded'])) ? $row['last_updated'] : $row['reminded'];

// need to make DST correct in opposite direction to entry creation
// so that user see what he expects to see
$duration      = $row['duration'] - cross_dst($row['start_time'],
                                              $row['end_time']);
$writeable     = getWritable($row['create_by'], $user, $room_id);


// Get the area settings for the entry's area.   In particular we want
// to know how to display private/public bookings in this area.
get_area_settings($row['area_id']);

if (is_private_event($private) && !$writeable) 
{
  $name = "[".get_vocab('private')."]";
  $description = $name;
  $create_by = $name;
  $keep_private = TRUE;
}
else
{
  $keep_private = FALSE;
}

if ($enable_periods)
{
  list($start_period, $start_date) =  period_date_string($row['start_time']);
}
else
{
  $start_date = time_date_string($row['start_time']);
}

if ($enable_periods)
{
  list( , $end_date) =  period_date_string($row['end_time'], -1);
}
else
{
  $end_date = time_date_string($row['end_time']);
}


$rep_type = REP_NONE;

if ($series == 1)
{
  $rep_type     = $row['rep_type'];
  $rep_end_date = utf8_strftime('%A %d %B %Y',$row['end_date']);
  $rep_opt      = $row['rep_opt'];
  $rep_num_weeks = $row['rep_num_weeks'];
  // I also need to set $id to the value of a single entry as it is a
  // single entry from a series that is used by del_entry.php and
  // edit_entry.php
  // So I will look for the first entry in the series where the entry is
  // as per the original series settings
  $sql = "SELECT id
          FROM $tbl_entry
          WHERE repeat_id=\"$id\" AND entry_type=\"1\"
          ORDER BY start_time
          LIMIT 1";
  $res = sql_query($sql);
  if (! $res)
  {
    fatal_error(0, sql_error());
  }
  if (sql_count($res) < 1)
  {
    // if all entries in series have been modified then
    // as a fallback position just select the first entry
    // in the series
    // hopefully this code will never be reached as
    // this page will display the start time of the series
    // but edit_entry.php will display the start time of the entry
    sql_free($res);
    $sql = "SELECT id
            FROM $tbl_entry
            WHERE repeat_id=\"$id\"
            ORDER BY start_time
            LIMIT 1";
    $res = sql_query($sql);
    if (! $res)
    {
      fatal_error(0, sql_error());
    }
  }
  $row = sql_row_keyed($res, 0);
  $repeat_id = $id;  // Save the repeat_id
  $id = $row['id'];
  sql_free($res);
}
else
{
  $repeat_id = $row['repeat_id'];

  if ($repeat_id != 0)
  {
    $res = sql_query("SELECT rep_type, end_date, rep_opt, rep_num_weeks
                      FROM $tbl_repeat WHERE id=$repeat_id LIMIT 1");
    if (! $res)
    {
      fatal_error(0, sql_error());
    }

    if (sql_count($res) == 1)
    {
      $row = sql_row_keyed($res, 0);

      $rep_type     = $row['rep_type'];
      $rep_end_date = utf8_strftime('%A %d %B %Y',$row['end_date']);
      $rep_opt      = $row['rep_opt'];
      $rep_num_weeks = $row['rep_num_weeks'];
    }
    sql_free($res);
  }
}


$enable_periods ? toPeriodString($start_period, $duration, $dur_units) : toTimeString($duration, $dur_units);

$repeat_key = "rep_type_" . $rep_type;

// Now that we know all the data we start drawing it


echo "<h3" . (($keep_private) ? " class=\"private\"" : "") . ">\n";
echo $name;
if (is_private_event($private) && $writeable) 
{
  echo ' ('.get_vocab('private').')';
}
echo "</h3>\n";


echo "<table id=\"entry\">\n";

// Output any error messages
if (!empty($error))
{
  echo "<tr><td>&nbsp;</td><td class=\"error\">" . get_vocab($error) . "</td></tr>\n";
}

// If we're using provisional bookings, put the buttons to do with managing
// the bookings in the footer
if ($provisional_enabled && ($status == STATUS_PROVISIONAL))
{
  echo "<tfoot id=\"confirm_buttons\">\n";
  // PHASE 2 - REJECT
  if (isset($action) && ($action == "reject"))
  {
    // del_entry expects the id of a member of a series
    // when deleting a series and not the repeat_id
    generateTextArea("del_entry.php", $id, $series,
                     "reject", $returl,
                     get_vocab("reject"),
                     get_vocab("reject_reason"));
  }
  // PHASE 2 - MORE INFO
  elseif (isset($action) && ($action == "more_info"))
  {
    // but confirm_entry_handler expects the id to be a repeat_id
    // if $series is true (ie behaves like the rest of MRBS).
    // Sometime this difference in behaviour should be rationalised
    // because it is very confusing!
    $target_id = ($series) ? $repeat_id : $id;
    $info_time = ($series) ? $repeat_info_time : $entry_info_time;
    $info_user = ($series) ? $repeat_info_user : $entry_info_user;
    $info_text = ($series) ? $repeat_info_text : $entry_info_text;
    
    if (empty($info_time))
    {
      $value = '';
    }
    else
    {
      $value = get_vocab("sent_at") . time_date_string($info_time);
      if (!empty($info_user))
      {
        $value .= "\n" . get_vocab("by") . " $info_user";
      }
      $value .= "\n----\n";
      $value .= $info_text;
    }
    generateTextArea("confirm_entry_handler.php", $target_id, $series,
                     "more_info", $returl,
                     get_vocab("send"),
                     get_vocab("request_more_info"),
                     $value);
  }
  // PHASE 1 - first time through this page
  else
  {
    // Buttons for those who are allowed to confirm this booking
    if (auth_book_admin($user, $room_id))
    {
      if (!$series)
      {
        generateConfirmButtons($id, FALSE);
      }
      if (!empty($repeat_id) || $series)
      {
        generateConfirmButtons($repeat_id, TRUE);
      }    
    }
    // Buttons for the owner of this booking
    elseif ($user == $create_by)
    {
      generateOwnerButtons($id, $series);
    }
    // Others don't get any buttons
    else
    {
      // But valid HTML requires that there's something inside the <tfoot></tfoot>
      echo "<tr><td></td><td></td></tr>\n";
    }
  }
  echo "</tfoot>\n";
}
?>

<tbody>
  <tr>
    <td><?php echo get_vocab("description") ?>:</td>
    <?php
    echo "<td" . (($keep_private) ? " class=\"private\"" : "") . ">" . mrbs_nl2br($description) . "</td>\n";
    ?>
  </tr>
  <?php
  if ($provisional_enabled)
  {
    echo "<tr>\n";
    echo "<td>" . get_vocab("status") . ":</td>\n";
    echo "<td>" . (($status == STATUS_PROVISIONAL) ? get_vocab("provisional") : get_vocab("confirmed")) . "</td>\n";
    echo "</tr>\n";
  }
  ?>
  <tr>
    <td><?php echo get_vocab("room") ?>:</td>
    <td><?php    echo  mrbs_nl2br($area_name . " - " . $room_name) ?></td>
  </tr>
  <tr>
    <td><?php echo get_vocab("start_date") ?>:</td>
    <td><?php    echo $start_date ?></td>
  </tr>
  <tr>
    <td><?php echo get_vocab("duration") ?>:</td>
    <td><?php    echo $duration . " " . $dur_units ?></td>
  </tr>
  <tr>
    <td><?php echo get_vocab("end_date") ?>:</td>
    <td><?php    echo $end_date ?></td>
  </tr>
  <tr>
    <td><?php echo get_vocab("type") ?>:</td>
    <td><?php    echo empty($typel[$type]) ? "?$type?" : $typel[$type] ?></td>
  </tr>
  <tr>
    <td><?php echo get_vocab("createdby") ?>:</td>
    <?php
    echo "<td" . (($keep_private) ? " class=\"private\"" : "") . ">" . $create_by . "</td>\n";
    ?>
  </tr>
  <tr>
    <td><?php echo get_vocab("lastupdate") ?>:</td>
    <td><?php    echo $updated ?></td>
  </tr>
<?php
  if (count($custom_fields))
  {
    $fields = sql_field_info($tbl_entry);
    $field_natures = array();
    $field_lengths = array();
    foreach ($fields as $field)
    {
      $field_natures[$field['name']] = $field['nature'];
      $field_lengths[$field['name']] = $field['length'];
    }
    foreach ($custom_fields as $key => $value)
    {
      echo "<tr>\n";
      echo "<td>" . get_loc_field_name($tbl_entry, $key) . ":</td>\n";
      // Output a yes/no if it's a boolean or integer <= 2 bytes (which we will
      // assume are intended to be booleans)
      if (($field_natures[$key] == 'boolean') || 
          (($field_natures[$key] == 'integer') && isset($field_lengths[$key]) && ($field_lengths[$key] <= 2)) )
      {
        $shown_value = empty($value) ? get_vocab("no") : get_vocab("yes");
      }
      // Otherwise output a string
      else
      {
        $shown_value = (isset($value)) ? htmlspecialchars($value): "&nbsp;"; 
      }
      echo "<td>$shown_value</td>\n";
      echo "</tr>\n";
    }
  }
?>
  <tr>
    <td><?php echo get_vocab("rep_type") ?>:</td>
    <td><?php    echo get_vocab($repeat_key) ?></td>
  </tr>
<?php

if($rep_type != REP_NONE)
{
  $opt = "";
  if (($rep_type == REP_WEEKLY) || ($rep_type == REP_N_WEEKLY))
  {
    // Display day names according to language and preferred weekday start.
    for ($i = 0; $i < 7; $i++)
    {
      $daynum = ($i + $weekstarts) % 7;
      if ($rep_opt[$daynum])
      {
        $opt .= day_name($daynum) . " ";
      }
    }
  }
  if ($rep_type == REP_N_WEEKLY)
  {
    echo "<tr><td>".get_vocab("rep_num_weeks")." ".get_vocab("rep_for_nweekly").":</td><td>$rep_num_weeks</td></tr>\n";
  }

  if ($opt)
  {
    echo "<tr><td>".get_vocab("rep_rep_day").":</td><td>$opt</td></tr>\n";
  }

  echo "<tr><td>".get_vocab("rep_end_date").":</td><td>$rep_end_date</td></tr>\n";
}

?>
</tbody>
</table>

<div id="view_entry_nav">
  <div>
    <?php
    if (!$series)
    {
      echo "<a href=\"edit_entry.php?id=$id&amp;returl=$link_returl\">". get_vocab("editentry") ."</a>";
    }
    
    if (!empty($repeat_id)  && !$series && $repeats_allowed)
    {
      echo " - ";
    }
    
    if ((!empty($repeat_id) || $series) && $repeats_allowed)
    {
      echo "<a href=\"edit_entry.php?id=$id&amp;edit_type=series&amp;day=$day&amp;month=$month&amp;year=$year&amp;returl=$link_returl\">".get_vocab("editseries")."</a>";
    }
    
     ?>
  </div>
  <div>
    <?php
    
    // Copy and Copy series
    if (!$series)
    {
      echo "<a href=\"edit_entry.php?id=$id&amp;copy=1&amp;returl=$link_returl\">". get_vocab("copyentry") ."</a>";
    }
       
    if (!empty($repeat_id) && !$series && $repeats_allowed)
    {
      echo " - ";
    }
       
    if ((!empty($repeat_id) || $series) && $repeats_allowed) 
    {
      echo "<a href=\"edit_entry.php?id=$id&amp;edit_type=series&amp;day=$day&amp;month=$month&amp;year=$year&amp;copy=1&amp;returl=$link_returl\">".get_vocab("copyseries")."</a>";
    }
    
    ?>
  </div>
  <div>
    <?php
    if (!$series)
    {
      echo "<a href=\"del_entry.php?id=$id&amp;series=0&amp;returl=$link_returl\" onclick=\"return confirm('".get_vocab("confirmdel")."');\">".get_vocab("deleteentry")."</a>";
    }
    
    if (!empty($repeat_id) && !$series && $repeats_allowed)
    {
      echo " - ";
    }
    
    if ((!empty($repeat_id) || $series) && $repeats_allowed)
    {
      echo "<a href=\"del_entry.php?id=$id&amp;series=1&amp;day=$day&amp;month=$month&amp;year=$year&amp;returl=$link_returl\" onClick=\"return confirm('".get_vocab("confirmdel")."');\">".get_vocab("deleteseries")."</a>";
    }
    
    ?>
  </div>
  <div>
    <?php
    if (isset($HTTP_REFERER)) //remove the link if displayed from an email
    {
    ?>
    <a href="<?php echo htmlspecialchars($HTTP_REFERER) ?>"><?php echo get_vocab("returnprev") ?></a>
    <?php
    }
    ?>
  </div>
</div>

<?php
require_once "trailer.inc";
?>
