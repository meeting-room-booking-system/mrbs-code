<?php
namespace MRBS;

use MRBS\Form\Form;
use MRBS\Form\ElementFieldset;
use MRBS\Form\ElementInputHidden;
use MRBS\Form\ElementInputImage;
use MRBS\Form\FieldInputEmail;
use MRBS\Form\FieldInputText;
use MRBS\Form\FieldSelect;
use MRBS\Form\FieldSubmit;


require "defaultincludes.inc";


function generate_area_change_form($enabled_areas, $disabled_areas)
{
  global $is_admin;
  global $area, $day, $month, $year;
  
  $form = new Form();
  
  $attributes = array('id'     => 'areaChangeForm',
                      'action' => this_page(),
                      'method' => 'post');
                      
  $form->setAttributes($attributes);
  
  $fieldset = new ElementFieldset();
  $fieldset->addLegend('');
  
  // The area select
  if ($is_admin)
  {
    $options = array(get_vocab("enabled") => $enabled_areas,
                     get_vocab("disabled") => $disabled_areas);
  }
  else
  {
    $options = $enabled_areas;
  }
  
  $field = new FieldSelect();
  $field->setLabel(get_vocab('area'))
        ->setControlAttributes(array('id'       => 'area_select',
                                     'name'     => 'area',
                                     'class'    => 'room_area_select',
                                     'onchange' => 'this.form.submit()'))
        ->addOptions($options, $area);
  $fieldset->addElement($field);
  
  // Hidden inputs for page day, month, year
  $vars = array('day', 'month', 'year');
  foreach ($vars as $var)
  {
    $element = new ElementInputHidden();
    $element->setAttributes(array('name'  => $var,
                                  'value' => $$var));
    $fieldset->addElement($element);
  }

  // The change area button (won't be needed or displayed if JavaScript is enabled)
  $field = new FieldSubmit();
  $field->setAttribute('class', 'js_none')
        ->setControlAttributes(array('value' => get_vocab('change'),
                                     'name'  => 'change'));
  $fieldset-> addElement($field);
  
  // If they're an admin then give them edit and delete buttons for the area
  if ($is_admin)
  {
    // Can't use <button> because IE6 does not support those properly
    // (But we don't support IE6 any more - so this can change!)
    $element = new ElementInputImage();
    $element->setAttributes(array('class'      => 'button',
                                  'name'       => 'edit',
                                  'src'        => 'images/edit.png',
                                  'formaction' => 'edit_area_room.php?change_area=1',
                                  'title'      => get_vocab('edit'),
                                  'alt'        => get_vocab('edit')));
    $fieldset->addElement($element);
    
    $element = new ElementInputImage();
    $element->setAttributes(array('class'      => 'button',
                                  'name'       => 'delete',
                                  'src'        => 'images/delete.png',
                                  'formaction' => 'del.php?type=area',
                                  'title'      => get_vocab('delete'),
                                  'alt'        => get_vocab('delete')));
    $fieldset->addElement($element);
  }
  
  $form->addElement($fieldset);

  $form->render();
}


function generate_new_area_form()
{
  global $maxlength;
  
  $form = new Form();
  
  $attributes = array('id'     => 'add_area',
                      'class'  => 'form_admin',
                      'action' => 'add.php',
                      'method' => 'post');
                      
  $form->setAttributes($attributes);
  
  $fieldset = new ElementFieldset();
  $fieldset->addLegend(get_vocab('addarea'));
  
  // Hidden field for the type of operation
  $element = new ElementInputHidden();
  $element->setAttributes(array('name'  => 'type',
                                'value' => 'area'));
  $fieldset->addElement($element);
  
  // The name field
  $field = new FieldInputText();
  $field->setLabel(get_vocab('name'))
        ->setControlAttributes(array('id'        => 'area_name',
                                     'name'      => 'name',
                                     'maxlength' => $maxlength['area.area_name']));               
  $fieldset->addElement($field);
  
  // The submit button
  $field = new FieldSubmit();
  $field->setControlAttributes(array('value' => get_vocab('addarea'),
                                     'class' => 'submit'));
  $fieldset-> addElement($field);
  
  $form->addElement($fieldset);
  
  $form->render();
}


