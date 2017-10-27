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
  
  
  // Adds a hidden input to the form
  public function addHiddenInput($name, $value)
  {
    $element = new ElementInputHidden();
    $element->setAttributes(array('name'  => $name,
                                  'value' => $value));
    $this->addElement($element);
    return $this;
  }
  
  
  // Adds an array of hidden inputs to the form
  public function addHiddenInputs(array $hidden_inputs)
  {
    foreach ($hidden_inputs as $key => $value)
    {
      $this->addHiddenInput($key, $value);
    }
    return $this;
  }
  
  
  // Checks the CSRF token against the stored value and dies with a fatal error
  // if they do not match.   Note that:
  //    (1) The CSRF token is always looked for in the POST data, never anywhere else.
  //        GET requests should only be used for operations that do not modify data or
  //        grant access.
  //    (2) Forms should never use a GET method.  Instead redirect to a URL with query string.
  //    (3) Actions should normally be taken by handler pages which are not designed to be
  //        accessed directly by the user and are only expecting POST requests.  These pages
  //        will look for the CSRF token however they are requested.  If they are requested via
  //        GET then they will still look for the token in the POST data and so fail.
  //    (4) There are some MRBS pages that can be accessed either via a URL with query string,
  //        or via a POST request.   These pages should not take any action, but as a matter of
  //        good practice should check the token anyway if they have been requested by a POST.
  //        To cater for these pages the $post_only parameter should be set to TRUE.
  public static function checkToken($post_only=false)
  {
    global $REMOTE_ADDR, $REQUEST_METHOD;
    
    if ($post_only && ($REQUEST_METHOD != 'POST'))
    {
      return;
    }
      
    $token = \MRBS\get_form_var(self::$token_name, 'string', null, INPUT_POST);
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
    $this->addHiddenInput(self::$token_name, self::getToken());
    return $this;
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
      return (isset($_SESSION[self::$token_name])) ? $_SESSION[self::$token_name] : null;
    }
    
    throw new \Exception("Need to do something with cookies here!");
  }
}
