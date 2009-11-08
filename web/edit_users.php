<?php
/*****************************************************************************\
*                                                                            *
*   File name     edit_users.php                                             *
*                                                                            *
*   Description   Edit the user database                                     *
*                                                                            *
*   Notes         Automatically creates the database if it's not present.    *
*                                                                            *
*                 Designed to be easily extensible:                          *
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
*                          parameters curreently in the config file are      *
*                          made editable from MRBS)                          *
*                                                                            *
*                 Only admins with at least user editing rights (level >=    *
*                 $min_user_editing_level) can edit other users, and they    *
*                 cannot edit users with a higher level than themselves      *
*                                                                            *
*                 To do:                                                     *
*                     - Localisability                                       *
*                                                                            *
*   History                                                                  *
*                 2003/12/29 JFL Created this file                           *
*                                                                            *
\*****************************************************************************/

// $Id$

require_once "defaultincludes.inc";

// Get form variables
$day = get_form_var('day', 'int');
$month = get_form_var('month', 'int');
$year = get_form_var('year', 'int');
$area = get_form_var('area', 'int');
$Action = get_form_var('Action', 'string');
$Id = get_form_var('Id', 'int');
$password0 = get_form_var('password0', 'string');
$password1 = get_form_var('password1', 'string');
$invalid_email = get_form_var('invalid_email', 'int');
$name_empty = get_form_var('name_empty', 'int');
$name_not_unique = get_form_var('name_not_unique', 'int');
$taken_name = get_form_var('taken_name', 'string');
$pwd_not_match = get_form_var('pwd_not_match', 'string');

$fields = array();
$field_props = array();

/* Get the list of fields actually in the table. (Allows the addition of new fields later on) */
function get_fields()
{
  global $tbl_users;
  global $fields, $field_props;
  array_splice($fields, 0);        // clear out any existing field names
  array_splice($field_props, 0);   // and properties
  $result = sql_query("select * from $tbl_users limit 1");
  $nfields = sql_num_fields($result);
  for ($i=0; $i<$nfields ;$i++)
  {
    $field_name = sql_field_name($result, $i);
    $fields[] = $field_name;
    $field_props[$field_name]['type'] = sql_field_type($result, $i);
    $field_props[$field_name]['istext'] = ($field_props[$field_name]['type'] == 'string') ? true : false;
    $field_props[$field_name]['isnum'] = preg_match('/(int|real)/',$field_props[$field_name]['type']) ? true : false;
    $field_props[$field_name]['isbool'] = ($field_props[$field_name]['type'] == 'boolean') ? true : false;
  }
  sql_free($result);
}


$nusers = sql_query1("select count(*) from $tbl_users");

// Get the list of fields in the table
get_fields();

/* Get localized field name */
function get_loc_field_name($name)
{
  global $vocab;

  // Search for indexes "user_name", "user_password", etc, in the localization array.
  if (isset($vocab["user_".$name]))
  {
    return get_vocab("user_".$name);
  }
  // If there is no entry (likely if user-defined fields have been added), return itself.
  return $name;
}

/*---------------------------------------------------------------------------*\
|                         Authenticate the current user                         |
\*---------------------------------------------------------------------------*/

$initial_user_creation = 0;

if ($nusers > 0)
{
  $user = getUserName();
  $level = authGetUserLevel($user);
  // Do not allow unidentified people to browse the list.
  if(!getAuthorised(1))
  {
    showAccessDenied($day, $month, $year, $area, "");
    exit;
  }
}
else 
// We've just created the table.   Assume the person doing this IS an administrator
// and then send them through to the screen to add the first user (which we'll force
// to be an admin)
{
  $initial_user_creation = 1;
  if (!isset($Action))   // second time through it will be set to "Update"
  {
    $Action = "Add";
    $Id = -1;
  }
  $level = $max_level;
  $user = "";           // to avoid an undefined variable notice
}

/*---------------------------------------------------------------------------*\
|             Edit a given entry - 1st phase: Get the user input.             |
\*---------------------------------------------------------------------------*/

