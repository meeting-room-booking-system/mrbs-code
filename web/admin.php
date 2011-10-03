<?php

// $Id$

require_once "defaultincludes.inc";

// Get non-standard form variables
$area_name = get_form_var('area_name', 'string');
$error = get_form_var('error', 'string');
// the image buttons:  need to specify edit_x rather than edit etc. because
// IE6 only returns _x and _y
$edit_x = get_form_var('edit_x', 'int');
$delete_x = get_form_var('delete_x', 'int');


// Check to see whether the Edit or Delete buttons have been pressed and redirect
// as appropriate
$std_query_string = "area=$area&day=$day&month=$month&year=$year";
if (isset($edit_x))
{
  $location = $location = "edit_area_room.php?change_area=1&phase=1&$std_query_string";
  header("Location: $location");
  exit;
}
if (isset($delete_x))
{
  $location = "del.php?type=area&$std_query_string";
  header("Location: $location");
  exit;
}
  
// Check the user is authorised for this page
checkAuthorised();

// Also need to know whether they have admin rights
$user = getUserName();
$required_level = (isset($max_level) ? $max_level : 2);
$is_admin = (authGetUserLevel($user) >= $required_level);

print_header($day, $month, $year, isset($area) ? $area : "", isset($room) ? $room : "");

// Get the details we need for this area
if (isset($area))
{
  $res = sql_query("SELECT area_name, custom_html FROM $tbl_area WHERE id=$area LIMIT 1");
  if (! $res)
  {
    trigger_error(sql_error(), E_USER_WARNING);
    fatal_error(FALSE, get_vocab("fatal_db_error"));
  }
  if (sql_count($res) == 1)
  {
    $row = sql_row_keyed($res, 0);
    $area_name = $row['area_name'];
    $custom_html = $row['custom_html'];
  }
  sql_free($res);
}


echo "<h2>" . get_vocab("administration") . "</h2>\n";
if (!empty($error))
{
  echo "<p class=\"error\">" . get_vocab($error) . "</p>\n";
}

// TOP SECTION:  THE FORM FOR SELECTING AN AREA
echo "<div id=\"area_form\">\n";
$sql = "SELECT id, area_name, disabled
          FROM $tbl_area
      ORDER BY disabled, area_name";
$res = sql_query($sql);
$areas_defined = $res && (sql_count($res) > 0);
if (!$areas_defined)
{
  echo "<p>" . get_vocab("noareas") . "</p>\n";
}
else
{
  // Build an array with the area info and also see if there are going
  // to be any areas to display (in other words rooms if you are not an
  // admin whether any areas are enabled)
  $areas = array();
  $n_displayable_areas = 0;
  for ($i = 0; ($row = sql_row_keyed($res, $i)); $i++)
  {
    $areas[] = $row;
    if ($is_admin || !$row['disabled'])
    {
      $n_displayable_areas++;
    }
  }

  if ($n_displayable_areas == 0)
  {
    echo "<p>" . get_vocab("noareas_enabled") . "</p>\n";
  }
  else
  {
    // If there are some areas displayable, then show the area form
    echo "<form id=\"areaChangeForm\" method=\"get\" action=\"".
      htmlspecialchars($PHP_SELF)."\">\n";
    echo "<fieldset>\n";
    echo "<legend></legend>\n";
  
    // The area selector
    echo "<label id=\"area_label\" for=\"area_select\">" . get_vocab("area") . ":</label>\n";
    echo "<select class=\"room_area_select\" id=\"area_select\" name=\"area\" onchange=\"this.form.submit()\">";
    if ($is_admin)
    {
      if ($areas[0]['disabled'])
      {
        $done_change = TRUE;
        echo "<optgroup label=\"" . get_vocab("disabled") . "\">\n";
      }
      else
      {
        $done_change = FALSE;
        echo "<optgroup label=\"" . get_vocab("enabled") . "\">\n";
      }
    }
    foreach ($areas as $a)
    {
      if ($is_admin || !$a['disabled'])
      {
        if ($is_admin && !$done_change && $a['disabled'])
        {
          echo "</optgroup>\n";
          echo "<optgroup label=\"" . get_vocab("disabled") . "\">\n";
          $done_change = TRUE;
        }
        $selected = ($a['id'] == $area) ? "selected=\"selected\"" : "";
        echo "<option $selected value=\"". $a['id']. "\">" . htmlspecialchars($a['area_name']) . "</option>";
      }
    }
    if ($is_admin)
    {
      echo "</optgroup>\n";
    }
    echo "</select>\n";
  
    // Some hidden inputs for current day, month, year
    echo "<input type=\"hidden\" name=\"day\" value=\"$day\">\n";
    echo "<input type=\"hidden\" name=\"month\" value=\"$month\">\n";
    echo "<input type=\"hidden\" name=\"year\"  value=\"$year\">\n";
  
    // The change area button (won't be needed or displayed if JavaScript is enabled)
    echo "<input type=\"submit\" name=\"change\" class=\"js_none\" value=\"" . get_vocab("change") . "\">\n";
  
    // If they're an admin then give them edit and delete buttons for the area
    // and also a form for adding a new area
    if ($is_admin)
    {
      // Can't use <button> because IE6 does not support those properly
      echo "<input type=\"image\" class=\"button\" name=\"edit\" src=\"images/edit.png\"
             title=\"" . get_vocab("edit") . "\" alt=\"" . get_vocab("edit") . "\">\n";
      echo "<input type=\"image\" class=\"button\" name=\"delete\" src=\"images/delete.png\"
             title=\"" . get_vocab("delete") . "\" alt=\"" . get_vocab("delete") . "\">\n";
    }
  
    echo "</fieldset>\n";
    echo "</form>\n";
  }
}

