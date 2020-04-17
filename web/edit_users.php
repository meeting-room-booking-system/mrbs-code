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
*   File name     edit_users.php                                             *
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
$password0 = get_form_var('password0', 'string', null, INPUT_POST);
$password1 = get_form_var('password1', 'string', null, INPUT_POST);
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

// Checks whether the current user can view the target user
function can_view_user($target)
{
  global $auth, $min_user_viewing_level;

  $current_user = session()->getCurrentUser();

  // You can only see this user if you are logged in and (a) we allow everybody to see all
  // users or (b) you are an admin or (c) you are this user
  if (!isset($current_user))
  {
    return false;
  }

  return (!$auth['only_admin_can_see_other_users']  ||
          ($current_user->level >= $min_user_viewing_level) ||
          (strcasecmp($current_user->username, $target) === 0));
}


// Checks whether the current user can edit the target user
function can_edit_user($target)
{
  $current_user = session()->getCurrentUser();
    
  return (is_user_admin() || (isset($current_user) && strcasecmp($current_user->username, $target) === 0));
}


// Validates that the password conforms to the password policy
// (Ideally this function should also be matched by client-side
// validation, but unfortunately JavaScript's native support for Unicode
// pattern matching is very limited.   Would need to be implemented using
// an add-in library).
function validate_password($password)
{
  global $pwd_policy;
          
  if (isset($pwd_policy))
  {
    // Set up regular expressions.  Use p{Ll} instead of [a-z] etc.
    // to make sure accented characters are included
    $pattern = array('alpha'   => '/\p{L}/',
                     'lower'   => '/\p{Ll}/',
                     'upper'   => '/\p{Lu}/',
                     'numeric' => '/\p{N}/',
                     'special' => '/[^\p{L}|\p{N}]/');
    // Check for conformance to each rule                 
    foreach($pwd_policy as $rule => $value)
    {
      switch($rule)
      {
        case 'length':
          if (utf8_strlen($password) < $pwd_policy[$rule])
          {
            return false;
          }
          break;
        default:
          // turn on Unicode matching
          $pattern[$rule] .= 'u';

          $n = preg_match_all($pattern[$rule], $password, $matches);
          if (($n === false) || ($n < $pwd_policy[$rule]))
          {
            return false;
          }
          break;
      }
    }
  }
  
  // Everything is OK
  return true;
}


