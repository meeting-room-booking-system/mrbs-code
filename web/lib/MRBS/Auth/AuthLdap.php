<?php
namespace MRBS\Auth;

use MRBS\User;


class AuthLdap extends Auth
{
  
  private static $all_ldap_opts;
  private static $config_items;
  
  
  public function __construct()
  {
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
    
    // Check that ldap is installed
    if (!function_exists('ldap_connect'))
    {
      die("<hr><p><b>ERROR: PHP's 'ldap' extension is not installed/enabled. ".
          "Please check your MRBS and web server configuration.</b></p><hr>\n");
    }
    
    // Transfer the values from the config variables into a local
    // associative array, turning them all into arrays
    self::$config_items = array('ldap_host',
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
                                'ldap_filter_user_attr');

    self::$all_ldap_opts = array();
    
    // Get the array items (we'll handle the non-array items in a moment) and check
    // that they all have the same length
    $count = null;
    
    foreach (self::$config_items as $item)
    {
      if (isset($$item) && is_array($$item))
      {
        self::$all_ldap_opts[$item] = $$item;
        if (isset($count))
        {
          if (count($$item) != $count)
          {
            fatal_error("MRBS configuration error: Count of LDAP array config variables doesn't match, aborting!");
          }
        }
        else
        {
          $count = count($$item);
        }
      }
    }
    
    // Turn any non-array config items into arrays
    if (!isset($count))
    {
      $count = 1;
    }
    
    foreach (self::$config_items as $item)
    {
      if (isset($$item) && !is_array($$item))
      {
        self::$all_ldap_opts[$item] = array_fill(0, $count, $$item);
      }
    }

  }
  
  
  public function getUser($username)
  {
    $user = new User($username);
    
    $user->display_name = self::getDisplayName($username);
    
    
    return $user;
  }
  
  
  // Get the display name of the user from LDAP.  If none, returns the username
  private static function getDisplayName($username)
  {
    if (!isset($username) || ($username === ''))
    {
      return $username;
    }
    
    $object = array();
    
    $res = self::action('getNameCallback', $username, $object);

    return ($res) ? $object['name'] : $username;
  }
  
  
  /* action($callback, $username, &$object)
   * 
   * Connects/binds to all configured LDAP servers/base DNs and
   * then performs a callback, passing the LDAP object, $base_dn,
   * user DN (in $dn), $username and a generic object $object
   *
   * $callback - The callback function
   * $username - The user name
   * &$object  - Reference to the generic object, type defined by caller
   * 
   * Returns:
   *   false    - The pair are invalid or do not exist
   *   string   - The validated username
   */
  public static function action($callback, $username, &$object)
  {
    $method = __METHOD__;
    
    foreach (self::$all_ldap_opts['ldap_host'] as $idx => $host)
    {
      // establish ldap connection
      if (isset(self::$all_ldap_opts['ldap_port'][$idx]))
      {
        $ldap = ldap_connect($host, self::$all_ldap_opts['ldap_port'][$idx]);
      }
      else
      {
        $ldap = ldap_connect($host);
      }

      // Check that connection was established
      if ($ldap)
      {
        self::debug("$method: got LDAP connection");

        if (isset(self::$all_ldap_opts['ldap_deref'][$idx]))
        {
          ldap_set_option($ldap, LDAP_OPT_DEREF, self::$all_ldap_opts['ldap_deref'][$idx]);
        }
        
        if (isset(self::$all_ldap_opts['ldap_v3'][$idx]) &&
            self::$all_ldap_opts['ldap_v3'][$idx])
        {
          ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
        }
        
        if (isset(self::$all_ldap_opts['ldap_tls'][$idx]) &&
            self::$all_ldap_opts['ldap_tls'][$idx])
        {
          ldap_start_tls($ldap);
        }
        
        if(isset(self::$all_ldap_opts['ldap_disable_referrals'][$idx]) && self::$all_ldap_opts['ldap_disable_referrals'][$idx])
        {
          // Required to do a search on Active Directory for Win 2003+
          ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);
        }
        
        if (isset(self::$all_ldap_opts['ldap_dn_search_attrib'][$idx]))
        {
          if (isset(self::$all_ldap_opts['ldap_dn_search_dn'][$idx]) &&
              isset(self::$all_ldap_opts['ldap_dn_search_password'][$idx]))
          {
            // Bind with DN and password
            $res = self::ldapBind($ldap, self::$all_ldap_opts['ldap_dn_search_dn'][$idx],
                                  self::$all_ldap_opts['ldap_dn_search_password'][$idx]);
          }
          else
          {
            // Anonymous bind
            $res = self::ldapBind($ldap);
          }

          if (!$res)
          {
            self::debug("$method: initial bind failed: " . ldap_error($ldap));
          }
          else
          {
            self::debug("$method: initial bind was successful");

            $res = ldap_search($ldap,
                               self::$all_ldap_opts['ldap_base_dn'][$idx],
                               "(" . self::$all_ldap_opts['ldap_dn_search_attrib'][$idx] . "=$username)");

            if (ldap_count_entries($ldap, $res) == 1)
            {
              self::debug("$method: found one entry using '" .
                          self::$all_ldap_opts['ldap_dn_search_attrib'][$idx] . "'");
              $entries = ldap_get_entries($ldap, $res);
              $dn = $entries[0]["dn"];
              $user_search = "distinguishedName=" . $dn;
            }
            else
            {
              self::debug("$method: didn't find entry using '" .
                          self::$all_ldap_opts['ldap_dn_search_attrib'][$idx] . "'");
            }
            self::debug("$method: base_dn '" .
                        self::$all_ldap_opts['ldap_base_dn'][$idx] .
                        "' user '$username' dn '$dn'");
          }
        }
        else
        {
          // construct dn for user
          $user_search = self::$all_ldap_opts['ldap_user_attrib'][$idx] . "=" . $username;
          $dn = $user_search . "," . self::$all_ldap_opts['ldap_base_dn'][$idx];

          self::debug("$method: constructed dn '$dn' and " .
                      "user_search '$user_search' using '" .
                      self::$all_ldap_opts['ldap_user_attrib'][$idx] . "'");
        }

        foreach (self::$config_items as $item)
        {
          if (isset(self::$all_ldap_opts[$item][$idx]))
          {
            $object['config'][$item] = self::$all_ldap_opts[$item][$idx];
          }
        }

        if (!$dn)
        {
          self::debug("$method: no DN determined, not calling callback");
        }
        else
        {
          $res = self::$callback($ldap, self::$all_ldap_opts['ldap_base_dn'][$idx], $dn,
                                 $user_search, $username, $object);
          if ($res)
          {
            return $username;
          }
        }

      } // if ($ldap)

      ldap_unbind($ldap);
    } // foreach
    
