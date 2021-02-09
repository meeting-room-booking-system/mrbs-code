<?php
namespace MRBS;

use MRBS\Form\Form;

require "defaultincludes.inc";

// Check the CSRF token.
Form::checkToken();

// Check the user is authorised for this page
checkAuthorised(this_page());

$action = get_form_var('action', 'string');
$name = get_form_var('name', 'string');
$button_save = get_form_var('button_save', 'string');

if (isset($button_save))
{
  if (isset($action) && ($action == 'add'))
  {
    if (!isset($name) || ($name === ''))
    {
      $error = 'empty_name';
    }
    else
    {
      $group = new Group($name);
      if ($group->exists())
      {
        $error = 'group_exists';
      }
      else
      {
        $group->save();
      }
    }
  }
  else
  {
    $group_id = get_form_var('group_id', 'int');
    $roles = get_form_var('roles', 'array');
    // Clean up the roles
    $roles = array_map('intval', $roles);
    $group = Group::getById($group_id);
    if (isset($group))
    {
      $group->roles = $roles;
      $group->save();
    }
  }
}

$return_url = 'edit_group.php';

if (isset($error))
{
  $query_string_args = array(
      'action' => $action,
      'error'  => $error,
      'name'   => $name
    );
  $return_url .= '?' . http_build_query($query_string_args, '', '&');
}

location_header($return_url);
