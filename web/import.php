<?php
// $Id$

require_once "defaultincludes.inc";
require_once "functions_ical.inc";
require_once "mrbs_sql.inc";

function get_room_id($location)
{
  global $area_room_order, $area_room_delimiter, $area_room_create;
  global $tbl_room, $tbl_area;
  
  // If there's no delimiter we assume we've just been given a room name (that will
  // have to be unique).   Otherwise we split the location into its area and room parts
  if (strpos($location, $area_room_delimiter) === FALSE)
  {
    $location_area = '';
    $location_room = $location;
  }
  elseif ($area_room_order = 'area_room')
  {
    list($location_area, $location_room) = explode($area_room_delimiter, $location);
  }
  else
  {
    list($location_room, $location_area) = explode($area_room_delimiter, $location);
  }
  $location_area = trim($location_area);
  $location_room = trim($location_room);
  
  // Now search the database for the room
  
  // Case 1:  we've just been given a room name, in which case we hope it happens
  // to be unique, because if we find more than one we won't know which one is intended
  // and if we don't find one at all we won't be able to create it because we won't 
  // know which area to put it in.
  if ($location_area == '')
  {
    $sql = "SELECT COUNT(*) FROM $tbl_room WHERE room_name='" . addslashes($location_room) . "'";
    $count = sql_query1($sql);
    if ($count < 0)
    {
      trigger_error(sql_error(), E_USER_WARNING);
      fatal_error(FALSE, get_vocab("fatal_db_error"));
    }
    elseif ($count == 0)
    {
      echo "Room '$location_room' does not exist and cannot be added - no area given.<br>\n";
      return FALSE;
    }
    elseif ($count > 1)
    {
      echo "There is more than one room called '$location_room'.  Cannot choose which one without an area.<br>\n";
      return FALSE;
    }
    else // we've got a unique room name
    {
      $sql = "SELECT id FROM $tbl_room WHERE room_name='" . addslashes($location_room) . "' LIMIT 1";
      $id = sql_query1($sql);
      if ($id < 0)
      {
        trigger_error(sql_error(), E_USER_WARNING);
        fatal_error(FALSE, get_vocab("fatal_db_error"));
      }
      return $id;
    }
  }
  
  // Case 2:  we've got an area and room name
  else
  {
    // First of all get the area id
    $sql = "SELECT id
              FROM $tbl_area
             WHERE area_name='" . addslashes($location_area) . "'
             LIMIT 1";
    $area_id = sql_query1($sql);
    if ($area_id < 0)
    {
      $sql_error = sql_error();
      if (!empty($sql_error))
      {
        trigger_error(sql_error(), E_USER_WARNING);
        fatal_error(FALSE, get_vocab("fatal_db_error"));
      }
      else
      {
        // The area does not exist - create it if we are allowed to
        if (!$area_room_create)
        {
          echo get_vocab("area_does_not_exist") . " '$location_area'<br>\n";
          return FALSE;
        }
        else
        {
          echo get_vocab("creating_new_area") . " '$location_area'<br>\n";
          $error = '';
          $area_id = mrbsAddArea($location_area, $error);
          if ($area_id === FALSE)
          {
            echo get_vocab("could_not_create_area") . " '$location_area'<br>\n";
            return FALSE;
          }
        }
      }
    }
  }
  // Now we've got the area_id get the room_id
  $sql = "SELECT id
            FROM $tbl_room
           WHERE room_name='" . addslashes($location_room) . "'
             AND area_id=$area_id
           LIMIT 1";
  $room_id = sql_query1($sql);
  if ($room_id < 0)
  {
    $sql_error = sql_error();
    if (!empty($sql_error))
    {
      trigger_error(sql_error(), E_USER_WARNING);
      fatal_error(FALSE, get_vocab("fatal_db_error"));
    }
    else
    {
      // The room does not exist - create it if we are allowed to
      if (!$area_room_create)
      {
        echo get_vocab("room_does_not_exist") . " '$location_room'<br>\n";
        return FALSE;
      }
      else
      {
        echo get_vocab("creating_new_room") . " '$location_room'<br>\n";
        $error = '';
        $room_id = mrbsAddRoom($location_room, $area_id, $error);
        if ($room_id === FALSE)
        {
          echo get_vocab("could_not_create_room") . " '$location_room'<br>\n";
          return FALSE;
        }
      }
    }
  }
  return $room_id;
}