    return false;
  }
  
  
  /* getNameCallback(&$ldap, $base_dn, $dn, $user_search,
                     $username, &$object)
   *
   * Get the name of a found user
   *
   * &$ldap       - Reference to the LDAP object
   * $base_dn     - The base DN
   * $dn          - The user's DN
   * $user_search - The LDAP filter to find the user
   * $username    - The user name
   * &$object     - Reference to the generic object
   *
   * Returns:
   *   false    - Didn't find a user
   *   true     - Found a user
   */
  private static function getNameCallback(&$ldap, $base_dn, $dn, $user_search,
                                          $username, &$object)
  {
    $method = __METHOD__;
    $name_attrib = $object['config']['ldap_name_attrib'];

    self::debug("$method: base_dn '$base_dn' dn '$dn' " .
                "user_search '$user_search' user '$username'");

    if ($ldap && $base_dn && $dn && $user_search)
    {
      $res = ldap_read($ldap,
                       $dn,
                       "(objectclass=*)",
                       array(\MRBS\utf8_strtolower($name_attrib)) );
      
      if (ldap_count_entries($ldap, $res) > 0)
      {
        self::debug("$method: search successful");
        $entries = ldap_get_entries($ldap, $res);
        $object['name'] = $entries[0][\MRBS\utf8_strtolower($name_attrib)][0];

        self::debug("$method: name is '" . $object['name'] . "'");

        return true;
      }
    }
    return false;
  }


  // A wrapper for ldap_bind() that optionally suppresses "invalid credentials" errors.
  private static function ldapBind ($link_identifier, $bind_rdn=null, $bind_password=null)
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


  /* debug($message)
   *
   * Output LDAP debugging, if the configuration variable
   * $ldap_debug is true.
   *
   */
  private static function debug($message)
  {
    global $ldap_debug;

    if ($ldap_debug)
    {
      error_log($message);
    }
  }
  
}
