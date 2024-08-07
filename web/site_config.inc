<?php
declare(strict_types=1);
namespace MRBS;

require_once 'grab_globals.inc.php';

global $multisite;

if ($multisite)
{
  $site = get_form_var('site', 'string', $default_site ?? '');
  if ($site !== '')
  {
    $site_config = __DIR__ . "/sites/$site/config.inc.php";
    if (is_readable($site_config))
    {
      require_once $site_config;
    }
    else
    {
      http_response_code(403);
      exit;
    }
  }
}
