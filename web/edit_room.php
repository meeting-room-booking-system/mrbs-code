<?php
declare(strict_types=1);
namespace MRBS;

use MRBS\Form\ElementFieldset;
use MRBS\Form\ElementInputSubmit;
use MRBS\Form\ElementP;
use MRBS\Form\FieldInputCheckbox;
use MRBS\Form\FieldInputEmail;
use MRBS\Form\FieldInputNumber;
use MRBS\Form\FieldInputRadioGroup;
use MRBS\Form\FieldInputSubmit;
use MRBS\Form\FieldInputText;
use MRBS\Form\FieldSelect;
use MRBS\Form\FieldTextarea;
use MRBS\Form\Form;

require "defaultincludes.inc";
require_once "mrbs_sql.inc";


// If you want to add some extra columns to the room table to describe the room
// then you can do so and this page should automatically recognise them and handle
// them.    At the moment support is limited to the following column types:
//
// MySQL        PostgreSQL            Form input type
// -----        ----------            ---------------
// bigint       bigint                text
// int          integer               text
// mediumint                          text
// smallint     smallint              checkbox
// tinyint                            checkbox
// text         text                  textarea
// tinytext                           textarea
//              character varying     textarea
// varchar(n)   character varying(n)  text/textarea, depending on the value of n
//              character             text
// char(n)      character(n)          text/textarea, depending on the value of n
//
// NOTE 1: For char(n) and varchar(n) fields, a text input will be presented if
// n is less than or equal to $text_input_max, otherwise a textarea box will be
// presented.
//
// NOTE 2: PostgreSQL booleans are not supported, due to difficulties in
// handling the fields in a database independent way (a PostgreSQL boolean
// will return a PHP boolean type when read by a PHP query, whereas a MySQL
// tinyint returns an int).   In order to have a boolean field in the room
// table you should use a smallint in PostgreSQL or a smallint or a tinyint
// in MySQL.
//
// You can put a description of the column that will be used as the label in
// the form in the $vocab_override variable in the config file using the tag
// 'room.[columnname]'.
//
// For example if you want to add a column specifying whether or not a room
// has a coffee machine you could add a column to the room table called
// 'coffee_machine' of type tinyint, in MySQL, or smallint in PostgreSQL.
// Then in the config file you would add the line
//
// $vocab_override['en']['room.coffee_machine'] = "Coffee machine";  // or appropriate translation
//
// If MRBS can't find an entry for the field in the lang file or vocab overrides, then
// it will use the fieldname, eg 'coffee_machine'.


function get_custom_fields(Room $room)
{
  global $standard_fields, $text_input_max;

  // TODO: have a common way of generating custom fields for all tables

  $result = array();
  $disabled = !is_admin();

  // Get the information about the columns in the room table
  $columns = db()->field_info(_tbl('room'));

  foreach ($columns as $column)
  {
    if (!in_array($column['name'], $standard_fields['room']))
    {
      $label = get_loc_field_name(_tbl('room'), $column['name']);
      $name = $column['name'];
      $value = $room->{$column['name']};

      // Output a checkbox if it's a boolean or integer <= 2 bytes (which we will
      // assume are intended to be booleans)
      if (($column['nature'] == 'boolean') ||
          (($column['nature'] == 'integer') && isset($column['length']) && ($column['length'] <= 2)) )
      {
        $field = new FieldInputCheckbox();
        $field->setLabel($label)
              ->setControlAttributes(array('name'     => $name,
                                           'disabled' => $disabled))
              ->setChecked($value);
      }
      // Output a textarea if it's a character string longer than the limit for a
      // text input
      elseif (($column['nature'] == 'character') && isset($column['length']) && ($column['length'] > $text_input_max))
      {
        $field = new FieldTextarea();
        $field->setLabel($label)
              ->setControlAttributes(array('name'     => $name,
                                           'disabled' => $disabled))
              ->setControlText($value ?? '');
      }
      // Otherwise output a text input
      else
      {
        $field = new FieldInputText();
        $field->setLabel($label)
              ->setControlAttributes(array('name'      => $name,
                                           'value'     => $value,
                                           'maxlength' => maxlength('room.' . $column['name']),
                                           'disabled'  => $disabled));
      }
      $result[] = $field;
    }
  }

  return $result;
}


function get_fieldset_errors(array $errors) : ElementFieldset
{
  $fieldset = new ElementFieldset();
  $fieldset->addLegend('')
           ->setAttribute('class', 'error');

  foreach ($errors as $error)
  {
    $element = new ElementP();
    $element->setText(get_vocab($error));
    $fieldset-> addElement($element);
  }

  return $fieldset;
}


