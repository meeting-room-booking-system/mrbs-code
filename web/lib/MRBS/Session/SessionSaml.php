<?php
namespace MRBS\Session;

use \SimpleSAML_Auth_Simple;


class SessionSaml extends SessionWithLogin
{
  private static $ssp;
  
  
  public function __construct()
  {
    global $auth;
    
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
    
    self::$ssp = new SimpleSAML_Auth_Simple($authSource);
  }
  
  
  // No need to prompt for a name - this is done by SimpleSamlPhp
  public static function authGet()
  {
    self::$ssp->requireAuth();
  }
  
  
  public static function getUsername()
  {
    global $auth;
    
    if (!self::$ssp->isAuthenticated())
    {
      return null;
    }
    
    $userData = self::$ssp->getAttributes();
    $userNameAttr = $auth['saml']['attr']['username'];
    
    return array_key_exists($userNameAttr, $userData) ? $userData[$userNameAttr][0] : null;
  }
  
  
  public static function getLogonFormParams()
  {
    $target_url = '/' . \MRBS\this_page(true);
    $url = self::$ssp->getLoginURL($target_url);
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
  
  
  public static function getLogoffFormParams()
  {
    $target_url = '/' . \MRBS\this_page(true);
    $url = self::$ssp->getLogoutURL($target_url);
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
}
