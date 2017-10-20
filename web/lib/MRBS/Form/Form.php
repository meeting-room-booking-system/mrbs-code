<?php

namespace MRBS\Form;

class Form extends Element
{
  
  public function __construct()
  {
    parent::__construct('form');
    $this->addCSRFToken();
  }
  
  
  private function addCSRFToken()
  {
    $token = self::generateToken();
    self::storeToken($token);
    $this->addElement(new ElementHidden('csrf_token', $token));
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
  
  
  private static function storeToken($token)
  {
    if ((session_id() !== '') || session_start())
    {
      $_SESSION['csrf_token'] = $token;
      return;
    }
    
    throw new \Exception("Need to do something with cookies here!");
  }
}