function get_fieldset_general(Room $room) : ElementFieldset
{
  global $auth;

  $disabled = !is_admin();

  $fieldset = new ElementFieldset();

  // The area select
  $areas = new Areas();
  $field = new FieldSelect();
  $field->setLabel(get_vocab('area'))
        ->setControlAttributes(array('name'     => 'new_area',
                                     'disabled' => $disabled))
        ->addSelectOptions($areas->getNames(true), $room->area_id, true);
  $fieldset->addElement($field);

  // Room name
  $field = new FieldInputText();
  $field->setLabel(get_vocab('name'))
        ->setControlAttributes(array('name'      => 'room_name',
                                     'value'     => $room->room_name,
                                     'maxlength' => maxlength('room.room_name'),
                                     'required'  => true,
                                     'disabled'  => $disabled));
  $fieldset->addElement($field);

  // Sort key
  if (is_admin())
  {
    $field = new FieldInputText();
    $field->setLabel(get_vocab('sort_key'))
          ->setLabelAttribute('title', get_vocab('sort_key_note'))
          ->setControlAttributes(array('name'      => 'sort_key',
                                       'value'     => $room->sort_key,
                                       'maxlength' => maxlength('room.sort_key'),
                                       'disabled'  => $disabled));
    $fieldset->addElement($field);
  }

  // Status - Enabled or Disabled
  if (is_admin())
  {
    $options = array('0' => get_vocab('enabled'),
                     '1' => get_vocab('disabled'));
    $value = ($room->isDisabled()) ? '1' : '0';
    $field = new FieldInputRadioGroup();
    $field->setLabel(get_vocab('status'))
          ->setLabelAttributes(array('title' => get_vocab('disabled_room_note')))
          ->addRadioOptions($options, 'disabled', $value, true, $disabled);
    $fieldset->addElement($field);
  }

  // Description
  $field = new FieldInputText();
  $field->setLabel(get_vocab('description'))
        ->setControlAttributes(array('name'      => 'description',
                                     'value'     => $room->description,
                                     'maxlength' => maxlength('room.description'),
                                     'disabled'  => $disabled));
  $fieldset->addElement($field);

  // Capacity
  $field = new FieldInputNumber();
  $field->setLabel(get_vocab('capacity'))
        ->setControlAttributes(array('name'     => 'capacity',
                                     'min'      => '0',
                                     'value'    => $room->capacity,
                                     'disabled' => $disabled));
  $fieldset->addElement($field);

  // Room admin email
  $field = new FieldInputEmail();
  $field->setLabel(get_vocab('room_admin_email'))
        ->setLabelAttribute('title', get_vocab('email_list_note'))
        ->setControlAttributes(array('name'      => 'room_admin_email',
                                     'value'     => $room->room_admin_email,
                                     'multiple'  => true,
                                     'disabled'  => $disabled));
  $fieldset->addElement($field);

  // Invalid types
  $type_options = get_type_options(true);
  if (!empty($type_options))
  {
    $field = new FieldSelect();
    $field->setAttribute('class', 'multiline')
          ->setLabel(get_vocab('invalid_types'))
          ->setLabelAttribute('title', get_vocab('invalid_types_note'))
          ->setControlAttributes(array(
              'name'      => 'invalid_types[]',
              'title'     => get_vocab('select_note'),
              'multiple'  => true)
            )
          ->addSelectOptions($type_options, $room->invalid_types, true);
    $fieldset->addElement($field);
  }

  // The custom HTML
  if (is_admin() && $auth['allow_custom_html'])
  {
    // Only show the raw HTML to admins.  Non-admins will see the rendered HTML
    $field = new FieldTextarea();
    $field->setLabel(get_vocab('custom_html'))
          ->setLabelAttribute('title', get_vocab('custom_html_note'))
          ->setControlAttribute('name', 'custom_html')
          ->setControlText($room->custom_html ?? '');
    $fieldset->addElement($field);
  }

  // Then the custom fields
  $fields = get_custom_fields($room);
  $fieldset->addElements($fields);

  // The Submit and Back buttons
  $field = new FieldInputSubmit();

  $back = new ElementInputSubmit();
  $back->setAttributes(array(
      'value'           => get_vocab('back'),
      'formnovalidate'  => true,
      'formaction'      => multisite('admin.php'))
    );
  $field->setAttribute('class', 'buttons')
        ->addLabelClass('no_suffix')
        ->addLabelElement($back)
        ->setControlAttribute('value', get_vocab('save'));
  if (!is_admin())
  {
    $field->removeControl();
  }
  $fieldset->addElement($field);

  return $fieldset;
}


function generate_room_form($room_id, $errors=null)
{
  global $auth;

  // Get the details for this room
  if (empty($room_id) ||
      is_null($room = Room::getById($room_id)) ||
      !$room->isVisible())
  {
    fatal_error(get_vocab('invalid_room'));
  }

  // Generate the form
  $form = new Form(Form::METHOD_POST);

  $attributes = array(
      'id'     => 'edit_room',
      'class'  => 'standard',
      'action' => multisite('edit_room_handler.php')
    );

  // Non-admins will only be allowed to view room details, not change them
  $legend = (is_admin()) ? get_vocab('editroom') : get_vocab('viewroom');

  $form->setAttributes($attributes)
       ->addHiddenInputs(array(
           'room' => $room->id,
           'area' => $room->area_id,
           'old_area' => $room->area_id,
           'old_room_name' => $room->room_name
         ));

  $outer_fieldset = new ElementFieldset();

  $outer_fieldset->addLegend($legend)
                 ->addElement(get_fieldset_errors($errors))
                 ->addElement(get_fieldset_general($room));

  $form->addElement($outer_fieldset);

  $form->render();

  if ($auth['allow_custom_html'])
  {
    // Now the custom HTML
    echo "<div id=\"div_custom_html\">\n";
    // no escape_html() because we want the HTML!
    echo (isset($room->custom_html)) ? $room->custom_html . "\n" : "";
    echo "</div>\n";
  }
}


// Check the user is authorised for this page
checkAuthorised(this_page());

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

$errors = get_form_var('errors', 'array');
generate_room_form($room, $errors);

print_footer();
