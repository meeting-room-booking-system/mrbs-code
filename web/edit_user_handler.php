<?php
namespace MRBS;

require "defaultincludes.inc";

use MRBS\Form\Form;


function get_form_data(User &$user) : void
{
  global $auth, $initial_user_creation;

  // Get the password fields which are treated differently
  $user->password0 = get_form_var('password0', 'string', null, INPUT_POST);
  $user->password1 = get_form_var('password1', 'string', null, INPUT_POST);

  // The non-standard form variables
  $form_vars = array(
    'roles' => 'array'
  );

  // The rest
  $columns = Columns::getInstance(_tbl(User::TABLE_NAME));

  foreach ($columns as $column)
  {
    $name = $column->name;

    $ignore = array('password_hash', 'timestamp', 'last_login', 'reset_key_hash', 'reset_key_expiry', 'auth_type');

    if (in_array($name, $ignore))
    {
      continue;
    }

    // "Level" is an exception because it's a smallint and would normally
    // be treated as a boolean
    $form_vars[$name] = ($name == 'level') ? 'int' : $column->getFormVarType();
  }

  // GET THE FORM DATA
  foreach($form_vars as $var => $var_type)
  {
    $value = get_form_var($var, $var_type);

    // Ignore any null values - the field might have been disabled by JavaScript
    if (is_null($value))
    {
      continue;
    }

    // Trim any strings
    if (is_string($value))
    {
      $value = trim($value);
    }

    // Stop ordinary users trying to change fields they are not allowed to
    if ($initial_user_creation ||
        is_user_admin() ||
        (($auth['type'] == 'db') && !in_array($var, $auth['db']['protected_fields'])) )
    {
      $user->$var = $value;
    }
  }
}


// Tidies up and validates the form data
function validate_form_data(User &$user)
{
  global $auth, $initial_user_creation;

  // Initialise the error array
  $errors = array();

  // Clean up the roles
  $user->roles = array_map('intval', $user->roles);

  // Check that the name is not empty and is unique
  if (!isset($user->name) || $user->name === '')
  {
    $errors['name_empty'] = 1;
  }
  else
  {
    if ($auth['type'] == 'db')
    {
      // Convert the name to lowercase for the 'db' scheme
      $user->name = utf8_strtolower($user->name);
    }
    // If there's already a user with this name then it can only be this user.
    $tmp = User::getByName($user->name, $user->auth_type);
    if (isset($tmp) && (!isset($user->id) || ($user->id != $tmp->id)))
    {
      $errors['name_not_unique'] = 1;
      $errors['taken_name'] = $user->name;
    }
  }

  // Check that the email address is valid
  if (isset($user->email) &&
      ($user->email !== '') &&
      !validate_email_list($user->email))
  {
    $errors['invalid_email'] = 1;
  }

  // PASSWORD
  // Check that the two passwords match
  if ($user->password0 !== $user->password1)
  {
    $errors['pwd_not_match'] = 1;
  }
  // Check that the password conforms to the password policy
  // if it's a new user, or else if it's an existing user
  // trying to change their password
  elseif (!isset($user->id) ||
          (isset($user->password0) && ($user->password0 !== '')))
  {
    if (!auth()->validatePassword($user->password0))
    {
      $errors['pwd_invalid'] = 1;
    }
    else
    {
      $user->password_hash = password_hash($user->password0, PASSWORD_DEFAULT);
    }
  }

  // AUTHORISATION CHECKS
  if (!isset($user->level))
  {
    $user->level = 0;
  }

  if (!$initial_user_creation)
  {
    $mrbs_user = session()->getCurrentUser();
    if (!isset($mrbs_user))
    {
      throw new \Exception("Attempt to edit a user by an un-logged in user");
    }

    // Check that we are not trying to upgrade our level.    This shouldn't be
    // possible but someone might have spoofed the input in the edit form
    if ($user->level > $mrbs_user->level)
    {
      $message = "Attempt to edit or create a user with a higher level than the current user's.";
      throw new \Exception($message);
    }

    if (!is_user_admin())
    {
      if (!isset($user->id))
      {
        $message = "Attempt by a non-admin to create a new user";
        throw new \Exception($message);
      }
      else
      {
        $this_user = User::getByName($mrbs_user->username, $auth['type']);
        if ($this_user->id !== $user->id)
        {
          $message = "Attempt by a non-admin to edit another user";
          throw new \Exception($message);
        }
      }
    }

    // Validate some particular database types
    $columns = Columns::getInstance(_tbl(User::TABLE_NAME));
    foreach ($columns as $index => $column)
    {
      // If this a Date type check that we've got a valid date format before
      // we get an SQL error.  If the field is nullable and the string is empty
      // we assume that the user is trying to nullify the value.
      if ($column->getType() == 'date')
      {
        if (!validate_iso_date($user->{$column->name}))
        {
          if ($column->getIsNullable() && ($user->{$column->name} === ''))
          {
            $user->{$column->name} = null;
          }
          else
          {
            // Need to add an index otherwise previous elements will be overwritten
            $errors["invalid_dates[$index]"] = $column->name;
          }
        }
      }
    }
  }

  return $errors;
}


