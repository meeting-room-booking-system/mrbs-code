<?php
namespace MRBS;

use MRBS\Form\Form;
use MRBS\Form\ElementFieldset;
use MRBS\Form\ElementInputSubmit;
use MRBS\Form\ElementP;
use MRBS\Form\FieldInputEmail;
use MRBS\Form\FieldInputPassword;
use MRBS\Form\FieldInputSubmit;
use MRBS\Form\FieldInputText;
use MRBS\Form\FieldSelect;

/*****************************************************************************\
*                                                                            *
*   File name     edit_user.php                                             *
*                                                                            *
*   Description   Edit the user database                                     *
*                                                                            *
*   Notes         Designed to be easily extensible:                          *
*                 Adding more fields for each user does not require          *
*                 modifying the editor code. Only to add the fields in       *
*                 the database creation code.                                *
*                                                                            *
*                 An admin rights model is used where the level (an          *
*                 integer between 0 and $max_level) denotes rights:          *
*                      0:  no rights                                         *
*                      1:  an ordinary user                                  *
*                      2+: admins, with increasing rights.   Designed to     *
*                          allow more granularity of admin rights, for       *
*                          example by having booking admins, user admins     *
*                          snd system admins.  (System admins might be       *
*                          necessary in the future if, for example, some     *
*                          parameters currently in the config file are      *
*                          made editable from MRBS)                          *
*                                                                            *
*                 Only admins with at least user editing rights (level >=    *
*                 $min_user_editing_level) can edit other users, and they    *
*                 cannot edit users with a higher level than themselves      *
*                                                                            *
*                                                                            *
\*****************************************************************************/

require "defaultincludes.inc";

// Get non-standard form variables
$action = get_form_var('action', 'string');
$id = get_form_var('id', 'int');
$invalid_email = get_form_var('invalid_email', 'int');
$name_empty = get_form_var('name_empty', 'int');
$name_not_unique = get_form_var('name_not_unique', 'int');
$taken_name = get_form_var('taken_name', 'string');
$pwd_not_match = get_form_var('pwd_not_match', 'string');
$pwd_invalid = get_form_var('pwd_invalid', 'string');
$datatable = get_form_var('datatable', 'int');  // Will only be set if we're using DataTables
$back_button = get_form_var('back_button', 'string');
$delete_button = get_form_var('delete_button', 'string');
$edit_button = get_form_var('edit_button', 'string');
$update_button = get_form_var('update_button', 'string');

if (isset($back_button))
{
  unset($action);
}
elseif (isset($delete_button))
{
  $action = 'delete';
}
elseif (isset($edit_button))
{
  $action = 'edit';
}
elseif (isset($update_button))
{
  $action = 'update';
}

$is_ajax = is_ajax();


function print_effective_permissions(User $user)
{
  return;
  // Under development - not doing anything for the moment.
  echo "<h2>" . get_vocab('effective_permissions') . "</h2>\n";

  $areas = new Areas();

  echo "<table>\n";
  foreach ($areas as $area)
  {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($area->area_name) . "</td>";
    echo "</tr>\n";
    $rooms = new Rooms($area->id);
    foreach ($rooms as $room)
    {
      echo "<tr>";
      echo "<td>" . htmlspecialchars($room->room_name) . "</td>";
      echo "</tr>\n";
    }
  }
  echo "</table>\n";
}


// Checks whether the current user can view the target user
function can_view_user($target)
{
  global $auth, $min_user_viewing_level;

  $mrbs_user = session()->getCurrentUser();

  // You can only see this user if you are logged in and (a) we allow everybody to see all
  // users or (b) you are an admin or (c) you are this user
  if (!isset($mrbs_user))
  {
    return false;
  }

  return (!$auth['only_admin_can_see_other_users']  ||
          ($mrbs_user->level >= $min_user_viewing_level) ||
          (strcasecmp($mrbs_user->username, $target) === 0));
}


// Checks whether the current user can edit the target user
function can_edit_user($target)
{
  $mrbs_user = session()->getCurrentUser();

  return (is_user_admin() || (isset($mrbs_user) && strcasecmp($mrbs_user->username, $target) === 0));
}


// Get the type that should be used with get_form_var() for
// a field which is a member of the array returned by get_field_info()
function get_form_var_type($field)
{
  // "Level" is an exception because we've forced the value to be a string
  // so that it can be used in an associative array
  if ($field['name'] == 'level')
  {
    return 'string';
  }
  switch($field['nature'])
  {
    case 'character':
      $type = 'string';
      break;
    case 'integer':
      // Smallints and tinyints are considered to be booleans
      $type = (isset($field['length']) && ($field['length'] <= 2)) ? 'string' : 'int';
      break;
    // We can only really deal with the types above at the moment
    default:
      $type = 'string';
      break;
  }
  return $type;
}


