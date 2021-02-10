<?php
namespace MRBS;

use MRBS\Form\ElementFieldset;
use MRBS\Form\ElementInputSubmit;
use MRBS\Form\ElementP;
use MRBS\Form\FieldDiv;
use MRBS\Form\FieldInputSubmit;
use MRBS\Form\FieldInputText;
use MRBS\Form\Form;

require "defaultincludes.inc";


function generate_add_group_form($error=null, $name=null)
{
  $form = new Form();
  $form->addHiddenInput('action', 'add')
    ->setAttributes(array('action' => multisite('edit_group_handler.php'),
                          'class'  => 'standard',
                          'method' => 'post'));

  // Name field
  $fieldset = new ElementFieldset();

  if (isset($error))
  {
    $field = new FieldDiv();
    $p = new ElementP();
    $p->setText(get_vocab($error, $name))
      ->setAttribute('class', 'error');
    $field->addControlElement($p);
    $fieldset->addElement($field);
  }

  $field = new FieldInputText();
  // Set a pattern as well as required to prevent a string of whitespace
  $field->setLabel(get_vocab('role_name'))
        ->setControlAttributes(array('name'     => 'name',
                                     'required' => true,
                                     'pattern'  => REGEX_TEXT_POS));
  if (null !== ($maxlength = maxlength('group.name')))
  {
    $field->setControlAttribute('maxlength', $maxlength);
  }
  $fieldset->addElement($field);
  $form->addElement($fieldset);

  // Submit button
  $fieldset = new ElementFieldset();
  $field = new FieldDiv();
  $element = new ElementInputSubmit();
  $element->setAttributes(array(
      'name'  => 'button_save',
      'value' => get_vocab('add_group'))
  );
  $field->addControl($element);
  $fieldset->addElement($field);
  $form->addElement($fieldset);

  $form->render();
}


function generate_groups_table()
{
  global $auth;

  $groups = new Groups();

  echo "<table class=\"admin_table display\" id=\"groups\">\n";

  echo "<thead>\n";
  echo "<tr>";
  if ($auth['type'] == 'db')
  {
    echo "<th>";
    // TODO 1. Implement delete button
    // generate_delete_button($group);
    echo "</th>";
  }
  echo "<th>" . htmlspecialchars(get_vocab('group')) . "</th>";
  echo "<th>" . htmlspecialchars(get_vocab('roles')) . "</th>";
  echo "</tr>\n";
  echo "</thead>\n";

  echo "<tbody>\n";

  foreach ($groups as $group)
  {
    echo "<tr>";
    if ($auth['type'] == 'db')
    {
      echo "<td>";
      // TODO 1. Implement delete button
      // generate_delete_button($group);
      echo "</td>";
    }

    echo "<td>";
    $href = multisite(this_page() . '?group_id=' . $group->id);
    echo '<a href="' . htmlspecialchars($href). '">' . htmlspecialchars($group->name) . '</a>';
    echo "</td>";

    echo "<td>";
    $links = array();
    foreach ($group->role_names as $id => $name)
    {
      $links[] = '<a href="' . multisite(htmlspecialchars("edit_role.php?role_id=$id")) . '">' .
                 htmlspecialchars($name) . '</a>';
    }
    echo implode(', ', $links);
    echo "</td>";

    echo "</tr>\n";
  }

  echo "</tbody>\n";
  echo "</table>\n";
}


function generate_edit_group_form(Group $group)
{
  $form = new Form();
  $form->addHiddenInputs(array('group_id' => $group->id))
       ->setAttributes(array('class' => 'standard',
                             'action' => multisite('edit_group_handler.php'),
                             'method' => 'post'));

  $roles = new Roles();
  $form->addElement($roles->getFieldset($group->roles));

  // Submit buttons
  $fieldset = new ElementFieldset();
  $field = new FieldInputSubmit();
  $field->setAttribute('class', 'submit_buttons')
        ->setLabelAttribute('class', 'no_suffix');

  $button = new ElementInputSubmit();
  $button->setAttributes(array('name'           => 'button_back',
                               'value'          => get_vocab('back'),
                               'formnovalidate' => true));

  $field->setAttribute('class', 'submit_buttons')
        ->setLabelAttribute('class', 'no_suffix')
        ->addLabelElement($button)
        ->setControlAttributes(array('class' => 'default_action',
                                     'name'  => 'button_save',
                                     'value' => get_vocab('save')));

  $fieldset->addElement($field);
  $form->addElement($fieldset);

  $form->render();
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

$group_id = get_form_var('group_id', 'int');
$error = get_form_var('error', 'string');
$name = get_form_var('name', 'string');

print_header($context);

if (isset($group_id))
{
  $group = Group::getById($group_id);
}

if (isset($group))
{
  echo "<h2>" . htmlspecialchars(get_vocab('group_heading', $group->name)) . "</h2>";
  generate_edit_group_form($group);
}
else
{
  echo "<h2>" . htmlspecialchars(get_vocab('groups')) . "</h2>";
  if ($auth['type'] == 'db')
  {
    generate_add_group_form($error, $name);
  }
  generate_groups_table();
}

print_footer();
