<?php
namespace MRBS;

require_once MRBS_ROOT . '/auth/cms/joomla.inc';
require_once MRBS_ROOT . '/auth/functions_auth.inc';

// Check we've got the right session scheme
if ($auth['session'] !== 'joomla')
{
  die("MRBS configuration error: auth_joomla needs \$auth['session'] set to 'joomla'.");
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
 *   true     - The user has been validated and logged in
 */
function authValidateUser($user, $pass)
{
      $mainframe = JFactory::getApplication('site');
   
      return $mainframe->login(array('username' => $user,
                                     'password' => $pass));
}