function output_row(User $user)
{
  global $auth;
  global $is_ajax, $json_data;
  global $fields, $ignore_columns, $select_options;

  $values = array();

  // First column, which is the display name
  // Make sure we've got a display name.  If not, use the username.
  if (!isset($user->display_name) || (trim($user->display_name) === ''))
  {
    $user->display_name = $user->name;
  }

  $form_value = $user->display_name;

  // You can only edit a user if you have sufficient admin rights, or else if that user is yourself
  if (can_edit_user($user->name))
  {
    $form = new Form();
    $form->setAttributes(array('method' => 'post',
                               'action' => multisite(this_page())));
    $form->addHiddenInput('id', $user->id);
    $submit = new ElementInputSubmit();
    $submit->setAttributes(array('class' => 'link',
                                 'name'  => 'edit_button',
                                 'value' => $form_value));
    $form->addElement($submit);
    $first_column_value = $form->toHTML();
  }
  else
  {
    $first_column_value = "<span class=\"normal\">" . htmlspecialchars($form_value) . "</span>";
  }

  $sortname = get_sortable_name($user->display_name);
  $values[] = '<span title="' . htmlspecialchars($sortname) . '"></span>' . $first_column_value;

  // Then the username
  $name_value = "<span class=\"normal\">" . htmlspecialchars($user->name) . "</span>";
  $values[] = '<span title="' . htmlspecialchars($user->name) . '"></span>' . $name_value;
  // Then the email address
  // we don't want to truncate the email address
  $escaped_email = htmlspecialchars($user->email);
  $values[] = "<div class=\"string\">\n" .
    "<a href=\"mailto:$escaped_email\">$escaped_email</a>\n" .
    "</div>\n";
  // And add the groups, which aren't one of the table columns
  $group_name_list = implode(', ', $user->group_names);
  $values[] = "<div class=\"string\" title=\"" . htmlspecialchars($group_name_list) . "\">" .
    htmlspecialchars($group_name_list) . "</div>";
  // And add the roles, which aren't one of the table columns either
  $role_name_list = implode(', ', $user->role_names);
  $values[] = "<div class=\"string\" title=\"" . htmlspecialchars($role_name_list) . "\">" .
    htmlspecialchars($role_name_list) . "</div>";

  if ($auth['type'] == 'db')
  {
    // Other columns
    foreach ($fields as $field)
    {
      $key = $field['name'];
      if (!in_array($key, $ignore_columns))
      {
        $col_value = $user->{$key};

        // If you are not a user admin then you are only allowed to see the last_updated
        // and last_login times for yourself.
        if (in_array($key, array('timestamp', 'last_login')) &&
          !can_edit_user($user->name))
        {
          $col_value = null;
        }

        switch ($key)
        {
          // special treatment for some fields
          case 'level':
            // the level field contains a code and we want to display a string
            // (but we put the code in a span for sorting)
            $values[] = "<span title=\"$col_value\"></span>" .
              "<div class=\"string\">" . get_vocab("level_$col_value") . "</div>";
            break;

          case 'timestamp':
            // Convert the SQL timestamp into a time value and back into a localised string and
            // put the UNIX timestamp in a span so that the JavaScript can sort it properly.
            $unix_timestamp = strtotime($col_value);
            if (($unix_timestamp === false) || ($unix_timestamp < 0))
            {
              // To cater for timestamps before the start of the Unix Epoch
              $unix_timestamp = 0;
            }
            $values[] = "<span title=\"$unix_timestamp\"></span>" .
              (($unix_timestamp) ? time_date_string($unix_timestamp) : '');
            break;

          case 'last_login':
            $values[] = "<span title=\"$col_value\"></span>" .
              (($col_value) ? time_date_string($col_value) : '');
            break;

          default:
            // Where there's an associative array of options, display
            // the value rather than the key
            if (isset($select_options["user.$key"]) &&
              is_assoc($select_options["user.$key"]))
            {
              if (isset($select_options["user.$key"][$user->{$key}]))
              {
                $col_value = $select_options["user.$key"][$user->{$key}];
              }
              else
              {
                $col_value = '';
              }
              $values[] = "<div class=\"string\">" . htmlspecialchars($col_value) . "</div>";
            }
            elseif (($field['nature'] == 'boolean') ||
              (($field['nature'] == 'integer') && isset($field['length']) && ($field['length'] <= 2)))
            {
              // booleans: represent by a checkmark
              $values[] = (!empty($col_value)) ? MRBS_HEAVY_CHECK_MARK : '';
            }
            elseif (($field['nature'] == 'integer') && isset($field['length']) && ($field['length'] > 2))
            {
              // integer values
              $values[] = $col_value;
            }
            else
            {
              // strings
              $values[] = "<div class=\"string\" title=\"" . htmlspecialchars($col_value) . "\">" .
                htmlspecialchars($col_value) . "</div>";
            }
            break;
        }  // end switch
      }
    }  // end foreach
  }

  if ($is_ajax)
  {
    $json_data['aaData'][] = $values;
  }
  else
  {
    echo "<tr>\n<td>\n";
    echo implode("</td>\n<td>", $values);
    echo "</td>\n</tr>\n";
  }
}


