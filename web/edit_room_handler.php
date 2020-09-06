<?php
namespace MRBS;

require "defaultincludes.inc";

use MRBS\Form\Form;


function get_form()
{
  $form = array();

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
    'custom_html'      => 'string'
  );

  // Add in the custom fields, which have a special prefix
  $columns = new Columns(_tbl(Room::TABLE_NAME));

  foreach ($columns as $column)
  {
    if (array_key_exists(VAR_PREFIX . $column->name, $_POST))
    {
      $nature = $column->getNature();
      $length = $column->getLength();
      switch($nature)
      {
        case Column::NATURE_CHARACTER:
          $var_type = 'string';
          break;
        case Column::NATURE_INTEGER:
          // Smallints and tinyints are considered to be booleans
          $var_type = (isset($length) && ($length <= 2)) ? 'string' : 'int';
          break;
        // We can only really deal with the types above at the moment
        default:
          $var_type = 'string';
          break;
      }
      $form_vars[VAR_PREFIX . $column->name] = $var_type;
    }
  }

  $prefix_length = strlen(VAR_PREFIX);
  foreach($form_vars as $var => $var_type)
  {
    $key = (strpos($var, VAR_PREFIX) === 0) ? substr($var, $prefix_length) : $var;
    $form[$key] = get_form_var($var, $var_type);

    // Trim the strings and truncate them to the maximum field length
    if (is_string($form[$key]))
    {
      $column = $columns->getColumnByName($key);
      // Some variables, eg decimals, will also be PHP strings, so only
      // trim columns with a database nature of 'character'.
      if (!isset($column) || ($column->getNature() === Column::NATURE_CHARACTER))
      {
        $form[$key] = trim($form[$key]);
        $form[$key] = truncate($form[$key], "room.$key");
      }
    }
  }

  return $form;
}


function update_room($room_id, array $form)
{
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
