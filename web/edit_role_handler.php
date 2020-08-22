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


if (isset($action))
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
    default:
      break;
  }
  if (isset($error))
  {
    $query_string = "error=$error&name=$name";
  }
  location_header($returl);
}

// Shouldn't normally get here
location_header('index.php');