function get_field_level($params, $disabled=false)
{
  global $level;

  // Only display options up to and including one's own level (you can't upgrade yourself).
  // If you're not some kind of admin then the select will also be disabled.
  // (Note - disabling individual options doesn't work in older browsers, eg IE6)
  $options = array();

  for ($i=0; $i<=$level; $i++)
  {
    $options[$i] = get_vocab("level_$i");
  }

  $field = new FieldSelect();
  $field->setLabel($params['label'])
        ->setControlAttributes(array('name' => $params['name'],
                                     'disabled' => $disabled))
        ->addSelectOptions($options, $params['value'], true);

  return $field;
}


function get_fieldset_roles($user)
{
  global $initial_user_creation;

  $roles = new Roles();
  $disabled = !$initial_user_creation && !is_user_admin();
  return $roles->getFieldset($user->roles, $disabled);
}


function get_field_name($params, $disabled=false)
{
  $field = new FieldInputText();

  $field->setLabel($params['label'])
        ->setControlAttributes(array('name'     => $params['name'],
                                     'value'    => $params['value'],
                                     'disabled' => $disabled,
                                     'required' => true,
                                     'pattern'  => REGEX_TEXT_POS));

  if (null !== ($maxlength = maxlength('user.name')))
  {
    $field->setControlAttribute('maxlength', $maxlength);
  }

  // If the name field is disabled we need to add a hidden input, because
  // otherwise it won't be posted.
  if ($disabled)
  {
    $field->addHiddenInput($params['name'], $params['value']);
  }

  return $field;
}


function get_field_display_name($params, $disabled=false)
{
  $field = new FieldInputText();

  $field->setLabel($params['label'])
    ->setControlAttributes(array('name'     => $params['name'],
                                 'value'    => $params['value'],
                                 'disabled' => $disabled,
                                 'required' => true,
                                 'pattern'  => REGEX_TEXT_POS));

  if (null !== ($maxlength = maxlength('user.display_name')))
  {
    $field->setControlAttribute('maxlength', $maxlength);
  }

  // If the name field is disabled we need to add a hidden input, because
  // otherwise it won't be posted.
  if ($disabled)
  {
    $field->addHiddenInput($params['name'], $params['value']);
  }

  return $field;
}


function get_field_email($params, $disabled=false)
{
  $field = new FieldInputEmail();

  $field->setLabel($params['label'])
        ->setControlAttributes(array('name'     => $params['name'],
                                     'value'    => $params['value'],
                                     'disabled' => $disabled,
                                     'multiple' => true));

  if (null !== ($maxlength = maxlength('user.email')))
  {
    $field->setControlAttribute('maxlength', $maxlength);
  }

  return $field;
}


