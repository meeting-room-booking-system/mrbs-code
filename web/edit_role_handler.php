<?php
namespace MRBS;

use MRBS\Form\Form;

require "defaultincludes.inc";


function add_role_area($role_id, $area_id, $permission, $state)
{
  $area_permission = new AreaPermission($area_id, $role_id);
}


// Check the CSRF token.
Form::checkToken();

// Check the user is authorised for this page
checkAuthorised(this_page());

$action = get_form_var('action', 'string');
$area_id = get_form_var('area_id', 'string');
$permission = get_form_var('permission', 'string');
$role_id = get_form_var('role_id', 'string');
$state = get_form_var('state', 'string');

if (isset($action))
{
  $returl = 'edit_role.php';
  switch ($action)
  {
    case 'add':
      $name = get_form_var('name', 'string');
      $role = new Role($name);
      if ($role->exists())
      {
        $returl .= "?error=role_exists&name=$name";
      }
      else
      {
        $role->save();
      }
      break;

    case 'add_role_area':
      add_role_area($role_id, $area_id, $permission, $state);
      break;

    case 'delete':
      Role::deleteById($role_id);
      break;

    default:
      break;
  }
  if (isset($error))
  {
    $returl .= "?error=$error&name=$name";
  }
  location_header($returl);
}

// Shouldn't normally get here
location_header('index.php');
