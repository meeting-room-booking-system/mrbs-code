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
  global $ldap_filter;
  global $ldap_group_member_attrib;
  global $ldap_admin_group_dn;
  global $ldap_email_attrib;
  global $ldap_disable_referrals;

  if (!function_exists("ldap_connect"))
  {
    die("<hr><p><b>ERROR: PHP's 'ldap' extension is not installed/enabled. ".
        "Please check your MRBS and web server configuration.</b></p><hr>\n");
  }

  // Transfer the values from the config variables into a local
  // associative array, turning them all into arrays
  
  $config_items = array(
                        'ldap_host',
                        'ldap_port',
                        'ldap_base_dn',
                        'ldap_user_attrib',
                        'ldap_dn_search_attrib',
                        'ldap_dn_search_dn',
                        'ldap_dn_search_password',
                        'ldap_filter',
                        'ldap_group_member_attrib',
                        'ldap_admin_group_dn',
                        'ldap_v3',
                        'ldap_tls',
                        'ldap_email_attrib',
                        'ldap_disable_referrals'
                       );

  $all_ldap_opts = array();

  
  foreach ($config_items as $item)
  {
    if (!isset($$item))
    {
      continue;
    }
    if (is_array($$item))
    {
      $all_ldap_opts[$item] = $$item;
    }
    // The case where the config item _isn't_ an array is handled
    // further down
  }

  $count = null;
  foreach ($all_ldap_opts as $key => $value)
  {
    if (isset($count))
    {
      if (count($value) != $count)
      {
        authLdapDebug("Count of LDAP array config variables doesn't match, aborting!");
        fatal_error(TRUE,
                    "MRBS configuration error: Count of LDAP array config variables doesn't match, aborting!",
                    false);
        return 0;
      }
    }
    $count = count($value);
  }

  // Turn any non-array config items into arrays in $all_ldap_opts
  if (!isset($count))
  {
    $count = 1;
  }
  foreach ($config_items as $item)
  {
    if (!isset($$item))
    {
      continue;
    }
    if (!is_array($$item))
    {
      $all_ldap_opts[$item] = array_fill(0, $count, $$item);
    }
  }

  foreach ($all_ldap_opts['ldap_host'] as $idx => $host)
  {
    // establish ldap connection
    // the '@' suppresses errors
    if (isset($all_ldap_opts['ldap_port'][$idx]))
    {
      $ldap = @ldap_connect($host, $all_ldap_opts['ldap_port'][$idx]);
    }
    else
    {
      $ldap = @ldap_connect($host);
    }

    // Check that connection was established
    if ($ldap)
    {
      authLdapDebug("authLdapAction: Got LDAP connection");

      if (isset($all_ldap_opts['ldap_v3'][$idx]) &&
          $all_ldap_opts['ldap_v3'][$idx])
      {
        ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
      }
      if (isset($all_ldap_opts['ldap_tls'][$idx]) &&
          $all_ldap_opts['ldap_tls'][$idx])
      {
        ldap_start_tls($ldap);
      }
      if(isset($all_ldap_opts['ldap_disable_referrals'][$idx]) && $all_ldap_opts['ldap_disable_referrals'][$idx])
      {
        // Required to do a search on Active Directory for Win 2003+
        ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);
      }
      
      if (isset($all_ldap_opts['ldap_dn_search_attrib'][$idx]))
      {
        if (isset($all_ldap_opts['ldap_dn_search_dn'][$idx]) &&
            isset($all_ldap_opts['ldap_dn_search_password'][$idx]))
        {
          // Bind with DN and password
          $res = @ldap_bind($ldap, $all_ldap_opts['ldap_dn_search_dn'][$idx],
                            $all_ldap_opts['ldap_dn_search_password'][$idx]);
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
                              $all_ldap_opts['ldap_base_dn'][$idx],
                              "(". $all_ldap_opts['ldap_dn_search_attrib'][$idx] ."=$user)");

          if (@ldap_count_entries($ldap, $res) == 1)
          {
            authLdapDebug("authLdapAction: Found one entry using '".
                          $all_ldap_opts['ldap_dn_search_attrib'][$idx]."'");
            $entries = ldap_get_entries($ldap, $res);
            $dn = $entries[0]["dn"];
            $user_search = "distinguishedName=" . $dn;
          }
          else
          {
            authLdapDebug("authLdapAction: Didn't find entry using '".
                          $all_ldap_opts['ldap_dn_search_attrib'][$idx]."'");
          }
          authLdapDebug("authLdapAction: base_dn '".
                        $all_ldap_opts['ldap_base_dn'][$idx].
                        "' user $user dn $dn");
        }
      }
      else
      {
        // construct dn for user
        $user_search = $all_ldap_opts['ldap_user_attrib'][$idx] . "=" . $user;
        $dn = $user_search . "," . $all_ldap_opts['ldap_base_dn'][$idx];

        authLdapDebug("authLdapAction: Constructed dn '$dn' and ".
                      "user_search '$user_search' using '".
                      $all_ldap_opts['ldap_user_attrib'][$idx]."'");
      }

      foreach ($config_items as $item)
      {
        if (isset($all_ldap_opts[$item][$idx]))
        {
          $object['config'][$item] = $all_ldap_opts[$item][$idx];
        }
      }

      $res = $callback($ldap, $all_ldap_opts['ldap_base_dn'][$idx], $dn,
                       $user_search, $user, $object);
      if ($res)
      {
        return $res;
      }

    } // if ($ldap)

    @ldap_unbind($ldap);
  } // foreach
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
  $email_attrib = $object['config']['ldap_email_attrib'];

  authLdapDebug("authLdapGetEmailCallback: base_dn '$base_dn' dn '$dn' ".
                "user_search '$user_search' user '$user'");

  if ($ldap && $base_dn && $dn && $user_search)
  {
    $res = @ldap_read(
                      $ldap,
                      $dn,
                      "(objectclass=*)",
                      array(strtolower($email_attrib))
                     );
    if (@ldap_count_entries($ldap, $res) > 0)
    {
      authLdapDebug("authLdapGetEmailCallback: search successful");
      $entries = ldap_get_entries($ldap, $res);
      $object['email'] = $entries[0][strtolower($email_attrib)][0];

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
  authLdapDebug("authValidateUserCallback: base_dn '$base_dn' ".
                "dn '$dn' user '$user'");

  $pass = $object['pass'];

  // try an authenticated bind
  // use this to confirm that the user/password pair
  if ($dn && @ldap_bind($ldap, $dn, $pass))
  {
    $filter = $object['config']['ldap_filter'];

    // however if there is a filter check that the
    // user is part of the group defined by the filter
    if (! $filter)
    {
      authLdapDebug("authValidateUserCallback: Successful authenticated ".
                    "bind with no \$ldap_filter");
      return 1;
    }
    else
    {
      authLdapDebug("authValidateUserCallback: Successful authenticated ".
                    "bind checking '$filter'");

      $res = @ldap_read($ldap,
                        $dn,
                        "($filter)",
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
    authLdapDebug("authValidateUserCallback: Bind to '$dn' failed");
  }

  if ($ldap_unbind_between_attempts)
  {
    @ldap_unbind($ldap);
  }

  // return failure if no connection is established
  return 0;
}


/* authLdapCheckAdminGroupCallback(&$ldap, $base_dn, $dn, $user_search,
                            $user, &$object)
 * 
 * Checks if the specified username is in an admin group
 *
 * &$ldap       - Reference to the LDAP object
 * $base_dn     - The base DN
 * $dn          - The user's DN
 * $user_search - The LDAP filter to find the user
 * $user        - The user name
 * &$object     - Reference to the generic object
 * 
 * Returns:
 *   0        - Not in the admin group
 *   non-zero - In the admin group
 */
function authLdapCheckAdminGroupCallback(&$ldap, $base_dn, $dn, $user_search,
                                         $user, &$object)
{
  $admin_group_dn = $object['config']['ldap_admin_group_dn'];
  $group_member_attrib = $object['config']['ldap_group_member_attrib'];

  authLdapDebug("authLdapCheckAdminGroupCallback: base_dn '$base_dn' ".
                "dn '$dn' user_search '$user_search' user '$user'");

  if ($ldap && $base_dn && $dn && $user_search)
  {
  $res = @ldap_read(
                    $ldap,
                    $dn,
                    "(objectclass=*)",
                    array(strtolower($group_member_attrib))
                   );
    if (@ldap_count_entries($ldap, $res) > 0)
    {
      authLdapDebug("authCheckAdminGroupCallback: search successful".
                    " $group_member_attrib");
      $entries = ldap_get_entries($ldap, $res);
      foreach ($entries[0][strtolower($group_member_attrib)] as $group)
      {
        if (strcasecmp($group, $admin_group_dn) == 0)
        {
          return 1;
        }
      }
    }
  }
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
  global $ldap_admin_group_dn;

  $admins = $auth['admin'];
  // User not logged in, user level '0'
  if (!isset($user))
  {
    return 0;
  }
  
  if ($ldap_admin_group_dn)
  {
    $res = authLdapAction("authLdapCheckAdminGroupCallback", $user, $object);
    if ($res)
    {
      return 2;
    }
    else
    {
      return 1;
    }
  }
    
  // Check if the user is an admin
  foreach ($admins as $admin)
  {
    if (strcasecmp($user, $admin) == 0)
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
