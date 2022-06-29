<?php
namespace MRBS\Auth;

use MRBS\Exception;
use MRBS\User;
use function MRBS\fatal_error;
use function MRBS\get_microtime;
use function MRBS\in_arrayi;
use function MRBS\session;
use function MRBS\utf8_strtolower;


class AuthLdap extends Auth
{
  // LDAP ERROR CODES
  const LDAP_SUCCESS =                        0x00;
  const LDAP_OPERATIONS_ERROR =               0x01;
  const LDAP_PROTOCOL_ERROR =                 0x02;
  const LDAP_TIMELIMIT_EXCEEDED =             0x03;
  const LDAP_SIZELIMIT_EXCEEDED =             0x04;
  const LDAP_COMPARE_FALSE =                  0x05;
  const LDAP_COMPARE_TRUE =                   0x06;
  const LDAP_AUTH_METHOD_NOT_SUPPORTED =      0x07;
  const LDAP_STRONG_AUTH_REQUIRED =           0x08;
  // Not used in LDAPv3
  const LDAP_PARTIAL_RESULTS =                0x09;

  // Next 5 new in LDAPv3
  const LDAP_REFERRAL =                       0x0a;
  const LDAP_ADMINLIMIT_EXCEEDED =            0x0b;
  const LDAP_UNAVAILABLE_CRITICAL_EXTENSION = 0x0c;
  const LDAP_CONFIDENTIALITY_REQUIRED =       0x0d;
  const LDAP_SASL_BIND_INPROGRESS =           0x0e;

  const LDAP_NO_SUCH_ATTRIBUTE =              0x10;
  const LDAP_UNDEFINED_TYPE =                 0x11;
  const LDAP_INAPPROPRIATE_MATCHING =         0x12;
  const LDAP_CONSTRAINT_VIOLATION =           0x13;
  const LDAP_TYPE_OR_VALUE_EXISTS =           0x14;
  const LDAP_INVALID_SYNTAX =                 0x15;

  const LDAP_NO_SUCH_OBJECT =                 0x20;
  const LDAP_ALIAS_PROBLEM =                  0x21;
  const LDAP_INVALID_DN_SYNTAX =              0x22;
  // Next two not used in LDAPv3 =
  const LDAP_IS_LEAF =                        0x23;
  const LDAP_ALIAS_DEREF_PROBLEM =            0x24;

  const LDAP_INAPPROPRIATE_AUTH =             0x30;
  const LDAP_INVALID_CREDENTIALS =            0x31;
  const LDAP_INSUFFICIENT_ACCESS =            0x32;
  const LDAP_BUSY =                           0x33;
  const LDAP_UNAVAILABLE =                    0x34;
  const LDAP_UNWILLING_TO_PERFORM =           0x35;
  const LDAP_LOOP_DETECT =                    0x36;

  const LDAP_SORT_CONTROL_MISSING =           0x3C;
  const LDAP_INDEX_RANGE_ERROR =              0x3D;

  const LDAP_NAMING_VIOLATION =               0x40;
  const LDAP_OBJECT_CLASS_VIOLATION =         0x41;
  const LDAP_NOT_ALLOWED_ON_NONLEAF =         0x42;
  const LDAP_NOT_ALLOWED_ON_RDN =             0x43;
  const LDAP_ALREADY_EXISTS =                 0x44;
  const LDAP_NO_OBJECT_CLASS_MODS =           0x45;
  const LDAP_RESULTS_TOO_LARGE =              0x46;
  // Next two for LDAPv3
  const LDAP_AFFECTS_MULTIPLE_DSAS =          0x47;
  const LDAP_OTHER =                          0x50;

