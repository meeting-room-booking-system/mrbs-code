<?php
namespace MRBS;



define('LDAP_SUCCESS',                        0x00);
define('LDAP_OPERATIONS_ERROR',               0x01);
define('LDAP_PROTOCOL_ERROR',                 0x02);
define('LDAP_TIMELIMIT_EXCEEDED',             0x03);
define('LDAP_SIZELIMIT_EXCEEDED',             0x04);
define('LDAP_COMPARE_FALSE',                  0x05);
define('LDAP_COMPARE_TRUE',                   0x06);
define('LDAP_AUTH_METHOD_NOT_SUPPORTED',      0x07);
define('LDAP_STRONG_AUTH_REQUIRED',           0x08);
// Not used in LDAPv3
define('LDAP_PARTIAL_RESULTS',                0x09);

// Next 5 new in LDAPv3
define('LDAP_REFERRAL',                       0x0a);
define('LDAP_ADMINLIMIT_EXCEEDED',            0x0b);
define('LDAP_UNAVAILABLE_CRITICAL_EXTENSION', 0x0c);
define('LDAP_CONFIDENTIALITY_REQUIRED',       0x0d);
define('LDAP_SASL_BIND_INPROGRESS',           0x0e);

define('LDAP_NO_SUCH_ATTRIBUTE',              0x10);
define('LDAP_UNDEFINED_TYPE',                 0x11);
define('LDAP_INAPPROPRIATE_MATCHING',         0x12);
define('LDAP_CONSTRAINT_VIOLATION',           0x13);
define('LDAP_TYPE_OR_VALUE_EXISTS',           0x14);
define('LDAP_INVALID_SYNTAX',                 0x15);

define('LDAP_NO_SUCH_OBJECT',                 0x20);
define('LDAP_ALIAS_PROBLEM',                  0x21);
define('LDAP_INVALID_DN_SYNTAX',              0x22);
// Next two not used in LDAPv3',
define('LDAP_IS_LEAF',                        0x23);
define('LDAP_ALIAS_DEREF_PROBLEM',            0x24);

define('LDAP_INAPPROPRIATE_AUTH',             0x30);
define('LDAP_INVALID_CREDENTIALS',            0x31);
define('LDAP_INSUFFICIENT_ACCESS',            0x32);
define('LDAP_BUSY',                           0x33);
define('LDAP_UNAVAILABLE',                    0x34);
define('LDAP_UNWILLING_TO_PERFORM',           0x35);
define('LDAP_LOOP_DETECT',                    0x36);

define('LDAP_SORT_CONTROL_MISSING',           0x3C);
define('LDAP_INDEX_RANGE_ERROR',              0x3D);

define('LDAP_NAMING_VIOLATION',               0x40);
define('LDAP_OBJECT_CLASS_VIOLATION',         0x41);
define('LDAP_NOT_ALLOWED_ON_NONLEAF',         0x42);
define('LDAP_NOT_ALLOWED_ON_RDN',             0x43);
define('LDAP_ALREADY_EXISTS',                 0x44);
define('LDAP_NO_OBJECT_CLASS_MODS',           0x45);
define('LDAP_RESULTS_TOO_LARGE',              0x46);
// Next two for LDAPv3
define('LDAP_AFFECTS_MULTIPLE_DSAS',          0x47);
define('LDAP_OTHER',                          0x50);

// Used by some APIs
define('LDAP_SERVER_DOWN',                    0x51);
define('LDAP_LOCAL_ERROR',                    0x52);
define('LDAP_ENCODING_ERROR',                 0x53);
define('LDAP_DECODING_ERROR',                 0x54);
define('LDAP_TIMEOUT',                        0x55);
define('LDAP_AUTH_UNKNOWN',                   0x56);
define('LDAP_FILTER_ERROR',                   0x57);
define('LDAP_USER_CANCELLED',                 0x58);
define('LDAP_PARAM_ERROR',                    0x59);
define('LDAP_NO_MEMORY',                      0x5a);

// Preliminary LDAPv3 codes
define('LDAP_CONNECT_ERROR',                  0x5b);
define('LDAP_NOT_SUPPORTED',                  0x5c);
define('LDAP_CONTROL_NOT_FOUND',              0x5d);
define('LDAP_NO_RESULTS_RETURNED',            0x5e);
define('LDAP_MORE_RESULTS_TO_RETURN',         0x5f);
define('LDAP_CLIENT_LOOP',                    0x60);
define('LDAP_REFERRAL_LIMIT_EXCEEDED',        0x61);


/* authLdapAction($callback, $user, &$object)
 * 
 * Connects/binds to all configured LDAP servers/base DNs and
 * then performs a callback, passing the LDAP object, $base_dn,
 * user DN (in $dn), $user and a generic object $object
 *
 * $callback   - The callback function
 * $user       - The user name
 * &$object    - Reference to the generic object, type defined by caller
 * $keep_going - Don't stop when a user has been found, but keep going through all the LDAP
 *               hosts.  Useful, for example, when you want to get a list of all users.
 * 
 * Returns:
 *   false    - The pair are invalid or do not exist
 *   string   - The validated username
 */
