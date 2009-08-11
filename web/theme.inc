<?php

// $Id$

// Gets the styling appropriate to the theme.     If there isn't a styling file
// present for the theme then the default styling is used.

require_once "systemdefaults.inc.php";
require_once "config.inc.php";
require_once "Themes/default/styling.inc";
@include_once "Themes/$theme/styling.inc";   // overwrite the default with the specified styling if it exists

// Do some consistency checking of user settings from config.inc.php
// (and redefine $max_slots if we're using periods)
if ($enable_periods)
{
  $max_slots = count($periods);  // We know exactly how many we need
  if (count($periods) > 60)
  {
    die('Configuration error: too many periods defined');
  }
}
else
{
  $start_first_slot = ($morningstarts*60) + $morningstarts_minutes;   // minutes
  $start_last_slot  = ($eveningends*60) + $eveningends_minutes;       // minutes
  $start_difference = ($start_last_slot - $start_first_slot) * 60;    // seconds
  if (($start_difference < 0) or ($start_difference%$resolution != 0))
  {
    die('Configuration error: start and end of day incorrectly defined');
  }
}
?>