  // Used by some APIs
  const LDAP_SERVER_DOWN =                    0x51;
  const LDAP_LOCAL_ERROR =                    0x52;
  const LDAP_ENCODING_ERROR =                 0x53;
  const LDAP_DECODING_ERROR =                 0x54;
  const LDAP_TIMEOUT =                        0x55;
  const LDAP_AUTH_UNKNOWN =                   0x56;
  const LDAP_FILTER_ERROR =                   0x57;
  const LDAP_USER_CANCELLED =                 0x58;
  const LDAP_PARAM_ERROR =                    0x59;
  const LDAP_NO_MEMORY =                      0x5a;

  // Preliminary LDAPv3 codes
  const LDAP_CONNECT_ERROR =                  0x5b;
  const LDAP_NOT_SUPPORTED =                  0x5c;
  const LDAP_CONTROL_NOT_FOUND =              0x5d;
  const LDAP_NO_RESULTS_RETURNED =            0x5e;
  const LDAP_MORE_RESULTS_TO_RETURN =         0x5f;
  const LDAP_CLIENT_LOOP =                    0x60;
  const LDAP_REFERRAL_LIMIT_EXCEEDED =        0x61;

  // Default ports
  const DEFAULT_PORT_LDAP  = 389;
  const DEFAULT_PORT_LDAPS = 636;

