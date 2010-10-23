<?php

// $Id$

require_once "defaultincludes.inc";

// Get non-standard form variables
$name = get_form_var('name', 'string');
$description = get_form_var('description', 'string');
$capacity = get_form_var('capacity', 'int');
$type = get_form_var('type', 'string');

// Check the user is authorised for this page
checkAuthorised();

// This file is for adding new areas/rooms

// First of all check that we've got an area or room name
if (!isset($name) || ($name === ''))
{
  $error = "empty_name";
}

// we need to do different things depending on if its a room
// or an area
elseif ($type == "area")
{
  // Truncate the name field to the maximum length as a precaution.
  $name = substr($name, 0, $maxlength['area.area_name']);
  $area_name_q = addslashes($name);
  // Acquire a mutex to lock out others who might be editing the area
  if (!sql_mutex_lock("$tbl_area"))
  {
    fatal_error(TRUE, get_vocab("failed_to_acquire"));
  }
  // Check that the area name is unique
  if (sql_query1("SELECT COUNT(*) FROM $tbl_area WHERE area_name='$area_name_q' LIMIT 1") > 0)
  {
    $error = "invalid_area_name";
  }
  // If so, insert the area into the database.   We insert the area name that
  // we have been given, together with the default values for the per-area settings
  else
  {
    // Build arrays of data to be inserted into the table
    $sql_col = array();
    $sql_val = array();
    // Get the information about the fields in the room table
    $fields = sql_field_info($tbl_area);
    // Loop through the fields and build up the arrays
    foreach ($fields as $field)
    {
      $key = $field['name'];
      switch ($key)
      {
      case 'area_name':
        $sql_col[] = $key;
        $sql_val[] = "'$area_name_q'";
        break;
      default:
        if (array_key_exists($key, $area_defaults))
        {
          $sql_col[] = $key;
          if (in_array($key, $boolean_fields['area']))
          {
            $sql_val[] = ($area_defaults[$key]) ? 1 : 0;
          }
          elseif ($field['nature'] == 'integer')
          {
            $sql_val[] = $area_defaults[$key];
          }
          else
          {
            $sql_val[] = "'" . addslashes($area_defaults[$key]) . "'";
          }
        }
        break;
      }
    }
    $sql = "INSERT INTO $tbl_area (" . implode(', ',$sql_col) . ") VALUES (" . implode(', ',$sql_val) . ")";
    if (sql_command($sql) < 0)
    {
      fatal_error(1, sql_error());
    }
    $area = sql_insert_id("$tbl_area", "id");
  }
  // Release the mutex
  sql_mutex_unlock("$tbl_area");
}

elseif ($type == "room")
{
  // Truncate the name and description fields to the maximum length as a precaution.
  $name = substr($name, 0, $maxlength['room.room_name']);
  $description = substr($description, 0, $maxlength['room.description']);
  // Add SQL escaping
  $room_name_q = addslashes($name);
  $description_q = addslashes($description);
  if (empty($capacity))
  {
    $capacity = 0;
  }
  // Acquire a mutex to lock out others who might be editing rooms
  if (!sql_mutex_lock("$tbl_room"))
  {
    fatal_error(TRUE, get_vocab("failed_to_acquire"));
  }
  // Check that the room name is unique within the area
  if (sql_query1("SELECT COUNT(*) FROM $tbl_room WHERE room_name='$room_name_q' AND area_id=$area LIMIT 1") > 0)
  {
    $error = "invalid_room_name";
  }
  // If so, insert the room into the datrabase
  else
  {
    $sql = "INSERT INTO $tbl_room (room_name, sort_key, area_id, description, capacity)
            VALUES ('$room_name_q', '$room_name_q', $area, '$description_q',$capacity)";
    if (sql_command($sql) < 0)
    {
      fatal_error(1, sql_error());
    }
  }
  // Release the mutex
  sql_mutex_unlock("$tbl_room");
}

$returl = "admin.php?area=$area" . (!empty($error) ? "&error=$error" : "");
header("Location: $returl");
