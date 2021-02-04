<?php
namespace MRBS;

use MRBS\Form\Form;

require '../defaultincludes.inc';

// Check the CSRF token
Form::checkToken();

// Check the user is authorised for this page
checkAuthorised(this_page());

// Get the non-standard form variables
$id = get_form_var('id', 'int');
$roles = get_form_var('roles', 'array');

$user = User::getById($id);

// It's possible that we're adding a new user
if (!isset($user))
{
  $user = new User();
}

$user->roles = $roles;

echo $user->effectivePermissionsHTML();