function get_field_custom($custom_field, $params, $disabled=false)
{
  global $select_options, $datalist_options, $is_mandatory_field;
  global $text_input_max;

  // Output a checkbox if it's a boolean or integer <= 2 bytes (which we will
  // assume are intended to be booleans)
  if (($custom_field['nature'] == 'boolean') ||
      (($custom_field['nature'] == 'integer') && isset($custom_field['length']) && ($custom_field['length'] <= 2)) )
  {
    $class = 'FieldInputCheckbox';
  }
  // Output a textarea if it's a character string longer than the limit for a
  // text input
  elseif (($custom_field['nature'] == 'character') && isset($custom_field['length']) && ($custom_field['length'] > $text_input_max))
  {
    $class = 'FieldTextarea';
  }
  elseif (!empty($select_options[$params['field']]))
  {
    $class = 'FieldSelect';
  }
  elseif (!empty($datalist_options[$params['field']]))
  {
    $class = 'FieldInputDatalist';
  }
  else
  {
    $class = 'FieldInputText';
  }

  $full_class = __NAMESPACE__ . "\\Form\\$class";
  $field = new $full_class();
  $field->setLabel($params['label'])
          ->setControlAttribute('name', $params['name']);

  if (!empty($is_mandatory_field[$params['field']]))
  {
    $field->setControlAttribute('required', true);
  }
  if ($disabled)
  {
    $field->setControlAttribute('disabled', true);
    $field->addHiddenInput($params['name'], $params['value']);
  }

  switch ($class)
  {
    case 'FieldInputCheckbox':
      $field->setChecked($params['value']);
      break;

    case 'FieldSelect':
      $options = $select_options[$params['field']];
      $field->addSelectOptions($options, $params['value']);
      break;

    case 'FieldInputDatalist':
      $options = $datalist_options[$params['field']];
      $field->addDatalistOptions($options);
      // Drop through

    case 'FieldInputText':
      if (!empty($is_mandatory_field[$params['field']]))
      {
        // Set a pattern as well as required to prevent a string of whitespace
        $field->setControlAttribute('pattern', REGEX_TEXT_POS);
      }
      // Drop through

    case 'FieldTextarea':
      if ($class == 'FieldTextarea')
      {
        $field->setControlText($params['value']);
      }
      else
      {
        $field->setControlAttribute('value', $params['value']);
      }
      if (null !== ($maxlength = maxlength($params['field'])))
      {
        $field->setControlAttribute('maxlength', $maxlength);
      }
      break;

    default:
      throw new \Exception("Unknown class '$class'");
      break;
  }

  return $field;
}


function get_fieldset_password($id=null, $disabled=false)
{
  $fieldset = new ElementFieldset();
  $fieldset->addLegend(get_loc_field_name(User::TABLE_NAME, 'password'));

  // If this is an existing user then give them the message about optionally
  // changing their password.
  if (isset($id))
  {
    $p = new ElementP();
    $p->setText(get_vocab('password_twice'));
    $fieldset->addElement($p);
  }

  for ($i=0; $i<2; $i++)
  {
    $field = new FieldInputPassword();
    $field->setLabel(get_loc_field_name(User::TABLE_NAME, 'password'))
          ->setControlAttributes(array('id'   => "password$i",
                                       'name' => "password$i",
                                       'disabled' => $disabled,
                                       'autocomplete' => 'new-password'));
    // No need to add a hidden input if the password is disabled because
    // we don't put the password in the form anyway.
    $fieldset->addElement($field);
  }

  return $fieldset;
}


// Adds the submit buttons.
//    $delete               If true, make the second button a Delete button instead of a Back button
//    $disabled             If true, disable the Delete button
//    $last_admin_warning   If true, add a warning about editing the last admin
function get_fieldset_submit_buttons($delete=false, $disabled=false, $last_admin_warning=false)
{
  $fieldset = new ElementFieldset();

  if ($last_admin_warning)
  {
    $p = new ElementP();
    $p->setText(get_vocab('warning_last_admin'));
    $fieldset->addElement($p);
  }

  $field = new FieldInputSubmit();

  $button = new ElementInputSubmit();

  if ($delete)
  {
    $name = 'delete_button';
    $value = get_vocab('delete_user');
  }
  else
  {
    $name = 'back_button';
    $value = get_vocab('back');
  }
  $button->setAttributes(array('name'           => $name,
                               'value'          => $value,
                               'disabled'       => $disabled,
                               'formnovalidate' => true));

  $field->setAttribute('class', 'submit_buttons')
        ->setLabelAttribute('class', 'no_suffix')
        ->addLabelElement($button)
        ->setControlAttributes(array('class' => 'default_action',
                                     'name'  => 'update_button',
                                     'value' => get_vocab('save')));

  // Remove the 'for' attribute which will automatically have been set by
  // setControlAttributes().  It is unnecessary and leaving it in results in
  // an HTML5 validation error.
  $field->removeLabelAttribute('for');

  $fieldset->addElement($field);

  return $fieldset;
}


function get_form()
{
  $params = array();

  // Get the special parameters which don't have a corresponding column
  $params['password0'] = get_form_var('password0', 'string', null, INPUT_POST);
  $params['password1'] = get_form_var('password1', 'string', null, INPUT_POST);
  $params['roles'] = get_form_var('roles', 'array');

  // Clean up the roles
  $params['roles'] = array_map('intval', $params['roles']);

  // Get all the others
  $columns = new Columns(_tbl(User::TABLE_NAME));

  foreach ($columns as $column)
  {
    $name = $column->name;

    $ignore = array('password_hash', 'timestamp', 'last_login', 'reset_key_hash', 'reset_key_expiry', 'auth_type');

    if (in_array($name, $ignore))
    {
      continue;
    }

    if (!array_key_exists($name, $_POST) && !(array_key_exists($name, $_GET)))
    {
      continue;
    }

    // "Level" is an exception because we've forced the value to be a string
    // so that it can be used in an associative array.
    // TODO: revisit why level has to be a string.
    $var_type = ($name == 'level') ? 'string' : $column->getFormVarType();

    $params[$name] = get_form_var($name, $var_type);

    // Don't sanitize level because it's a smallint and will thus be treated as
    // a boolean.
    // TODO: implement proper boolean data types in the database tables.
    if ($name == 'level')
    {
      $params[$name] = trim($params[$name]);
    }
    else
    {
      $params[$name] = $column->sanitizeFormVar($params[$name]);
    }
  }

  return $params;
}


