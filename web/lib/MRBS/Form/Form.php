<?php

namespace MRBS\Form;

use function MRBS\fatal_error;
use function MRBS\generate_token;
use function MRBS\get_form_var;
use function MRBS\get_vocab;
use function MRBS\session;


class Form extends Element
{
  private const TOKEN_NAME = 'csrf_token';

  private static $token = null;
  private static $cookie_set = false;


  public function __construct()
  {
    parent::__construct('form');
    // Add a MAX_FILE_SIZE hidden input for use by forms that have a file
    // upload input.  This hidden input must come before the file input
    // element if it is to be used by PHP. Although at the time of writing
    // it is not used by any browsers, we can add some JavaScript to check
    // the file size when it is selected and thus save a failed upload attempt.
    $max_file_size = ini_get('upload_max_filesize');
    if ($max_file_size !== false)
    {
      $max_file_size = self::convertToBytes($max_file_size);
      $this->addHiddenInput('MAX_FILE_SIZE', $max_file_size);
    }
    // Add a CSRF token
    $this->addCSRFToken();
  }


  // Adds a hidden input to the form
  public function addHiddenInput(string $name, $value) : Form
  {
    $element = new ElementInputHidden($name, $value);
    $this->addElement($element);
    return $this;
  }


  // Adds an array of hidden inputs to the form
  public function addHiddenInputs(array $hidden_inputs) : Form
  {
    foreach ($hidden_inputs as $key => $value)
    {
      $this->addHiddenInput($key, $value);
    }
    return $this;
  }


  // Returns the HTML for a hidden field containing a CSRF token
  public static function getTokenHTML() : string
  {
    $element = new ElementInputHidden();
    $element->setAttributes(array('name'  => self::TOKEN_NAME,
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
  public static function checkToken($post_only=false) : void
  {
    global $server;

    if ($post_only && ($server['REQUEST_METHOD'] != 'POST'))
    {
      return;
    }

    $token = get_form_var(self::TOKEN_NAME, 'string', null, INPUT_POST);
    $stored_token = self::getStoredToken();

    if (!self::compareTokens($stored_token, $token))
    {
      if (isset($stored_token))
      {
        // Only report a possible CSRF attack if the stored token exists.   If it doesn't
        // it's normally because the user session has expired in between the form being
        // displayed and submitted.
        trigger_error('Possible CSRF attack from IP address ' . $server['REMOTE_ADDR'], E_USER_NOTICE);
      }

      if (method_exists(session(), 'logoffUser'))
      {
        session()->logoffUser();
      }

      fatal_error(get_vocab("session_expired"));
    }
  }


  // $max_unit can be set to 'seconds', 'minutes', 'hours', etc. and
  // can be used to specify the maximum unit to return.
  public static function getTimeUnitOptions($max_unit=null) : array
  {
    $options = array();
    $units = array('seconds', 'minutes', 'hours', 'days', 'weeks');

    foreach ($units as $unit)
    {
      $options[$unit] = get_vocab($unit);
      if (isset($max_unit) && ($max_unit == $unit))
      {
        break;
      }
    }
    return $options;
  }


  private function addCSRFToken() : Form
  {
    $this->addHiddenInput(self::TOKEN_NAME, self::getToken());
    return $this;
  }


  // Get a CSRF token
  public static function getToken() : string
  {
    $token_length = 32;

    if (!isset(self::$token))
    {
      $stored_token = self::getStoredToken();
      // The test below should really be isset() rather than !empty().  However occasionally MRBS has the
      // value 0 stored in the session variable.  It's not clear how or why this is happening.  Until the
      // root cause is found we test for empty() and if the token is set but empty we generate a new token.
      // Update: it seems that when the token is 0, so are all the other session variables.  So the problem
      // is probably not in the form code, but elsewhere.
      if (!empty($stored_token))
      {
        self::$token = $stored_token;
      }
      else
      {
        if (isset($stored_token))
        {
          // The token is set but empty
          $message = "Stored token is '$stored_token'.  This should not be possible. " .
                     "Generating a new token.";
          trigger_error($message,E_USER_WARNING);
        }
        self::$token = generate_token($token_length);
        self::storeToken(self::$token);
      }
    }

    return self::$token;
  }


  // Compare two tokens in a timing attack safe manner.
  // Returns true if they are equal, otherwise false.
  // Note: it is important to provide the user-supplied string as the
  // second parameter, rather than the first.
  private static function compareTokens($known_token, $user_token) : bool
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


  private static function storeToken($token) : void
  {
    session()->set(self::TOKEN_NAME, $token);
  }


  private static function getStoredToken() : ?string
  {
    return session()->get(self::TOKEN_NAME);
  }


  // Convert a file size to bytes
  // See https://www.php.net/manual/en/faq.using.php#faq.using.shorthandbytes
  private static function convertToBytes(string $size) : int
  {
    // Split the size into value and units (if any)
    $values = preg_split('/(?<=[0-9])(?=[^0-9]+)/i', $size);

    if (count($values) == 2)
    {
      $result = intval($values[0]);
      switch ($values[1])
      {
        case 'G':
          $result = 1024 * $result;
          // Fall through
        case 'M':
          $result = 1024 * $result;
          // Fall through
        case 'K':
          $result = 1024 * $result;
          return $result;
          break;
        default:
          // Unrecognised suffix
          break;
      }
    }

    return intval($size);
  }

}
