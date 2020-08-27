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
$name = get_form_var('name', 'string');
$permission = get_form_var('permission', 'string');
$role_id = get_form_var('role_id', 'string');
$state = get_form_var('state', 'string');

if (isset($action))
{
  $returl = 'edit_role.php';
  $query_string_args = array();
  switch ($action)
  {
    case 'add':
      $role = new Role($name);
      if ($role->exists())
      {
        $query_string_args = array(
            'action' => $action,
            'error'  => 'role_exists',
            'name'   => $name
          );
      }
      else
      {
        $role->save();
      }
      break;

    case 'add_role_area':
      $area_permission = new AreaPermission($role_id, $area_id);
      if ($area_permission->exists())
      {
        $query_string_args = array(
            'action'  => $action,
            'error'   => 'role_area_exists',
            'role_id' => $role_id,
            'area_id' => $area_id
          );
      }
      else
      {
        $area_permission->permission = $permission;
        $area_permission->state = $state;
        $area_permission->save();
      }
      break;

    case 'delete':
      Role::deleteById($role_id);
      break;

    default:
      break;
  }
  if (!empty($query_string_args))
  {
    $returl .= '?' . http_build_query($query_string_args, '', '&');
  }
  location_header($returl);
}

// Shouldn't normally get here
location_header('index.php');
