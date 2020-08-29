<?php
namespace MRBS\Auth;

use MRBS\User;
use \phpCAS;

class AuthCas extends Auth
{

  public function __construct()
  {
    $this->checkSessionMatchesType();
    $this->init();
  }


  // Initialise CAS
  public function init()
  {
    global $auth;

    static $init_complete = false;

    if ($init_complete)
    {
      return;
    }

    if ($auth['cas']['debug'])
    {
      phpCAS::setDebug();
      phpCAS::setVerbose(true);
    }

    phpCAS::client(CAS_VERSION_2_0,
      $auth['cas']['host'],
      (int)$auth['cas']['port'],
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

    $locale = \Locale::parseLocale(\MRBS\get_lang());
    if (isset($cas_lang_map[$locale['language']]))
    {
      phpCAS::setLang($cas_lang_map[$locale['language']]);
    }

    $init_complete = true;
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
  public function validateUser($user, $pass)
  {
    return (phpCAS::isAuthenticated()) ? $user : false;
  }


  public function getUser($username)
  {
    $user = new User($username);
    $user->level = $this->getLevel($username);
    $user->email = $this->getDefaultEmail($username);

    return $user;
  }


  private function getLevel($username)
  {
    global $auth;

    // User not logged in, user level '0'
    if (!isset($username))
    {
      return 0;
    }

    // If the attribute filters are set, check to see whether the user has
    // the required attributes
    if (isset($auth['cas']['filter_attr_name']) &&
        isset($auth['cas']['filter_attr_values']))
    {
      // getAttribute can return either a scalar or an array
      $actual_values = phpCAS::getAttribute($auth['cas']['filter_attr_name']);
      if (!is_array($actual_values))
      {
        $actual_values = array($actual_values);
      }
      // $auth['cas']['filter_attr_values'] can be either a scalar or an array
      $required_values = $auth['cas']['filter_attr_values'];
      if (!is_array($required_values))
      {
        $required_values = array($required_values);
      }
      // If the user doesn't have at least one of the required attributes they are level 0
      if (count(array_intersect($actual_values, $required_values)) === 0)
      {
        return 0;
      }
    }

    // Check the config file to see whether the user is an admin
    return $this->getDefaultLevel($username);
  }

}
