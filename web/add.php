<?php

// $Id$

require "defaultincludes.inc";
require_once "mrbs_sql.inc";

// Get non-standard form variables
$name = get_form_var('name', 'string');
$description = get_form_var('description', 'string');
$capacity = get_form_var('capacity', 'int');
$type = get_form_var('type', 'string');

// Check the user is authorised for this page
checkAuthorised();

// This file is for adding new areas/rooms

$error = '';

// First of all check that we've got an area or room name
if (!isset($name) || ($name === ''))
{
  $error = "empty_name";
}

// we need to do different things depending on if its a room
// or an area
elseif ($type == "area")
{
  $area = mrbsAddArea($name, $error);
}

elseif ($type == "room")
{
  $room = mrbsAddRoom($name, $area, $error, $description, $capacity);
}

$returl = "admin.php?area=$area" . (!empty($error) ? "&error=$error" : "");
header("Location: $returl");
