<?php

// $Id$

// This should probably be a class, but you can only have protected
// class members in PHP 5, so we won't bother

require_once('version_num.inc');

function get_mrbs_version()
{
  global $hg_command;
  global $mrbs_version_number, $mrbs_hg_id, $mrbs_hg_branch;

  // MRBS developers, make sure to update this string before each release
  $mrbs_version = "MRBS $mrbs_version_number";

  if (function_exists('exec') && !function_disabled('exec'))
  {
    $hg_id_out = exec("$hg_command id --id --branch", $output, $retval);

    if (($retval == 0) && (strlen($hg_id_out)))
    {
      $mrbs_version .= "+hg ($hg_id_out)";
    }
  }

  return $mrbs_version;
}
