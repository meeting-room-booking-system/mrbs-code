<?php
namespace MRBS;

require_once MRBS_ROOT . '/auth/cms/joomla.inc';


// Check we've got the right session scheme
if ($auth['session'] !== 'joomla')
{
  die("MRBS configuration error: auth_joomla needs \$auth['session'] set to 'joomla'.");
}

