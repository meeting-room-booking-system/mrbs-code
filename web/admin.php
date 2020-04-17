<?php
namespace MRBS;

use MRBS\Form\Form;
use MRBS\Form\ElementButton;
use MRBS\Form\ElementFieldset;
use MRBS\Form\ElementImg;
use MRBS\Form\ElementInputImage;
use MRBS\Form\FieldInputEmail;
use MRBS\Form\FieldInputNumber;
use MRBS\Form\FieldInputText;
use MRBS\Form\FieldInputSubmit;
use MRBS\Form\FieldSelect;


require "defaultincludes.inc";


function generate_room_delete_form($room, $area)
{
  $form = new Form();

  $attributes = array('action' => multisite('del.php'),
                      'method' => 'post');
                      
  $form->setAttributes($attributes);
  
  // Hidden inputs
  $hidden_inputs = array('type' => 'room',
                         'area' => $area,
                         'room' => $room);
  $form->addHiddenInputs($hidden_inputs);
  
  // The button
  $element = new ElementInputImage();
  $element->setAttributes(array('class'  => 'button',
                                'src'    => 'images/delete.png',
                                'width'  => '16',
                                'height' => '16',
                                'title'  => get_vocab('delete'),
                                'alt'    => get_vocab('delete')));
  $form->addElement($element);

  $form->render();
}


function generate_area_change_form($enabled_areas, $disabled_areas)
{
  global $area, $day, $month, $year;
  
  $form = new Form();
  
  $attributes = array('class'  => 'areaChangeForm',
                      'action' => multisite(this_page()),
                      'method' => 'post');
                      
  $form->setAttributes($attributes);
  
  // Hidden inputs for page day, month, year
  $hidden_inputs = array('day'   => $day,
                         'month' => $month,
                         'year'  => $year);
  $form->addHiddenInputs($hidden_inputs);

  // Now the visible fields
  $fieldset = new ElementFieldset();
  $fieldset->addLegend('');
  
  // The area select
  if (is_admin())
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
        ->addSelectOptions($options, $area, true);
  $fieldset->addElement($field);

  // The change area button (won't be needed or displayed if JavaScript is enabled)
  $field = new FieldInputSubmit();
  $field->setAttribute('class', 'js_none')
        ->setControlAttributes(array('value' => get_vocab('change'),
                                     'name'  => 'change'));
  $fieldset->addElement($field);
  
  // If they're an admin then give them edit and delete buttons for the area
  if (is_admin())
  {
    $img = new ElementImg();
    $img->setAttributes(array('src'   => 'images/edit.png',
                              'alt'   => get_vocab('edit')));
    $button = new ElementButton();
    $button->setAttributes(array('class'      => 'image',
                                 'title' => get_vocab('edit'),
                                 'formaction' => 'edit_area.php'))
           ->addElement($img);
    $fieldset->addElement($button);
    
    $img = new ElementImg();
    $img->setAttributes(array('src'   => 'images/delete.png',
                              'alt'   => get_vocab('delete')));
    $button = new ElementButton();
    $button->setAttributes(array('class'      => 'image',
                                 'title' => get_vocab('delete'),
                                 'formaction' => 'del.php?type=area'))
           ->addElement($img);
    $fieldset->addElement($button);
  }
  
  $form->addElement($fieldset);

  $form->render();
}


function generate_new_area_form()
{
  $form = new Form();
  
  $attributes = array('id'     => 'add_area',
                      'class'  => 'form_admin standard',
                      'action' => multisite('add.php'),
                      'method' => 'post');
                      
  $form->setAttributes($attributes);
  
  // Hidden field for the type of operation
  $form->addHiddenInput('type', 'area');
  
  // Now the visible fields
  $fieldset = new ElementFieldset();
  $fieldset->addLegend(get_vocab('addarea'));
  
  // The name field
  $field = new FieldInputText();
  $field->setLabel(get_vocab('name'))
        ->setControlAttributes(array('id'        => 'area_name',
                                     'name'      => 'name',
                                     'required'  => true,
                                     'maxlength' => maxlength('area.area_name')));               
  $fieldset->addElement($field);
  
  // The submit button
  $field = new FieldInputSubmit();
  $field->setControlAttributes(array('value' => get_vocab('addarea'),
                                     'class' => 'submit'));
  $fieldset->addElement($field);
  
  $form->addElement($fieldset);
  
  $form->render();
}


