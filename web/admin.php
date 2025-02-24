<?php
declare(strict_types=1);
namespace MRBS;

use MRBS\Form\ElementButton;
use MRBS\Form\ElementFieldset;
use MRBS\Form\ElementImg;
use MRBS\Form\ElementInputImage;
use MRBS\Form\ElementInputSubmit;
use MRBS\Form\FieldInputEmail;
use MRBS\Form\FieldInputNumber;
use MRBS\Form\FieldInputSubmit;
use MRBS\Form\FieldInputText;
use MRBS\Form\FieldSelect;
use MRBS\Form\Form;


require "defaultincludes.inc";


function generate_room_delete_form(int $room, int $area) : void
{
  $form = new Form(Form::METHOD_POST);

  $attributes = array('action' => multisite('del.php'));

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


function generate_area_change_form(array $enabled_areas, array $disabled_areas) : void
{
  global $area, $day, $month, $year;

  $form = new Form(Form::METHOD_POST);

  $attributes = array('class'  => 'areaChangeForm',
                      'action' => multisite(this_page()));

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
                                 'formaction' => multisite('edit_area.php')))
           ->addElement($img);
    $fieldset->addElement($button);

    $img = new ElementImg();
    $img->setAttributes(array('src'   => 'images/delete.png',
                              'alt'   => get_vocab('delete')));
    $button = new ElementButton();
    $button->setAttributes(array('class'      => 'image',
                                 'title' => get_vocab('delete'),
                                 'formaction' => multisite('del.php?type=area')))
           ->addElement($img);
    $fieldset->addElement($button);
  }

  $form->addElement($fieldset);

  $form->render();
}


