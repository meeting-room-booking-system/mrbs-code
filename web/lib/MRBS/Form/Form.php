<?php

namespace MRBS\Form;

class Form extends Element
{
  private static $token = null;
  private static $token_name = 'csrf_token';  // As of PHP 7.1 this would be a private const
  
  public function __construct()
  {
    parent::__construct('form');
    $this->addCSRFToken();
  }
  
  
  public static function checkToken()
  {
    global $REMOTE_ADDR;
    
    $token = \MRBS\get_form_var(self::$token_name, 'string');
    $stored_token = self::getStoredToken();
    
    if (!self::compareTokens($token, $stored_token))
    {
      trigger_error("Possible CSRF attack from IP address $REMOTE_ADDR", E_USER_WARNING);
      if (function_exists("\\MRBS\\logoff_user"))
      {
        \MRBS\logoff_user();
      }
      \MRBS\fatal_error(\MRBS\get_vocab("session_expired"));
    }
  }
  
  
  private function addCSRFToken()
  {
    $token = self::getToken();
    $this->addElement(new ElementHidden(self::$token_name, $token));
  }
  

  // Get a CSRF token
  private static function getToken()
  {
    if (!isset(self::$token))
    {
      self::$token = self::generateToken();
      self::storeToken(self::$token);
    }
    
    return self::$token;
  }
  
  
  private static function generateToken()
  {
    $length = 32;
    
    if (function_exists('random_bytes'))
    {
      return bin2hex(random_bytes($length));  // PHP 7 and above
    }
    
    if (function_exists('mcrypt_create_iv'))
    {
      return bin2hex(mcrypt_create_iv($length, MCRYPT_DEV_URANDOM));
    }
    
    if (function_exists('openssl_random_pseudo_bytes'))
    {
      return bin2hex(openssl_random_pseudo_bytes($length));
    }
    
    return md5(uniqid(rand(), true));
  }
  
  
  // Compare two tokens in a timing attack safe manner.
  // Returns true if they are equal, otherwise false.
  private static function compareTokens($token1, $token2)
  {
    if (function_exists('hash_equals'))
    {
      return hash_equals($token1, $token2);
    }
    
    // Could do fancier things here to give a timing attack safe comparison,
    // For example https://github.com/indigophp/hash-compat
    return ($token1 === $token2);
  }
  
  
  private static function storeToken($token)
  {
    if ((session_id() !== '') || session_start())
    {
      $_SESSION[self::$token_name] = $token;
      return;
    }
    
    throw new \Exception("Need to do something with cookies here!");
  }
  
  
  private static function getStoredToken()
  {
    if ((session_id() !== '') || session_start())
    {
      return $_SESSION[self::$token_name];
    }
    
    throw new \Exception("Need to do something with cookies here!");
  }
}
