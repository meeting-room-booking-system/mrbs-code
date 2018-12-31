<?php
namespace MRBS\Session;

use \SimpleSAML_Auth_Simple;


class SessionSaml extends Session
{
  private static $ssp_path;
  
  
  public function __construct()
  {
    global $auth;
    
    private static $ssp;
    
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
}
