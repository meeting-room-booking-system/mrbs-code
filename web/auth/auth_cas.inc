<?php

// Initialise CAS
phpCAS::client(CAS_VERSION_2_0,'secure.its.yale.edu',443,'/cas');
phpCAS::setNoCasServerValidation();


/** 
 * Checks if the specified username/password pair are valid.
 *
 * For this authentication scheme always validates.
 * 
 * @param   string  $user   The user name
 * @param   string  $pass   The password
 * @return  int             non-zero - Always valid
 */
 
function authValidateUser($user, $pass)
{
if (phpCAS::isAuthenticated()){
    return 1;
   } 
  else
  {
    return 0;
  }  
}

/* authGetUserLevel($user)
 * 
 * Determines the users access level
 * 
 * $user - The user name
 *
 * Returns:
 *   The users access level
 */
function authGetUserLevel($user)
{
  global $auth;
  $admins = $auth['admin'];
  // User not logged in, user level '0'
  if (!isset($user))
  {
    return 0;
  }

  // Check if the user is can modify
  for ($i = 0; isset($admins[$i]); $i++)
  {
    if (strcasecmp($user, $admins[$i]) == 0)
    {
      return 2;
    }
  }

  // Everybody else is access level '1'
  return 1;
}

?>