  private static $all_ldap_opts;
  private static $config_items;
  private static $profile_clock;


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
          "Please check your web server configuration.</b></p><hr>\n");
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
                                'ldap_filter_user_attr',
                                'ldap_client_cert',
                                'ldap_client_key'
                                );

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


  /* validateUser($user, $pass)
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
  public function validateUser(?string $user, ?string $pass)
  {
    // Check if we do not have a username/password
    // User can always bind to LDAP anonymously with empty password,
    // therefore we need to block empty password here...
    if (!isset($user) || !isset($pass) || strlen($pass)==0)
    {
      self::debug('empty username or password passed');
      return false;
    }

    $object = array();
    $object['pass'] = $pass;

    return ($this->action('validateUserCallback', $user, $object)) ? $user : false;
  }


  /* validateUserCallback(&$ldap, $base_dn, $dn, $user_search,
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
   *   false      - Didn't find a user
   *   true       - Found a user
   */
  private static function validateUserCallback(&$ldap, $base_dn, $dn, $user_search,
                                               $user, &$object)
  {
    self::debug("base_dn '$base_dn' dn '$dn' user '$user'");

    $pass = $object['pass'];

    // try an authenticated bind
    // use this to confirm that the user/password pair
    if ($dn && self::ldapBind($ldap, $dn, $pass))
    {
      // however if there is a filter check that the
      // user is part of the group defined by the filter
      if (!isset($object['config']['ldap_filter']) || ($object['config']['ldap_filter'] === ''))
      {
        self::debug("successful authenticated bind with no \$ldap_filter");
        return true;
      }
      else
      {
        // If we've got a search DN and password, then bind again using those credentials because
        // it's possible that the user doesn't have read access in the directory, even for their own
        // entry, in which case we'll get a "No such object" result.
        if (isset($object['config']['ldap_dn_search_dn']) &&
          isset($object['config']['ldap_dn_search_password']))
        {
          self::debug("rebinding as '" . $object['config']['ldap_dn_search_dn'] . "'");
          if (!self::ldapBind($ldap, $object['config']['ldap_dn_search_dn'], $object['config']['ldap_dn_search_password']))
          {
            self::debug("rebinding failed: " . self::ldapError($ldap));
            return false;
          }
          self::debug('rebinding successful');
        }

        $filter = $object['config']['ldap_filter'];

        self::debug("successful authenticated bind checking '$filter'");

        // If ldap_filter_base_dn is set, set the filter to search for the user
        // in the given base_dn (OpenLDAP).  If not, read from the user
        // attribute (AD)
        if (isset($object['config']['ldap_filter_base_dn']))
        {
          $f = "(&(".
            $object['config']['ldap_filter_user_attr'].
            "=$user)($filter))";
          $filter_dn = $object['config']['ldap_filter_base_dn'];
          $call = 'ldap_search';
        }
        else
        {
          $f = "($filter)";
          $filter_dn = $dn;
          $call = 'ldap_read';
        }

        self::debug("trying filter: $f: dn: $filter_dn: method: $call");

        $res = $call(
          $ldap,
          $filter_dn,
          $f,
          array()
        );
        if (ldap_count_entries($ldap, $res) > 0)
        {
          self::debug('found entry with filter');
          return true;
        }
        self::debug('no entry found with filter');
      }
    }
    else
    {
      self::debug("bind to '$dn' failed: ". self::ldapError($ldap));
    }

    // return failure if no connection is established
    return false;
  }


  protected function getUserFresh(string $username) : ?User
  {
    if (!isset($username) || ($username === ''))
    {
      return null;
    }

    $object = array();

    $res = $this->action('getUserCallback', $username, $object);
    if (!$res || !isset($object['user']))
    {
      return null;
    }

    // Use $object['user']['username'] rather than $username because they won't necessarily be
    // the same.  See https://sourceforge.net/p/mrbs/bugs/518/
    $user = parent::getUserFresh($object['user']['username']);
    $keys = array('display_name', 'email', 'level');

    foreach ($keys as $key)
    {
      if (isset($object['user'][$key]))
      {
        $user->$key = $object['user'][$key];
      }
    }

    return $user;
  }


  /* getUserCallback(&$ldap, $base_dn, $dn, $user_search,
                     $username, &$object)
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
  private static function getUserCallback(&$ldap, $base_dn, $dn, $user_search,
                                          $user, &$object)
  {
    global $ldap_get_user_email, $ldap_debug_attributes;

    self::debug("base_dn '$base_dn' dn '$dn' user_search '$user_search' user '$user'");

    if (!$ldap || !$base_dn || !$dn || !$user_search)
    {
      self::debug("invalid parameters, could not call ldap_read, returning false");
      return false;
    }

    $attributes = self::getAttributes($object, $ldap_get_user_email, true);

    self::resetProfileClock();
    // We suppress the errors because it's possible to get a "No such object" error if
    // the DN doesn't exist - which it won't if (a) we're searching an array of LDAP hosts
    // or (b) the DN has been deleted since the booking was made.   But check the error
    // code afterwards and trigger an error if it was any other kind of error.
    $res = @ldap_read(
        $ldap,
        $dn,
        "(objectclass=*)",
        array_values($attributes),
        0,
        1
      );
    $t = self::getProfileClock();

    if ($res === false)
    {
      self::debug("ldap_read() failed: " . self::ldapError($ldap));
      if (self::LDAP_NO_SUCH_OBJECT !== ($errno = ldap_errno($ldap)))
      {
        if ($errno === self::LDAP_SUCCESS)
        {
          // The errno is reporting success, but that's just the ldap errno.  If ldap_read()
          // doesn't get as far as interrogating the directory, it will return false,
          // but ldap_errno() will return success. To get more details try temporarily
          // removing the error suppression operator ('@').
          $message = "ldap_read() failed, not due to an LDAP error but probably due " .
                     "to an initialization error such as 'Array initialization wrong'";
        }
        else
        {
          $message = ldap_err2str($errno);
        }
        trigger_error($message, E_USER_WARNING);
      }
      return false;
    }

    $n_entries = ldap_count_entries($ldap, $res);

    if ($n_entries === false)
    {
      self::debug("No entries found - ldap_count_entries() error");
      return false;
    }

    self::debug("$n_entries entries found");

    if ($n_entries === 0)
    {
      return false;
    }

    self::debug("ldap_read() succeeded, taking $t seconds");

    if ($ldap_debug_attributes)
    {
      // Repeat the read, this time fetching all the attributes and then write
      // the attributes and their values to the debug log.  Useful for discovering
      // attribute names.
      $res2 = @ldap_read($ldap, $dn, "(objectclass=*)", [], 0, 1);
      $entry = ldap_first_entry($ldap, $res2);
      $attribute = ldap_first_attribute($ldap, $entry);
      while ($attribute)
      {
        $values = ldap_get_values($ldap, $entry, $attribute);
        unset($values['count']);  // We don't need this element
        self::debug("Attribute: \"$attribute\"; Value(s): \"" . implode('", "', $values) . '"');
        $attribute = ldap_next_attribute($ldap, $entry);
      }
    }

    $entry = ldap_first_entry($ldap, $res);
    $user = self::getResult($ldap, $entry, $attributes);

    if (!isset($user['username']))
    {
      $message = 'No username found.  Check the value of $ldap_user_attrib in the MRBS config file.';
      $message .= " It is currently set to '" . $object['config']['ldap_user_attrib'] . "'.";
      self::debug($message);
      return false;
    }

    if (!isset($user['display_name']))
    {
      $user['display_name'] = $user['username'];
    }

    if (isset($user['groups']))
    {
      if (isset($object['config']['ldap_admin_group_dn']))
      {
        $user['level'] = in_arrayi($object['config']['ldap_admin_group_dn'], $user['groups']) ? 2 : 1;
      }
    }

    self::debug("User '" . $user['username'] . "' found");
    $object['user'] = $user;
    return true;
  }


  public function getUsernames()
  {
    $mrbs_user = session()->getCurrentUser();

    if (!isset($mrbs_user))
    {
      return false;
    }

    $object = array();
    $object['users'] = array();
    $users = array();

    $res = $this->action('getUsernamesCallback', $mrbs_user->username, $object, true);

    if ($res === false)
    {
      trigger_error("MRBS: could not get LDAP usernames.", E_USER_WARNING);
      return false;
    }

    if (isset($object['users']))
    {
      $users = $object['users'];
    }

    self::sortUsers($users);

    return $users;
  }


  private static function getUsernamesCallback(&$ldap, $base_dn, $dn, $user_search,
                                                       $user, &$object)
  {
    self::debug("base_dn '$base_dn'");

    if (!$ldap || !$base_dn || !isset($object['config']['ldap_user_attrib']))
    {
      self::debug("invalid parameters, could not call ldap_search, returning false");
      return false;
    }

    if (isset($object['config']['ldap_filter']))
    {
      $filter = $object['config']['ldap_filter'];
    }
    else
    {
      $filter = 'objectclass=*';
    }
    $filter = "($filter)";

    // Form the attributes
    $username_attrib = utf8_strtolower($object['config']['ldap_user_attrib']);
    $attributes = array($username_attrib);

    // The display name attribute might not have been set in the config file
    if (isset($object['config']['ldap_name_attrib']))
    {
      $display_name_attrib = utf8_strtolower($object['config']['ldap_name_attrib']);
      $attributes[] = $display_name_attrib;
    }

    self::debug("searching with base_dn '$base_dn' and filter '$filter'");
    self::resetProfileClock();
    $res = ldap_search($ldap, $base_dn, $filter, $attributes);
    $t = self::getProfileClock();

    if ($res == false)
    {
      self::debug("ldap_search failed: " . self::ldapError($ldap));
      return false;
    }

    self::debug(ldap_count_entries($ldap, $res) . " entries found in $t seconds");

    $entry = ldap_first_entry($ldap, $res);

    // Loop through the entries to get all the users
    while ($entry)
    {
      // Initialise all keys in the user array to NULL, in case an attribute isn't present
      $user = array('username' => null,
        'display_name' => null);

      $attribute = ldap_first_attribute($ldap, $entry);

      // Loop through all the attributes for this user
      while ($attribute)
      {
        $values = ldap_get_values($ldap, $entry, $attribute);
        $attribute = utf8_strtolower($attribute);  // ready for the comparisons

        if ($attribute == $username_attrib)
        {
          $user['username'] = $values[0];
        }
        elseif ($attribute == $display_name_attrib)
        {
          $user['display_name'] = $values[0];
        }

        $attribute = ldap_next_attribute($ldap, $entry);
      }

      if (isset($user['username']))
      {
        if (!isset($user['display_name']))
        {
          $user['display_name'] = $user['username'];
        }
        $object['users'][] = $user;
      }

      $entry = ldap_next_entry($ldap, $entry);
    }

    return true;
  }


  // Returns an array of attributes for use in an LDAP query
  private static function getAttributes(array $object, bool $include_email=true, bool $include_groups=true) : array
  {
    $result = array();

    // Username
    $result['username'] = utf8_strtolower($object['config']['ldap_user_attrib']);

    // The display name attribute might not have been set in the config file
    if (isset($object['config']['ldap_name_attrib']))
    {
      $result['display_name'] = utf8_strtolower($object['config']['ldap_name_attrib']);
    }

    // The email address
    if ($include_email && isset($object['config']['ldap_email_attrib']))
    {
      $result['email'] = utf8_strtolower($object['config']['ldap_email_attrib']);
    }

    // The group name attribute might not have been set in the config file
    if ($include_groups && isset($object['config']['ldap_group_member_attrib']))
    {
      $result['groups'] = utf8_strtolower($object['config']['ldap_group_member_attrib']);
    }

    return $result;
  }


  // Returns an associative array from the result of an LDAP search
  private static function getResult($ldap, $entry, array $attributes) : array
  {
    // Initialise all keys in the user array, in case an attribute isn't present
    $attributes_keys = array_keys($attributes);
    $user = array();
    foreach ($attributes_keys as $key)
    {
      switch ($key)
      {
        case 'username':
        case 'display_name':
        case 'email':
          $user[$key] = null;
          break;
        case 'groups':
          $user[$key] = array();
          break;
        default:
          throw new Exception("Unknown key '$key'");
      }
    }

    $attribute = ldap_first_attribute($ldap, $entry);

    // Loop through all the attributes for this user
    while ($attribute)
    {
      $values = ldap_get_values($ldap, $entry, $attribute);
      $attribute = utf8_strtolower($attribute);  // ready for the comparisons

      if ($attribute == $attributes['username'])
      {
        $user['username'] = $values[0];
      }
      elseif (isset($attributes['display_name']) && ($attribute == $attributes['display_name']))
      {
        $user['display_name'] = $values[0];
      }
      elseif (isset($attributes['email']) && ($attribute == $attributes['email']))
      {
        $user['email'] = $values[0];
      }
      elseif (isset($attributes['groups']) && ($attribute == $attributes['groups']))
      {
        for ($i=0; $i<$values['count']; $i++)
        {
          $user['groups'][] = $values[$i];
        }
      }

      $attribute = ldap_next_attribute($ldap, $entry);
    }

    return $user;
  }


  /* action($callback, $username, &$object)
   *
   * Connects/binds to all configured LDAP servers/base DNs and
   * then performs a callback, passing the LDAP object, $base_dn,
   * user DN (in $dn), $username and a generic object $object
   *
   * $callback   - The callback function
   * $username   - The user name
   * &$object    - Reference to the generic object, type defined by caller
   * $keep_going - Don't stop when a user has been found, but keep going through all the LDAP
  *               hosts.  Useful, for example, when you want to get a list of all users.
   *
   * Returns:
   *   boolean   - Whether the action was successful
   */
  public function action(string $callback, string $username, &$object, bool $keep_going=false) : bool
  {
    global $ldap_unbind_between_attempts;

    $result = false;

    for ($idx=0; $idx < count(self::$all_ldap_opts['ldap_host']); $idx++)
    {
      // Establish LDAP connection
      $uri = self::getUri($idx);
      $ldap = ldap_connect($uri);

      // Check that connection was established
      if ($ldap)
      {
        self::debug("got LDAP connection using $uri");

        // Set any applicable LDAP options
        self::setOptions($ldap, $idx);

        if (isset(self::$all_ldap_opts['ldap_dn_search_attrib'][$idx]))
        {
          if (isset(self::$all_ldap_opts['ldap_dn_search_dn'][$idx]) &&
              isset(self::$all_ldap_opts['ldap_dn_search_password'][$idx]))
          {
            // Bind with DN and password
            self::debug("binding with search_dn and search_password");
            $res = self::ldapBind($ldap, self::$all_ldap_opts['ldap_dn_search_dn'][$idx],
                                  self::$all_ldap_opts['ldap_dn_search_password'][$idx]);
          }
          else
          {
            // Anonymous bind
            self::debug("binding anonymously");
            $res = self::ldapBind($ldap);
          }

          if (!$res)
          {
            self::debug("initial bind failed: " . self::ldapError($ldap));
          }
          else
          {
            self::debug("initial bind was successful");

            $base_dn = self::$all_ldap_opts['ldap_base_dn'][$idx];
            $filter = "(" . self::$all_ldap_opts['ldap_dn_search_attrib'][$idx] . "=$username)";

            self::debug("searching using base_dn '$base_dn' and filter '$filter'");
            $res = ldap_search($ldap, $base_dn, $filter);

            if ($res === false)
            {
              self::debug("ldap_search failed: ". self::ldapError($ldap));
            }
            else
            {
              if (ldap_count_entries($ldap, $res) == 1)
              {
                $entries = ldap_get_entries($ldap, $res);
                $dn = $entries[0]["dn"];
                $user_search = "distinguishedName=" . $dn;
                self::debug("found one entry dn '$dn'");
              }
              else
              {
                self::debug(ldap_count_entries($ldap, $res) . " entries found, no unique dn");
              }
            }
          }
        }
        else
        {
          // construct dn for user
          $user_search = self::$all_ldap_opts['ldap_user_attrib'][$idx] . "=" . $username;
          $dn = $user_search . "," . self::$all_ldap_opts['ldap_base_dn'][$idx];

          self::debug("constructed dn '$dn' and " .
                      "user_search '$user_search' using '" .
                      self::$all_ldap_opts['ldap_user_attrib'][$idx] . "'");
        }

        foreach (self::$config_items as $item)
        {
          if (isset(self::$all_ldap_opts[$item][$idx]))
          {
            $object['config'][$item] = self::$all_ldap_opts[$item][$idx];
          }
          else
          {
            $object['config'][$item] = null;
          }
        }

        if (empty($dn))
        {
          self::debug("no DN determined, not calling callback");
        }
        else
        {
          $res = self::$callback($ldap, self::$all_ldap_opts['ldap_base_dn'][$idx], $dn,
                                 $user_search, $username, $object);
          if ($res)
          {
            $result = true;
          }
        }

        if ($ldap_unbind_between_attempts)
        {
          self::debug("unbinding from $uri");
          ldap_unbind($ldap);
        }

      } // if ($ldap)

      if ($result && !$keep_going)
      {
        return true;
      }
    } // for ()

    return $result;
  }


  // A wrapper for ldap_bind() that optionally suppresses "invalid credentials" errors.
  private static function ldapBind ($link_identifier, ?string $bind_rdn=null, ?string $bind_password=null) : bool
  {
    global $ldap_suppress_invalid_credentials;

    // Suppress all errors and then look to see what the error was and then
    // trigger the error again, depending on config settings.
    $result = @ldap_bind($link_identifier, $bind_rdn, $bind_password);

    if (!$result)
    {
      $errno = ldap_errno($link_identifier);
      if (!$ldap_suppress_invalid_credentials || ($errno != self::LDAP_INVALID_CREDENTIALS))
      {
        trigger_error(ldap_err2str($errno), E_USER_WARNING);
      }
    }

    return $result;
  }


  // Gets the full LDAP URI
  private static function getUri(int $idx) : string
  {
    // First get the scheme and host
    $host = self::$all_ldap_opts['ldap_host'][$idx];
    $parsed_url = parse_url($host);
    if (isset($parsed_url['scheme']))
    {
      $scheme = $parsed_url['scheme'];
      $host = $parsed_url['host'];
    }

    // If we haven't got a scheme then make an educated guess based on the port.
    if (!isset($scheme))
    {
      // And if there isn't a port defined either then use a sensible default
      $port = self::$all_ldap_opts['ldap_port'][$idx] ?? self::DEFAULT_PORT_LDAP;
      $scheme = ($port == self::DEFAULT_PORT_LDAPS) ? 'ldaps' : 'ldap';
    }
    // If we have got a scheme then get the port.  If it has been defined explicitly in the
    // config file, then use that. Otherwise make an educated guess based on the scheme.
    else
    {
      if (isset(self::$all_ldap_opts['ldap_port'][$idx]))
      {
        $port = self::$all_ldap_opts['ldap_port'][$idx];
      }
      else
      {
        $port = ($scheme == 'ldaps') ? self::DEFAULT_PORT_LDAPS : self::DEFAULT_PORT_LDAP;
      }
    }

    return "$scheme://$host:$port";
  }


  private static function setOptions($ldap, int $idx) : void
  {
    if (isset(self::$all_ldap_opts['ldap_deref'][$idx]))
    {
      ldap_set_option($ldap, LDAP_OPT_DEREF, self::$all_ldap_opts['ldap_deref'][$idx]);
    }

    if (isset(self::$all_ldap_opts['ldap_v3'][$idx]) &&
        self::$all_ldap_opts['ldap_v3'][$idx])
    {
      ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
    }

    if (isset(self::$all_ldap_opts['ldap_client_cert'][$idx]) &&
        self::$all_ldap_opts['ldap_client_cert'][$idx])
    {
      // Requires PHP 7.1.0 or later
      ldap_set_option($ldap, LDAP_OPT_X_TLS_CERTFILE, self::$all_ldap_opts['ldap_client_cert'][$idx]);
    }

    if (isset(self::$all_ldap_opts['ldap_client_key'][$idx]) &&
        self::$all_ldap_opts['ldap_client_key'][$idx])
    {
      // Requires PHP 7.1.0 or later
      ldap_set_option($ldap, LDAP_OPT_X_TLS_KEYFILE, self::$all_ldap_opts['ldap_client_key'][$idx]);
    }

    if (isset(self::$all_ldap_opts['ldap_tls'][$idx]) &&
        self::$all_ldap_opts['ldap_tls'][$idx])
    {
      ldap_start_tls($ldap);
    }

    if (isset(self::$all_ldap_opts['ldap_disable_referrals'][$idx]) &&
        self::$all_ldap_opts['ldap_disable_referrals'][$idx])
    {
      // Required to do a search on Active Directory for Win 2003+
      ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);
    }
  }


  // Adds extra diagnostic information to ldap_error()
  private static function ldapError ($link_identifier) : string
  {
    $result = ldap_error($link_identifier);

    // LDAP_OPT_DIAGNOSTIC_MESSAGE is not supported by all LDAP libraries
    if (defined('LDAP_OPT_DIAGNOSTIC_MESSAGE') &&
        ldap_get_option($link_identifier, LDAP_OPT_DIAGNOSTIC_MESSAGE, $err) &&
        isset($err) && ($err !== ''))
    {
      $result .= " [$err]";
    }

    return $result;
  }


  /* debug($message)
   *
   * Output LDAP debugging, if either of the configuration variables
   * $ldap_debug or $ldap_debug_attributes is true.
   *
   */
  private static function debug(string $message) : void
  {
    global $ldap_debug, $ldap_debug_attributes;

    if ($ldap_debug || $ldap_debug_attributes)
    {
      self::logDebugMessage($message);
    }
  }


  private static function getProfileClock()
  {
    global $ldap_debug;

    if ($ldap_debug)
    {
      return (get_microtime() - self::$profile_clock);
    }
    else
    {
      return null;
    }
  }


  private static function resetProfileClock() : void
  {
    global $ldap_debug;

    if ($ldap_debug)
    {
      self::$profile_clock = get_microtime();
    }
  }


}