function validate_form(array $form)
{
  global $auth, $level;

  $errors = array();

  // NAME
  // Check that the name is not empty.
  if ($form['name'] === '')
  {
    $errors['name_empty'] = 1;
  }

  if ($auth['type'] == 'db')
  {
    // EMAIL ADDRESS
    // check that the email address is valid
    if (isset($form['email']) &&
      ($form['email'] !== '') &&
      !validate_email_list($form['email']))
    {
      $errors['invalid_email'] = 1;
    }

    // LEVEL
    // Check that we are not trying to upgrade our level.    This shouldn't be
    // possible but someone might have spoofed the input in the edit form
    if (isset($form['level']) && ($form['level'] > $level))
    {
      $message = "Attempt to edit or create a user with a higher level than the current user's.";
      throw new \Exception($message);
    }

    // Check that the name is unique.
    // If there's already a user with this name then it can only be this user.
    $user = User::getByName($form['name'], $auth['type']);
    if (isset($user) && (!isset($form['id']) || ($form['id'] != $user->id)))
    {
      $errors['name_not_unique'] = 1;
      $errors['taken_name'] = $form['name'];
    }

    // PASSWORD
    // Check that the two passwords match
    if ($form['password0'] !== $form['password1'])
    {
      $errors['pwd_not_match'] = 1;
    }
    // Check that the password conforms to the password policy
    // if it's a new user, or else if it's an existing user
    // trying to change their password
    if (!isset($form['id']) ||
      (isset($form['password0']) && ($form['password0'] !== '')))
    {
      if (!auth()->validatePassword($form['password0']))
      {
        $errors['pwd_invalid'] = 1;
      }
    }
  }

  return $errors;
}


// Adds/updates the user specified by the $form parameters.
// $form is assumed to have been validated already.
function update_user(array $form)
{
  global $auth, $initial_user_creation;

  if (isset($form['id']))
  {
    $user = User::getById($form['id']);
    if (!isset($user))
    {
      // Probably because someone has deleted the user in the meantime
      trigger_error("Could not find user with id " . $form['id']);
    }
  }

  // If it's a new user, or if for some reason the getById() failed, then
  // create a new one.
  if (!isset($user))
  {
    $user = new User();
    $user->auth_type = $auth['type'];
  }

  foreach ($form as $key => $value)
  {
    // Stop ordinary users trying to change fields they are not allowed to
    if (($auth['type'] == 'db') &&
        !$initial_user_creation &&
        !is_user_admin() &&
        in_array($key, $auth['db']['protected_fields']))
    {
      continue;
    }

    // Some of the fields get special treatment
    switch ($key)
    {
      case 'level':
        $user->level = (isset($value)) ? $value : 0;
        break;
      case 'name':
        // Convert the name to lowercase for the 'db' scheme, otherwise respect the case.
        $user->name = ($auth['type'] == 'db') ? utf8_strtolower($value) : $value;
        break;
      case 'password0':
        // If the password field is blank then we are not changing it
        if (isset($value) && ($value !== ''))
        {
          $user->password_hash = password_hash($value, PASSWORD_DEFAULT);
        }
        break;
      default:
        $user->{$key} = $value;
        break;
    }
  }

  // TODO: do something more elegant
  if ($auth['type'] != 'db')
  {
    $user->level = 0;
  }

  $user->save();
}

// Set up for Ajax.   We need to know whether we're capable of dealing with Ajax
// requests, which will only be if the browser is using DataTables.    We also need
// to initialise the JSON data array.
$ajax_capable = $datatable;

if ($is_ajax)
{
  $json_data['aaData'] = array();
}

// Get the information about the fields in the user table
$fields = db()->field_info(_tbl(User::TABLE_NAME));

$users = new Users();


/*---------------------------------------------------------------------------*\
|                         Authenticate the current user                         |
\*---------------------------------------------------------------------------*/

// Check the CSRF token if we're going to be altering the database
if (isset($action) && in_array($action, array('delete', 'update')))
{
  Form::checkToken();
}

