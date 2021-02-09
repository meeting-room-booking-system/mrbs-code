<?php
namespace MRBS;

use MRBS\Form\Form;

require "defaultincludes.inc";

// Check the CSRF token.
Form::checkToken();

// Check the user is authorised for this page
checkAuthorised(this_page());

$action = get_form_var('action', 'string');
$button_save = get_form_var('button_save', 'string');

if (isset($button_save))
{
  // Lock the table while we update it
  if (!db()->mutex_lock(_tbl(Group::TABLE_NAME)))
  {
    fatal_error(get_vocab('failed_to_acquire'));
  }

  // Add a new group
  if (isset($action) && ($action == 'add'))
  {
    $name = get_form_var('name', 'string');
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
  // Edit an existing group
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

  // Unlock the table
  db()->mutex_unlock(_tbl(Group::TABLE_NAME));
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
