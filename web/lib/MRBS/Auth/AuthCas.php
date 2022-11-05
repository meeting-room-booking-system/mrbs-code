<?php
namespace MRBS\Auth;

use MRBS\Locale;
use MRBS\User;
use phpCAS;
use function MRBS\get_lang;
use function MRBS\is_https;

class AuthCas extends Auth
{

  public function __construct()
  {
    $this->checkSessionMatchesType();
    $this->init();
  }


  // Initialise CAS
  public function init() : void
  {
    global $auth, $server;

    static $init_complete = false;

    if ($init_complete)
    {
      return;
    }

    // We still use a couple of deprecated features - the phpCAS autoloader instead of composer and
    // phpCAS::setDebug() instead of phpCAS::setLogger() - so temporarily disable deprecation errors
    // and restore them later.
    // TODO: Fix this
    $old_level = error_reporting();
    error_reporting($old_level & ~E_USER_DEPRECATED);

    if ($auth['cas']['debug'])
    {
      phpCAS::setDebug();
      phpCAS::setVerbose(true);
    }

    // Form a client service name if we haven't been given one
    if (isset($auth['cas']['client_service_name']))
    {
      $client_service_name = $auth['cas']['client_service_name'];
    }
    else
    {
      $client_service_name = ((is_https()) ? 'https' : 'http') . '://' . $server['HTTP_HOST'];
      $client_service_name .= (isset($server['SERVER_PORT'])) ? ':' . $server['SERVER_PORT'] : '';
    }

    phpCAS::client(CAS_VERSION_2_0,
      $auth['cas']['host'],
      (int)$auth['cas']['port'],
      $auth['cas']['context'],
      $client_service_name
    );

    // Restore the original level of error reporting now that we've made the first
    // call to phpCAS.
    error_reporting($old_level);

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
      'gl' => PHPCAS_LANG_GALEGO,
      'ja' => PHPCAS_LANG_JAPANESE,
      'pt' => PHPCAS_LANG_PORTUGUESE,
      'zh' => PHPCAS_LANG_CHINESE_SIMPLIFIED
    );

    $locale = Locale::parseLocale(get_lang());
    
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
  public function validateUser(
    #[\SensitiveParameter]
    ?string $user,
    #[\SensitiveParameter]
    ?string $pass)
  {
    return (phpCAS::isAuthenticated()) ? $user : false;
  }


  protected function getUserFresh(string $username) : ?User
  {
    $user = new User($username);

    if (isset($user) && !$this->hasRequiredAttributes())
    {
      $user->level = 0;
    }

    return $user;
  }


  private function hasRequiredAttributes() : bool
  {
    global $auth;

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
        return false;
      }
    }

    return true;
  }

}