function process_event($vevent)
{
  global $import_default_type, $skip;
  global $morningstarts, $morningstarts_minutes, $resolution;
  
  // We are going to cache the settings ($resolution etc.) for the rooms
  // in order to avoid lots of database lookups
  static $room_settings = array();
  
  // Set up the booking with some defaults
  $booking = array();
  $booking['status'] = 0;
  $booking['rep_type'] = REP_NONE;
  $booking['create_by'] = getUserName();
  $booking['type'] = $import_default_type;
  // Parse all the lines first because we'll need to get the start date
  // for calculating some of the other settings
  $properties = array();
  $problems = array();
  foreach ($vevent as $line)
  {
    $property = parse_ical_property($line);
    $properties[$property['name']] = array('params' => $property['params'],
                                           'value' => $property['value']);
  }
  // Get the start time because we'll need it later
  if (!isset($properties['DTSTART']))
  {
    trigger_error("No DTSTART", E_USER_WARNING);
  }
  else
  {
    $booking['start_time'] = get_time($properties['DTSTART']['value'],
                                      $properties['DTSTART']['params']);
  }
  // Now go through the rest of the properties
  foreach($properties as $name => $details)
  {
    switch ($name)
    {
      case 'SUMMARY':
        $booking['name'] = $details['value'];
        break;
      case 'DESCRIPTION':
        $booking['description'] = $details['value'];
        break;
      case 'LOCATION':
        $booking['room_id'] = get_room_id($details['value']);
        if (empty($booking['room_id']))
        {
          $problems[] = get_vocab("could_not_find_room") . " '${details['value']}'";
        }
        break;
      case 'DTEND':
        $booking['end_time'] = get_time($details['value'], $details['params']);
        break;
      case 'DURATION':
        trigger_error("DURATION not yet supported by MRBS", E_USER_WARNING);
        break;
      case 'RRULE':
        $rrule_errors = array();
        $repeat_details = get_repeat_details($details['value'], $booking['start_time'], $rrule_errors);
        if ($repeat_details === FALSE)
        {
          $problems = array_merge($problems, $rrule_errors);
        }
        else
        {
          foreach ($repeat_details as $key => $value)
          {
            $booking[$key] = $value;
          }
        }
        break;
      case 'CLASS':
        if (in_array($details['value'], array('PRIVATE', 'CONFIDENTIAL')))
        {
          $booking['status'] |= STATUS_PRIVATE;
        }
        break;
      case 'STATUS':
        if ($details['value'] == 'TENTATIVE')
        {
          $booking['status'] |= STATUS_TENTATIVE;
        }
        break;
      case 'UID':
        $booking['ical_uid'] = $details['value'];
        break;
      case 'SEQUENCE':
        $booking['ical_sequence'] = $details['value'];
        break;
    }
  }

  // A SUMMARY is optional in RFC 5545, however a brief description is mandatory
  // in MRBS.   So if the VEVENT didn't include a name, we'll give it one
  if (!isset($booking['name']))
  {
    $booking['name'] = "Imported event - no SUMMARY name";
  }
  
  // On the other hand a UID is mandatory in RFC 5545.   We'll be lenient and
  // provide one if it is missing
  if (!isset($booking['ical_uid']))
  {
    $booking['ical_uid'] = generate_global_uid($booking['name']);
    $booking['sequence'] = 0;  // and we'll start the sequence from 0
  }
  
  if (empty($problems))
  {
    // Get the area settings for this room, if we haven't got them already
    if (!isset($room_settings[$booking['room_id']]))
    {
      get_area_settings(get_area($booking['room_id']));
      $room_settings[$booking['room_id']]['morningstarts'] = $morningstarts;
      $room_settings[$booking['room_id']]['morningstarts_minutes'] = $morningstarts_minutes;
      $room_settings[$booking['room_id']]['resolution'] = $resolution;
    }
    // Round the start and end times to slot boundaries
    $date = getdate($booking['start_time']);
    $m = $date['mon'];
    $d = $date['mday'];
    $y = $date['year'];
    $am7 = mktime($room_settings[$booking['room_id']]['morningstarts'],
                  $room_settings[$booking['room_id']]['morningstarts_minutes'],
                  0, $m, $d, $y,
                  is_dst($m, $d, $y, $room_settings[$booking['room_id']]['morningstarts']));
    $booking['start_time'] = round_t_down($booking['start_time'],
                                          $room_settings[$booking['room_id']]['resolution'],
                                          $am7);
    $booking['end_time'] = round_t_up($booking['end_time'],
                                      $room_settings[$booking['room_id']]['resolution'],
                                      $am7);
    // Make the bookings
    $bookings = array($booking);
    $result = mrbsMakeBookings($bookings, NULL, FALSE, $skip);
    if ($result['valid_booking'])
    {
      return TRUE;
    }
  }
  // There were problems - list them
  echo "<div class=\"problem_report\">\n";
  echo get_vocab("could_not_import") . " UID:" . htmlspecialchars($booking['ical_uid']);
  echo "<ul>\n";
  foreach ($problems as $problem)
  {
    echo "<li>" . htmlspecialchars($problem) . "</li>\n";
  }
  if (!empty($result['rules_broken']))
  {
    echo "<li>" . get_vocab("rules_broken") . "</li>\n";
    echo "<li><ul>\n";
    foreach ($result['rules_broken'] as $rule)
    {
      echo "<li>$rule</li>\n";
    }
    echo "</ul></li>\n";
  }
  if (!empty($result['conflicts']))
  {
    echo "<li>" . get_vocab("conflict"). "</li>\n";
    echo "<li><ul>\n";
    foreach ($result['conflicts'] as $conflict)
    {
      echo "<li>$conflict</li>\n";
    }
    echo "</ul></li>\n";
  }
  echo "</ul>\n";
  echo "</div>\n";
  
  return FALSE;
}


