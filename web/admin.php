<?php

// $Id$

define ('MAX_TEXT_LENGTH', 20);   // the maximum number of characters to display in the room table

require_once "defaultincludes.inc";

// Get form variables
$day = get_form_var('day', 'int');
$month = get_form_var('month', 'int');
$year = get_form_var('year', 'int');
$area = get_form_var('area', 'int');
$room = get_form_var('room', 'int');
$area_name = get_form_var('area_name', 'string');
$error = get_form_var('error', 'string');
$action = get_form_var('action', 'string');

// If we dont know the right date then make it up 
if (!isset($day) or !isset($month) or !isset($year))
{
  $day   = date("d");
  $month = date("m");
  $year  = date("Y");
}

if (empty($area))
{
  $area = get_default_area();
}

// Check to see whether the Edit or Delete buttons have been pressed and redirect
// as appropriate
if (isset($action))
{
  $std_query_string = "area=$area&day=$day&month=$month&year=$year";
  switch($action) {
    case 'edit':
      $location = "edit_area_room.php?$std_query_string";
      break;
    case 'delete':
      $location = "del.php?type=area&$std_query_string";
      break;
    default:
      unset($location);
      break;
  }
  if (isset($location))
  {
    header("Location: $location");
    exit;
  }
}

// Users must be at least Level 1 for this page as we will be displaying
// information such as email addresses
if (!getAuthorised(1))
{
  showAccessDenied($day, $month, $year, $area, "");
  exit();
}
$user = getUserName();
$required_level = (isset($max_level) ? $max_level : 2);
$is_admin = (authGetUserLevel($user) >= $required_level);

print_header($day, $month, $year, isset($area) ? $area : "", isset($room) ? $room : "");

// If area is set but area name is not known, get the name.
if (isset($area))
{
  if (empty($area_name))
  {
    $res = sql_query("SELECT area_name FROM $tbl_area WHERE id=$area");
    if (! $res) fatal_error(0, sql_error());
    if (sql_count($res) == 1)
    {
      $row = sql_row_keyed($res, 0);
      $area_name = $row['area_name'];
    }
    sql_free($res);
  }
}


echo "<h2>" . get_vocab("administration") . "</h2>\n";
if (!empty($error))
{
  echo "<p class=\"error\">" . get_vocab($error) . "</p>\n";
}