function generate_new_room_form()
{
  global $area;
  
  $form = new Form();
  
  $attributes = array('id'     => 'add_room',
                      'class'  => 'form_admin standard',
                      'action' => multisite('add.php'),
                      'method' => 'post');
                      
  $form->setAttributes($attributes);

  // Hidden inputs
  $hidden_inputs = array('type' => 'room',
                         'area' => $area);
  $form->addHiddenInputs($hidden_inputs);
  
  // Visible fields
  $fieldset = new ElementFieldset();
  $fieldset->addLegend(get_vocab('addroom'));
  
  // The name field
  $field = new FieldInputText();
  $field->setLabel(get_vocab('name'))
        ->setControlAttributes(array('id'        => 'room_name',
                                     'name'      => 'name',
                                     'required'  => true,
                                     'maxlength' => maxlength('room.room_name')));               
  $fieldset->addElement($field);
  
  // The description field
  $field = new FieldInputText();
  $field->setLabel(get_vocab('description'))
        ->setControlAttributes(array('id'        => 'room_description',
                                     'name'      => 'description',
                                     'maxlength' => maxlength('room.description')));               
  $fieldset->addElement($field);
   
  // Capacity
  $field = new FieldInputNumber();
  $field->setLabel(get_vocab('capacity'))
        ->setControlAttributes(array('name' => 'capacity',
                                     'min'  => '0'));
  $fieldset->addElement($field);
        
  // The email field
  $field = new FieldInputEmail();
  $field->setLabel(get_vocab('room_admin_email'))
        ->setLabelAttribute('title', get_vocab('email_list_note'))
        ->setControlAttributes(array('id'       => 'room_admin_email',
                                     'name'     => 'room_admin_email',
                                     'multiple' => true));           
  $fieldset->addElement($field);
  
  // The submit button
  $field = new FieldInputSubmit();
  $field->setControlAttributes(array('value' => get_vocab('addroom'),
                                     'class' => 'submit'));
  $fieldset->addElement($field);
      
  $form->addElement($fieldset);
  
  $form->render();
}


// Check the CSRF token.
// Only check the token if the page is accessed via a POST request.  Therefore
// this page should not take any action, but only display data.
Form::checkToken($post_only=true);

// Check the user is authorised for this page
checkAuthorised(this_page());



// Get non-standard form variables
$error = get_form_var('error', 'string');

print_header($view, $view_all, $year, $month, $day, isset($area) ? $area : null, isset($room) ? $room : null);

// Get the details we need for this area
if (isset($area))
{
  $res = db()->query("SELECT area_name, custom_html FROM $tbl_area WHERE id=? LIMIT 1", array($area));

  if ($res->count() == 1)
  {
    $row = $res->next_row_keyed();
    $area_name = $row['area_name'];
    $custom_html = $row['custom_html'];
  }
}


echo "<h2>" . get_vocab("administration") . "</h2>\n";
if (!empty($error))
{
  echo "<p class=\"error\">" . htmlspecialchars(get_vocab($error)) . "</p>\n";
}

// TOP SECTION:  THE FORM FOR SELECTING AN AREA
echo "<div id=\"area_form\">\n";

$sql = "SELECT id, area_name, disabled
          FROM $tbl_area
      ORDER BY disabled, sort_key";
$res = db()->query($sql);

$enabled_areas = array();
$disabled_areas = array();

while (false !== ($row = $res->next_row_keyed()))
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
  if (!is_admin() && empty($enabled_areas))
  {
    echo "<p>" . get_vocab("noareas_enabled") . "</p>\n";
  }
  else
  {
    // If there are some areas to display, then show the area form
    generate_area_change_form($enabled_areas, $disabled_areas);
  }
}

if (is_admin())
{
  // New area form
  generate_new_area_form();
}
echo "</div>";  // area_form


// Now the custom HTML
if ($auth['allow_custom_html'])
{
  echo "<div id=\"div_custom_html\">\n";
  // no htmlspecialchars() because we want the HTML!
  echo (isset($custom_html)) ? "$custom_html\n" : "";
  echo "</div>\n";
}


// BOTTOM SECTION: ROOMS IN THE SELECTED AREA
// Only display the bottom section if the user is an admin or
// else if there are some areas that can be displayed
if (is_admin() || !empty($enabled_areas))
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
    $rooms = get_rooms($area, true);

    if (count($rooms) == 0)
    {
      echo "<p>" . get_vocab("norooms") . "</p>\n";
    }
    else
    {
       // Get the information about the fields in the room table
      $fields = db()->field_info($tbl_room);
    
      // See if there are going to be any rooms to display (in other words rooms if
      // you are not an admin whether any rooms are enabled)
      $n_displayable_rooms = 0;
      foreach ($rooms as $r)
      {
        if (is_admin() || !$r['disabled'])
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
        if (is_admin())
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
        
        if (is_admin())
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
          if (is_admin() || !$r['disabled'])
          {
            $row_class = ($row_class == "even") ? "odd" : "even";
            echo "<tr class=\"$row_class\">\n";

            $html_name = htmlspecialchars($r['room_name']);
            // We insert an invisible span containing the sort key so that the rooms will
            // be sorted properly
            echo "<td><div>" .
                 "<span>" . htmlspecialchars($r['sort_key']) . "</span>" .
                 "<a title=\"$html_name\" href=\"edit_room.php?room=" . $r['id'] . "\">$html_name</a>" .
                 "</div></td>\n";
            if (is_admin())
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
            
            // Give admins a delete button
            if (is_admin())
            {
              echo "<td>\n<div>\n";
              generate_room_delete_form($r['id'], $area);


              echo "</div>\n</td>\n";
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
  if (is_admin() && $areas_defined && !empty($area))
  {
    generate_new_room_form();
  }
  echo "</div>\n";
}

print_footer();