// Get the type that should be used with get_form_var() for
// a field which is a member of the array returned by get_field_info()
function get_form_var_type($field)
{
  // "Level" is an exception because we've forced the value to be a string
  // so that it can be used in an associative aeeay
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


function output_row(&$row)
{
  global $is_ajax, $json_data;
  global $fields, $ignore_columns, $select_options;
  
  $values = array();
  
  // First column, which is the name
  // You can only edit a user if you have sufficient admin rights, or else if that user is yourself
  if (can_edit_user($row['name']))
  {
    $form = new Form();
    $form->setAttributes(array('method' => 'post',
                               'action' => multisite(this_page())));
    $form->addHiddenInput('id', $row['id']);
    $submit = new ElementInputSubmit();
    $submit->setAttributes(array('class' => 'link',
                                 'name'  => 'edit_button',
                                 'value' => $row['name']));
    $form->addElement($submit);
    $name_value = $form->toHTML();
  }
  else
  {
    $name_value = "<span class=\"normal\">" . htmlspecialchars($row['name']) . "</span>";
  }
  
  $values[] = '<span title="' . htmlspecialchars($row['name']) . '"></span>' . $name_value;
    
  // Other columns
  foreach ($fields as $field)
  {
    $key = $field['name'];
    if (!in_array($key, $ignore_columns))
    {
      $col_value = $row[$key];
      
      // If you are not a user admin then you are only allowed to see the last_updated
      // and last_login times for yourself.
      if (in_array($key, array('timestamp', 'last_login')) &&
          !can_edit_user($row['name']))
      {
        $col_value = null;
      }
            
      switch($key)
      {
        // special treatment for some fields
        case 'level':
          // the level field contains a code and we want to display a string
          // (but we put the code in a span for sorting)
          $values[] = "<span title=\"$col_value\"></span>" .
                      "<div class=\"string\">" . get_vocab("level_$col_value") . "</div>";
          break;
        case 'email':
          // we don't want to truncate the email address
          $escaped_email = htmlspecialchars($col_value);
          $values[] = "<div class=\"string\">\n" .
                      "<a href=\"mailto:$escaped_email\">$escaped_email</a>\n" .
                      "</div>\n";
          break;
        case 'timestamp':
          // Convert the SQL timestamp into a time value and back into a localised string and
          // put the UNIX timestamp in a span so that the JavaScript can sort it properly.
          $unix_timestamp = strtotime($col_value);
          if ($unix_timestamp === false)
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
          if (isset($select_options["users.$key"]) &&
              is_assoc($select_options["users.$key"]))
          {
            if (isset($select_options["users.$key"][$row[$key]]))
            {
              $col_value = $select_options["users.$key"][$row[$key]];
            }
            else
            {
              $col_value = '';
            }
            $values[] = "<div class=\"string\">" . htmlspecialchars($col_value) . "</div>";
          }
          elseif (($field['nature'] == 'boolean') || 
              (($field['nature'] == 'integer') && isset($field['length']) && ($field['length'] <= 2)) )
          {
            // booleans: represent by a checkmark
            $values[] = (!empty($col_value)) ? "<img src=\"images/check.png\" alt=\"check mark\" width=\"16\" height=\"16\">" : "&nbsp;";
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


function get_field_name($params, $disabled=false)
{
  $field = new FieldInputText();
  
  $field->setLabel($params['label'])
        ->setControlAttributes(array('name'     => $params['name'],
                                     'value'    => $params['value'],
                                     'disabled' => $disabled,
                                     'required' => true,
                                     'pattern'  => REGEX_TEXT_POS));
                                     
  if (null !== ($maxlength = maxlength('users.name')))
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
  
  if (null !== ($maxlength = maxlength('users.email')))
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


function get_fieldset_password($id=null)
{
  $fieldset = new ElementFieldset();
  
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
    $field->setLabel(get_vocab('users.password'))
          ->setControlAttributes(array('id'   => "password$i",
                                       'name' => "password$i",
                                       'autocomplete' => 'new-password'));
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
  
  $field->setLabelAttribute('class', 'no_suffix')
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


// Set up for Ajax.   We need to know whether we're capable of dealing with Ajax
// requests, which will only be if the browser is using DataTables.    We also need
// to initialise the JSON data array.
$ajax_capable = $datatable;

if ($is_ajax)
{
  $json_data['aaData'] = array();
}

// Get the information about the fields in the users table
$fields = db()->field_info($tbl_users);

$users = auth()->getUsers();


/*---------------------------------------------------------------------------*\
|                         Authenticate the current user                         |
\*---------------------------------------------------------------------------*/

// Check the CSRF token if we're going to be altering the database
if (isset($action) && in_array($action, array('delete', 'update')))
{
  Form::checkToken();
}

$initial_user_creation = false;

if (count($users) > 0)
{
  $current_user = session()->getCurrentUser();
  $level = (isset($current_user)) ? $current_user->level : 0;
  // Check the user is authorised for this page
  checkAuthorised(this_page());
}
else 
// We've just created the table.   Assume the person doing this IS an administrator
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

if (isset($action) && ( ($action == "edit") or ($action == "add") ))
{
  
  if (isset($id))
  {
    // If it's an existing user then get the data from the database
    $result = db()->query("SELECT * FROM $tbl_users WHERE id=?", array($id));
    $data = $result->next_row_keyed();
    unset($result);
    // Check that we've got a valid result.   We should do normally, but if somebody alters
    // the id parameter in the query string then we won't.   If the result is invalid, go somewhere
    // safe.
    if (!$data)
    {
      trigger_error("Invalid user id $id", E_USER_NOTICE);
      location_header(this_page());
    }
  }
  if (!isset($id) || (!$data))
  {
    // Otherwise try and get the data from the query string, and if it's
    // not there set the default to be blank.  (The data will be in the 
    // query string if there was an error on validating the data after it
    // had been submitted.   We want to preserve the user's original values
    // so that they don't have to re-type them).
    foreach ($fields as $field)
    {
      $type = get_form_var_type($field);
      $value = get_form_var($field['name'], $type);
      $data[$field['name']] = (isset($value)) ? $value : "";
    }
  }

  // First make sure the user is authorized
  if (!$initial_user_creation && !can_edit_user($data['name']))
  {
    showAccessDenied();
    exit();
  }
  
  print_header($view, $view_all, $year, $month, $day, isset($area) ? $area : null, isset($room) ? $room : null);
  
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
    $n_admins = db()->query1("SELECT COUNT(*) FROM $tbl_users WHERE level=?", array($max_level));
    $editing_last_admin = ($n_admins <= 1) && ($data['level'] == $max_level);
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
        echo "<li>$value " . get_vocab("policy_" . $rule) . "</li>\n";
      }
      echo "</ul>\n";
    }
  }
  
  $form = new Form();
  
  $form->setAttributes(array('id'     => 'form_edit_users',
                             'class'  => 'standard',
                             'method' => 'post',
                             'action' => multisite(this_page())));
  
  if (isset($id))
  {    
    $form->addHiddenInput('id', $id);
  }
                             
  $fieldset = new ElementFieldset();
  
  foreach ($fields as $field)
  {
    $key = $field['name'];
    
    $params = array('label' => get_loc_field_name($tbl_users, $key),
                    'name'  => VAR_PREFIX . $key,
                    'value' => $data[$key]);
    
    $disabled = !$initial_user_creation &&
                !is_user_admin() &&
                in_array($key, $auth['db']['protected_fields']);
    
    switch ($key)
    {
      case 'id':            // We've already got this in a hidden input
      case 'password_hash': // We don't want to do anything with this
      case 'timestamp':     // Nor this
      case 'last_login':
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
        
      case 'email':
        $fieldset->addElement(get_field_email($params, $disabled));
        break;
        
      default:
        $params['field'] = "users.$key";
        $fieldset->addElement(get_field_custom($field, $params, $disabled));
        break;
        
    }
  }
  
  $form->addElement($fieldset)
       ->addElement(get_fieldset_password($id));
       
  // Administrators get the right to delete users, but only those at the
  // the same level as them or lower.  Otherwise present a Back button.
  $delete = isset($id) &&
            is_user_admin() &&
            ($level >= $data['level']);
  
  // Don't let the last admin be deleted, otherwise you'll be locked out.
  $button_disabled = $delete && $editing_last_admin;
  
  $form->addElement(get_fieldset_submit_buttons($delete, $button_disabled, $editing_last_admin));
  
  $form->render();
  
  // Print footer and exit
  print_footer();
  exit;
}

/*---------------------------------------------------------------------------*\
|             Edit a given entry - 2nd phase: Update the database.            |
\*---------------------------------------------------------------------------*/

if (isset($action) && ($action == "update"))
{
  // If you haven't got the rights to do this, then exit
  if (isset($current_user))
  {
    $my_id = db()->query1("SELECT id FROM $tbl_users WHERE name=? LIMIT 1",
                          array(utf8_strtolower($current_user->level)));
  }
  else
  {
    $my_id = null;
  }
  
  // You are only alowed to do this if (a) you're creating the first user or
  // (b) you are a user admin or (c) you are editing your own details
  if (!$initial_user_creation &&
      !is_user_admin() && 
      (!isset($my_id) || ($id != $my_id )))
  {
    // It shouldn't normally be possible to get here.
    trigger_error("Attempt made to update a user without sufficient rights.", E_USER_NOTICE);
    location_header('edit_users.php');
  }
  
  // otherwise go ahead and update the database
  $values = array();
  $q_string = (isset($id)) ? "action=edit" : "action=add";
  foreach ($fields as $index => $field)
  {
    $fieldname = $field['name'];
    $type = get_form_var_type($field);
      
    if ($fieldname == 'id')
    {
      // id: don't need to do anything except add the id to the query string;
      // the field itself is auto-incremented
      if (isset($id))
      {
        $q_string .= "&id=$id";
      }
      continue; 
    }

    // first, get all the other form variables, except for password_hash which is
    // a special case,and put them into an array, $values, which  we will use
    // for entering into the database assuming we pass validation
    if ($fieldname !== 'password_hash')
    {
      $values[$fieldname] = get_form_var(VAR_PREFIX. $fieldname, $type);
      // Turn checkboxes into booleans
      if (($fieldname !== 'level') &&
          ($field['nature'] == 'integer') &&
          isset($field['length']) &&
          ($field['length'] <= 2))
      {
        $values[$fieldname] = (empty($values[$fieldname])) ? 0 : 1;
      }
      // Trim the field to remove accidental whitespace
      $values[$fieldname] = trim($values[$fieldname]);
      // Truncate the field to the maximum length as a precaution.
      if (null !== ($maxlength = maxlength("users.$fieldname")))
      {
        $values[$fieldname] = utf8_substr($values[$fieldname], 0, $maxlength);
      }
    }
    
    // we will also put the data into a query string which we will use for passing
    // back to this page if we fail validation.   This will enable us to reload the
    // form with the original data so that the user doesn't have to
    // re-enter it.  (Instead of passing the data in a query string we
    // could pass them as session variables, but at the moment MRBS does
    // not rely on PHP sessions).
    switch ($fieldname)
    {
      // some of the fields get special treatment
      case 'name':
        // name: convert it to lower case
        $q_string .= "&$fieldname=" . $values[$fieldname];
        $values[$fieldname] = utf8_strtolower($values[$fieldname]);
        break;
      case 'password_hash':
        // password: if the password field is blank it means
        // that the user doesn't want to change the password
        // so don't do anything; otherwise calculate the hash.
        // Note: we don't put the password in the query string
        // for security reasons.
        if ($password0 !== '')
        {
          $values[$fieldname] = password_hash($password0, PASSWORD_DEFAULT);
        }
        break;
      case 'level':
        // level:  set a safe default (lowest level of access)
        // if there is no value set
        $q_string .= "&$fieldname=" . $values[$fieldname];
        if (!isset($values[$fieldname]))
        {
          $values[$fieldname] = 0;
        }
        // Check that we are not trying to upgrade our level.    This shouldn't be possible
        // but someone might have spoofed the input in the edit form
        if ($values[$fieldname] > $level)
        {
          location_header('edit_users.php');
        }
        break;
      case 'timestamp':
      case 'last_login':
        // Don't update this field ourselves at all
        unset($fields[$index]);
        unset($values[$fieldname]);
        break;
      default:
        $q_string .= "&$fieldname=" . $values[$fieldname];
        break;
    }
  }

  // Now do some form validation
  $valid_data = true;
  foreach ($values as $fieldname => $value)
  {
    switch ($fieldname)
    {
      case 'name':
        // check that the name is not empty
        if ($value === '')
        {
          $valid_data = false;
          $q_string .= "&name_empty=1";
        }

        $sql_params = array();

        // Check that the name is unique.
        // If it's a new user, then to check to see if there are any rows with that name.
        // If it's an update, then check to see if there are any rows with that name, except
        // for that user.
        $query = "SELECT id FROM $tbl_users WHERE name=?";
        $sql_params[] = $value;
        if (isset($id))
        {
          $query .= " AND id != ?";
          $sql_params[] = $id;
        }
        $query .= " LIMIT 1";  // we only want to know if there is at least one instance of the name
        $result = db()->query($query, $sql_params);
        if ($result->count() > 0)
        {
          $valid_data = false;
          $q_string .= "&name_not_unique=1";
          $q_string .= "&taken_name=$value";
        }
        break;
      case 'password_hash':
        // check that the two passwords match
        if ($password0 != $password1)
        {
          $valid_data = false;
          $q_string .= "&pwd_not_match=1";
        }
        // check that the password conforms to the password policy
        // if it's a new user, or else if it's an existing user
        // trying to change their password
        if (!isset($id) || ($password0 !== ''))
        {
          if (!validate_password($password0))
          {
            $valid_data = false;
            $q_string .= "&pwd_invalid=1";
          }
        }
        break;
      case 'email':
        // check that the email address is valid
        if (isset($value) && ($value !== '') && !validate_email_list($value))
        {
          $valid_data = false;
          $q_string .= "&invalid_email=1";
        }
        break;
    }
  }

  // if validation failed, go back to this page with the query 
  // string, which by now has both the error codes and the original
  // form values 
  if (!$valid_data)
  { 
    location_header("edit_users.php?$q_string");
  }

  
  // If we got here, then we've passed validation and we need to
  // enter the data into the database

  $sql_params = array();
  $sql_fields = array();

  // For each db column get the value ready for the database
  foreach ($fields as $field)
  {
    $fieldname = $field['name'];;
    
    // Stop ordinary users trying to change fields they are not allowed to
    if (!$initial_user_creation &&
        !is_user_admin() &&
        in_array($fieldname, $auth['db']['protected_fields']))
    {
      continue;
    }
    
    // If the password field is blank then we are not changing it
    if (($fieldname == 'password_hash') && (!isset($values[$fieldname])))
    {
      continue;
    }
    
    if ($fieldname != 'id')
    {
      // pre-process the field value for SQL
      $value = $values[$fieldname];
      switch ($field['nature'])
      {
        case 'integer':
          if (!isset($value) || ($value === ''))
          {
            // Try and set it to NULL when we can because there will be cases when we
            // want to distinguish between NULL and 0 - especially when the field
            // is a genuine integer.
            $value = ($field['is_nullable']) ? null : 0;
          }
          break;
        default:
          // No special handling
          break;
      }
     
      /* If we got here, we have a valid, sql-ified value for this field,
       * so save it for later */
      $sql_fields[$fieldname] = $value;
    }                   
  } /* end for each column of user database */

  /* Now generate the SQL operation based on the given array of fields */
  if (isset($id))
  {
    /* if the id exists - then we are editing an existing user, rather than
     * creating a new one */

    $assign_array = array();
    $operation = "UPDATE $tbl_users SET ";

    foreach ($sql_fields as $fieldname => $value)
    {
      array_push($assign_array, db()->quote($fieldname) . "=?");
      $sql_params[] = $value;
    }
    $operation .= implode(",", $assign_array) . " WHERE id=?";
    $sql_params[] = $id;
  }
  else
  {
    /* The id field doesn't exist, so we're adding a new user */

    $fields_list = array();
    $values_list = array();

    foreach ($sql_fields as $fieldname => $value)
    {
      array_push($fields_list,$fieldname);
      array_push($values_list,'?');
      $sql_params[] = $value;
    }

    foreach ($fields_list as &$field)
    {
      $field = db()->quote($field);
    }
    $operation = "INSERT INTO $tbl_users " .
      "(". implode(",", $fields_list) . ")" .
      " VALUES " . "(" . implode(",", $values_list) . ")";
  }

  /* DEBUG lines - check the actual sql statement going into the db */
  //echo "Final SQL string: <code>" . htmlspecialchars($operation) . "</code>";
  //exit;
  db()->command($operation, $sql_params);

  /* Success. Redirect to the user list, to remove the form args */
  location_header('edit_users.php');
}

/*---------------------------------------------------------------------------*\
|                                Delete a user                                |
\*---------------------------------------------------------------------------*/

if (isset($action) && ($action == "delete"))
{
  $target_level = db()->query1("SELECT level FROM $tbl_users WHERE id=? LIMIT 1", array($id));
  if ($target_level < 0)
  {
    fatal_error("Fatal error while deleting a user");
  }
  // you can't delete a user if you're not some kind of admin, and then you can't
  // delete someone higher than you
  if (!is_user_admin() || ($level < $target_level))
  {
    showAccessDenied();
    exit();
  }

  db()->command("DELETE FROM $tbl_users WHERE id=?", array($id));

  /* Success. Do not display a message. Simply fall through into the list display. */
}

/*---------------------------------------------------------------------------*\
|                          Display the list of users                          |
\*---------------------------------------------------------------------------*/

/* Print the standard MRBS header */

if (!$is_ajax)
{
  print_header($view, $view_all, $year, $month, $day, isset($area) ? $area : null, isset($room) ? $room : null);

  echo "<h2>" . get_vocab("user_list") . "</h2>\n";

  if (is_user_admin()) /* Administrators get the right to add new users */
  {
    $form = new Form();
    
    $form->setAttributes(array('id'     => 'add_new_user',
                               'method' => 'post',
                               'action' => multisite(this_page())));
                               
    $form->addHiddenInput('action', 'add');
                                 
    $submit = new ElementInputSubmit();
    $submit->setAttribute('value', get_vocab('add_new_user'));
    $form->addElement($submit);
    
    $form->render();
  }
}

if ($initial_user_creation != 1)   // don't print the user table if there are no users
{
  // Display the user data in a table
  
  // We don't display these columns or they get special treatment
  $ignore_columns = array('id', 'password_hash', 'name'); 
  
  if (!$is_ajax)
  {
    echo "<div id=\"user_list\" class=\"datatable_container\">\n";
    echo "<table class=\"admin_table display\" id=\"users_table\">\n";
  
    // The table header
    echo "<thead>\n";
    echo "<tr>";
  
    // First column which is the name
    echo '<th><span class="normal" data-type="title-string">' . get_vocab("users.name") . "</th>\n";
  
    // Other column headers
    foreach ($fields as $field)
    {
      $fieldname = $field['name'];
    
      if (!in_array($fieldname, $ignore_columns))
      {
        $heading = get_loc_field_name($tbl_users, $fieldname);
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
        echo "<th>$heading</th>";
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
      if (can_view_user($user['name']))
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
  
}   // ($initial_user_creation != 1)

if ($is_ajax)
{
  http_headers(array("Content-Type: application/json"));
  echo json_encode($json_data);
}
else
{
  print_footer();
}