function generate_new_area_form() : void
{
  $form = new Form(Form::METHOD_POST);

  $attributes = array('id'     => 'add_area',
                      'class'  => 'form_admin standard',
                      'action' => multisite('add.php'));

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


function generate_new_room_form() : void
{
  global $area;

  $form = new Form(Form::METHOD_POST);

  $attributes = array('id'     => 'add_room',
                      'class'  => 'form_admin standard',
                      'action' => multisite('add.php'));

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


function display_rooms($area_id)
{
  global $max_content_length;

  $rooms = new Rooms($area_id);

  if ($rooms->countVisible(true) == 0)
  {
    echo "<p>" . get_vocab("norooms") . "</p>\n";
  }
  else
  {
    // Get the information about the columns in the room table
    $columns = Columns::getInstance(_tbl(Room::TABLE_NAME));

    // See if there are going to be any rooms to display (in other words rooms if
    // you are not an admin whether any rooms are enabled)
    $n_displayable_rooms = 0;
    foreach ($rooms as $room)
    {
      if (is_admin() || !$room->isDisabled())
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

      echo '<th><span class="normal" data-type="string">' . get_vocab("name") . "</span></th>\n";
      if (is_admin())
      {
        // Don't show ordinary users the disabled status:  they are only going to see enabled rooms
        echo "<th>" . get_vocab("enabled") . "</th>\n";
      }
      // Ignore these columns, either because we don't want to display them,
      // or because we have already displayed them in the header column
      $ignore = array('id', 'area_id', 'room_name', 'disabled', 'sort_key', 'custom_html');

      foreach($columns as $column)
      {
        if (!in_array($column->name, $ignore))
        {
          switch ($column->name)
          {
            // the standard MRBS fields
            case 'description':
            case 'capacity':
            case 'room_admin_email':
            case 'invalid_types':
              $text = get_vocab($column->name);
              break;
            // any user defined fields
            default:
              $text = get_loc_field_name(_tbl('room'), $column->name);
              break;
          }
          // Add a data-type to help JavaScript sort
          if ($column->getNature() == Column::NATURE_CHARACTER)
          {
            $text = '<span class="normal" data-type="string">' . $text . '</span>';
          }
          // We don't use escape_html() here because (a) the column names are
          // trusted and some of them may deliberately contain HTML entities (eg &nbsp;)
          // (b) $text could contain the span above.
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
      foreach ($rooms as $room)
      {
        // Don't show ordinary users disabled or invisible rooms
        if (is_admin() || (!$room->isDisabled() && $room->isVisible()))
        {
          $row_class = ($row_class == "even") ? "odd" : "even";
          echo "<tr class=\"$row_class\">\n";

          $html_name = escape_html($room->room_name);
          $href = multisite('edit_room.php?room=' . $room->id);
          // We insert a data attribute containing the sort key so that the rooms will
          // be sorted properly
          echo '<td data-order="' . escape_html($room->sort_key) . '"><div>' .
            "<a title=\"$html_name\" href=\"" . escape_html($href) . "\">$html_name</a>" .
            "</div></td>\n";
          if (is_admin())
          {
            // Don't show ordinary users the disabled status:  they are only going to see enabled rooms
            echo "<td class=\"boolean\"><div>" . ((!$room->isDisabled()) ? MRBS_HEAVY_CHECK_MARK : '') . "</div></td>\n";
          }
          foreach($columns as $column)
          {
            if (!in_array($column->name, $ignore))
            {
              switch ($column->name)
              {
                // the standard MRBS fields
                case 'description':
                case 'room_admin_email':
                  echo "<td><div>" . escape_html($room->{$column->name} ?? '') . "</div></td>\n";
                  break;
                case 'capacity':
                  $value = $room->{$column->name} ?? '';
                  echo "<td class=\"int\"><div>" . escape_html($value) . "</div></td>\n";
                  break;
                case 'invalid_types':
                  echo "<td><div>" . get_type_names($room->{$column->name}) . "</div></td>\n";
                  break;
                // any user defined fields
                default:
                  if ($column->isBooleanLike())
                  {
                    // booleans: represent by a checkmark
                    echo "<td class=\"boolean\"><div>";
                    echo (!empty($room->{$column->name})) ? MRBS_HEAVY_CHECK_MARK : '';
                    echo "</div></td>\n";
                  }
                  elseif ($column->getNature() == Column::NATURE_INTEGER)
                  {
                    // integer values
                    $value = $room->{$column->name} ?? '';
                    echo "<td class=\"int\"><div>" . escape_html($value) . "</div></td>\n";
                  }
                  elseif ($column->getNature() == Column::NATURE_REAL)
                  {
                    // floats
                    // TODO: check whether these sort properly and, if not, the best way to do so
                    $value = $room->{$column->name} ?? '';
                    echo "<td><div>" . escape_html($value) . "</div></td>\n";
                  }
                  else
                  {
                    // strings
                    $value = $room->{$column->name} ?? '';
                    $html = "<td title=\"" . escape_html($value) . "\"><div>";
                    // Truncate before conversion, otherwise you could chop off in the middle of an entity
                    $html .= escape_html(utf8_substr($value, 0, $max_content_length));
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
            generate_room_delete_form($room->id, $area_id);


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

// Check the CSRF token.
// Only check the token if the page is accessed via a POST request.  Therefore
// this page should not take any action, but only display data.
Form::checkToken(true);

// Check the user is authorised for this page
checkAuthorised(this_page());



// Get non-standard form variables
$error = get_form_var('error', 'string');


$context = array(
    'view'      => $view,
    'view_all'  => $view_all,
    'year'      => $year,
    'month'     => $month,
    'day'       => $day,
    'area'      => $area ?? null,
    'room'      => $room ?? null
  );

print_header($context);

// Get the details we need for this area
if (isset($area))
{
  $area_object = Area::getById($area);
}

// Add in the link for editing the message
if (is_book_admin())
{
  echo "<h2>" . get_vocab("message") . "</h2>\n";
  // Display the message, if any
  $message = Message::getInstance();
  $message->load();
  if ($message->getText() !== '')
  {
    $from_string = $message->getFromLocalString();
    $until_string = $message->getUntilLocalString();
    if (empty($from_string))
    {
      $text = (empty($until_string)) ? get_vocab("this_message") : get_vocab("this_message_until", $until_string);
    }
    else
    {
      $text = (empty($until_string)) ? get_vocab("this_message_from", $from_string) : get_vocab("this_message_from_until", $from_string, $until_string);
    }
    echo '<p>' . escape_html($text) . "</p>\n";
    echo '<p class="message_top">' . $message->getEscapedText() . "</p>\n";
  }
  else
  {
    echo '<p>' . escape_html(get_vocab("no_message")) . "</p>\n";
  }
  // Add an edit button
  $url = 'edit_message.php?' . http_build_query($context,  '', '&');
  $form = new Form(Form::METHOD_POST);
  $form->setAttributes(array(
      'id'     => 'edit_message',
      'action' => multisite($url)
    )
  );
  $submit = new ElementInputSubmit();
  $submit->setAttribute('value', get_vocab('edit_message'));
  $form->addElement($submit);
  $form->render();
}

echo "<h2>" . get_vocab("administration") . "</h2>\n";
if (!empty($error))
{
  echo "<p class=\"error\">" . escape_html(get_vocab($error)) . "</p>\n";
}

// TOP SECTION:  THE FORM FOR SELECTING AN AREA
echo "<div id=\"area_form\">\n";

$areas = new Areas();

$enabled_areas = array();
$disabled_areas = array();

foreach ($areas as $a)
{
  if ($a->isVisible())
  {
    if ($a->isDisabled())
    {
      $disabled_areas[$a->id] = $a->area_name;
    }
    else
    {
      $enabled_areas[$a->id] = $a->area_name;
    }
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
if ($auth['allow_custom_html'] &&
    isset($area_object) &&
    isset($area_object->custom_html) &&
    ($area_object->custom_html !== ''))
{
  echo "<div id=\"div_custom_html\">\n";
  // no escape_html() because we want the HTML!
  echo $area_object->custom_html . "\n";
  echo "</div>\n";
}


// BOTTOM SECTION: ROOMS IN THE SELECTED AREA
// Only display the bottom section if the user is an admin or
// else if there are some areas that can be displayed
if (is_admin() || !empty($enabled_areas))
{
  echo "<h2>\n";
  echo get_vocab("rooms");
  if(isset($area_object))
  {
    echo " " . get_vocab("in") . " " . escape_html($area_object->area_name);
  }
  echo "</h2>\n";

  echo "<div id=\"room_form\">\n";
  if (isset($area))
  {
    display_rooms($area);
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
