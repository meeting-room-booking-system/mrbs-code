<?php

// Get a minicalendar which is 'relative' months away (typically 1 or -1) from the 'reference',
// which is in YYYY-MM format.

namespace MRBS;

use MRBS\Form\Form;

require '../defaultincludes.inc';

// Check the CSRF token.
Form::checkToken();

$mincal = get_form_var('reference', 'string');
$page = filter_var(get_form_var('page', 'string'), FILTER_SANITIZE_URL);
$relative = get_form_var('relative', 'int', 0);

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

if ($relative < 1)
{
  $mini_calendar->sub($interval);
}
else
{
  $mini_calendar->add($interval);
}

echo $mini_calendar->toHTML(true, $page);
