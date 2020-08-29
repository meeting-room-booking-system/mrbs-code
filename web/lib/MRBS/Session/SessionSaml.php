<?php
namespace MRBS\Session;

use \SimpleSAML_Auth_Simple;


/*
 * Session management scheme that delegates everything to a ready configured
 * SimpleSamlPhp instance.  You should use this scheme, along with the
 * authentication scheme with the same name, if you want your users to
 * authenticate using SAML Single Sign-on.
 *
 * in config.inc.php (assuming Active Directory attributes):
 * $auth['type'] = 'saml';
 * $auth['session'] = 'saml';
 * $auth['saml']['ssp_path'] = '/opt/simplesamlphp';
 * $auth['saml']['authsource'] = 'default-sp';
 * $auth['saml']['attr']['username'] = 'sAMAccountName';
 * $auth['saml']['attr']['mail'] = 'mail';
 * $auth['saml']['admin']['memberOf'] = ['CN=Domain Admins,CN=Users,DC=example,DC=com'];
 *
 * This scheme assumes that you've already configured SimpleSamlPhp,
 * and that you have set up aliases in your webserver so that SimpleSamlPhp
 * can handle incoming assertions.  Refer to the SimpleSamlPhp documentation
 * for more information on how to do that.
 *
 * https://simplesamlphp.org/docs/stable/simplesamlphp-install
 * https://simplesamlphp.org/docs/stable/simplesamlphp-sp
 */


class SessionSaml extends SessionWithLogin
{
  public $ssp;


  public function __construct()
  {
    global $auth;

    $this->checkTypeMatchesSession();

    // Check that the config variables have been set
    if (!isset($auth['saml']['ssp_path']))
    {
      throw new \Exception('$auth["saml"]["ssp_path"] must be set in the config file.');
    }

    if (!isset($auth['saml']['attr']['username']))
    {
      throw new \Exception('$auth["saml"]["attr"]["username"] must be set in the config file.');
    }

    // Include the SimpleSamlPhp autoloader
    require_once $auth['saml']['ssp_path'] . '/lib/_autoload.php';

    // Get the SimpleSamlPhp instance for the configured auth source
    if (isset($auth['saml']['authsource']))
    {
      $authSource = $auth['saml']['authsource'];
    }
    else
    {
      $authSource = 'default-sp';
    }

    $this->ssp = new SimpleSAML_Auth_Simple($authSource);
    parent::__construct();
  }


  // No need to prompt for a name - this is done by SimpleSamlPhp
  public function authGet($target_url=null, $returl=null, $error=null, $raw=false)
  {
    $this->ssp->requireAuth();
  }


  public function getCurrentUser()
  {
    $current_username = $this->getUsername();

    return (isset($current_username)) ? \MRBS\auth()->getUser($current_username) : null;
  }


  public function getUsername()
  {
    global $auth;

    if (!$this->ssp->isAuthenticated())
    {
      return null;
    }

    $userData = $this->ssp->getAttributes();
    $userNameAttr = $auth['saml']['attr']['username'];

    return array_key_exists($userNameAttr, $userData) ? $userData[$userNameAttr][0] : null;
  }


  public function getLogonFormParams()
  {
    $target_url = \MRBS\url_base() . \MRBS\this_page(true);
    $url = $this->ssp->getLoginURL($target_url);
    $baseURL = strstr($url, '?', true);
    parse_str(substr(strstr($url, '?'), 1), $params);

    $result = array(
        'action' => $baseURL,
        'method' => 'get'
      );

    if (!empty($params))
    {
      $result['hidden_inputs'] = $params;
    }

    return $result;
  }


  public function getLogoffFormParams()
  {
    $target_url = \MRBS\url_base() . \MRBS\this_page(true);
    $url = $this->ssp->getLogoutURL($target_url);
    $baseURL = strstr($url, '?', true);
    parse_str(substr(strstr($url, '?'), 1), $params);

    $result = array(
        'action' => $baseURL,
        'method' => 'get'
      );

    if (!empty($params))
    {
      $result['hidden_inputs'] = $params;
    }

    return $result;
  }


  public function processForm()
  {
    // No need to do anything - all handled by SAML
  }

}
