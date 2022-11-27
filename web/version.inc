<?php
namespace MRBS;

// This should probably be a class, but you can only have protected
// class members in PHP 5, so we won't bother

function get_mrbs_version() : string
{
  // Read the actual version number from a much simpler file
  require_once MRBS_ROOT . '/version_num.inc';

  global $git_command;

  $mrbs_version = "MRBS $mrbs_version_number";

  if (function_exists('exec') && !function_disabled('exec'))
  {
    // Suppress any errors because we are only interested in success
    $git_out = @exec("$git_command git rev-parse --abbrev-ref HEAD", $output, $retval);

    if (($retval == 0) && (strlen($git_out)))
    {
      $git_out2 = @exec("$git_command git rev-parse --short HEAD", $output, $retval);

      if (($retval == 0) && (strlen($git_out2)))
      {
        $mrbs_version .= "+git ($git_out $git_out2)";
      }
    }
  }

  return $mrbs_version;
}
