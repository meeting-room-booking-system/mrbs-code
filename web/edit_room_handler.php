<?php
namespace MRBS;

require "defaultincludes.inc";

use MRBS\Form\Form;

// Check the CSRF token.
Form::checkToken();

// Check the user is authorised for this page
checkAuthorised();

// Get non-standard form variables
$new_area = get_form_var('new_area', 'int');
$old_area = get_form_var('old_area', 'int');
$room_name = get_form_var('room_name', 'string');
$room_disabled = get_form_var('room_disabled', 'string');
$sort_key = get_form_var('sort_key', 'string');
$old_room_name = get_form_var('old_room_name', 'string');
$description = get_form_var('description', 'string');
$capacity = get_form_var('capacity', 'int');
$room_admin_email = get_form_var('room_admin_email', 'string');
$custom_html = get_form_var('custom_html', 'string');  // Used for both area and room, but you only ever have one or the other

// Get the information about the fields in the room table
$fields = db()->field_info($tbl_room);

// Get any custom fields
foreach($fields as $field)
{
  switch($field['nature'])
  {
    case 'character':
      $type = 'string';
      break;
    case 'integer':
      $type = 'int';
      break;
    // We can only really deal with the types above at the moment
    default:
      $type = 'string';
      break;
  }
  $var = VAR_PREFIX . $field['name'];
  $$var = get_form_var($var, $type);
  if (($type == 'int') && ($$var === ''))
  {
    unset($$var);
  }
}


// UPDATE THE DATABASE
// -------------------

if (empty($area))
{
  throw new \Exception('$area is empty');
}

// Intialise the error array
$errors = array();


// Go back to the admin page
header("Location: admin.php?day=$day&month=$month&year=$year&area=$area&room=$room");
exit();