<?php

// $Id$

/* ~~JFL 2003/11/12 By default, use the http session mechanism */
if (!isset($auth['session']))
{
  $auth['session']='http';
}

/* authLdapAction($callback, $user, &$object)
 * 
 * Connects/binds to all configured LDAP servers/base DNs and
 * then performs a callback, passing the LDAP object, $base_dn,
 * user DN (in $dn), $user and a generic object $object
 *
 * $callback - The callback function
 * $user     - The user name
 * &$object  - Reference to the generic object, type defined by caller
 * 
 * Returns:
 *   0        - The pair are invalid or do not exist
 *   non-zero - The pair are valid
 */
function authLdapAction($callback, $user, &$object)
{
  global $auth;
  global $ldap_host;
  global $ldap_port;
  global $ldap_v3;
  global $ldap_tls;
  global $ldap_base_dn;
  global $ldap_user_attrib;
  global $ldap_dn_search_attrib;
  global $ldap_dn_search_dn;
  global $ldap_dn_search_password;

  if (!function_exists("ldap_connect"))
  {
    die("<hr><p><b>ERROR: PHP's 'ldap' extension is not installed/enabled. ".
        "Please check your MRBS and web server configuration.</b></p><hr>\n");
  }

  $all_ldap_base_dn     = array();
  $all_ldap_user_attrib = array();

  // Check that if there is an array of hosts and an array of ports
  // then the number of each must be the same or the authenication
  // is forced to fail.
  if (is_array( $ldap_base_dn ) && is_array( $ldap_user_attrib ) &&
      count($ldap_user_attrib) != count($ldap_base_dn) )
  {
    return 0;
  }

  // Transfer the based dn(s) to an new value to ensure that
  // an array is always used.
  // If a single value is passed then turn it into an array
  if (is_array( $ldap_base_dn ) )
  {
    $all_ldap_base_dn = $ldap_base_dn;
  }
  else
  {
    $all_ldap_base_dn = array($ldap_base_dn);
  }

  // Transfer the array of user attributes to a new value.
  // Create an array of the user attributes to match the number of
  // base dn's if a single user attribute has been passed.
  if (is_array( $ldap_user_attrib ) )
  {
    $all_ldap_user_attrib = $ldap_user_attrib;
  }
  else
  {
    while ( each($all_ldap_base_dn ) )
    {
      $all_ldap_user_attrib[] = $ldap_user_attrib;
    }
  }

  // establish ldap connection
  // the '@' suppresses errors
  if (isset($ldap_port))
  {
    $ldap = @ldap_connect($ldap_host, $ldap_port);
  }
  else
  {
    $ldap = @ldap_connect($ldap_host);
  }

  // Check that connection was established
  if ($ldap)
  {
    authLdapDebug("authLdapAction: Got LDAP connection");

    if ($ldap_v3)
    {
      ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
    }
    if ($ldap_tls)
    {
      ldap_start_tls($ldap);
    }

    // now process all base dn's until authentication is achieved
    // or fail
    foreach ( $all_ldap_base_dn as $idx => $base_dn)
    {
      authLdapDebug("authLdapAction: start of foreach $base_dn");
      if (isset($ldap_dn_search_attrib))
      {
        if (isset($ldap_dn_search_dn) &&
            isset($ldap_dn_search_password))
        {
          // Bind with DN and password
          $res = @ldap_bind($ldap, $ldap_dn_search_dn,
                            $ldap_dn_search_password);
        }
        else
        {
          // Anonymous bind
          $res = @ldap_bind($ldap);
        }
        authLdapDebug("authLdapAction: Result of initial bind is $res");

        if ($res)
        {
          $res = @ldap_search($ldap,
                              $base_dn,
                              "(". $ldap_dn_search_attrib ."=$user)");

          if (@ldap_count_entries($ldap, $res) == 1)
          {
            authLdapDebug("authLdapAction: Found one entry using ".
                          "$ldap_dn_search_attrib");
            $entries = ldap_get_entries($ldap, $res);
            $dn = $entries[0]["dn"];
            $user_search = "distinguishedName=" . $dn;
          }
          else
          {
            authLdapDebug("authLdapAction: Didn't find entry using ".
                          "$ldap_dn_search_attrib");
          }
          authLdapDebug("authLdapAction: base_dn $base_dn user $user ".
                        "dn $dn");
        }
      }
      else
      {
        // construct dn for user
        $user_search = $all_ldap_user_attrib[$idx] . "=" . $user;
        $dn = $user_search . "," . $base_dn;

        authLdapDebug("authLdapAction: Constructed dn $dn and ".
                      "user_search $user_search using ".
                      "\$ldap_user_attrib");
      }

      $res = $callback($ldap, $base_dn, $dn, $user_search, $user, $object);
      if ($res)
      {
        return $res;
      }

    } // foreach

    @ldap_unbind($ldap);
  } // if ($ldap)
}


