<?php
namespace MRBS;

require_once MRBS_ROOT . '/auth/cms/wordpress.inc';


// Check we've got the right session scheme
if ($auth['session'] !== 'wordpress')
{
  die("MRBS configuration error: auth_wordpress needs \$auth['session'] set to 'wordpress'.");
}

