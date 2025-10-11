<?php
declare(strict_types=1);
namespace MRBS;

use MRBS\DB\DBException;
use MRBS\Form\Form;

require "defaultincludes.inc";
require_once "mrbs_sql.inc";


// Check the CSRF token
Form::checkToken();

// Check the user is authorised for this page
checkAuthorised(this_page());

// Get non-standard form variables
$name = get_form_var('name', 'string', null, INPUT_POST);
$description = get_form_var('description', 'string', null, INPUT_POST);
$capacity = get_form_var('capacity', 'int', null, INPUT_POST);
$room_admin_email = get_form_var('room_admin_email', 'string', null, INPUT_POST);
$type = get_form_var('type', 'string', null, INPUT_POST);

// This file is for adding new areas/rooms
$error = '';

// First of all check that we've got an area or room name
if (!isset($name) || ($name === ''))
{
  $error = "empty_name";
}
else
{
  // Strip out any extra whitespace that the user may accidentally have typed in the name
  $name = remove_extra_whitespace($name);

  // We need to do different things depending on if it's a room
  // or an area
  if ($type === 'area')
  {
    $area_object = new Area($name);

    try
    {
      $area_object->insert();
      $area = $area_object->id;
    }
    catch (DBException $e)
    {
      if ($area_object->exists())
      {
        $error = 'invalid_area_name';
      }
      else
      {
        throw $e;
      }
    }
  }

  elseif ($type === 'room')
  {
    $room = mrbsAddRoom($name, $area, $error, $description, $capacity, $room_admin_email);
  }
}

$returl = "admin.php?area=$area" . (!empty($error) ? "&error=$error" : "");
location_header($returl);
