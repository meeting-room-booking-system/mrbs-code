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
  id        int DEFAULT '0' NOT NULL auto_increment,
  name      varchar(30),
  password  varchar(32),
  email     varchar(75),

  /* The following fields are application-specific. However only int and varchar are editable. */


  PRIMARY KEY (id)
);";
    $r = sql_command($cmd);
    if ($r == -1)
        { // No need to localize this: Only the admin running this for the first time would see it.
        print "Error creating the $tbl_users table.<br>\n";
        print sql_error() . "<br>\n";
        exit();
        }
    $nusers = 0;
    }

/* Get the list of fields actually in the table. (Allows the addition of new fields later on) */
$result = sql_query("select * from $tbl_users limit 1");
$nfields = sql_num_fields($result);
for ($i=0; $i<$nfields ;$i++)
    {
    $field_name[$i] = sql_field_name($result, $i);
// print "<p>field_name[$i] = $field_name[$i]</p>\n";
    $field_type[$i] = sql_field_type($result, $i);
// print "<p>field_type[$i] = $field_type[$i]</p>\n";
    $field_istext[$i] = ((stristr($field_type[$i], "char")) || (stristr($field_type[$i], "string"))) ? true : false;
// print "<p>field_istext[$i] = $field_istext[$i]</p>\n";
    $field_isnum[$i] = ((stristr($field_type[$i], "int")) || (stristr($field_type[$i], "real"))) ? true : false;
// print "<p>field_isnum[$i] = $field_isnum[$i]</p>\n";
    }
sql_free($result);

/* Get localized field name */
function get_loc_field_name($i)
    {
    global $field_name, $vocab;

    $name = $field_name[$i];  // $name = "name", "password", ...
    // Search for indexes "user_name", "user_password", etc, in the localization array.
    if (isset($vocab["user_".$name])) return get_vocab("user_".$name);
    // If there is no entry (likely if user-defined fields have been added), return itself.
    return $name;
    }

/*---------------------------------------------------------------------------*\
|                         Authentify the current user                         |
\*---------------------------------------------------------------------------*/

if ($nusers > 0)
    {
    $user = getUserName();
    $level = authGetUserLevel($user, $auth["admin"]);
    // Do not allow unidentified people to browse the list.
    if(!getAuthorised($user, getUserPassword(), 1))
        {
        showAccessDenied($day, $month, $year, $area);
        exit;
        }
    }
else /* We've just created the table. Assume the person doing this IS the administrator. */
    {
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
        $data = sql_row($result, 0);
        sql_free($result);
        }
    if (($Id == -1) || (!$data)) /* Set blank data for undefined entries */
    	{
    	for ($i=0; $i<$nfields; $i++) $data[$i] = "";
    	}

    /* First make sure the user is authorized */
    if (!getWritable($data[1], $user))
        {
        showAccessDenied(0, 0, 0, "");
        exit();
        }

    print_header(0, 0, 0, 0);

    if ($Action == "Edit")
    {
        print "<h2>" . get_vocab("edit_user") . "</h2>\n";
    }
    else
    {
        print "<h2>" . get_vocab("add_new_user") . "</h2>\n";
    }

    if (($Id >= 0) && ($level == 2)) /* Administrators get the right to delete users */
        {
        print "<p><form method=post action=\"" . basename($PHP_SELF) . "\">\n";
        print "\t<input type=hidden name=Action value=Delete />\n";
        print "\t<input type=hidden name=Id value=$Id />\n";
        print "\t<input style=\"margin:0\" type=submit value=\"" . get_vocab("delete_user") . "\" />\n";
        print "</form></p>\n";
        }

    print "<form method=post action=\"" . basename($PHP_SELF) . "\">\n";
    print "  <table>\n";

    for ($i=0; $i<$nfields; $i++)
        {
        /* The ID field cannot change; The password field must not be shown. */
        if ($field_name[$i] == "id")
            {
            print "    <input type=hidden name=Id value=$Id />\n";
            continue;
            }
        if ($field_name[$i] == "password")
            {
            print "    <input type=hidden name=\"Field[$i]\" value=\"".
                        htmlspecialchars($data[$i])."\" />\n";
            continue;
            }
        print "    <tr>\n";
        print "      <td align=right valign=bottom>" . get_loc_field_name($i) . "</td>\n";
        print "      <td><input type=text name=\"".htmlspecialchars("Field[$i]").
                          "\" value=\"".htmlspecialchars($data[$i])."\" /></td>\n";
        // Display message about invalid email
        (!isset($invalid_email)) ? $invalid_email = '' : '' ;
        if ( ($field_name[$i] == "email") && (1 == $invalid_email) )
        {
            print ("<td><STRONG>" . get_vocab('invalid_email') . "<STRONG></td>\n");
        }
        print "    </tr>\n";
        }
    print "  </table>\n";

    print " <br>" . get_vocab("password_twice") . "...<br><br>\n";
    print "  <table>\n";
    for ($i=0; $i<2; $i++)
        {
        print "    <tr>\n";
        print "      <td align=right valign=center>" . get_vocab("user_password") . "</td>\n";
        print "      <td><input type=password name=password$i value=\"\" /></td>\n";
        print "    </tr>\n";
        }
    print "  </table>\n";
