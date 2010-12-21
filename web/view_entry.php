<?php
// $Id$

require_once "defaultincludes.inc";
require_once "mrbs_sql.inc";
require_once "functions_view.inc";

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

// Generates the Approve, Reject and More Info buttons
function generateApproveButtons($id, $series)
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
  generateButton("approve_entry_handler.php", $id, $series, "approve", $returl, get_vocab("approve"));
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
  
  // Remind button if you're the owner AND there's a booking awaiting
  // approval AND sufficient time has passed since the last reminder
  // AND we want reminders in the first place
  if (($reminders_enabled) &&
      ($user == $create_by) && 
      ($status & STATUS_AWAITING_APPROVAL) &&
      (working_time_diff(time(), $last_reminded) >= $reminder_interval))
  {
    echo "<tr>\n";
    echo "<td>&nbsp;</td>\n";
    echo "<td>\n";
    generateButton("approve_entry_handler.php", $id, $series, "remind", $this_page . "?id=$id&amp;area=$area", get_vocab("remind_admin"));
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

$row = mrbsGetBookingInfo($id, $series);

// Get the area settings for the entry's area.   In particular we want
// to know how to display private/public bookings in this area.
get_area_settings($row['area_id']);

// Work out whether the room or area is disabled
$room_disabled = $row['room_disabled'] || $row['area_disabled'];
// Get the status
$status = $row['status'];
// Work out whether this event should be kept private
$private = $row['status'] & STATUS_PRIVATE;
$writeable = getWritable($row['create_by'], $user, $row['room_id']);
$keep_private = (is_private_event($private) && !$writeable);

// Work out when the last reminder was sent
$last_reminded = (empty($row['reminded'])) ? $row['last_updated'] : $row['reminded'];


if ($series == 1)
{
  $repeat_id = $id;  // Save the repeat_id
  // I also need to set $id to the value of a single entry as it is a
  // single entry from a series that is used by del_entry.php and
  // edit_entry.php
  // So I will look for the first entry in the series where the entry is
  // as per the original series settings
  $sql = "SELECT id
          FROM $tbl_entry
          WHERE repeat_id=\"$id\" AND entry_type=" . ENTRY_RPT_ORIGINAL . "
          ORDER BY start_time
          LIMIT 1";
  $id = sql_query1($sql);
  if ($id < 1)
  {
    // if all entries in series have been modified then
    // as a fallback position just select the first entry
    // in the series
    // hopefully this code will never be reached as
    // this page will display the start time of the series
    // but edit_entry.php will display the start time of the entry
    $sql = "SELECT id
            FROM $tbl_entry
            WHERE repeat_id=\"$id\"
            ORDER BY start_time
            LIMIT 1";
    $id = sql_query1($sql);
  }
}
else
{
  $repeat_id = $row['repeat_id'];
}


// PHASE 2 - DOWNLOADING ICALENDAR FILES
// -------------------------------------

if (isset($action) && ($action == "download"))
{
  if ($keep_private  || $enable_periods)
  {
    // should never normally be able to get here, but if we have then
    // go somewhere safe.
    header("Location: index.php");
    exit;
  }
  else
  {
    require_once "functions_ical.inc";
    header("Content-Type: application/ics;  charset=" . get_charset(). "; name=\"" . $mail_settings['ics_filename'] . ".ics\"");
    header("Content-Disposition: attachment; filename=\"" . $mail_settings['ics_filename'] . ".ics\"");
    
    // Construct the SQL query
    $sql = "SELECT E.*, "
         .  sql_syntax_timestamp_to_unix("E.timestamp") . " AS last_updated, "
         . "A.area_name, R.room_name, "
         . "A.approval_enabled, A.confirmation_enabled";
    if ($series)
    {
      // If it's a series we want the repeat information
      $sql .= ", T.rep_type, T.end_date, T.rep_opt, T.rep_num_weeks";
    }
    $sql .= " FROM $tbl_area A, $tbl_room R, $tbl_entry E";
    if ($series)
    {
      $sql .= ", $tbl_repeat T"
            . " WHERE E.repeat_id=$repeat_id"
            . " AND E.repeat_id=T.id"
            . " ORDER BY E.ical_recur_id";
    }
    else
    {
      $sql .= " WHERE E.id=$id";
    }
    $res = sql_query($sql);
    
    // Export the calendar
    export_icalendar($res, $keep_private);
    exit;
  }
}

// PHASE 1 - VIEW THE ENTRY
// ------------------------

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


// Now that we know all the data we start drawing it

echo "<h3" . (($keep_private && $is_private_field['entry.name']) ? " class=\"private\"" : "") . ">\n";
echo htmlspecialchars($row['name']);
if (is_private_event($private) && $writeable) 
{
  echo ' ('.get_vocab("private").')';
}
echo "</h3>\n";


echo "<table id=\"entry\">\n";

// Output any error messages
if (!empty($error))
{
  echo "<tr><td>&nbsp;</td><td class=\"error\">" . get_vocab($error) . "</td></tr>\n";
}

// If bookings require approval, and the room is enabled, put the buttons
// to do with managing the bookings in the footer
if ($approval_enabled && !$room_disabled && ($status & STATUS_AWAITING_APPROVAL))
{
  echo "<tfoot id=\"approve_buttons\">\n";
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
    // but approve_entry_handler expects the id to be a repeat_id
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
    generateTextArea("approve_entry_handler.php", $target_id, $series,
                     "more_info", $returl,
                     get_vocab("send"),
                     get_vocab("request_more_info"),
                     $value);
  }
  // PHASE 1 - first time through this page
  else
  {
    // Buttons for those who are allowed to approve this booking
    if (auth_book_admin($user, $room_id))
    {
      if (!$series)
      {
        generateApproveButtons($id, FALSE);
      }
      if (!empty($repeat_id) || $series)
      {
        generateApproveButtons($repeat_id, TRUE);
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

echo create_details_body($row, TRUE, $keep_private, $room_disabled);

?>
</table>

<div id="view_entry_nav">
  <?php
  // Only show the links for Edit and Delete if the room is enabled.    We're
  // allowed to view and copy existing bookings in disabled rooms, but not to
  // modify or delete them.
  if (!$room_disabled)
  {
    // Edit and Edit Series
    echo "<div>\n";
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
    echo "</div>\n";
    
    // Delete and Delete Series
    echo "<div>\n";
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
    echo "</div>\n";
  }
  
  // Copy and Copy Series
  echo "<div>\n";
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
  echo "</div>\n";
  
  // Download and Download Series
  if (!$keep_private && !$enable_periods)
  {
    // The iCalendar information has the full booking details in it, so we will not allow
    // it to be downloaded if it is private and the user is not authorised to see it.
    // iCalendar information doesn't work with periods at the moment (no periods to times mapping)
    echo "<div>\n";
    if (!$series)
    {
      echo "<a href=\"view_entry.php?action=download&amp;id=$id&amp;returl=$link_returl\">". get_vocab("downloadentry") ."</a>";
    } 
    if (!empty($repeat_id)  && !$series)
    {
      echo " - ";
    }  
    if (!empty($repeat_id) || $series)
    {
      echo "<a href=\"view_entry.php?action=download&amp;id=$repeat_id&amp;series=1&amp;day=$day&amp;month=$month&amp;year=$year&amp;returl=$link_returl\">".get_vocab("downloadseries")."</a>";
    }
    echo "</div>\n";
  }
  ?>
  <div id="returl">
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
