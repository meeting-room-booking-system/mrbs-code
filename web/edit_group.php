<?php
namespace MRBS;

use MRBS\Form\ElementFieldset;
use MRBS\Form\ElementInputSubmit;
use MRBS\Form\FieldInputSubmit;
use MRBS\Form\Form;

require "defaultincludes.inc";

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
    $role_name_list = implode(', ', $group->role_names);
    echo htmlspecialchars($role_name_list);
    echo "</td>";

    echo "</tr>\n";
  }

  echo "</tbody>\n";
  echo "</table>\n";
}


function generate_edit_group_form(Group $group)
{
  $form = new Form();
  $form->setAttributes(array(
      'class' => 'standard',
      'action' => multisite('edit_group_handler.php'),
      'method' => 'post'
    ));

  $fieldset = new ElementFieldset();
  $roles = new Roles();
  $fieldset->addElement($roles->getFormField($group->roles));
  $form->addElement($fieldset);

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
  generate_groups_table();
}

print_footer();