function generate_new_room_form()
{
  global $maxlength;
  global $area;
  
  $form = new Form();
  
  $attributes = array('id'     => 'add_room',
                      'class'  => 'form_admin',
                      'action' => 'add.php',
                      'method' => 'post');
                      
  $form->setAttributes($attributes);

  $fieldset = new ElementFieldset();
  $fieldset->addLegend(get_vocab('addroom'));
  
  // Hidden field for the type of operation
  $element = new ElementInputHidden();
  $element->setAttributes(array('name'  => 'type',
                                'value' => 'room'));
  $fieldset->addElement($element);
  
  // Hidden field for the area
  $element = new ElementInputHidden();
  $element->setAttributes(array('name'  => 'area',
                                'value' => $area));
  $fieldset->addElement($element);
  
  // The name field
  $field = new FieldInputText();
  $field->setLabel(get_vocab('name'))
        ->setControlAttributes(array('id'        => 'room_name',
                                     'name'      => 'name',
                                     'maxlength' => $maxlength['room.room_name']));               
  $fieldset->addElement($field);
  
  // The description field
  $field = new FieldInputText();
  $field->setLabel(get_vocab('description'))
        ->setControlAttributes(array('id'        => 'room_description',
                                     'name'      => 'description',
                                     'maxlength' => $maxlength['room.description']));               
  $fieldset->addElement($field);
  
  // The capacity field
  $field = new FieldInputText();
  $field->setLabel(get_vocab('capacity'))
        ->setControlAttributes(array('id'   => 'room_capacity',
                                     'name' => 'capacity'));               
  $fieldset->addElement($field);
        
  // The email field
  $field = new FieldInputEmail();
  $field->setLabel(get_vocab('room_admin_email'))
        ->setControlAttributes(array('id'       => 'room_admin_email',
                                     'name'     => 'room_admin_email',
                                     'multiple' => null));           
  $fieldset->addElement($field);
  
  // The submit button
  $field = new FieldSubmit();
  $field->setControlAttributes(array('value' => get_vocab('addroom'),
                                     'class' => 'submit'));
  $fieldset-> addElement($field);
      
  $form->addElement($fieldset);
  
  $form->render();
}


// Check the CSRF token
Form::checkToken();

// Check the user is authorised for this page
checkAuthorised();

// Also need to know whether they have admin rights
$user = getUserName();
$required_level = (isset($max_level) ? $max_level : 2);
$is_admin = (authGetUserLevel($user) >= $required_level);

// Get non-standard form variables
$error = get_form_var('error', 'string');

print_header($day, $month, $year, isset($area) ? $area : null, isset($room) ? $room : null);

// Get the details we need for this area
if (isset($area))
{
  $res = db()->query("SELECT area_name, custom_html FROM $tbl_area WHERE id=? LIMIT 1", array($area));

  if ($res->count() == 1)
  {
    $row = $res->row_keyed(0);
    $area_name = $row['area_name'];
    $custom_html = $row['custom_html'];
  }
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
      ORDER BY disabled, sort_key";
$res = db()->query($sql);

$enabled_areas = array();
$disabled_areas = array();
for ($i = 0; ($row = $res->row_keyed($i)); $i++)
{
  if ($row['disabled'])
  {
    $disabled_areas[$row['id']] = $row['area_name'];
  }
  else
  {
    $enabled_areas[$row['id']] = $row['area_name'];
  }
}

$areas_defined = !empty($enabled_areas) || !empty($disabled_areas);

if (!$areas_defined)
{
  echo "<p>" . get_vocab("noareas") . "</p>\n";
}
else
{
  if (!$is_admin && empty($enabled_areas))
  {
    echo "<p>" . get_vocab("noareas_enabled") . "</p>\n";
  }
  else
  {
    // If there are some areas to display, then show the area form
    generate_area_change_form($enabled_areas, $disabled_areas);
  }
}

if ($is_admin)
{
  // New area form
  generate_new_area_form();
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
    $res = db()->query("SELECT * FROM $tbl_room WHERE area_id=? ORDER BY sort_key", array($area));

    if ($res->count() == 0)
    {
      echo "<p>" . get_vocab("norooms") . "</p>\n";
    }
    else
    {
       // Get the information about the fields in the room table
      $fields = db()->field_info($tbl_room);
    
      // Build an array with the room info and also see if there are going
      // to be any rooms to display (in other words rooms if you are not an
      // admin whether any rooms are enabled)
      $rooms = array();
      $n_displayable_rooms = 0;
      for ($i = 0; ($row = $res->row_keyed($i)); $i++)
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

        echo "<th>" . get_vocab("name") . "</th>\n";
        if ($is_admin)
        {
        // Don't show ordinary users the disabled status:  they are only going to see enabled rooms
          echo "<th>" . get_vocab("enabled") . "</th>\n";
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
            echo "<th>$text</th>\n";
          }
        }
        
        if ($is_admin)
        {
          echo "<th>&nbsp;</th>\n";
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
            // We insert an invisible span containing the sort key so that the rooms will
            // be sorted properly
            echo "<td><div>" .
                 "<span>" . htmlspecialchars($r['sort_key']) . "</span>" .
                 "<a title=\"$html_name\" href=\"edit_area_room.php?change_room=1&amp;phase=1&amp;room=" . $r['id'] . "\">$html_name</a>" .
                 "</div></td>\n";
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
                      $html .= htmlspecialchars(utf8_substr($value, 0, $max_content_length));
                      $html .= (utf8_strlen($value) > $max_content_length) ? '&hellip;' : '';
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
    generate_new_room_form();
  }
  echo "</div>\n";
}

output_trailer();

