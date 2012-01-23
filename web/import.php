<?php
// $Id$

require_once "defaultincludes.inc";
require_once "functions_ical.inc";
require_once "mrbs_sql.inc";


function get_room_id($location)
{
  global $area_room_order, $area_room_delimiter, $area_room_create, $test;
  global $tbl_room, $tbl_area;
  
  // We cache the contents of the room and area tables so that we can do a test
  // import.   It will also help a little with performance.
  static $rooms = array();
  static $areas = array();
  static $room_ids = array();
  static $area_ids = array();
  
  $sql = "SELECT id, room_name, area_id FROM $tbl_room";
  $res = sql_query($sql);
  if ($res === FALSE)
  {
    trigger_error(sql_error(), E_USER_WARNING);
    fatal_error(FALSE, get_vocab("fatal_db_error"));
  }
  for ($i = 0; ($row = sql_mysql_row_keyed($res, $i)); $i++)
  {
    $rooms[] = $row;
    $room_ids[] = $row['id'];
  }
  
  $sql = "SELECT id, area_name FROM $tbl_area";
  $res = sql_query($sql);
  if ($res === FALSE)
  {
    trigger_error(sql_error(), E_USER_WARNING);
    fatal_error(FALSE, get_vocab("fatal_db_error"));
  }
  for ($i = 0; ($row = sql_mysql_row_keyed($res, $i)); $i++)
  {
    $areas[] = $row;
    $area_ids[] = $row['id'];
  }
  
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
    $ids = array();
    foreach ($rooms as $room)
    {
      if ($room['room_name'] == $location_room)
      {
        $ids[] = $room['id'];
      }
    }
    if (count($ids) == 1)
    {
      return $ids[0];
    }
    elseif (count($ids) == 0)
    {
      echo "Room '$location_room' does not exist and cannot be added - no area given.<br>\n";
      return FALSE;
    }
    else
    {
      echo "There is more than one room called '$location_room'.  Cannot choose which one without an area.<br>\n";
      return FALSE;
    }
  }
  // Case 2:  we've got an area and room name
  else
  {
    // First of all get the area_id
    $area_id = NULL;
    foreach ($areas as $area)
    {
      if ($area['area_name'] == $location_area)
      {
        $area_id = $area['id'];
        break;
      }
    }
    if (!isset($area_id))
    {
      if (!$area_room_create)
      {
        echo get_vocab("area_does_not_exist") . " '$location_area'<br>\n";
        return FALSE;
      }
      else
      {
        echo get_vocab("creating_new_area") . " '$location_area'<br>\n";
        if ($test)
        {
          $area_id = max($area_ids) + 1;
        }
        else
        {
          $error = '';
          $area_id = mrbsAddArea($location_area, $error);
          if ($area_id === FALSE)
          {
            echo get_vocab("could_not_create_area") . " '$location_area'<br>\n";
            return FALSE;
          }
        }
        $areas[] = array('id' => $area_id, 'area_name' => $location_area);
        $area_ids[] = $area_id;
      }
    }
    // Now we've got the area_id we can find the room_id
    $room_id = NULL;
    foreach ($rooms as $room)
    {
      if (($room['room_name'] == $location_room) && ($room['area_id'] == $area_id))
      {
        $room_id = $room['id'];
        break;
      }
    }
    if (!isset($room_id))
    {
      if (!$area_room_create)
      {
        echo get_vocab("area") . " $location_area. " . 
             get_vocab("room_does_not_exist") . " '$location_room'<br>\n";
        return FALSE;
      }
      else
      {
        echo get_vocab("area") . " $location_area. " .
             get_vocab("creating_new_room") . " '$location_room'<br>\n";
        if ($test)
        {
          $room_id = max($room_ids) + 1;
        }
        else
        {
          $error = '';
          $room_id = mrbsAddRoom($location_room, $area_id, $error);
          if ($room_id === FALSE)
          {
            echo get_vocab("could_not_create_room") . " '$location_room'<br>\n";
            return FALSE;
          }
        }
        $rooms[] = array('id' => $room_id, 'room_name' => $location_room, 'area_id' => $area_id);
        $room_ids[] = $room_id;
      }
    }
    return $room_id;
  }
}


function process_event($vevent)
{
  $booking = array();
  $booking['status'] = 0;
  $booking['rep_type'] = REP_NONE;
  $properties = array();
  // Parse all the lines first because we'll need to get the start date
  // for calculating some of the other settings
  foreach ($vevent as $line)
  {
    $property = parse_ical_property($line);
    $properties[$property['name']] = array('params' => $property['params'],
                                           'value' => $property['value']);
  }
  // Get the start time and UID, because we'll need them later
  if (!isset($properties['DTSTART']))
  {
    trigger_error("No DTSTART", E_USER_WARNING);
  }
  else
  {
    $booking['start_time'] = get_time($properties['DTSTART']['value'],
                                      $properties['DTSTART']['params']);
  }
  $booking['ical_uid'] = (isset($properties['UID'])) ? $properties['UID']['value'] : "unknown UID";
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
        break;
      case 'DTEND':
        $booking['end_time'] = get_time($details['value'], $details['params']);
        break;
      case 'DURATION':
        trigger_error("DURATION not yet supported by MRBS", E_USER_WARNING);
        break;
      case 'RRULE':
        $repeat_details = get_repeat_details($details['value'], $booking['start_time']);
        if ($repeat_details === FALSE)
        {
          echo "Could not import event with UID ${booking['ical_uid']}.   Recurrence rule not supported";
          return;
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
      case 'SEQUENCE':
        $booking['ical_sequence'] = $details['value'];
        break;
    }
  }

  mrbsMakeBooking($booking);
}


// Check the user is authorised for this page
checkAuthorised();

print_header($day, $month, $year, $area, $room);

$import = get_form_var('import', 'string');
$test = get_form_var('test', 'string');
$area_room_order = get_form_var('area_room_order', 'string');
$area_room_delimiter = get_form_var('area_room_delimiter', 'string');
$area_room_create = get_form_var('area_room_create', 'string');

// Set defaults
if (!isset($area_room_order))
{
  $area_room_order = 'area_room';
}
if (!isset($area_room_delimiter))
{
  $area_room_delimiter = ';';
}
if (!isset($area_room_create))
{
  $area_room_crete = FALSE;
}

// PHASE 2 - Process the files
// ---------------------------

if (!empty($test) || !empty($import))
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
      $lines = explode("\r\n", ical_unfold($vcalendar));
      // Check that this bears some resemblance to a VCALENDAR
      if ((array_shift($lines) != "BEGIN:VCALENDAR") ||
          (array_pop($lines) != "END:VCALENDAR"))
      {
        echo "<p>\n" . get_vocab("badly_formed_ics") . "</p>\n";
      }
      // Looks OK - find all the VEVENTS which we are going to put in a two dimensional array -
      // each event will consist of an array of lines making up the event.  (Note - we
      // are going to ignore any VTIMEZONE definitions.   We will honour TZID data in
      // a VEVENT but we will use the PHP definition of the timezone)
      else
      {
        $vevents = array();
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
      foreach ($vevents as $vevent)
      {
        process_event($vevent);
      }
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

// The Submit button
echo "<div id=\"import_submit\">\n";
echo "<input class=\"submit default_action\" type=\"submit\" name=\"test\" value=\"" . get_vocab("test") . "\">\n";
echo "<input class=\"submit\" type=\"submit\" name=\"import\" value=\"" . get_vocab("import") . "\">\n";
echo "</div>\n";

echo "</fieldset>\n";

echo "</form>\n";
  
require_once "trailer.inc";
?>