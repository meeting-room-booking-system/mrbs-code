<?php
namespace MRBS;

use \phpCAS;

// Initialise CAS

if ($auth['cas']['debug'])
{
  phpCAS::setDebug();
  phpCAS::setVerbose(true);
}

phpCAS::client(CAS_VERSION_2_0,
               $auth['cas']['host'],
               (int) $auth['cas']['port'],
               $auth['cas']['context']);

if ($auth['cas']['no_server_validation'])
{
  phpCAS::setNoCasServerValidation();
}
elseif (!empty($auth['cas']['ca_cert_path']))
{
  phpCAS::setCasServerCACert($auth['cas']['ca_cert_path']);
}

// Handle incoming logout requests
if (empty($auth['cas']['real_hosts']))
{
  phpCAS::handleLogoutRequests();
}
else
{
  phpCAS::handleLogoutRequests(true, $auth['cas']['real_hosts']);
}

// Set the language
// (The language constants will only be defined after the first call to a phpCAS method)
$cas_lang_map = array(
    'ca' => PHPCAS_LANG_CATALAN,
    'de' => PHPCAS_LANG_GERMAN,
    'el' => PHPCAS_LANG_GREEK,
    'en' => PHPCAS_LANG_ENGLISH,
    'es' => PHPCAS_LANG_SPANISH,
    'fr' => PHPCAS_LANG_FRENCH,
    'ja' => PHPCAS_LANG_JAPANESE,
    'zh' => PHPCAS_LANG_CHINESE_SIMPLIFIED
  );
  
$locale = \Locale::parseLocale(get_lang());
if (isset($cas_lang_map[$locale['language']]))
{
  phpCAS::setLang($cas_lang_map[$locale['language']]);
}


/* authValidateUser($user, $pass)
 * 
 * Checks if the specified username/password pair are valid
 * 
 * $user  - The user name
 * $pass  - The password
 * 
 * Returns:
 *   false    - The pair are invalid or do not exist
 *   string   - The validated username
 */
function authValidateUser($user, $pass)
{
  if (phpCAS::isAuthenticated())
  {
    return $user;
  } 
  
  return false;
}


/* authGetUserLevel($user)
 * 
 * Determines the user's access level
 * 
 * $user - The user name
 *
 * Returns:
 *   The user's access level
 */
function authGetUserLevel($user)
{
  global $auth;

  // User not logged in, user level '0'
  if(!isset($user))
  {
    return 0;
  }

  // Check whether the user is an admin
  foreach ($auth['admin'] as $admin)
  {
    if(strcasecmp($user, $admin) === 0)
    {
      return 2;
    }
  }

  // Everybody else is access level '1'
  return 1;
}


// Gets the user's email address.   Returns an empty
// string if one can't be found
function authGetUserEmail($user)
{
  if (!isset($user) || $user === '')
  {
    return '';
  }
  
  return get_default_email($user);
}