$initial_user_creation = false;

if (($auth['type'] != 'db') || (count($users) > 0))
{
  $mrbs_user = session()->getCurrentUser();
  $level = (isset($mrbs_user)) ? $mrbs_user->level : 0;
  // Check the user is authorised for this page
  checkAuthorised(this_page());
}
else
// We've just installed MRBS.   Assume the person doing this IS an administrator
// and then send them through to the screen to add the first user (which we'll force
// to be an admin)
{
  $initial_user_creation = true;
  if (!isset($action))   // second time through it will be set to "update"
  {
    $action = "add";
    $id = null;
  }
  $level = $max_level;
}


/*---------------------------------------------------------------------------*\
|             Edit a given entry - 1st phase: Get the user input.             |
\*---------------------------------------------------------------------------*/

if (isset($action) && ( ($action == 'edit') or ($action == 'add') ))
{

  if (isset($id))
  {
    // If it's an existing user then get the user from the database
    $user = User::getById($id);
    if (!isset($user))
    {
      trigger_error("Invalid user id $id", E_USER_NOTICE);
      location_header(this_page());
    }
  }

  if (!isset($id) || (!$user))
  {
    // Otherwise try and construct the user from the query string.
    // (The data will be in the query string if there was an error on
    // validating the data after it had been submitted.   We want to
    // preserve the user's original values so that they don't have to
    // re-type them).
    $user = new User();
    foreach ($fields as $field)
    {
      if ($field['name'] == 'auth_db')
      {
        continue;
      }
      $type = get_form_var_type($field);
      $value = get_form_var($field['name'], $type);
      $user->{$field['name']} = (isset($value)) ? $value : '';
    }
    $user->username = $user->name;
  }

  // First make sure the user is authorized
  if (!$initial_user_creation && !can_edit_user($user->username))
  {
    showAccessDenied();
    exit();
  }

  $context = array(
      'view'      => $view,
      'view_all'  => $view_all,
      'year'      => $year,
      'month'     => $month,
      'day'       => $day,
      'area'      => isset($area) ? $area : null,
      'room'      => isset($room) ? $room : null
    );

  print_header($context);

  echo "<h2>";
  if ($initial_user_creation)
  {
    echo get_vocab('no_users_initial');
  }
  else
  {
    echo ($action == 'edit') ? get_vocab('edit_user') : get_vocab('add_new_user');
  }
  echo "</h2>\n";

  if ($initial_user_creation)
  {
    echo "<p>" . get_vocab('no_users_create_first_admin') . "</p>\n";
  }

  // Find out how many admins are left in the table - it's disastrous if the last one is deleted,
  // or admin rights are removed!
  if ($action == "edit")
  {
    $editing_last_admin = (UserDb::getNAdmins() <= 1) && ($user->level == $max_level);
  }
  else
  {
    $editing_last_admin = false;
  }

  // Error messages
  if (!empty($invalid_email))
  {
    echo "<p class=\"error\">" . get_vocab('invalid_email') . "</p>\n";
  }
  if (!empty($name_not_unique))
  {
    echo "<p class=\"error\">'" . htmlspecialchars($taken_name) . "' " . get_vocab('name_not_unique') . "<p>\n";
  }
  if (!empty($name_empty))
  {
    echo "<p class=\"error\">" . get_vocab('name_empty') . "<p>\n";
  }

  // Now do any password error messages
  if (!empty($pwd_not_match))
  {
    echo "<p class=\"error\">" . get_vocab("passwords_not_eq") . "</p>\n";
  }
  if (!empty($pwd_invalid))
  {
    echo "<p class=\"error\">" . get_vocab("password_invalid") . "</p>\n";
    if (isset($pwd_policy))
    {
      echo "<ul class=\"error\">\n";
      foreach ($pwd_policy as $rule => $value)
      {
        if ($value != 0)
        {
          echo "<li>" . get_vocab('policy_' . $rule, $value) . "</li>\n";
        }
      }
      echo "</ul>\n";
    }
  }
  // TODO: rewrite all of this
  $form = new Form();

  $form->setAttributes(array('id'     => 'form_edit_user',
                             'class'  => 'standard',
                             'method' => 'post',
                             'action' => multisite(this_page())));

  if (isset($id))
  {
    $form->addHiddenInput('id', $id);
  }

  $fieldset = new ElementFieldset();
  $fieldset->addLegend(get_vocab('general_settings'));

  foreach ($fields as $field)
  {
    $key = $field['name'];

    if (($auth['type'] != 'db') && !in_array($key, array('name', 'display_name')))
    {
      continue;
    }

    $params = array('label' => get_loc_field_name(_tbl(User::TABLE_NAME), $key),
                    'name'  => $key,
                    'value' => $user->{$key});

    $disabled = ($auth['type'] != 'db') ||
                (!$initial_user_creation &&
                 !is_user_admin() &&
                 in_array($key, $auth['db']['protected_fields']));

    switch ($key)
    {
      // We've already got this in a hidden input
      case 'id':
      // We don't want to do anything with these
      case 'auth_type':
      case 'password_hash':
      case 'timestamp':
      case 'last_login':
      case 'reset_key_hash':
      case 'reset_key_expiry':
        break;

      case 'level':
        if ($action == 'add')
        {
          // If we're creating a new user and it's the very first user, then they
          // should have maximum rights.  Otherwise make them an ordinary user.
          $params['value'] = ($initial_user_creation) ? $max_level : 1;
        }
        // Work out whether the level select input should be disabled (NB you can't make a <select> readonly)
        // We don't want the user to be able to change the level if (a) it's the first user being created or
        // (b) it's the last admin left or (c) they don't have admin rights
        $level_disabled = $initial_user_creation || $editing_last_admin || $disabled;
        $fieldset->addElement(get_field_level($params, $level_disabled));
        // Add a hidden input if the field is disabled
        if ($level_disabled)
        {
          $form->addHiddenInput($params['name'], $params['value']);
        }
        break;

      case 'name':
        $fieldset->addElement(get_field_name($params, $disabled));
        break;

      case 'display_name':
        $fieldset->addElement(get_field_display_name($params, $disabled));
        break;

      case 'email':
        $fieldset->addElement(get_field_email($params, $disabled));
        break;

      default:
        $params['field'] = "user.$key";
        $fieldset->addElement(get_field_custom($field, $params, $disabled));
        break;

    }
  }

  $form->addElement($fieldset);

  // Add in the roles
  $form->addElement(get_fieldset_roles($user));

  if ($auth['type'] == 'db')
  {
    // Now the password fields
    $disabled = !$initial_user_creation &&
      !is_user_admin() &&
      in_array('password_hash', $auth['db']['protected_fields']);


    $form->addElement(get_fieldset_password($id, $disabled));
  }

  // Administrators get the right to delete users, but only those at the
  // the same level as them or lower.  Otherwise present a Back button.
  $delete = isset($id) &&
            is_user_admin() &&
            ($level >= $user->level);

  // Don't let the last admin be deleted, otherwise you'll be locked out.
  $button_disabled = $delete && $editing_last_admin;

  $form->addElement(get_fieldset_submit_buttons($delete, $button_disabled, $editing_last_admin));

  $form->render();

  print_effective_permissions($user);

  // Print footer and exit
  print_footer();
  exit;
}


