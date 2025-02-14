<?php
declare(strict_types=1);
namespace MRBS;

use MRBS\Form\Form;

require "defaultincludes.inc";


function update_rules($role_id, array $area_rules, array $room_rules)
{
  foreach ($area_rules as $area_id => $settings)
  {
    $rule = new AreaRule($role_id, $area_id);
    $rule->permission = $settings['permission'];
    $rule->state = $settings['state'];
    $rule->save();
  }

  foreach ($room_rules as $room_id => $settings)
  {
    $rule = new RoomRule($role_id, $room_id);
    $rule->permission = $settings['permission'];
    $rule->state = $settings['state'];
    $rule->save();
  }
}


function add_role($name)
{
  $error = null;

  if (!isset($name) || ($name === ''))
  {
    $error = 'empty_name';
  }
  else
  {
    $role = new Role($name);
    if ($role->exists())
    {
      $error = 'role_exists';
    }
    else
    {
      $role->save();
    }
  }

  return $error;
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
$area_rules = get_form_var('area_rules', 'array');
$room_rules = get_form_var('room_rules', 'array');
$button_delete = get_form_var('button_delete', 'string');
$button_save = get_form_var('button_save', 'string');

$returl = 'edit_role.php';

if (isset($button_delete))
{
  if (isset($area_id))
  {
    $rule = new AreaRule($role_id, $area_id);
  }
  else
  {
    $rule = new RoomRule($role_id, $room_id);
  }
  $rule->delete();
}

elseif (isset($button_save) && isset($action))
{
  $query_string_args = array();
  switch ($action)
  {
    case 'add':
      $error = add_role($name);
      if (isset($error))
      {
        $query_string_args = array(
            'action' => $action,
            'error'  => $error,
            'name'   => $name
          );
      }
      break;

    case 'add_area_rule':
      $area_rule = new AreaRule($role_id, $area_id);
      if ($area_rule->exists())
      {
        $query_string_args = array(
            'action'  => $action,
            'error'   => 'area_rule_exists',
            'role_id' => $role_id,
            'area_id' => $area_id
          );
      }
      else
      {
        $area_rule->permission = $permission;
        $area_rule->state = $state;
        $area_rule->save();
      }
      break;

    case 'add_room_rule':
      $room_rule = new RoomRule($role_id, $room_id);
      if ($room_rule->exists())
      {
        $query_string_args = array(
          'action'  => $action,
          'error'   => 'room_rule_exists',
          'role_id' => $role_id,
          'room_id' => $room_id
        );
      }
      else
      {
        $room_rule->permission = $permission;
        $room_rule->state = $state;
        $room_rule->save();
      }
      break;

    case 'delete_role':
      Role::deleteById($role_id);
      break;

    case 'edit_role_area_room':
      update_rules($role_id, $area_rules, $room_rules);
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
