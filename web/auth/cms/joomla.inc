<?php

global $auth;

define('_JEXEC', 1);

$joomla_path = realpath(MRBS_ROOT . '/' . $auth['joomla']['rel_path']);

if ($joomla_path === false)
{
  $message = MRBS_ROOT . '/' . $auth['joomla']['rel_path'] . ' does not exist.  Check the setting ' .
             'of $auth["joomla"]["rel_path"] in your config file.';
  die($message);  // Too early for fatal_error()
}

define('JPATH_BASE', $joomla_path);

require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';
// Something changed at Joomla 4.3.0 - it's not clear what - but adding these
// lines seems to work.  Not the cleanest solution.
// See https://groups.google.com/g/joomla-dev-general/c/55J2s9hhMxA/m/IpBrs3HZAgAJ?utm_medium=email&utm_source=footer&pli=1
// TODO: something better
if (version_compare(JVERSION, '4.3.0', '>='))
{
  require_once JPATH_BASE . '/plugins/authentication/joomla/src/Extension/Joomla.php';
  require_once JPATH_BASE . '/plugins/authentication/cookie/src/Extension/Cookie.php';
  require_once JPATH_BASE . '/plugins/authentication/ldap/src/Extension/Ldap.php';
  require_once JPATH_BASE . '/plugins/authentication/ldap/src/Factory/LdapFactoryInterface.php';
  require_once JPATH_BASE . '/plugins/authentication/ldap/src/Factory/LdapFactory.php';
}