// TOP SECTION:  THE FORM FOR SELECTING AN AREA
echo "<div id=\"area_form\">\n";
$sql = "select id, area_name from $tbl_area order by area_name";
$res = sql_query($sql);
$areas_defined = $res && (sql_count($res) > 0);
if ($areas_defined)
{
  // If there are some areas defined, then show the area form
  echo "<form id=\"areaChangeForm\" method=\"get\" action=\"$PHP_SELF\">\n";
  echo "<fieldset>\n";
  echo "<legend></legend>\n";
  
  // The area selector
  echo "<label id=\"area_label\" for=\"area_select\">" . get_vocab("area") . ":</label>\n";
  echo "<select class=\"room_area_select\" id=\"area_select\" name=\"area\" onchange=\"this.form.submit()\">";
  for ($i = 0; ($row = sql_row_keyed($res, $i)); $i++)
  {
    $selected = ($row['id'] == $area) ? "selected=\"selected\"" : "";
    echo "<option $selected value=\"". $row['id']. "\">" . htmlspecialchars($row['area_name']) . "</option>";
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
    // The edit button
    echo "<button type=\"submit\" name=\"action\" value=\"edit\" title=\"" . get_vocab("edit") . "\">\n";
    echo "<img src=\"images/edit.png\" width=\"16\" height=\"16\" alt=\"" . get_vocab("edit") . "\">\n";
    echo "</button>\n";

    // The delete button
    echo "<button type=\"submit\" name=\"action\" value=\"delete\" title=\"" . get_vocab("delete") . "\">\n";
    echo "<img src=\"images/delete.png\" width=\"16\" height=\"16\" alt=\"" . get_vocab("delete") . "\">\n";
    echo "</button>\n";
  }
  
  echo "</fieldset>\n";
  echo "</form>\n";
}
else
{
  echo "<p>" . get_vocab("noareas") . "</p>\n";
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
echo "</div>";

// BOTTOM SECTION: ROOMS IN THE SELECTED AREA
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
    fatal_error(0, sql_error());
  }
  if (sql_count($res) == 0)
  {
    echo "<p>" . get_vocab("norooms") . "</p>\n";
  }
  else
  {
     // Get the information about the fields in the room table
    $fields = sql_field_info($tbl_room);
    
    // Build an array with the room info
    $rooms = array();
    for ($i = 0; ($row = sql_row_keyed($res, $i)); $i++)
    {
      $rooms[] = $row;
    }

    // Display it in a table [Actually two tables side by side so that we can
    // achieve a "Freeze Panes" effect: there doesn't seem to be a good way of
    // getting a colgroup to scroll, so we have to distort the mark-up a little]
    
    echo "<div id=\"room_info\">\n";
    // (a) the "header" column containing the room names
    echo "<div id=\"header_column\">\n";
    echo "<table class=\"admin_table\">\n";
    echo "<thead>\n";
    echo "<tr>\n";
    if ($is_admin)
    {
      echo "<th><div>&nbsp;</div></th>\n";
      echo "<th><div>&nbsp;</div></th>\n";
    }
    echo "<th><div>" . get_vocab("name") . "</div></th>\n";
    echo "</tr>\n";
    echo "</thead>\n";
    echo "<tbody>\n";
    $row_class = "odd_row";
    foreach ($rooms as $r)
    {
      $row_class = ($row_class == "even_row") ? "odd_row" : "even_row";
      echo "<tr class=\"$row_class\">\n";
      // Give admins delete and edit links
      if ($is_admin)
      {
        // Delete link
        echo "<td><div>\n";
        echo "<a href=\"del.php?type=room&amp;room=" . $r['id'] . "\">\n";
        echo "<img src=\"images/delete.png\" width=\"16\" height=\"16\" 
                   alt=\"" . get_vocab("delete") . "\"
                   title=\"" . get_vocab("delete") . "\">\n";
        echo "</a>\n";
        echo "</div></td>\n";
        // Delete link
        echo "<td><div>\n";
        echo "<a href=\"edit_area_room.php?room=" . $r['id'] . "\">\n";
        echo "<img src=\"images/edit.png\" width=\"16\" height=\"16\" 
                   alt=\"" . get_vocab("edit") . "\"
                   title=\"" . get_vocab("edit") . "\">\n";
        echo "</a>\n";
        echo "</div></td>\n";
      }
      echo "<td><div><a href=\"edit_area_room.php?room=" . $r['id'] . "\">" . htmlspecialchars($r['room_name']) . "</a></div></td>\n";
      echo "</tr>\n";
    }
    echo "</tbody>\n";
    echo "</table>\n";
    echo "</div>\n";
    
    // (b) the "body" columns containing the room info
    echo "<div id=\"body_columns\">\n";
    echo "<table class=\"admin_table\">\n";
    echo "<thead>\n";
    echo "<tr>\n";
    // ignore these columns, either because we don't want to display them,
    // or because we have already displayed them in the header column
    $ignore = array('id', 'area_id', 'room_name', 'sort_key');
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
            $text = substr($tbl_room, strlen($db_tbl_prefix));  // strip the prefix off the table name
            $text .= "." . $field['name'];           // add on the fieldname
            // then if there's a string in the vocab array for $tag use that
            // otherwise just use the fieldname
            $text = (isset($vocab[$text])) ? get_vocab($text) : $field['name'];
            break;
        }
        echo "<th><div>" . htmlspecialchars($text) . "</div></th>\n";
      }
    }
    echo "</tr>\n";
    echo "</thead>\n";
    echo "<tbody>\n";
    $row_class = "odd_row";
    foreach ($rooms as $r)
    {
      $row_class = ($row_class == "even_row") ? "odd_row" : "even_row";
      echo "<tr class=\"$row_class\">\n";
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
                echo "<td class=\"int\"><div>";
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
                $text = htmlspecialchars($r[$field['name']]);
                echo "<td title=\"$text\"><div>";
                echo substr($text, 0, MAX_TEXT_LENGTH);
                echo (strlen($text) > MAX_TEXT_LENGTH) ? " ..." : "";
                echo "</div></td>\n";
              }
              break;
          }
        }
      }
      echo "</tr>\n";
    }
    echo "</tbody>\n";
    echo "</table>\n";
    echo "</div>\n";
    echo "</div>\n";
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


require_once "trailer.inc"
?>