if ($is_admin)
{
  // New area form
  ?>
  <form id="add_area" class="form_admin" action="add.php" method="post">
    <fieldset>
    <legend><?php echo get_vocab("addarea") ?></legend>
        
      <input type="hidden" name="type" value="area">

      <div>
        <label for="area_name"><?php echo get_vocab("name") ?>:</label>
        <input type="text" id="area_name" name="name" maxlength="<?php echo $maxlength['area.area_name'] ?>">
      </div>
          
      <div>
        <input type="submit" class="submit" value="<?php echo get_vocab("addarea") ?>">
      </div>

    </fieldset>
  </form>
  <?php
}
echo "</div>";  // area_form

// Now the custom HTML
echo "<div id=\"custom_html\">\n";
// no htmlspecialchars() because we want the HTML!
echo (!empty($custom_html)) ? "$custom_html\n" : "";
echo "</div>\n";


// BOTTOM SECTION: ROOMS IN THE SELECTED AREA
// Only display the bottom section if the user is an admin or
// else if there are some areas that can be displayed
if ($is_admin || ($n_displayable_areas > 0))
{
  echo "<h2>\n";
  echo get_vocab("rooms");
  if(isset($area_name))
  { 
    echo " " . get_vocab("in") . " " . htmlspecialchars($area_name); 
  }
  echo "</h2>\n";

  echo "<div id=\"room_form\">\n";
  if (isset($area))
  {
    $res = sql_query("SELECT * FROM $tbl_room WHERE area_id=$area ORDER BY sort_key");
    if (! $res)
    {
      trigger_error(sql_error(), E_USER_WARNING);
      fatal_error(FALSE, get_vocab("fatal_db_error"));
    }
    if (sql_count($res) == 0)
    {
      echo "<p>" . get_vocab("norooms") . "</p>\n";
    }
    else
    {
       // Get the information about the fields in the room table
      $fields = sql_field_info($tbl_room);
    
      // Build an array with the room info and also see if there are going
      // to be any rooms to display (in other words rooms if you are not an
      // admin whether any rooms are enabled)
      $rooms = array();
      $n_displayable_rooms = 0;
      for ($i = 0; ($row = sql_row_keyed($res, $i)); $i++)
      {
        $rooms[] = $row;
        if ($is_admin || !$row['disabled'])
        {
          $n_displayable_rooms++;
        }
      }

      if ($n_displayable_rooms == 0)
      {
        echo "<p>" . get_vocab("norooms_enabled") . "</p>\n";
      }
      else
      {
        echo "<div id=\"room_info\" class=\"datatable_container\">\n";
        // Build the table.    We deal with the name and disabled columns
        // first because they are not necessarily the first two columns in
        // the table (eg if you are running PostgreSQL and have upgraded your
        // database)
        echo "<table id=\"rooms_table\" class=\"admin_table display\">\n";
        
        // The header
        echo "<thead>\n";
        echo "<tr>\n";

        echo "<th><div>" . get_vocab("name") . "</div></th>\n";
        if ($is_admin)
        {
        // Don't show ordinary users the disabled status:  they are only going to see enabled rooms
          echo "<th><div>" . get_vocab("enabled") . "</div></th>\n";
        }
        // ignore these columns, either because we don't want to display them,
        // or because we have already displayed them in the header column
        $ignore = array('id', 'area_id', 'room_name', 'disabled', 'sort_key', 'custom_html');
        foreach($fields as $field)
        {
          if (!in_array($field['name'], $ignore))
          {
            switch ($field['name'])
            {
              // the standard MRBS fields
              case 'description':
              case 'capacity':
              case 'room_admin_email':
                $text = get_vocab($field['name']);
                break;
              // any user defined fields
              default:
                $text = get_loc_field_name($tbl_room, $field['name']);
                break;
            }
            // We don't use htmlspecialchars() here because the column names are
            // trusted and some of them may deliberately contain HTML entities (eg &nbsp;)
            echo "<th><div>$text</div></th>\n";
          }
        }
        
        if ($is_admin)
        {
          echo "<th><div>&nbsp;</div></th>\n";
        }
        
        echo "</tr>\n";
        echo "</thead>\n";
        
        // The body
        echo "<tbody>\n";
        $row_class = "odd";
        foreach ($rooms as $r)
        {
          // Don't show ordinary users disabled rooms
          if ($is_admin || !$r['disabled'])
          {
            $row_class = ($row_class == "even") ? "odd" : "even";
            echo "<tr class=\"$row_class\">\n";

            $html_name = htmlspecialchars($r['room_name']);
            echo "<td><div><a title=\"$html_name\" href=\"edit_area_room.php?change_room=1&amp;phase=1&amp;room=" . $r['id'] . "\">$html_name</a></div></td>\n";
            if ($is_admin)
            {
              // Don't show ordinary users the disabled status:  they are only going to see enabled rooms
              echo "<td class=\"boolean\"><div>" . ((!$r['disabled']) ? "<img src=\"images/check.png\" alt=\"check mark\" width=\"16\" height=\"16\">" : "&nbsp;") . "</div></td>\n";
            }
            foreach($fields as $field)
            {
              if (!in_array($field['name'], $ignore))
              {
                switch ($field['name'])
                {
                  // the standard MRBS fields
                  case 'description':
                  case 'room_admin_email':
                    echo "<td><div>" . htmlspecialchars($r[$field['name']]) . "</div></td>\n";
                    break;
                  case 'capacity':
                    echo "<td class=\"int\"><div>" . $r[$field['name']] . "</div></td>\n";
                    break;
                  // any user defined fields
                  default:
                    if (($field['nature'] == 'boolean') || 
                        (($field['nature'] == 'integer') && isset($field['length']) && ($field['length'] <= 2)) )
                    {
                      // booleans: represent by a checkmark
                      echo "<td class=\"boolean\"><div>";
                      echo (!empty($r[$field['name']])) ? "<img src=\"images/check.png\" alt=\"check mark\" width=\"16\" height=\"16\">" : "&nbsp;";
                      echo "</div></td>\n";
                    }
                    elseif (($field['nature'] == 'integer') && isset($field['length']) && ($field['length'] > 2))
                    {
                      // integer values
                      echo "<td class=\"int\"><div>" . $r[$field['name']] . "</div></td>\n";
                    }
                    else
                    {
                      // strings
                      $value = $r[$field['name']];
                      $html = "<td title=\"" . htmlspecialchars($value) . "\"><div>";
                      // Truncate before conversion, otherwise you could chop off in the middle of an entity
                      $html .= htmlspecialchars(substr($value, 0, $max_content_length));
                      $html .= (strlen($value) > $max_content_length) ? " ..." : "";
                      $html .= "</div></td>\n";
                      echo $html;
                    }
                    break;
                }  // switch
              }  // if
            }  // foreach
            
            // Give admins a delete link
            if ($is_admin)
            {
              // Delete link
              echo "<td><div>\n";
              echo "<a href=\"del.php?type=room&amp;area=$area&amp;room=" . $r['id'] . "\">\n";
              echo "<img src=\"images/delete.png\" width=\"16\" height=\"16\" 
                         alt=\"" . get_vocab("delete") . "\"
                         title=\"" . get_vocab("delete") . "\">\n";
              echo "</a>\n";
              echo "</div></td>\n";
            }
            
            echo "</tr>\n";
          }
        }

        echo "</tbody>\n";
        echo "</table>\n";
        echo "</div>\n";
        
      }
    }
  }
  else
  {
    echo get_vocab("noarea");
  }

  // Give admins a form for adding rooms to the area - provided 
  // there's an area selected
  if ($is_admin && $areas_defined && !empty($area))
  {
  ?>
    <form id="add_room" class="form_admin" action="add.php" method="post">
      <fieldset>
      <legend><?php echo get_vocab("addroom") ?></legend>
        
        <input type="hidden" name="type" value="room">
        <input type="hidden" name="area" value="<?php echo $area; ?>">
        
        <div>
          <label for="room_name"><?php echo get_vocab("name") ?>:</label>
          <input type="text" id="room_name" name="name" maxlength="<?php echo $maxlength['room.room_name'] ?>">
        </div>
        
        <div>
          <label for="room_description"><?php echo get_vocab("description") ?>:</label>
          <input type="text" id="room_description" name="description" maxlength="<?php echo $maxlength['room.description'] ?>">
        </div>
        
        <div>
          <label for="room_capacity"><?php echo get_vocab("capacity") ?>:</label>
          <input type="text" id="room_capacity" name="capacity">
        </div>
       
        <div>
          <input type="submit" class="submit" value="<?php echo get_vocab("addroom") ?>">
        </div>
        
      </fieldset>
    </form>
  <?php
  }
  echo "</div>\n";
}


require_once "trailer.inc"
?>