/*    print "  <input type=hidden name=Id value=\"$this_id\" /> <br />\n"; */
    print "  <input type=hidden name=Action value=Update /> <br />\n";
    print "  <input type=submit value=\" " . get_vocab("ok") . " \" /> <br />\n";
    print "</form>\n</body>\n</html>\n";

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

        print get_vocab("passwords_not_eq") . "<br>\n";

        print "<form method=post action=\"" . basename($PHP_SELF) . "\">\n";
        print "  <input type=submit value=\" " . get_vocab("ok") . " \" /> <br />\n";
        print "</form>\n</body>\n</html>\n";

        exit();
        }
    //
    // Verify email adresses
    include_once 'Mail/RFC822.php';
    (!isset($Field[3])) ? $Field[3] = '': '';
    $emails = explode(',', $Field[3]);
    $valid_email = new Mail_RFC822();
    foreach ($emails as $email)
    {
        // if no email address is entered, this is OK, even if isValidInetAddress
        // does not return TRUE
        if ( !$valid_email->isValidInetAddress($email, $strict = FALSE)
            && ('' != $Field[3]) )
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

    for ($i=0; $i<$nfields; $i++)
        {
        if ($field_name[$i]=="id") $Field[$i] = $Id;
        if ($field_name[$i]=="name") $Field[$i] = strtolower($Field[$i]);
        if (($field_name[$i]=="password") && ($password0!="")) $Field[$i]=md5($password0);
        /* print "$field_name[$i] = $Field[$i]<br>"; */
        if ($i > 0) $operation = $operation . ", ";
        if ($field_istext[$i]) $operation .= "'";
        if ($field_isnum[$i] && ($Field[$i] == "")) $Field[$i] = "0";
        $operation = $operation . $Field[$i];
        if ($field_istext[$i]) $operation .= "'";
        }
    $operation = $operation . ");";

    /* print $operation . "<br>\n"; */
    $r = sql_command($operation);
    if ($r == -1)
        {
	print_header(0, 0, 0, "");

	// This is unlikely to happen in normal  operation. Do not translate.
        print "Error updating the $tbl_users table.<br>\n";
        print sql_error() . "<br>\n";
        
        print "<form method=post action=\"" . basename($PHP_SELF) . "\">\n";
        print "  <input type=submit value=\" " . get_vocab("ok") . " \" /> <br />\n";
        print "</form>\n</body>\n</html>\n";

        exit();
        }
    /* print "Database updated successfully.<br><br>\n"; */
    /* Success. Do not display a message. Simply fall through into the list display. */
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
        print "Error deleting entry $Id from the $tbl_users table.<br>\n";
        print sql_error() . "<br>\n";
        
        print "<form method=post action=\"" . basename($PHP_SELF) . "\">\n";
        print "  <input type=submit value=\" " . get_vocab("ok") . " \" /> <br />\n";
        print "</form>\n</body>\n</html>\n";

        exit();
        }
    /* print "Database updated successfully.<br><br>\n"; */
    /* Success. Do not display a message. Simply fall through into the list display. */
    }

/*---------------------------------------------------------------------------*\
|                          Display the list of users                          |
\*---------------------------------------------------------------------------*/

/* Print the standard MRBS header */

print_header(0, 0, 0, "");

print "<h2>" . get_vocab("user_list") . "</h2>\n";

if ($level == 2) /* Administrators get the right to add new users */
    {
    print "<p><form method=post action=\"" . basename($PHP_SELF) . "\">\n";
    print "\t<input type=hidden name=Action value=Add />\n";
    print "\t<input type=hidden name=Id value=\"-1\" />\n";
    print "\t<input style=\"margin:0\" type=submit value=\"" . get_vocab("add_new_user") . "\" />\n";
    print "</form></p>\n";
    }

$list = sql_query("select * from $tbl_users order by name");
print "<table border=1>\n";
print "<tr>";
// The first 2 columns are the user rights and uaser name.
print "<th>" . get_vocab("rights") . "</th><th>" . get_vocab("user_name") . "</th>";
// The remaining columns are all the columns from the database, past the initial 3 (id, name, password).
for ($i=3; $i<$nfields; $i++) print "<th>" . get_loc_field_name($i) . "</th>";
print "<th>" . get_vocab("action") . "</th>";
print "</tr>\n";
$i = 0; 
while ($line = sql_row($list, $i++))
    {
    print "\t<tr>\n";
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
                case 1: $right = get_vocab("user"); break;
                case 2: $right = get_vocab("administrator"); break; // MRBS admin
                case 3: $right = get_vocab("administrator"); break; // Reserved for future user admin.
                default: $right = get_vocab("unknown"); break;
                }
            print "\t\t<td>$right</td>\n";
            /* Fall through to display the name */
            }
        if ($j == 2)	/* The 3rd data is the password, which we must not display. */
            {
            continue;
            }
        /* Display the data, if any. */
        if ($col_value == "") $col_value = "&nbsp;"; // IE doesn't print a frame around void data.
        print "\t\t<td>$col_value</td>\n";
        }
    print "\t\t<td>\n";
    if (getWritable($name, $user)) /* If the logged-on user has the right to edit this entry */
    	{
        print "\t\t    <form method=post action=\"" . basename($PHP_SELF) . "\">\n";
        print "\t\t\t<input type=hidden name=Action value=Edit />\n";
        print "\t\t\t<input type=hidden name=Id value=\"$this_id\" />\n";
        print "\t\t\t<input style=\"margin:0\" type=submit value=\"" . get_vocab("edit") . "\" />\n";
        print "\t\t    </form>\n";
        }
    else
    	{
    	print "\t\t\t&nbsp;\n";
    	}
    print "\t\t</td>\n";
    print "\t</tr>\n";
    }
print "</table>\n";

include "trailer.inc";
?>