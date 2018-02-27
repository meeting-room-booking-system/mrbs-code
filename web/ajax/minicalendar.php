<?php

// Get a set of minicalendars starting at the month which is 'relative' months away (typically 1 or -1)
// from the 'reference', which is in YYYY-MM format, and continuing for 'length' months

namespace MRBS;

use MRBS\Form\Form;

require '../defaultincludes.inc';

// Check the CSRF token.
Form::checkToken();

// Check the user is authorised for this page
checkAuthorised();

$mincal = get_form_var('reference', 'string');
$page = filter_var(get_form_var('page', 'string'), FILTER_SANITIZE_URL);
$relative = get_form_var('relative', 'int', 0);
$length = get_form_var('length', 'int', 1);

// Validate the mincal input
if (isset($mincal))
{
  $date = DateTime::createFromFormat('Y-m', $mincal);
  if (($date === false) || array_sum($date->getLastErrors()))
  {
    $mincal = null;
  }
}

$mini_calendar = new MiniCalendar($view, $year, $month, $day, $area, $room, $mincal);

$interval = 'P' . abs($relative) . 'M';

for ($i=0; $i<$length; $i++)
{
  if ($relative < 1)
  {
    $mini_calendar->sub($interval);
  }
  else
  {
    $mini_calendar->add($interval);
  }
  echo $mini_calendar->toHTML(true, $page);
}