if (isset($Action) && ( ($Action == "Edit") or ($Action == "Add") ))
{
  
  if ($Id >= 0) /* -1 for new users, or >=0 for existing ones */
  {
    // If it's an existing user then get the data from the database
    $result = sql_query("select * from $tbl_users where id=$Id");
    $data = sql_row_keyed($result, 0);
    sql_free($result);
  }
  if (($Id == -1) || (!$data))
  {
    // Otherwise try and get the data from the query string, and if it's
    // not there set the default to be blank.  (The data will be in the 
    // query string if there was an error on validating the data after it
    // had been submitted.   We want to preserve the user's original values
    // so that they don't have to re-type them).
    foreach ($fields as $fieldname)
    {
      $value = get_form_var($fieldname, $field_props[$fieldname]['type']);
      $data[$fieldname] = (isset($value)) ? $value : "";
    }
  }

  /* First make sure the user is authorized */
  if (!$initial_user_creation && !getWritable($data['name'], $user))
  {
    showAccessDenied(0, 0, 0, "", "");
    exit();
  }

  print_header(0, 0, 0, 0, "");
  
  if ($initial_user_creation == 1)
  {
    print "<h3>" . get_vocab("no_users_initial") . "</h3>\n";
    print "<p>" . get_vocab("no_users_create_first_admin") . "</p>\n";
  }
  
  print "<div id=\"form_container\">";
  print "<form id=\"form_edit_users\" method=\"post\" action=\"" . htmlspecialchars(basename($PHP_SELF)). "\">\n";
    ?>
        <fieldset class="admin">
        <legend><?php echo (($Action == "Edit") ? get_vocab("edit_user") : get_vocab("add_new_user"));?></legend>
        <div id="edit_users_input_container">
          <?php
          // Find out how many admins are left in the table - it's disastrous if the last one is deleted,
          // or admin rights are removed!
          if ($Action == "Edit")
          {
            $n_admins = sql_query1("select count(*) from $tbl_users where level=$max_level");
            $editing_last_admin = ($n_admins <= 1) && ($data['level'] == $max_level);
          }
          else
          {
            $editing_last_admin = FALSE;
          }
          
          // Work out whether the level select input should be disabled (NB you can't make a <select> readonly)
          // We don't want the user to be able to change the level if (a) it's the first user being created or
          // (b) it's the last admin left or (c) they don't have admin rights
          $disable_select = ($initial_user_creation || $editing_last_admin || ($level < $min_user_editing_level));
          
          foreach ($fields as $fieldname)
          {
            // First of all output the input for the field
            // The ID field cannot change; The password field must not be shown.
            switch($fieldname)
            {
              case 'id':
                echo "<input type=\"hidden\" name=\"Id\" value=\"$Id\">\n";
                break;
              case 'password':
                echo "<input type=\"hidden\" name=\"Field_$fieldname\" value=\"". htmlspecialchars($data[$fieldname]) . "\">\n";
                break;
              case 'level':
                echo "<div>\n";
                echo "<label for=\"Field_$fieldname\">" . get_loc_field_name($fieldname) . ":</label>\n";
                echo "<select id=\"Field_$fieldname\" name=\"Field_$fieldname\"" . ($disable_select ? " disabled=\"disabled\"" : "") . ">\n";
                // Only display options up to and including one's own level (you can't upgrade yourself).
                // If you're not some kind of admin then the select will also be disabled.
                // (Note - disabling individual options doesn't work in older browsers, eg IE6)     
                for ($i=0; $i<=$level; $i++)
                {
                  echo "<option value=\"$i\"";
                  // Work out which option should be selected by default:
                  //   if we're editing an existing entry, then it should be the current value;
                  //   if we're adding the very first entry, then it should be an admin;
                  //   if we're adding a subsequent entry, then it should be an ordinary user;
                  if ( (($Action == "Edit")  && ($i == $data[$fieldname])) ||
                       (($Action == "Add") && $initial_user_creation && ($i == $max_level)) ||
                       (($Action == "Add") && !$initial_user_creation && ($i == 1)) )
                  {
                    echo " selected=\"selected\"";
                  }
                  echo ">" . get_vocab("level_$i") . "</option>\n";
                }
                echo "</select>\n";
                // If the level select input was disabled, we still need to submit a value with 
                // the form.   <select> can't be set to 'readonly' so instead we'll use a hidden input
                if ($disable_select)
                {
                  if ($initial_user_creation)
                  {
                    $v = $max_level;
                  }
                  else
                  {
                    $v = $data[$fieldname];
                  }
                  echo "<input type=\"hidden\" name=\"Field_$fieldname\" value=\"$v\">\n";
                }
                echo "</div>\n";
                break;
              case 'name':
                // you cannot change a username (even your own) unless you have user editing rights
                $html_fieldname = htmlspecialchars("Field_$fieldname");
                echo ("<div>\n");
                echo ("<label for=\"$html_fieldname\">" . get_loc_field_name($fieldname) . ":</label>\n");
                echo ("<input id=\"$html_fieldname\" name=\"$html_fieldname\" type=\"text\" " .
                      "maxlength=\"" . $maxlength['users.name'] . "\" " .
                     (($level < $min_user_editing_level) ? "disabled=\"disabled\" " : "") .
                      "value=\"" . htmlspecialchars($data[$fieldname]) . "\">\n");
                // if the field was disabled then we still need to pass through the value as a hidden input
                if ($level < $min_user_editing_level)
                {
                  echo "<input type=\"hidden\" name=\"Field_$fieldname\" value=\"" . $data[$fieldname] . "\">\n";
                }
                echo ("</div>\n");
                break;
              default:
                $html_fieldname = htmlspecialchars("Field_$fieldname");
                echo ("<div>\n");
                echo ("<label for=\"$html_fieldname\">" . get_loc_field_name($fieldname) . ":</label>\n");
                echo ("<input id=\"$html_fieldname\" name=\"$html_fieldname\" type=\"text\" " .
                     (isset($maxlength["users.$fieldname"]) ? "maxlength=\"" . $maxlength["users.$fieldname"] . "\" " : "") .
                      "value=\"" . htmlspecialchars($data[$fieldname]) . "\">\n");
                echo ("</div>\n");
                break;
            } // end switch
            
            
            // Then output any error messages associated with the field
            // except for the password field which is a special case
            switch($fieldname)
            {
              case 'email':
                if (!empty($invalid_email))
                {
                  echo "<p class=\"error\">" . get_vocab('invalid_email') . "<p>\n";
                }
                break;
              case 'name':
                if (!empty($name_not_unique))
                {
                  echo "<p class=\"error\">'" . htmlspecialchars($taken_name) . "' " . get_vocab('name_not_unique') . "<p>\n";
                }
                if (!empty($name_empty))
                {
                  echo "<p class=\"error\">" . get_vocab('name_empty') . "<p>\n";
                }
                break;
            }
                     
          } // end foreach
      
          print "<div><p>" . get_vocab("password_twice") . "...</p></div>\n";

          for ($i=0; $i<2; $i++)
          {
            print "<div>\n";
            print "<label for=\"password$i\">" . get_vocab("user_password") . ":</label>\n";
            print "<input type=\"password\" id=\"password$i\" name=\"password$i\" value=\"\">\n";
            print "</div>\n";
          }
          
          // Now do any password error messages
          if (!empty($pwd_not_match))
          {
            echo "<p class=\"error\">" . get_vocab("passwords_not_eq") . "</p>\n";
          }
          
          if ($editing_last_admin)
          {
            echo "<br><em>(" . get_vocab("warning_last_admin") . ")</em>\n";
          }
          ?>
          <input type="hidden" name="Action" value="Update">    
          <input class="submit" type="submit" value="<?php echo(get_vocab("save")); ?>">
          
        </div>
        </fieldset>
      </form>
      <?php
      /* Administrators get the right to delete users, but only those at the same level as them or lower */
      if (($Id >= 0) && ($level >= $min_user_editing_level) && ($level >= $data['level'])) 
      {
        echo "<form id=\"form_delete_users\" method=\"post\" action=\"" . htmlspecialchars(basename($PHP_SELF)) . "\">\n";
        echo "<div>\n";
        echo "<input type=\"hidden\" name=\"Action\" value=\"Delete\">\n";
        echo "<input type=\"hidden\" name=\"Id\" value=\"$Id\">\n";
        echo "<input class=\"submit\" type=\"submit\" " . 
              (($editing_last_admin) ? "disabled=\"disabled\"" : "") .
              "value=\"" . get_vocab("delete_user") . "\">\n";
        echo "</div>\n";
        echo "</form>\n";
      }
?>
      </div>
<?php

  // Print footer and exit
  print_footer(TRUE);
}