/* authLdapGetEmail($user)
 * 
 * Gets the email address of the user from LDAP
 * 
 * $user  - The user name
 * 
 * Returns:
 *   The user's email address or ''
 */
function authLdapGetEmail($user)
{
  $email = '';
  $object = array();

  $res = authLdapAction("authLdapGetEmailCallback", $user, $object);

  if ($res)
  {
    $email = $object['email'];
  }
  return $email;
}


/* authLdapGetEmailCallback(&$ldap, $base_dn, $dn, $user_search,
                            $user, &$object)
 * 
 * Checks if the specified username/password pair are valid
 *
 * &$ldap       - Reference to the LDAP object
 * $base_dn     - The base DN
 * $dn          - The user's DN
 * $user_search - The LDAP filter to find the user
 * $user        - The user name
 * &$object     - Reference to the generic object
 * 
 * Returns:
 *   0        - Didn't find a user
 *   non-zero - Found a user
 */
function authLdapGetEmailCallback(&$ldap, $base_dn, $dn, $user_search,
                                  $user, &$object)
{
  global $ldap_email_attrib;

  authLdapDebug("authLdapGetEmailCallback: base_dn $base_dn dn $dn ".
                "user_search $user_search user $user");

  if ($ldap && $base_dn && $dn && $user_search)
  {
	$res = @ldap_read($ldap,
                        $dn,
                        "(objectclass=*)",
                        array($ldap_email_attrib)
                       );
    if (@ldap_count_entries($ldap, $res) > 0)
    {
      authLdapDebug("authLdapGetEmailCallback: search successful");
      $entries = ldap_get_entries($ldap, $res);
      $object['email'] = $entries[0][$ldap_email_attrib][0];

      authLdapDebug("authLdapGetEmailCallback: email is '".
                    $object['email']."'");
      
      return 1;
    }
  }
  return 0;
}

/* authValidateUser($user, $pass)
 * 
 * Checks if the specified username/password pair are valid
 * 
 * $user  - The user name
 * $pass  - The password
 * 
 * Returns:
 *   0        - The pair are invalid or do not exist
 *   non-zero - The pair are valid
 */
function authValidateUser($user, $pass)
{
  // Check if we do not have a username/password
  // User can always bind to LDAP anonymously with empty password,
  // therefore we need to block empty password here...
  if (!isset($user) || !isset($pass) || strlen($pass)==0)
  {
    authLdapDebug("Empty username or password passed");
    return 0;
  }

  $object = array();
  $object['pass'] = $pass;

  return authLdapAction("authValidateUserCallback", $user, $object);
}


/* authValidateUserCallback(&$ldap, $base_dn, $dn, $user_search,
                            $user, &$object)
 * 
 * Checks if the specified username/password pair are valid
 *
 * &$ldap       - Reference to the LDAP object
 * $base_dn     - The base DN
 * $dn          - The user's DN
 * $user_search - The LDAP filter to find the user
 * $user        - The user name
 * &$object     - Reference to the generic object
 * 
 * Returns:
 *   0        - Didn't find a user
 *   non-zero - Found a user
 */
function authValidateUserCallback(&$ldap, $base_dn, $dn, $user_search,
                                  $user, &$object)
{
  global $ldap_filter;

  authLdapDebug("authValidateUserCallback: base_dn $base_dn dn $dn user $user");

  $pass = $object['pass'];

  // try an authenticated bind
  // use this to confirm that the user/password pair
  if ($dn && @ldap_bind($ldap, $dn, $pass))
  {
    // however if there is a filter check that the
    // user is part of the group defined by the filter
    if (! $ldap_filter)
    {
      authLdapDebug("authValidateUserCallback: Successful authenticated ".
                    "bind with no \$ldap_filter");
      return 1;
    }
    else
    {
      authLdapDebug("authValidateUserCallback: Successful authenticated ".
                    "bind checking $ldap_filter");

      $res = @ldap_read($ldap,
                        $dn,
                        "($ldap_filter)",
                        array()
                       );
      if (@ldap_count_entries($ldap, $res) > 0)
      {
        authLdapDebug("authValidateUserCallback: Found entry with filter");
        return 1;
      }
      authLdapDebug("authValidateUserCallback: No entry found with filter");
    }
  }
  else
  {
    authLdapDebug("authValidateUserCallback: Bind to $dn failed");
  }

  if ($ldap_unbind_between_attempts)
  {
    @ldap_unbind($ldap);
  }

  // return failure if no connection is established
  return 0;
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
  for ($i = 0; $admins[$i]; $i++)
  {
    if (strcasecmp($user, $admins[$i]) == 0)
    {
      return 2;
    }
  }
    
  // Everybody else is access level '1'
  return 1;
}

/* authLdapDebug($message)
 *
 * Output LDAP debugging, if the configuration variable
 * $ldap_debug is true.
 *
 */
function authLdapDebug($message)
{
  global $ldap_debug;

  if ($ldap_debug)
  {
    error_log($message);
  }
}

?>