// Check the user is authorised for this page
checkAuthorised();

print_header($day, $month, $year, $area, $room);

$import = get_form_var('import', 'string');
$area_room_order = get_form_var('area_room_order', 'string', 'area_room');
$area_room_delimiter = get_form_var('area_room_delimiter', 'string', ';');
$area_room_create = get_form_var('area_room_create', 'string', '0');
$import_default_type = get_form_var('import_default_type', 'string', $default_type);
$skip = get_form_var('skip', 'string', ((empty($skip_default)) ? '0' : '1'));


// PHASE 2 - Process the files
// ---------------------------

if (!empty($import))
{
  if ($_FILES['ics_file']['error'] !== UPLOAD_ERR_OK)
  {
    echo "<p>\n";
    echo get_vocab("upload_failed");
    switch($_FILES['ics_file']['error'])
    {
      case UPLOAD_ERR_INI_SIZE:
        echo "<br>\n";
        echo get_vocab("max_allowed_file_size") . " " . ini_get('upload_max_filesize');
        break;
      case UPLOAD_ERR_NO_FILE:
        echo "<br>\n";
        echo get_vocab("no_file");
        break;
      default:
        // None of the other possible errors would make much sense to the user, but should be reported
        trigger_error($_FILES['ics_file']['error'], E_USER_NOTICE);
        break;
    }
    echo "</p>\n";
  }
  elseif (!is_uploaded_file($_FILES['ics_file']['tmp_name']))
  {
    // This should not happen and if it does may mean that somebody is messing about
    echo "<p>\n";
    echo get_vocab("upload_failed");
    echo "</p>\n";
    trigger_error("Attempt to import a file that has not been uploaded", E_USER_WARNING);
  }
  // We've got a file
  else
  {
    $vcalendar = file_get_contents($_FILES['ics_file']['tmp_name']);
    if ($vcalendar !== FALSE)
    {
      $vevents = array();
      $lines = explode("\r\n", ical_unfold($vcalendar));
      $first_line = array_shift($lines);
      if (isset($first_line))
      {
        // Get rid of empty lines at the end of the file
        // (Strictly speaking there must be a CRLF at the end of the file, but
        // we will be tolerant and accept files without one)
        do
        {
          $last_line = array_pop($lines);
        }
        while (isset($last_line) && ($last_line == ''));
      }
      // Check that this bears some resemblance to a VCALENDAR
      if (!isset($last_line) ||
          ($first_line != "BEGIN:VCALENDAR") ||
          ($last_line != "END:VCALENDAR"))
      {
        echo "<p>\n" . get_vocab("badly_formed_ics") . "</p>\n";
      }
      // Looks OK - find all the VEVENTS which we are going to put in a two dimensional array -
      // each event will consist of an array of lines making up the event.  (Note - we
      // are going to ignore any VTIMEZONE definitions.   We will honour TZID data in
      // a VEVENT but we will use the PHP definition of the timezone)
      else
      {
        while ($line = array_shift($lines))
        {
          if ($line == "BEGIN:VEVENT")
          {
            $vevent = array();
            while (($vevent_line = array_shift($lines)) && ($vevent_line != "END:VEVENT"))
            {
                $vevent[] = $vevent_line;
            }
            $vevents[] = $vevent;
          }
        }
      }
      // Process each event, putting it in the database
      $n_success = 0;
      $n_failure = 0;
      foreach ($vevents as $vevent)
      {
        (process_event($vevent)) ? $n_success++ : $n_failure++;
      }
      echo "<p>\n";
      echo "$n_success " . get_vocab("events_imported");
      if ($n_failure > 0)
      {
        echo "<br>\n$n_failure " . get_vocab("events_not_imported");
      }
      echo "</p>\n";
    }
  }
}