/*---------------------------------------------------------------------------*\
|             Edit a given entry - 2nd phase: Update the database.            |
\*---------------------------------------------------------------------------*/

if (isset($Action) && ($Action == "Update"))
{
  // If you haven't got the rights to do this, then exit
  $my_id = sql_query1("SELECT id FROM $tbl_users WHERE name='".addslashes($user)."' LIMIT 1");
  if (($level < $min_user_editing_level) && ($Id != $my_id ))
  {
    Header("Location: edit_users.php");
    exit;
  }
  
  // otherwise go ahead and update the database
  else
  {
    $values = array();
    $q_string = ($Id >= 0) ? "Action=Edit" : "Action=Add";
    foreach ($fields as $fieldname)
    {
      // first, get all the form variables and put them into an array, $values, which 
      // we will use for entering into the database assuming we pass validation
      $values[$fieldname] = get_form_var("Field_$fieldname", $field_props[$fieldname]['type']);
      // Truncate the field to the maximum length as a precaution.
      if (isset($maxlength["users.$fieldname"]))
      {
        $values[$fieldname] = substr($values[$fieldname], 0, $maxlength["users.$fieldname"]);
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
        case 'id':
          // id: don't need to do anything except add the id to the query string
          $q_string .= "&Id=$Id";   
          break;
        case 'name':
          // name: convert it to lower case
          $q_string .= "&$fieldname=" . urlencode($values[$fieldname]);
          $values[$fieldname] = strtolower($values[$fieldname]);
          break;
        case 'password':
          // password: if the password field is blank it means
          // that the user doesn't want to change the password
          // so don't do anything; otherwise get the MD5 hash.
          // Note: we don't put the password in the query string
          // for security reasons.
          if ($password0 != "")
          {
            $values[$fieldname]=md5($password0);
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
            Header("Location: edit_users.php");
            exit;
          }
          break;
        default:
          $q_string .= "&$fieldname=" . urlencode($values[$fieldname]);
          break;
      }
    }

    // Now do some form validation
    $valid_data = TRUE;
    foreach ($values as $fieldname => $value)
    {
      switch ($fieldname)
      {
        case 'name':
          // check that the name is not empty
          if (empty($value))
          {
            $valid_data = FALSE;
            $q_string .= "&name_empty=1";
          }
          // Check that the name is unique.
          // If it's a new user, then to check to see if there are any rows with that name.
          // If it's an update, then check to see if there are any rows with that name, except
          // for that user.
          $query = "SELECT id FROM $tbl_users WHERE name='" . addslashes($value) . "'";
          if ($Id >= 0)
          {
            $query .= " AND id!='$Id'";
          }
          $query .= " LIMIT 1";  // we only want to know if there is at least one instance of the name
          $result = sql_query($query);
          if (sql_count($result) > 0)
          {
            $valid_data = FALSE;
            $q_string .= "&name_not_unique=1";
            $q_string .= "&taken_name=$value";
          }
          break;
        case 'password':
          // check that the two passwords match
          if ($password0 != $password1)
          {
            $valid_data = FALSE;
            $q_string .= "&pwd_not_match=1";
          }
          break;
        case 'email':
          // check that the email address is valid
          if (!empty($value) && !validate_email_list($value))
          {
            $valid_data = FALSE;
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
      Header("Location: edit_users.php?$q_string");
      exit;
    }

    
    // If we got here, then we've passed validation and we need to
    // enter the data into the database
    
    $sql_fields = array();
  
    // For each db column get the value ready for the database
    foreach ($values as $fieldname => $value)
    {
      // pre-process the field value for SQL
      if ($field_props[$fieldname]['istext'])
      {
        $value = "'" . addslashes($value) . "'";
      }
      else if ($field_props[$fieldname]['isbool'])
      {
        if ($value && $value == true)
        {
          $value = "TRUE";
        }
        else
        {
          $value = "FALSE";
        }
      }
      else
      {
        // put in a sensible default for a missing field
        if (($value == null) || ($value == ''))
        {
          if ($field_props[$fieldname]['isnum'])
          {
           $value = "0";
          }
          else
          {
            $value = "NULL";
          }
        }
      }
      
      /* If we got here, we have a valid, sql-ified value for this field,
       * so save it for later */
      $sql_fields[$fieldname] = $value;
                           
    } /* end for each column of user database */
  
    /* Now generate the SQL operation based on the given array of fields */
    if ($Id >= 0)
    {
      /* if the Id exists - then we are editing an existing user, rather th
       * creating a new one */
  
      $assign_array = array();
      $operation = "UPDATE $tbl_users SET ";
  
      foreach ($sql_fields as $fieldname => $value)
      {
        array_push($assign_array,"$fieldname=$value");
      }
      $operation .= implode(",", $assign_array) . " WHERE id=$Id;";
    }
    else
    {
      /* The id field doesn't exist, so we're adding a new user */
  
      $fields_list = array();
      $values_list = array();
  
      foreach ($sql_fields as $fieldname => $value)
      {
        array_push($fields_list,$fieldname);
        array_push($values_list,$value);
      }
      
      $operation = "INSERT INTO $tbl_users " .
        "(". implode(",",$fields_list) . ")" .
        " VALUES " . "(" . implode(",",$values_list) . ");";
    }
  
    /* DEBUG lines - check the actual sql statement going into the db */
    //echo "Final SQL string: <code>$operation</code>";
    //exit;
  
    $r = sql_command($operation);
    if ($r == -1)
    {
      print_header(0, 0, 0, "", "");
  
      // This is unlikely to happen in normal operation. Do not translate.
       
      print "<form class=\"edit_users_error\" method=\"post\" action=\"" . htmlspecialchars(basename($PHP_SELF)) . "\">\n";
      print "  <fieldset>\n";
      print "  <legend></legend>\n";
      print "    <p class=\"error\">Error updating the $tbl_users table.</p>\n";
      print "    <p class=\"error\">" . sql_error() . "</p>\n";
      print "    <input type=\"submit\" value=\" " . get_vocab("ok") . " \">\n";
      print "  </fieldset>\n";
      print "</form>\n";
  
      // Print footer and exit
      print_footer(TRUE);
    }
  
    /* Success. Redirect to the user list, to remove the form args */
    Header("Location: edit_users.php");
  }
}

/*---------------------------------------------------------------------------*\
|                                Delete a user                                |
\*---------------------------------------------------------------------------*/

if (isset($Action) && ($Action == "Delete"))
{
  $target_level = sql_query1("SELECT level FROM $tbl_users WHERE id=$Id LIMIT 1");
  if ($target_level < 0)
  {
    fatal_error(TRUE, "Fatal error while deleting a user");
  }
  // you can't delete a user if you're not some kind of admin, and then you can't
  // delete someone higher than you
  if (($level < $min_user_editing_level) || ($level < $target_level))
  {
    showAccessDenied(0, 0, 0, "", "");
    exit();
  }

  $r = sql_command("delete from $tbl_users where id=$Id;");
  if ($r == -1)
  {
    print_header(0, 0, 0, "", "");

    // This is unlikely to happen in normal  operation. Do not translate.
    
    print "<form class=\"edit_users_error\" method=\"post\" action=\"" . htmlspecialchars(basename($PHP_SELF)) . "\">\n";
    print "  <fieldset>\n";
    print "  <legend></legend>\n";
    print "    <p class=\"error\">Error deleting entry $Id from the $tbl_users table.</p>\n";
    print "    <p class=\"error\">" . sql_error() . "</p>\n";
    print "    <input type=\"submit\" value=\" " . get_vocab("ok") . " \">\n";
    print "  </fieldset>\n";
    print "</form>\n";

    // Print footer and exit
    print_footer(TRUE);
  }

  /* Success. Do not display a message. Simply fall through into the list display. */
}

/*---------------------------------------------------------------------------*\
|                          Display the list of users                          |
\*---------------------------------------------------------------------------*/

/* Print the standard MRBS header */

print_header(0, 0, 0, "", "");

print "<h2>" . get_vocab("user_list") . "</h2>\n";

if ($level >= $min_user_editing_level) /* Administrators get the right to add new users */
{
  print "<form id=\"add_new_user\" method=\"post\" action=\"" . htmlspecialchars(basename($PHP_SELF)) . "\">\n";
  print "  <div>\n";
  print "    <input type=\"hidden\" name=\"Action\" value=\"Add\">\n";
  print "    <input type=\"hidden\" name=\"Id\" value=\"-1\">\n";
  print "    <input type=\"submit\" value=\"" . get_vocab("add_new_user") . "\">\n";
  print "  </div>\n";
  print "</form>\n";
}

if ($initial_user_creation != 1)   // don't print the user table if there are no users
{
  $list = sql_query("SELECT * FROM $tbl_users ORDER BY level DESC, name");
  print "<table id=\"edit_users_list\" class=\"admin_table\">\n";
  print "<thead>\n";
  print "<tr>";
  
  // Column headers (we don't use 'id' and 'password')
  foreach ($fields as $fieldname)
  {
    if ($fieldname != 'id' && $fieldname != 'password')
    {
      print "<th>" . get_loc_field_name($fieldname) . "</th>";
    }
  }
  // Last column which is an action button
  print "<th>" . get_vocab("action") . "</th>";
  print "</tr>\n";
  print "</thead>\n";
  
  print "<tbody>\n";
  $i = 0; 
  while ($line = sql_row_keyed($list, $i++))
  {
    print "<tr>\n";
    
    // Column contents
    foreach ($line as $key=>$col_value) 
    {
      // sql_row_keyed returns an array indexed by both index number annd key name,
      // so skip past the index numbers
      if (is_int($key))
      {
        continue;
      }
      switch($key)
      {
        case 'id':
          $this_id = $col_value;  // Don't display it, but remember it.
          break;
        case 'password':
          break;                  // Don't display the password
        case 'level':
          echo "<td>" . get_vocab("level_$col_value") . "</td>\n";
          break;
        default:
          echo "<td>" . ((empty($col_value)) ? "&nbsp;" : htmlspecialchars($col_value)) . "</td>\n";
          break;
      }  // end switch   
    }  // end foreach
    
    // Last column (the action button)
    print "<td>\n";
    // You can only edit a user if you have sufficient admin rights, or else if that user is yourself
    if (($level >= $min_user_editing_level) || (strcasecmp($line['name'], $user) == 0))
    {
      print "<form method=\"post\" action=\"" . htmlspecialchars(basename($PHP_SELF)) . "\">\n";
      print "  <div>\n";
      print "    <input type=\"hidden\" name=\"Action\" value=\"Edit\">\n";
      print "    <input type=\"hidden\" name=\"Id\" value=\"$this_id\">\n";
      print "    <input type=\"submit\" value=\"" . get_vocab("edit") . "\">\n";
      print "  </div>\n";
      print "</form>\n";
    }
    else
    {
      print "&nbsp;\n";
    }
    print "</td>\n";
    print "</tr>\n";
    
  }  // end while
  
  print "</tbody>\n";
  print "</table>\n";
}   // ($initial_user_creation != 1)

require_once "trailer.inc";

?>