// Check the CSRF token if we're going to be altering the database
Form::checkToken();

$id = get_form_var('id', 'int');
$delete_button = get_form_var('delete_button', 'string');
$update_button = get_form_var('update_button', 'string');

$initial_user_creation = ($auth['type'] == 'db') && (count($users = new Users) === 0);

// Unless we're using the 'db' authentication type and there are no users
// in the system yet and we're trying to add the first one, check that the
// user is authorised for this page.
if (!(isset($update_button) && $initial_user_creation))
{
  checkAuthorised(this_page());
}

// Lock the table while we alter it
if (!db()->mutex_lock(_tbl(User::TABLE_NAME)))
{
  fatal_error(get_vocab('failed_to_acquire'));
}

// Get the user
if (isset($id))
{
  $user = User::getById($id);
  if (!isset($user))
  {
    // Probably because someone has deleted the user in the meantime
    trigger_error("Could not find user with id $id");
  }
}

// DELETE
if (isset($delete_button))
{
  if (isset($user) && is_user_admin())
  {
    $mrbs_user = session()->getCurrentUser();
    // Even if you're a user admin you can't delete someone at a higher level than you
    if ($mrbs_user->level >= $user->level)
    {
      // And if this is the 'db' scheme then you can't delete the last admin, to stop you
      // getting locked out of the system.
      if (($auth['type'] != 'db') || ($user->level < $max_level) || (Users::getNAdmins() > 1))
      {
        $user->delete();
      }
    }
  }
}

// UPDATE
elseif (isset($update_button))
{
  // If it's a new user, or if for some reason the getById() failed, then
  // create a new one.
  if (!isset($user))
  {
    $user = new User();
  }

  // Get the form data
  get_form_data($user);

  // Validate the data
  $errors = validate_form_data($user);

  if (empty($errors))
  {
    $user->save();
  }
  else
    {
    $query_string_parts = $errors;
    $query_string_parts['action'] = (isset($user->id)) ? 'edit' : 'add';
    // Add the form parameters to the query string so that the user doesn't have to
    // retype them.  (We could use session variables, but we can't assume the use of
    // sessions.)
    foreach ($_REQUEST as $key => $value)
    {
      if (!in_array($key, array('password0', 'password1')) && isset($user->$key))
      {
        if (is_bool($user->$key))
        {
          $query_string_parts[$key] = ($user->$key) ? 1 : 0;
        }
        else
        {
          $query_string_parts[$key] = $user->$key;
        }
      }
    }
  }
}

// Unlock the table
db()->mutex_unlock(_tbl(User::TABLE_NAME));

$returl = 'edit_user.php';
if (!empty($query_string_parts))
{
  $returl .= '?' . http_build_query($query_string_parts, '', '&');
}

location_header($returl);
