<?php

global $auth;

$moodle_dir = realpath(MRBS_ROOT . '/' . $auth['moodle']['rel_path']);

if ($moodle_dir === false)
{
  $message = MRBS_ROOT . '/' . $auth['moodle']['rel_path'] . ' does not exist.  Check the setting ' .
             'of $auth["moodle"]["rel_path"] in your config file.';
  die($message);  // Too early for fatal_error()
}
