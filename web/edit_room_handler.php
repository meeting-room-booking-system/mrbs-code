<?php
namespace MRBS;

require "defaultincludes.inc";

use MRBS\Form\Form;


function get_form_data(Room &$room)
{
  // The non-standard form variables
  $form_vars = array(
    'new_area'      => 'int',
    'old_area'      => 'int',
    'old_room_name' => 'string',
    'invalid_types' => 'array'
  );

  // The rest
  $columns = Columns::getInstance(_tbl(Room::TABLE_NAME));

  foreach ($columns as $column)
  {
    $name = $column->name;

    // Ignore the ones we've already got.
    // Also ignore 'id' and 'area_id' because they're not in the form data.
    if (array_key_exists($name, $form_vars) ||
        in_array($name, array('id', 'area_id')))
    {
      continue;
    }

    $form_vars[$name] = $column->getFormVarType();
  }

  // GET THE FORM DATA
  foreach($form_vars as $var => $var_type)
  {
    $value = get_form_var($var, $var_type);

    // Ignore any null values - the field might have been disabled by JavaScript
    if (is_null($value))
    {
      continue;
    }

    // Trim any strings
    if (is_string($value))
    {
      $value = trim($value);
    }

    $room->$var = $value;
  }
}


// Tidies up and validates the form data
function validate_form_data(Room &$room)
{
  global $booking_types;

  // Initialise the error array
  $errors = array();

  // Capacity
  if (empty($room->capacity))
  {
    $room->capacity = 0;
  }

  // Clean up the address list replacing newlines by commas and removing duplicates
  $room->room_admin_email = clean_address_list($room->room_admin_email);

  // Validate email addresses
  if (!validate_email_list($room->room_admin_email))
  {
    $errors[] = 'invalid_email';
  }

  // Make sure the invalid types exist
  if (isset($booking_types))
  {
    $room->invalid_types = array_intersect($room->invalid_types, $booking_types);
  }
  else
  {
    $room->invalid_types = array();
  }

  // Check that the area still exists
  $room->area_id = $room->new_area;
  $area = Area::getById($room->area_id);
  if (!isset($area))
  {
    $errors[] = 'invalid_area';
  }
  // If so, check that the room name is not already used in the area
  // (only do this if you're changing the room name or the area - if you're
  // just editing the other details for an existing room we don't want to reject
  // the edit because the room already exists!)
  elseif ((($room->new_area != $room->old_area) || ($room->room_name != $room->old_room_name)) &&
          $room->exists())
  {
    $errors[] = 'invalid_room_name';
  }

  return $errors;
}


// Check the CSRF token.
Form::checkToken();

// Check the user is authorised for this page
checkAuthorised(this_page());

if (empty($room))
{
  throw new \Exception('$room is empty');
}

// Get the existing room
$room_object = Room::getById($room);
if (!isset($room_object))
{
  throw new \Exception("The room with id $room no longer exists");
}
get_form_data($room_object);
// Save a copy before validation
$original_room_object = clone $room_object;
$errors = validate_form_data($room_object);

try {
  if (!empty($errors))
  {
    throw new Exception("Validation failed");
  }
  // Everything is OK, update the database and go back to the admin page (for the new area)
  $room_object->save();
  $returl = 'admin.php';
  $query_string = "day=$day&month=$month&year=$year&area=$room_object->new_area&room=$room";
}
catch (\Exception $e) {
  if (empty($errors))
  {
    // Revalidate the form in case something has changed in the (short) interval
    // between the original validation and saving.
    $errors = validate_form_data($original_room_object);
    if (empty($errors))
    {
      // If there are still no validation errors it must be something else, so
      // re-throw the exception.
      throw $e;
    }
  }
  // Go back to the room form with the errors
  $returl = 'edit_room.php';
  $query_string = "area=$room_object->old_area&room=$room";
  foreach ($errors as $error) {
    $query_string .= "&errors[]=$error";
  }
}

location_header("$returl?$query_string");
