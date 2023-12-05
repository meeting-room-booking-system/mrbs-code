<?php
declare(strict_types=1);
namespace MRBS;

use MRBS\Form\ElementFieldset;
use MRBS\Form\Form;

require 'defaultincludes.inc';

// Check the user is authorised for this page
checkAuthorised(this_page());

$context = array(
  'view'      => $view,
  'view_all'  => $view_all,
  'year'      => $year,
  'month'     => $month,
  'day'       => $day,
  'area'      => $area,
  'room'      => isset($room) ? $room : null
);

print_header($context);

$form = new Form();

$form->setAttributes(array(
  'class'  => 'standard',
  'id'     => 'message',
  'action' => multisite('edit_message_handler.php'),
  'method' => 'post')
);

$fieldset = new ElementFieldset();
$fieldset->addLegend(get_vocab('edit_message'));

$form->addElement($fieldset);

$form->render();

print_footer();