/*---------------------------------------------------------------------------*\
|             Sync users                                                      |
\*---------------------------------------------------------------------------*/

if (isset($action) && ($action == "sync"))
{
  $users->sync();
}


/*---------------------------------------------------------------------------*\
|             Edit a given entry - 2nd phase: Update the database.            |
\*---------------------------------------------------------------------------*/

if (isset($action) && ($action == "update"))
{
  $returl = this_page();

  // If you haven't got the rights to do this, then exit
  // You are only allowed to do this if (a) you're creating the first user or
  // (b) you are a user admin or (c) you are editing your own details
  if (!$initial_user_creation &&
      !is_user_admin() &&
      (!isset($mrbs_user) || ($id != $mrbs_user->id )))
  {
    // It shouldn't normally be possible to get here.
    trigger_error("Attempt made to update a user without sufficient rights.", E_USER_NOTICE);
    location_header(this_page());
  }

  // otherwise go ahead and update the database
  $form_params = get_form();
  $errors = validate_form($form_params);
  if (empty($errors))
  {
    update_user($form_params);
  }
  else
  {
    $query_string_parts = $errors;
    $query_string_parts['action'] = (isset($form_params['id'])) ? 'edit' : 'add';
    // Add the form parameters to the query string so that the user doesn't have to
    // retype them.  (We could use session variables, but we can't assume the use of
    // sessions.)
    foreach ($form_params as $key => $value)
    {
      if (!in_array($key, array('password0', 'password1')))
      {
        $query_string_parts[$key] = $value;
      }
    }
  }
  if (!empty($query_string_parts))
  {
    $returl .= '?' . http_build_query($query_string_parts, '', '&');
  }
  location_header($returl);
}

/*---------------------------------------------------------------------------*\
|                                Delete a user                                |
\*---------------------------------------------------------------------------*/

