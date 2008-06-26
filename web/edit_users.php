<?php
/*****************************************************************************\
*									      *
*   File name       edit_users.php					      *
*									      *
*   Description	    Edit the user database                                    *
*									      *
*   Notes	    Automatically creates the database if it's not present.   *
*									      *
*		    Designed to be easily extensible:                         *
*                   Adding more fields for each user does not require         *
*                    modifying the editor code. Only to add the fields in     *
*                    the database creation code.                              *
*									      *
*		    To do:						      *
*			- Localisability                                      *
*									      *
*   History								      *
*    2003/12/29 JFL Created this file					      *
*									      *
\*****************************************************************************/

// $Id$

require_once "grab_globals.inc.php";
include "config.inc.php";
include "functions.inc";
include "$dbsys.inc";
include "mrbs_auth.inc";

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

/*---------------------------------------------------------------------------*\
|                     Create the users database if needed                     |
\*---------------------------------------------------------------------------*/

$nusers = sql_query1("select count(*) from $tbl_users");

if ($nusers == -1)	/* If the table does not exist */
{			/* Then create it */
  $cmd = "
CREATE TABLE $tbl_users
(
  /* The first four fields are required. Don't remove or reorder. */
  id        int NOT NULL auto_increment,
  name      varchar(30),
  password  varchar(40),
  email     varchar(75),

  /* The following fields are application-specific. However only int and varchar are editable. */


  PRIMARY KEY (id)
);";
  $r = sql_command($cmd);
  if ($r == -1)
  {
    // No need to localize this: Only the admin running this for the first time would see it.
    print "<p class=\"error\">Error creating the $tbl_users table.</p>\n";
    print "<p class=\"error\">" . sql_error() . "</p>\n";
    exit();
  }
  $nusers = 0;
}

/* Get the list of fields actually in the table. (Allows the addition of new fields later on) */
$result = sql_query("select * from $tbl_users limit 1");
$nfields = sql_num_fields($result);
for ($i=0; $i<$nfields ;$i++)
{
  $field_name = sql_field_name($result, $i);
  $fields[] = $field_name;
  $field_props[$field_name]['type'] = sql_field_type($result, $i);
  $field_props[$field_name]['istext'] = ($field_props[$field_name]['type'] == 'string') ? true : false;
  $field_props[$field_name]['isnum'] = preg_match('/(int|real)/',$field_props[$field_name]['type']) ? true : false;
}
sql_free($result);

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
  $level = authGetUserLevel($user, $auth["admin"]);
  // Do not allow unidentified people to browse the list.
  if(!getAuthorised(1))
  {
    showAccessDenied($day, $month, $year, $area);
    exit;
  }
}
else /* We've just created the table. Assume the person doing this IS the administrator. */
{
  $initial_user_creation = 1;
  $user = "administrator";
  $level = 2;
}

/*---------------------------------------------------------------------------*\
|             Edit a given entry - 1st phase: Get the user input.             |
\*---------------------------------------------------------------------------*/

if (isset($Action) && ( ($Action == "Edit") or ($Action == "Add") ))
{
  if ($Id >= 0) /* -1 for new users, or >=0 for existing ones */
  {
    $result = sql_query("select * from $tbl_users where id=$Id");
    $data = sql_row_keyed($result, 0);
    sql_free($result);
  }
  if (($Id == -1) || (!$data)) /* Set blank data for undefined entries */
  {
    foreach ($fields as $fieldname)
    {
      $data[$fieldname] = "";
    }
  }

  /* First make sure the user is authorized */
  if (!getWritable($data['name'], $user))
  {
    showAccessDenied(0, 0, 0, "");
    exit();
  }

  print_header(0, 0, 0, 0);
  
  print "<div id=\"form_container\">";
  print "<form id=\"form_edit_users\" method=\"post\" action=\"" . htmlspecialchars(basename($PHP_SELF)). "\">\n";
    ?>
        <fieldset>
	     <legend><?php echo (($Action == "Edit") ? get_vocab("edit_user") : get_vocab("add_new_user"));?></legend>
		  <div id="edit_users_input_container">
          <?php

          foreach ($fields as $fieldname)
          {
            /* The ID field cannot change; The password field must not be shown. */
            if ($fieldname == "id")
            {
              print "    <input type=\"hidden\" name=\"Id\" value=\"$Id\">\n";
              continue;
            }
            if ($fieldname == "password")
            {
              print "    <input type=\"hidden\" name=\"Field_$fieldname\" value=\"". htmlspecialchars($data['password'])."\" >\n";
              continue;
            }
		      $html_fieldname = htmlspecialchars("Field_$fieldname");
				echo ("<div>\n");
		      echo ("<label for=\"$html_fieldname\">" . get_loc_field_name($fieldname) . ":</label>\n");
		      echo ("<input id=\"$html_fieldname\" name=\"$html_fieldname\" type=\"text\" value=\"" . htmlspecialchars($data[$fieldname]) . "\">\n");
		      echo ("</div>\n");
				
            // Display message about invalid email
            (!isset($invalid_email)) ? $invalid_email = '' : '' ;
            if ( ($fieldname == "email") && (1 == $invalid_email) )
            {
              print ("<p class=\"error\">" . get_vocab('invalid_email') . "<p>\n");
            }
          }


            print "<div><p>" . get_vocab("password_twice") . "...</p></div>\n";

          for ($i=0; $i<2; $i++)
          {
			   print "<div>\n";
            print "<label for=\"password$i\">" . get_vocab("user_password") . ":</label>\n";
            print "<input type=\"password\" id=\"password$i\" name=\"password$i\" value=\"\" >\n";
				print "</div>\n";
          }
          ?>
		    <input type="hidden" name="Action" value="Update">	 
          <input class="submit" type="submit" value="<?php echo(get_vocab("ok")); ?>">
		  </div>
        </fieldset>
      </form>
		<?php
	   if (($Id >= 0) && ($level == 2)) /* Administrators get the right to delete users */
      {
		  ?>
        <form id="form_delete_users" method="post" action="<?php echo(htmlspecialchars(basename($PHP_SELF))); ?>">
          <input type="hidden" name="Action" value="Delete">
          <input type="hidden" name="Id" value="<?php echo($Id); ?>">
          <input class="submit" type="submit" value="<?php echo(get_vocab("delete_user")); ?>">
        </form>
	     <?php
      }
      ?>
		</div>
    </body>
	 </html>
  <?php
  exit();
}

