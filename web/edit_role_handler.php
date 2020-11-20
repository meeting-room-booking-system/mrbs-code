<?php
namespace MRBS;

use MRBS\Form\Form;

require "defaultincludes.inc";


function update_permissions($role_id, array $area_permissions, array $room_permissions)
{
  foreach ($area_permissions as $area_id => $settings)
  {
    $p = new AreaPermission($role_id, $area_id);
    $p->permission = $settings['permission'];
    $p->state = $settings['state'];
    $p->save();
  }

  foreach ($room_permissions as $room_id => $settings)
  {
    $p = new RoomPermission($role_id, $room_id);
    $p->permission = $settings['permission'];
    $p->state = $settings['state'];
    $p->save();
  }
}


// Check the CSRF token.
Form::checkToken();

// Check the user is authorised for this page
checkAuthorised(this_page());

$action = get_form_var('action', 'string');
$area_id = get_form_var('area_id', 'int');
$room_id = get_form_var('room_id', 'int');
$name = get_form_var('name', 'string');
$permission = get_form_var('permission', 'string');
$role_id = get_form_var('role_id', 'int');
$state = get_form_var('state', 'string');
$area_permissions = get_form_var('area', 'array');
$room_permissions = get_form_var('room', 'array');
$button_save = get_form_var('button_save', 'string');

$returl = 'edit_role.php';

if (isset($button_save) && isset($action))
{
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

    case 'add_role_room':
      $room_permission = new RoomPermission($role_id, $room_id);
      if ($room_permission->exists())
      {
        $query_string_args = array(
          'action'  => $action,
          'error'   => 'role_room_exists',
          'role_id' => $role_id,
          'room_id' => $room_id
        );
      }
      else
      {
        $room_permission->permission = $permission;
        $room_permission->state = $state;
        $room_permission->save();
      }
      break;

    case 'delete_permission':
      if (isset($area_id))
      {
        $permission = new AreaPermission($role_id, $area_id);
      }
      else
      {
        $permission = new RoomPermission($role_id, $room_id);
      }
      $permission->delete();
      break;

    case 'delete_role':
      Role::deleteById($role_id);
      break;

    case 'edit_role_area_room':
      update_permissions($role_id, $area_permissions, $room_permissions);
      break;

    default:
      break;
  }
  if (!empty($query_string_args))
  {
    $returl .= '?' . http_build_query($query_string_args, '', '&');
  }

}

location_header($returl);