if (isset($action) && ($action == "delete"))
{
  $target_user = User::getById($id);

  if (!isset($target_user))
  {
    fatal_error("Fatal error while deleting a user");
  }
  // You can't delete a user if you're not some kind of admin, and then you can't
  // delete someone higher than you
  if (!is_user_admin() || (isset($target_user->level) && ($level < $target_user->level)))
  {
    showAccessDenied();
    exit();
  }

  $target_user->delete();
  // Success. Do not display a message. Simply fall through into the list display.
}

/*---------------------------------------------------------------------------*\
|                          Display the list of users                          |
\*---------------------------------------------------------------------------*/

/* Print the standard MRBS header */

if (!$is_ajax)
{
  $context = array(
    'view'      => $view,
    'view_all'  => $view_all,
    'year'      => $year,
    'month'     => $month,
    'day'       => $day,
    'area'      => isset($area) ? $area : null,
    'room'      => isset($room) ? $room : null
  );

  print_header($context);

  echo "<h2>" . get_vocab("user_list") . "</h2>\n";

  if (is_user_admin()) /* Administrators get the right to add new users */
  {
    // Add button for the 'db' auth type or where we can't get all the users
    if (($auth['type'] == 'db') || !method_exists(auth(), 'getUsers'))
    {
      $form = new Form();

      $form->setAttributes(array('id' => 'add_new_user',
        'method' => 'post',
        'action' => multisite(this_page())));

      $form->addHiddenInput('action', 'add');

      $submit = new ElementInputSubmit();
      $submit->setAttribute('value', get_vocab('add_new_user'));
      $form->addElement($submit);
      $form->render();
    }
    // Sync button otherwise
    else
    {
      $form = new Form();
      $form->setAttributes(array('id' => 'sync',
                                 'method' => 'post',
                                 'action' => multisite(this_page())));

      $form->addHiddenInput('action', 'sync');

      $submit = new ElementInputSubmit();
      $submit->setAttribute('value', get_vocab('sync'));
      $form->addElement($submit);
      $form->render();
    }

  }
}

if (!$initial_user_creation)   // don't print the user table if there are no users
{
  // Display the user data in a table

  // We don't display these columns or they get special treatment
  $ignore_columns = array(
      'id',
      'auth_type',
      'password_hash',
      'name',
      'display_name',
      'email',
      'reset_key_hash',
      'reset_key_expiry'
    );

  if (!$is_ajax)
  {
    echo "<div id=\"user_list\" class=\"datatable_container\">\n";
    echo "<table class=\"admin_table display\" id=\"users_table\">\n";

    // The table header
    echo "<thead>\n";
    echo "<tr>";

    // First three columns which are the name, display name, email address and roles
    echo '<th><span class="normal" data-type="title-string">' . get_vocab("user.display_name") . "</span></th>\n";
    echo '<th><span class="normal" data-type="title-string">' . get_vocab("user.name") . "</span></th>\n";
    echo '<th id="col_email">' . get_vocab("user.email") . "</th>\n";
    echo '<th><span class="normal" data-type="title-string">' . get_vocab("groups") . "</span></th>\n";
    echo '<th><span class="normal" data-type="title-string">' . get_vocab("roles") . "</span></th>\n";

    // Other column headers
    if ($auth['type'] == 'db')
    {
      foreach ($fields as $field)
      {
        $fieldname = $field['name'];

        if (!in_array($fieldname, $ignore_columns))
        {
          $heading = get_loc_field_name(_tbl(User::TABLE_NAME), $fieldname);
          // We give some columns a type data value so that the JavaScript knows how to sort them
          switch ($fieldname)
          {
            case 'level':
            case 'timestamp':
            case 'last_login':
              $heading = '<span class="normal" data-type="title-numeric">' . $heading . '</span>';
              break;
            default:
              break;
          }
          echo '<th id="col_' . htmlspecialchars($fieldname) . "\">$heading</th>";
        }
      }
    }

    echo "</tr>\n";
    echo "</thead>\n";

    // The table body
    echo "<tbody>\n";
  }

  // If we're Ajax capable and this is not an Ajax request then don't output
  // the table body, because that's going to be sent later in response to
  // an Ajax request
  if (!$ajax_capable || $is_ajax)
  {
    foreach ($users as $user)
    {
      if (can_view_user($user->name))
      {
        output_row($user);
      }
    }
  }

  if (!$is_ajax)
  {
    echo "</tbody>\n";

    echo "</table>\n";
    echo "</div>\n";
  }

}   // (!$initial_user_creation)

if ($is_ajax)
{
  http_headers(array("Content-Type: application/json"));
  echo json_encode($json_data);
}
else
{
  print_footer();
}