/*---------------------------------------------------------------------------*\
|             Edit a given entry - 2nd phase: Update the database.            |
\*---------------------------------------------------------------------------*/

if (isset($Action) && ($Action == "Update"))
{
  /* To do: Add JavaScript to verify passwords _before_ sending the form here */
  if ($password0 != $password1)
  {
    print_header(0, 0, 0, "");

    print "<form class=\"edit_users_error\" method=\"post\" action=\"" . htmlspecialchars(basename($PHP_SELF)) . "\">\n";
	 print "  <fieldset>\n";
	 print "  <legend></legend>\n";
    print "    <p class=\"error\">" . get_vocab("passwords_not_eq") . "</p>\n";
	 print "    <input type=\"submit\" value=\" " . get_vocab("ok") . " \" >\n";
	 print "  </fieldset>\n";
    print "</form>\n</body>\n</html>\n";

    exit();
  }
  //
  // Verify email adresses
  include_once 'Mail/RFC822.php';

  $email_var = get_form_var('Field_email', 'string');
  if (!isset($email_var))
  {
    $email_var = '';
  }
  $emails = explode(',', $email_var);
  $valid_email = new Mail_RFC822();
  foreach ($emails as $email)
  {
    // if no email address is entered, this is OK, even if isValidInetAddress
    // does not return TRUE
    if ( !$valid_email->isValidInetAddress($email, $strict = FALSE)
         && ('' != $email_var) )
    {
      // Now display this form again with an error message
      Header("Location: edit_users.php?Action=Edit&Id=$Id&invalid_email=1");
      exit;
    }
  }
  //
  if ($Id >= 0)
  {
    $operation = "replace into $tbl_users values (";
  }
  else
  {
    $operation = "insert into $tbl_users values (";
    $Id = sql_query1("select max(id) from $tbl_users;") + 1; /* Use the last index + 1 */
    /* Note: If the table is empty, sql_query1 returns -1. So use index 0. */
  }

  $i = 0;
  foreach ($fields as $fieldname)
  {
    if ($fieldname=="id")
    {
      $value = $Id;
    }
    else if ($fieldname=="name")
    {
      $value = strtolower(get_form_var('Field_name', 'string'));
    }
    else if (($fieldname=="password") && ($password0!=""))
    {
      $value=md5($password0);
    }
    else
    {
      $value = get_form_var("Field_$fieldname", $field_props[$fieldname]['istext'] ? 'string' : 'int');
    }

    if ($i > 0)
    {
      $operation = $operation . ", ";
    }
    if ($field_props[$fieldname]['istext'])
    {
      $operation .= "'" . slashes($value) . "'";
    }
    else
    {
      if ($field_props[$fieldname]['isnum'] && ($value == ""))
      {
        $value = "0";
      }
      $operation = $operation . $value;
    }
    $i++;
  }
  $operation = $operation . ");";

//  print $operation . "<br>\n";
//  exit;
  $r = sql_command($operation);
  if ($r == -1)
  {
    print_header(0, 0, 0, "");

    // This is unlikely to happen in normal operation. Do not translate.
     
    print "<form class=\"edit_users_error\" method=\"post\" action=\"" . htmlspecialchars(basename($PHP_SELF)) . "\">\n";
	 print "  <fieldset>\n";
	 print "  <legend></legend>\n";
	 print "    <p class=\"error\">Error updating the $tbl_users table.</p>\n";
    print "    <p class=\"error\">" . sql_error() . "</p>\n";
    print "    <input type=\"submit\" value=\" " . get_vocab("ok") . " \" >\n";
	 print "  </fieldset>\n";
    print "</form>\n</body>\n</html>\n";

    exit();
  }

  /* Success. Redirect to the user list, to remove the form args */
  Header("Location: edit_users.php");
}

