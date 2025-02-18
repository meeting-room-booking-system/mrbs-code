<?php
declare(strict_types=1);
namespace MRBS\Form;

use MRBS\Exception;
use function MRBS\fatal_error;
use function MRBS\generate_token;
use function MRBS\get_form_var;
use function MRBS\get_vocab;
use function MRBS\session;


class Form extends Element
{
  public const METHOD_GET = 0;
  public const METHOD_POST = 1;
  private const TOKEN_NAME = 'csrf_token';

  private static $token = null;
  private static $cookie_set = false;


  // Creates a new form, automatically adding a CSRF token and a MAX_FILE_SIZE value
  // as hidden inputs if the method is POST.  The GET method should only be used for
  // navigating between pages and not for submitting form data which could change the
  // database contents or reveal data to which the user is not entitled.
  public function __construct(int $method=self::METHOD_GET)
  {
    parent::__construct('form');
    $this->setMethod($method);
  }


  // Sets the form method.  If the method is POST then a CSRF token and a MAX_FILE_SIZE
  // value are also added as hidden inputs.  If the method is GET then these hidden
  // inputs are removed because, in the case of the CSRF token we don't want it to
  // appear in the URL for security reasons, and in the case of MAX_FILE_SIZE it should
  // only be needed for POST requests.
  // TODO: if this method is called after the constructor then make sure the MAX_FILE_SIZE
  // TODO: hidden input is at the beginning, before any possible file input elements.
  private function setMethod(int $method) : Element
  {
    if ($method === self::METHOD_GET)
    {
      $this->removeHiddenInput('MAX_FILE_SIZE');
      $this->removeHiddenInput(self::TOKEN_NAME);
    }

    elseif ($method === self::METHOD_POST)
    {
      // Add a MAX_FILE_SIZE hidden input for use by forms that have a file
      // upload input.  This hidden input must come before the file input
      // element if it is to be used by PHP. Although at the time of writing
      // it is not used by any browsers, we can add some JavaScript to check
      // the file size when it is selected and thus save a failed upload attempt.
      $max_file_size = ini_get('upload_max_filesize');
      if ($max_file_size !== false)
      {
        $max_file_size = self::convertToBytes($max_file_size);
        $this->addHiddenInput('MAX_FILE_SIZE', $max_file_size, 'MAX_FILE_SIZE');
      }
      // Add a CSRF token
      $this->addCSRFToken();
    }

    return parent::setAttribute('method', self::methodToString($method));
  }


  // Converts a method string (eg 'get' or 'GET') to a method constant (eg self::METHOD_GET)
  private static function methodToInt(string $string) : int
  {
    if (strcasecmp($string, 'get') === 0)
    {
      return self::METHOD_GET;
    }

    if (strcasecmp($string, 'post') === 0)
    {
      return self::METHOD_POST;
    }

    throw new Exception("Unsupported method $string");
  }


  // Converts a method constant (eg self::METHOD_GET) to a method string (eg 'get')
  private static function methodToString(int $int) : string
  {
    if ($int === self::METHOD_GET)
    {
      return 'get';
    }

    if ($int === self::METHOD_POST)
    {
      return 'post';
    }

    throw new Exception("Unsupported method constant $int");
  }


  // Sets a form attribute, taking special action in the case of the
  // method attribute to set/unset the CSRF token and MAX_FILE_SIZE
  // hidden inputs.  Can cope with either string or integer method values.
  public function setAttribute(string $name, $value=true): Element
  {
    if (strcasecmp($name, 'method') === 0)
    {
      if (is_string($value))
      {
        $value = self::methodToInt($value);
      }
      if ($value === self::METHOD_POST)
      {
        $message = "Changing the form method after the form has been created may result " .
                   "in the MAX_FILE_SIZE hidden input coming after a file input, thus " .
                   "making it unusable by the server.";
        trigger_error($message, E_USER_WARNING);
      }
      return $this->setMethod($value);
    }

    return parent::setAttribute($name, $value);
  }


  // Adds a hidden input to the form.  Optionally give the element a key
  // so that it can be removed later using the same key.
  public function addHiddenInput(string $name, $value, ?string $key=null) : Form
  {
    $element = new ElementInputHidden($name, $value);
    $this->addElement($element, $key);
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


  // Removes a hidden input from the form.
  private function removeHiddenInput(string $key) : Form
  {
    $this->removeElement($key);
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
  public static function checkToken(bool $post_only=false) : void
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
    $this->addHiddenInput(self::TOKEN_NAME, self::getToken(), self::TOKEN_NAME);
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
    $result = session()->get(self::TOKEN_NAME);

    // For some unknown reason the integer value 0 is sometimes stored in the session
    // variable.  It's not clear how this can happen.
    if (isset($result) && !is_string($result))
    {
      trigger_error("Stored token is of type " . gettype($result) . ", value $result", E_USER_WARNING);
      $result = strval($result);
    }

    return $result;
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