// PHASE 1 - Get the user input
// ----------------------------
echo "<form class=\"form_general\" method=\"POST\" enctype=\"multipart/form-data\" action=\"" . htmlspecialchars(basename($PHP_SELF)) . "\">\n";

echo "<fieldset class=\"admin\">\n";
echo "<legend>" . get_vocab("import_icalendar") . "</legend>\n";

echo "<p>\n" . get_vocab("import_intro") . "</p>\n";
  
echo "<div>\n";
echo "<label for=\"ics_file\">" . get_vocab("file_name") . ":</label>\n";
echo "<input type=\"file\" name=\"ics_file\" id=\"ics_file\">\n";
echo "</div>\n";

echo "<fieldset>\n";
echo "<legend>hhh</legend>\n";

echo "<div>\n";
echo "<label>" . get_vocab("area_room_order") . ":</label>\n";
echo "<div class=\"group\">\n";
echo "<label><input type=\"radio\" name=\"area_room_order\" value=\"area_room\"" .
     (($area_room_order == "area_room") ? " checked=\"checked\"" : "") . ">" .
     get_vocab("area_room") . "</label>\n";
echo "<label><input type=\"radio\" name=\"area_room_order\" value=\"room_area\"" .
     (($area_room_order == "room_area") ? " checked=\"checked\"" : "") . ">" .
     get_vocab("room_area") . "</label>\n";
echo "</div>\n";
echo "</div>\n";

echo "<div>\n";
echo "<label for=\"area_room_delimiter\">" . get_vocab("area_room_delimiter") . ":</label>\n";
echo "<input type=\"text\" name=\"area_room_delimiter\" id=\"area_room_delimiter\"" .
     " value=\"" . htmlspecialchars($area_room_delimiter) . "\">\n";
echo "</div>\n";

echo "<div>\n";
echo "<label for=\"area_room_create\">" . get_vocab("area_room_create") . ":</label>\n";
echo "<input type=\"checkbox\" name=\"area_room_create\" id=\"area_room_create\" value=\"yes\"" .
     (($area_room_create) ? " checked=\"checked\"" : "") . 
     ">\n";
echo "</div>\n";

echo "</fieldset>\n";

echo "<div>\n";
echo "<label for=\"import_default_type\">" . get_vocab("default_type") . ":</label>\n";
echo "<select name=\"import_default_type\" id=\"import_default_type\">\n";
foreach ($booking_types as $type)
{
  echo "<option value=\"$type\"" .
       (($type == $import_default_type) ? " selected=\"selected\"" : '') .
       ">" . get_vocab("type.$type") . "</option>\n";
}
echo "</select>\n";
echo "</div>\n";

echo "<div>\n";
echo "<label for=\"skip\">" . get_vocab("skip_conflicts") . ":</label>\n";
echo "<input type=\"checkbox\" class=\"checkbox\" " .
          "id=\"skip\" name=\"skip\" value=\"1\" " .
          (($skip) ? " checked=\"checked\"" : "") .
          ">\n";
echo "</div>\n";

// The Submit button
echo "<div id=\"import_submit\">\n";
echo "<input class=\"submit\" type=\"submit\" name=\"import\" value=\"" . get_vocab("import") . "\">\n";
echo "</div>\n";

echo "</fieldset>\n";

echo "</form>\n";
  
require_once "trailer.inc";
?>