/*---------------------------------------------------------------------------*\
|                                Delete a user                                |
\*---------------------------------------------------------------------------*/

if (isset($Action) && ($Action == "Delete"))
{
  if ($level < 2)
  {
    showAccessDenied(0, 0, 0, "");
    exit();
  }

  $r = sql_command("delete from $tbl_users where id=$Id;");
  if ($r == -1)
  {
    print_header(0, 0, 0, "");

    // This is unlikely to happen in normal  operation. Do not translate.
    
    print "<form class=\"edit_users_error\" method=\"post\" action=\"" . htmlspecialchars(basename($PHP_SELF)) . "\">\n";
    print "  <fieldset>\n";
	 print "  <legend></legend>\n";
	 print "    <p class=\"error\">Error deleting entry $Id from the $tbl_users table.</p>\n";
    print "    <p class=\"error\">" . sql_error() . "</p>\n";
	 print "    <input type=\"submit\" value=\" " . get_vocab("ok") . " \" >\n";
	 print "  </fieldset>\n";
    print "</form>\n</body>\n</html>\n";

    exit();
  }

  /* Success. Do not display a message. Simply fall through into the list display. */
}

/*---------------------------------------------------------------------------*\
|                          Display the list of users                          |
\*---------------------------------------------------------------------------*/

/* Print the standard MRBS header */

print_header(0, 0, 0, "");

print "<h2>" . get_vocab("user_list") . "</h2>\n";

if ($initial_user_creation == 1)
{
  print "<h3>" . get_vocab("no_users_initial") . "</h3>\n";
  print "<p>" . get_vocab("no_users_create_first_admin") . "</p>\n";
}

if ($level == 2) /* Administrators get the right to add new users */
{
  print "<form method=\"post\" action=\"" . htmlspecialchars(basename($PHP_SELF)) . "\">\n";
  print "  <input type=\"hidden\" name=\"Action\" value=\"Add\">\n";
  print "  <input type=\"hidden\" name=\"Id\" value=\"-1\">\n";
  print "  <input style=\"margin:0\" type=\"submit\" value=\"" . get_vocab("add_new_user") . "\" >\n";
  print "</form>\n";
}

if ($initial_user_creation != 1)   // don't print the user table if there are no users
{
  $list = sql_query("select * from $tbl_users order by name");
  print "<table id=\"edit_users_list\" class=\"admin_table\">\n";
  print "<thead>\n";
  print "<tr>";
  // The first 2 columns are the user rights and user name.
  print "<th>" . get_vocab("rights") . "</th><th>" . get_vocab("user_name") . "</th>";
  // The remaining columns are all the columns from the database, past the initial 3 (id, name, password).
  foreach ($fields as $fieldname)
  {
    if ($fieldname != 'id' && $fieldname != 'name' && $fieldname != 'password')
    {
      print "<th>" . get_loc_field_name($fieldname) . "</th>";
    }
  }
  print "<th>" . get_vocab("action") . "</th>";
  print "</tr>\n";
  print "</thead>\n";
  print "<tbody>\n";
  $i = 0; 
  while ($line = sql_row($list, $i++))
  {
    print "<tr>\n";
    $j = -1;
    $this_id = 0;
    foreach ($line as $col_value) 
    {
      $j += 1;
      if ($j == 0)	/* The 1st data is the ID. */
      {		/* Don't display it, but remember it. */
        $this_id = $col_value;
        continue;
      }
      if ($j == 1)	/* The 2nd data is the name. */
      {		/* Use it to tell if it's a user or an admin */
        $name = $col_value;
        switch (authGetUserLevel($name, $auth["admin"]))
        {
          case 1:
            $right = get_vocab("user");
            break;
          case 2:
            $right = get_vocab("administrator");
            break; // MRBS admin
          case 3:
            $right = get_vocab("administrator");
            break; // Reserved for future user admin.
          default:
            $right = get_vocab("unknown");
            break;
        }
        print "<td>$right</td>\n";
        /* Fall through to display the name */
      }
      if ($j == 2)	/* The 3rd data is the password, which we must not display. */
      {
        continue;
      }
      /* Display the data, if any. */
      if ($col_value == "")
      {
        $col_value = "&nbsp;"; // IE doesn't print a frame around void data.
      }
      print "<td>$col_value</td>\n";
    }
    print "<td>\n";
    if (getWritable($name, $user)) /* If the logged-on user has the right to edit this entry */
    {
      print "<form method=\"post\" action=\"" . htmlspecialchars(basename($PHP_SELF)) . "\">\n";
      print "  <input type=\"hidden\" name=\"Action\" value=\"Edit\">\n";
      print "  <input type=\"hidden\" name=\"Id\" value=\"$this_id\">\n";
      print "  <input style=\"margin:0\" type=\"submit\" value=\"" . get_vocab("edit") . "\" >\n";
      print "</form>\n";
    }
    else
    {
      print "&nbsp;\n";
    }
    print "</td>\n";
    print "</tr>\n";
  }
  print "</tbody>\n";
  print "</table>\n";
}   // ($initial_user_creation != 1)

include "trailer.inc";
?>
