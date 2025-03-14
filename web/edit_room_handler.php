<?php
declare(strict_types=1);
namespace MRBS;

require "defaultincludes.inc";

use MRBS\Form\Form;

// Check the CSRF token.
Form::checkToken();

// Check the user is authorised for this page
checkAuthorised(this_page());

// Get non-standard form variables
$form_vars = array(
  'new_area'         => 'int',
  'old_area'         => 'int',
  'room_name'        => 'string',
  'sort_key'         => 'string',
  'room_disabled'    => 'string',
  'old_room_name'    => 'string',
  'description'      => 'string',
  'capacity'         => 'int',
  'room_admin_email' => 'string',
  'invalid_types'    => 'array',
  'custom_html'      => 'string'
);

foreach($form_vars as $var => $var_type)
{
  $$var = get_form_var($var, $var_type);

  // Trim the strings and truncate them to the maximum field length
  if (is_string($$var))
  {
    $$var = trim($$var);
    $$var = truncate($$var, "room.$var");
  }

}

// Get the information about the fields in the room table
$fields = db()->field_info(_tbl('room'));

// Get any custom fields
foreach($fields as $field)
{
  $var = VAR_PREFIX . $field['name'];
  $$var = get_form_var($var, get_form_var_type($field));

  // Cast booleans to ints (0 or 1) for insertion into the database
  if (is_bool($$var))
  {
    $$var = intval($$var);
  }

  // Trim any strings and truncate them to the maximum field length
  if (is_string($$var) && ($field['nature'] != 'decimal'))
  {
    $$var = trim($$var);
    $$var = truncate($$var, 'room.' . $field['name']);
  }
}

if (empty($capacity))
{
  $capacity = 0;
}


// UPDATE THE DATABASE
// -------------------

// Initialise the error array
$errors = array();

// Clean up the address list replacing newlines by commas and removing duplicates
$room_admin_email = clean_address_list($room_admin_email);
// Validate email addresses
if (!validate_email_list($room_admin_email))
{
  $errors[] = 'invalid_email';
}

// Make sure the invalid types exist
if (isset($booking_types))
{
  $invalid_types = array_intersect($invalid_types, $booking_types);
}
else
{
  $invalid_types = array();
}


if (empty($errors))
{
  // Start a transaction
  db()->begin();

  // Check the new area still exists
  $sql = "SELECT id
            FROM " . _tbl('area') . "
           WHERE id=?
           LIMIT 1
      FOR UPDATE";  // lock this row

  if (db()->query1($sql, array($new_area)) < 1)
  {
    $errors[] = 'invalid_area';
    db()->rollback();
  }
  // If so, check that the room name is not already used in the area
  // (only do this if you're changing the room name or the area - if you're
  // just editing the other details for an existing room we don't want to reject
  // the edit because the room already exists!)
  elseif ( (($new_area != $old_area) || ($room_name != $old_room_name))
          && db()->query1("SELECT id
                                 FROM " . _tbl('room') . "
                                WHERE room_name=:room_name
                                  AND area_id=:area_id
                                LIMIT 1
                           FOR UPDATE", array(":room_name" => $room_name, ":area_id" => $new_area)) > 0)
  {
    $errors[] = 'invalid_room_name';
    db()->rollback();
  }
  // If everything is still OK, update the database
  else
  {
    // Convert booleans into 0/1 (necessary for PostgreSQL)
    $room_disabled = (!empty($room_disabled)) ? 1 : 0;
    $sql = "UPDATE " . _tbl('room') . " SET ";
    $sql_params = array();
    $assign_array = array();
    foreach ($fields as $field)
    {
      if ($field['name'] != 'id')  // don't do anything with the id field
      {
        switch ($field['name'])
        {
          // first of all deal with the standard MRBS fields
          case 'area_id':
            $assign_array[] = "area_id=?";
            $sql_params[] = $new_area;
            break;
          case 'disabled':
            $assign_array[] = "disabled=?";
            $sql_params[] = $room_disabled;
            break;
          case 'room_name':
            $assign_array[] = "room_name=?";
            $sql_params[] = $room_name;
            break;
          case 'sort_key':
            $assign_array[] = "sort_key=?";
            $sql_params[] = $sort_key;
            break;
          case 'description':
            $assign_array[] = "description=?";
            $sql_params[] = $description;
            break;
          case 'capacity':
            $assign_array[] = "capacity=?";
            $sql_params[] = $capacity;
            break;
          case 'room_admin_email':
            $assign_array[] = "room_admin_email=?";
            $sql_params[] = $room_admin_email;
            break;
          case 'invalid_types':
            $assign_array[] = "invalid_types=?";
            $sql_params[] = json_encode($invalid_types);
            break;
          case 'custom_html':
            $assign_array[] = "custom_html=?";
            $sql_params[] = $custom_html;
            break;
          // then look at any user defined fields
          default:
            $var = VAR_PREFIX . $field['name'];
            switch ($field['nature'])
            {
              case 'integer':
                if (!isset($$var) || ($$var === ''))
                {
                  // Try and set it to NULL when we can because there will be cases when we
                  // want to distinguish between NULL and 0 - especially when the field
                  // is a genuine integer.
                  $$var = ($field['is_nullable']) ? null : 0;
                }
                break;
              default:
                // Do nothing
                break;
            }
            $assign_array[] = db()->quote($field['name']) . "=?";
            $sql_params[] = $$var;
            break;
        }
      }
    }

    $sql .= implode(",", $assign_array) . " WHERE id=?";
    $sql_params[] = $room;
    db()->command($sql, $sql_params);

    // Commit the transaction
    db()->commit();

    // Go back to the admin page (for the new area)
    location_header("admin.php?day=$day&month=$month&year=$year&area=$new_area&room=$room");
  }

}


// Go back to the room form with errors
$query_string = "area=$old_area&room=$room";
foreach ($errors as $error)
{
  $query_string .= "&errors[]=$error";
}
location_header("edit_room.php?$query_string");
