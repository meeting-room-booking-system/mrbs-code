<?php

namespace MRBS\Form;

use MRBS\JFactory;
use MRBS\Session\SessionCookie;


class Form extends Element
{
  private static $token = null;
  private static $token_name = 'csrf_token';  // As of PHP 7.1 this would be a private const
  private static $cookie_set = false;

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


  // Returns the HTML for a hidden field containing a CSRF token
  public static function getTokenHTML()
  {
    $element = new ElementInputHidden();
    $element->setAttributes(array('name'  => self::$token_name,
                                  'value' => self::getToken()));
    return $element->toHTML();
  }


  // Checks the CSRF token against the stored value and dies with a fatal error
  // if they do not match.   Note that:
  //    (1) The CSRF token is always looked for in the POST data, never anywhere else.
  //        GET requests should only be used for operations that do not modify data or
  //        grant access.
  //    (2) Forms should normally use a POST method.
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
    global $server;

    if ($post_only && ($server['REQUEST_METHOD'] != 'POST'))
    {
      return;
    }

    $token = \MRBS\get_form_var(self::$token_name, 'string', null, INPUT_POST);
    $stored_token = self::getStoredToken();

    if (!self::compareTokens($stored_token, $token))
    {
      if (isset($stored_token))
      {
        // Only report a possible CSRF attack if the stored token exists.   If it doesn't
        // it's normally because the user session has expired in between the form being
        // displayed and submitted.
        trigger_error('Possible CSRF attack from IP address ' . $server['REMOTE_ADDR'], E_USER_WARNING);
      }

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
  public static function getToken()
  {
    if (!isset(self::$token))
    {
      $stored_token = self::getStoredToken();
      if (isset($stored_token))
      {
        self::$token = $stored_token;
      }
      else
      {
        self::$token = self::generateToken();
        self::storeToken(self::$token);
      }
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
  // Note: it is important to provide the user-supplied string as the
  // second parameter, rather than the first.
  private static function compareTokens($known_token, $user_token)
  {
    if (is_null($known_token) || is_null($user_token))
    {
      return false;
    }

    if (function_exists('hash_equals'))
    {
      return hash_equals($known_token, $user_token);
    }

    // Could do fancier things here to give a timing attack safe comparison,
    // For example https://github.com/indigophp/hash-compat
    return ($known_token === $user_token);
  }


  private static function storeToken($token)
  {
    global $auth, $csrf_cookie;

    if ($auth['session'] == 'joomla')
    {
      // Joomla has its own session handling and will clear the $_SESSION variable,
      // so if we are using Joomla authentication we need to do sessions the Joomla
      // way.   (Maybe MRBS should abstract session handling into a separate Session
      // class in due course?   Note also that Joomla's JSession class has methods for
      // getting and checking form tokens, so maybe that's another way of doing it?)
      $session = JFactory::getSession();
      $session->set(self::$token_name, $token);
      return;
    }

    $session_status = session_status();

    // Use PHP sessions if we can
    if ($session_status !== PHP_SESSION_DISABLED)
    {
      if ($session_status === PHP_SESSION_NONE)
      {
        if (false === session_start())
        {
          throw new \Exception("Could not start session");
        }
      }
      $_SESSION[self::$token_name] = $token;
      return;
    }

    // Otherwise use cookies
    if (!self::$cookie_set)
    {
      SessionCookie::setCookie('MRBS_CSRF',
                               $csrf_cookie['hash_algorithm'],
                               $csrf_cookie['secret'],
                               array(self::$token_name => $token),
                               0);  //Always a session cookie

      self::$cookie_set = true;
    }
  }


  private static function getStoredToken()
  {
    global $auth, $csrf_cookie;

    if ($auth['session'] == 'joomla')
    {
      $session = JFactory::getSession();
      return $session->get(self::$token_name);
    }

    $session_status = session_status();
    
    // Use PHP sessions if we can
    if ($session_status !== PHP_SESSION_DISABLED)
    {
      if ($session_status === PHP_SESSION_NONE)
      {
        if (false === session_start())
        {
          throw new \Exception("Could not start session");
        }
      }
      return (isset($_SESSION[self::$token_name])) ? $_SESSION[self::$token_name] : null;
    }

    // Otherwise use cookies
    $data = SessionCookie::getCookie('MRBS_CSRF',
                                     $csrf_cookie['hash_algorithm'],
                                     $csrf_cookie['secret']);

    return (isset($data[self::$token_name])) ? $data[self::$token_name] : null;
  }
}
