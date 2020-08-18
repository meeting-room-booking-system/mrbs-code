<?php
namespace MRBS;

require "defaultincludes.inc";

use MRBS\Form\Form;

// If we haven't got the ability to reset passwords then get out of here
if (!auth()->canResetPassword())
{
  location_header('index.php');
}

// Check the CSRF token.
Form::checkToken();

// Check the user is authorised for this page
checkAuthorised(this_page());

$action = get_form_var('action', 'string');
$username = get_form_var('username', 'string');

if (isset($action) && isset($username))
{
  $username = trim($username);
  if ($username !== '')
  {
    switch ($action)
    {
      case 'request':
        if (auth()->requestPassword($username))
        {
          $result = 'request_sent';
        }
        else
        {
          $result = 'request_failed';
          // Although the request failed, return a success message in order
          // to avoid giving away information about users in the system.
          // (Could make this a configuration option).
          $result = 'request_sent';
        }
        break;
      case 'reset':
        $key = get_form_var('key', 'string');
        $password0 = get_form_var('password0', 'string');
        $password1 = get_form_var('password1', 'string');
        if ($password0 !== $password1)
        {
          $error = 'pwd_not_match';
        }
        elseif (!auth()->validatePassword($password0))
        {
          $error = 'pwd_invalid';
        }
        else
        {
          if (auth()->resetPassword($username, $key, $password0))
          {
            $result = 'pwd_reset';
          }
          else
          {
            $result = 'reset_failed';
          }
        }
        break;
      default:
        // Shouldn't get here
        break;
    }
    if (isset($result))
    {
      $query_string = "result=$result";
    }
    elseif (isset($error))
    {
      $query_string = "action=reset&error=$error&usernames[0]=$username&key=$key";
    }
    else
    {
      // Shouldn't get here
    }
    location_header("reset_password.php?$query_string");
  }
}

// Shouldn't normally get here
location_header('index.php');
