<?php

global $auth;

define('_JEXEC', 1);

$joomla_path = realpath(MRBS_ROOT . DS . $auth['joomla']['rel_path']);

if ($joomla_path === false)
{
  $message = MRBS_ROOT . DS . $auth['joomla']['rel_path'] . ' does not exist.  Check the setting ' .
             'of $auth["joomla"]["rel_path"] in your config file.';
  die($message);  // Too early for fatal_error()
}

define('JPATH_BASE', $joomla_path);

require_once JPATH_BASE . DS . 'includes' . DS . 'defines.php';
require_once JPATH_BASE . DS . 'includes' . DS . 'framework.php';