function authLdapAction($callback, $user, &$object, $keep_going=false)
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
  global $ldap_name_attrib;
  global $ldap_disable_referrals;
  global $ldap_deref;
  global $ldap_filter_base_dn;
  global $ldap_filter_user_attr;

  if (!function_exists("ldap_connect"))
  {
    die("<hr><p><b>ERROR: PHP's 'ldap' extension is not installed/enabled. ".
        "Please check your MRBS and web server configuration.</b></p><hr>\n");
  }

  $ns_callback = __NAMESPACE__ . "\\$callback";
  
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
                        'ldap_name_attrib',
                        'ldap_disable_referrals',
                        'ldap_deref',
                        'ldap_filter_base_dn',
                        'ldap_filter_user_attr',
                        'ldap_client_cert',
                        'ldap_client_key'
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
        authLdapDebug("count of LDAP array config variables doesn't match, aborting!");
        fatal_error("MRBS configuration error: Count of LDAP array config variables doesn't match, aborting!");
        return false;
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
    if (isset($all_ldap_opts['ldap_port'][$idx]))
    {
      $ldap = ldap_connect($host, $all_ldap_opts['ldap_port'][$idx]);
    }
    else
    {
      $ldap = ldap_connect($host);
    }
    
    // Check that connection was established
    if ($ldap)
    {
      authLdapDebug("got LDAP connection");

      if (isset($all_ldap_opts['ldap_deref'][$idx]))
      {
        ldap_set_option($ldap, LDAP_OPT_DEREF, $all_ldap_opts['ldap_deref'][$idx]);
      }
      if (isset($all_ldap_opts['ldap_v3'][$idx]) &&
          $all_ldap_opts['ldap_v3'][$idx])
      {
        ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
      }
      if (isset($all_ldap_opts['ldap_client_cert'][$idx]) &&
          $all_ldap_opts['ldap_client_cert'][$idx])
      {
        // Requires PHP 7.1.0 or later
        ldap_set_option($ldap, LDAP_OPT_X_TLS_CERTFILE, $all_ldap_opts['ldap_client_cert'][$idx]);
      }
      if (isset($all_ldap_opts['ldap_client_key'][$idx]) &&
          $all_ldap_opts['ldap_client_key'][$idx])
      {
        // Requires PHP 7.1.0 or later
        ldap_set_option($ldap, LDAP_OPT_X_TLS_KEYFILE, $all_ldap_opts['ldap_client_key'][$idx]);
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
          authLdapDebug("binding with search_dn and search_password");
          $res = mrbs_ldap_bind($ldap, $all_ldap_opts['ldap_dn_search_dn'][$idx],
                                $all_ldap_opts['ldap_dn_search_password'][$idx]);
        }
        else
        {
          // Anonymous bind
          authLdapDebug("binding anonymously");
          $res = mrbs_ldap_bind($ldap);
        }

        if (!$res)
        {
          authLdapDebug("initial bind failed: ". mrbs_ldap_error($ldap));
        }
        else
        {
          authLdapDebug("initial bind was successful");

          $base_dn = $all_ldap_opts['ldap_base_dn'][$idx];
          $filter = "(" . $all_ldap_opts['ldap_dn_search_attrib'][$idx] . "=$user)";

          authLdapDebug("searching using base_dn '$base_dn' and filter '$filter'");
          $res = ldap_search($ldap, $base_dn, $filter);
                             
          if ($res === false)
          {
            authLdapDebug("ldap_search failed: ". mrbs_ldap_error($ldap));
          }                           
          else
          {
            if (ldap_count_entries($ldap, $res) == 1)
            {
              $entries = ldap_get_entries($ldap, $res);
              $dn = $entries[0]["dn"];
              $user_search = "distinguishedName=" . $dn;
              authLdapDebug("found one entry dn '$dn'");
            }
            else
            {
              authLdapDebug(ldap_count_entries($ldap, $res) . " entries found, no unique dn");
            }
          }
        }
      }
      else
      {
        // construct dn for user
        $user_search = $all_ldap_opts['ldap_user_attrib'][$idx] . "=" . $user;
        $dn = $user_search . "," . $all_ldap_opts['ldap_base_dn'][$idx];

        authLdapDebug("constructed dn '$dn' and ".
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

      if (empty($dn))
      {
        authLdapDebug("no DN determined, not calling callback");
        // If we are keeping going we want to be able to search all the LDAP
        // directories, so we return false if any one of them fails.
        if ($keep_going)
        {
          return false;
        }
      }
      else
      {
        $res = $ns_callback($ldap, $all_ldap_opts['ldap_base_dn'][$idx], $dn,
                            $user_search, $user, $object);
        if ($res && !$keep_going)
        {
          return $user;
        }
      }

    } // if ($ldap)
    
    ldap_unbind($ldap);
  } // foreach

  return ($keep_going) ? true : false;
}



function mrbs_ldap_escape ($value, $ignore='', $flags=0)
{
  if (function_exists('ldap_escape'))
  {
    return ldap_escape($value, $ignore, $flags);
  }
  else
  {
    return $value;
  }
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
    $calling_function = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];
    error_log("$calling_function: $message");
  }
}


// A wrapper for ldap_bind() that optionally suppresses "invalid credentials" errors.
function mrbs_ldap_bind ($link_identifier, $bind_rdn=null, $bind_password=null)
{
  global $ldap_suppress_invalid_credentials;
  
  // Suppress all errors and then look to see what the error was and then
  // trigger the error again, depending on config settings.
  $result = @ldap_bind($link_identifier, $bind_rdn, $bind_password);
  
  if (!$result)
  {
    $errno = ldap_errno($link_identifier);
    if (!$ldap_suppress_invalid_credentials || ($errno != LDAP_INVALID_CREDENTIALS))
    {
      trigger_error(ldap_err2str($errno), E_USER_WARNING);
    }
  }
 
  return $result;
}


// Adds extra diagnostic information to ldap_error()
function mrbs_ldap_error($link_identifier)
{
  $result = ldap_error($link_identifier);
  if (ldap_get_option($link_identifier, LDAP_OPT_DIAGNOSTIC_MESSAGE, $err) &&
      isset($err) && ($err !== ''))
  {
    $result .= " [$err]";
  }
  return $result;
}
