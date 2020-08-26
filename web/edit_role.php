<?php
namespace MRBS;

use MRBS\Form\ElementFieldset;
use MRBS\Form\ElementInputSubmit;
use MRBS\Form\ElementP;
use MRBS\Form\FieldDiv;
use MRBS\Form\FieldInputText;
use MRBS\Form\Form;

require "defaultincludes.inc";


function generate_add_role_form($error=null, $name=null)
{
  $form = new Form();
  $form->addHiddenInput('action', 'add')
       ->setAttributes(array('action' => multisite('edit_role_handler.php'),
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
  if (null !== ($maxlength = maxlength('roles.name')))
  {
    $field->setControlAttribute('maxlength', $maxlength);
  }
  $fieldset->addElement($field);
  $form->addElement($fieldset);

  // Submit button
  $fieldset = new ElementFieldset();
  $field = new FieldDiv();
  $element = new ElementInputSubmit();
  $element->setAttribute('value', get_vocab('add_role'));
  $field->addControl($element);
  $fieldset->addElement($field);
  $form->addElement($fieldset);

  $form->render();
}


function generate_add_role_area_button(Role $role)
{
  $form = new Form();
  $form->addHiddenInputs(array('action' => 'add_role_area',
                               'role_id' => $role->id))
       ->setAttributes(array('action' => multisite(this_page()),
                             'class'  => 'standard',
                             'method' => 'post'));

  // Submit button
  $fieldset = new ElementFieldset();
  $field = new FieldDiv();
  $element = new ElementInputSubmit();
  $element->setAttribute('value', get_vocab('add_role_area'));
  $field->addControl($element);
  $fieldset->addElement($field);
  $form->addElement($fieldset);

  $form->render();
}


function generate_add_role_area_form(Role $role)
{
  echo 'Hello';
  $areas = new Areas();
  $options = $areas->getNames(true);
  var_dump($options);
}


function generate_delete_button(Role $role)
{
  $form = new Form();
  $form->setAttributes(array('action' => multisite('edit_role_handler.php'),
                             'method' => 'post'));

  // Hidden inputs
  $form->addHiddenInputs(array(
    'action' => 'delete',
    'role_id' => $role->id
  ));

  // Submit button
  $button = new ElementInputSubmit();
  $message = get_vocab("confirm_del_role", $role->name);
  $button->setAttributes(array(
      'value' => get_vocab('delete'),
      'onclick' => "return confirm('" . escape_js($message) . "');"
    ));

  $form->addElement($button);
  $form->render();
}


function generate_roles_table()
{
  $roles = new Roles();

  echo "<table id=\"roles\">\n";
  echo "<tbody>\n";

  foreach ($roles as $role)
  {
    echo "<tr>";
    echo "<td>";
    generate_delete_button($role);
    echo "</td>";

    echo "<td>";
    $href = multisite(this_page() . '?role_id=' . $role->id);
    echo '<a href="' . htmlspecialchars($href). '">' . htmlspecialchars($role->name) . '</a>';
    echo "</td>";
    echo "</tr>\n";
  }

  echo "</tbody>\n";
  echo "</table>\n";
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

$action = get_form_var('action', 'string');
$error = get_form_var('error', 'string');
$role_id = get_form_var('role_id', 'int');
$name = get_form_var('name', 'string');

print_header($context);

if (isset($role_id))
{
  $role = Role::getById($role_id);
  echo "<h2>" . htmlspecialchars(get_vocab('role_heading', $role->name)) . "</h2>";
  if (isset($action) && ($action == 'add_role_area'))
  {
    generate_add_role_area_form($role);
  }
  else
  {
    generate_add_role_area_button($role);

    $permissions = new AreaPermissions();
    $permissions->getByRole($role);
    var_dump($permissions);
  }
}
else
{
  echo "<h2>" . htmlspecialchars(get_vocab('roles')) . "</h2>";
  generate_add_role_form($error, $name);
  generate_roles_table();
}

print_footer();
