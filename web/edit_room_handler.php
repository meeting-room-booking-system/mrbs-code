<?php
namespace MRBS;

require "defaultincludes.inc";

use MRBS\Form\Form;


function get_form()
{
  $params = array();

  // Get the special parameters which don't have a corresponding column
  $params['new_area'] = get_form_var('new_area', 'int');
  $params['old_area'] = get_form_var('old_area', 'int');
  $params['old_room_name'] = get_form_var('old_room_name', 'string');
  // And get the ones that have a different type
  $params['invalid_types'] = get_form_var('invalid_types', 'array');

  // Get all the others
  $columns = new Columns(_tbl(Room::TABLE_NAME));

  foreach ($columns as $column)
  {
    $name = $column->name;

    if ((!array_key_exists($name, $_POST) && !(array_key_exists($name, $_GET))) ||
        ($name == 'invalid_types'))
    {
      continue;
    }

    $var_type = $column->getFormVarType();

    $params[$name] = get_form_var($name, $var_type);
    $params[$name] = $column->sanitizeFormVar($params[$name]);
  }

  return $params;
}


function update_room($room_id, array $form)
{
  global $booking_types;

  $errors = array();
  $room = Room::getById($room_id);

  foreach($form as $key => $value)
  {
    switch ($key)
    {
      case 'capacity':
        $room->capacity = (empty($value)) ? 0 : $value;
        break;
      case 'new_area':
        $room->area_id = $value;
        break;
      case 'old_area':
      case 'old_room_name':
        // Don't do anything with these
        break;
      case 'room_admin_email':
        // Clean up the address list replacing newlines by commas and removing duplicates
        $value = clean_address_list($value);
        // Validate email addresses
        if (!validate_email_list($value))
        {
          $errors[] = 'invalid_email';
        }
        $room->room_admin_email = $value;
        break;
      case 'room_disabled':
        $room->disabled = $value;
        break;
      case 'invalid_types':
        // Make sure the invalid types exist
        if (isset($booking_types))
        {
          $room->invalid_types = array_intersect($value, $booking_types);
        }
        else
        {
          $room->invalid_types = array();
        }
        break;
      default:
        $room->{$key} = $value;
        break;
    }
  }

  /* TODO
  // Acquire a mutex to lock out others who might be deleting the new area
  if (!db()->mutex_lock(_tbl(Area::TABLE_NAME)))
  {
    fatal_error(get_vocab("failed_to_acquire"));
  }
  */

  // Check that the area still exists
  $area = Area::getById($room->area_id);
  if (!isset($area))
  {
    $errors[] = 'invalid_area';
  }
  // If so, check that the room name is not already used in the area
  // (only do this if you're changing the room name or the area - if you're
  // just editing the other details for an existing room we don't want to reject
  // the edit because the room already exists!)
  elseif ( (($form['new_area'] != $form['old_area']) || ($room->room_name != $form['old_room_name'])) &&
           $room->exists())
  {
    $errors[] = 'invalid_room_name';
  }
  // If everything is still OK, update the database
  else
  {
    $room->save();
  }

  /* TODO
  // Release the lock
  db()->mutex_unlock(_tbl(Area::TABLE_NAME));
  */
  return $errors;
}


// Check the CSRF token.
Form::checkToken();

// Check the user is authorised for this page
checkAuthorised(this_page());

$form = get_form();
$errors = update_room($room, $form);

if (count($errors) == 0)
{
  // Go back to the admin page (for the new area)
  $returl = 'admin.php';
  $query_string = "day=$day&month=$month&year=$year&area=${form['new_area']}&room=$room";
}
else
{
  // Go back to the room form with errors
  $returl = 'edit_room.php';
  $query_string = "room=$room";
  foreach ($errors as $error)
  {
    $query_string .= "&errors[]=$error";
  }
}

location_header("$returl?$query_string");
