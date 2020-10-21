<?php
namespace MRBS;

use MRBS\Form\Form;

require "defaultincludes.inc";

// Check the CSRF token.
Form::checkToken();

// Check the user is authorised for this page
checkAuthorised(this_page());

$button_save = get_form_var('button_save', 'string');

// TODO: adding a new group for the 'db' auth type
if (isset($button_save))
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

$return_url = 'edit_group.php';

location_header($return_url);
