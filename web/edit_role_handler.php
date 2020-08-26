<?php
namespace MRBS;

use MRBS\Form\Form;

require "defaultincludes.inc";

function add_role($name)
{
  $role = new Role($name);
  $role->save();
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


if (!isset($action))
{
  $returl = 'edit_role.php';
  switch ($action)
  {
    case 'add':
      $name = get_form_var('name', 'string');
      if (Role::exists($name))
      {
        $returl .= "?error=role_exists&name=$name";
      }
      else
      {
        add_role($name);
      }
      break;

    case 'add_role_area':
      echo "TODO";
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
