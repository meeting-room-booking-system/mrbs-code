<?php
declare(strict_types=1);
namespace MRBS;

use Throwable;


function init_errors() : void
{
  global $debug;

  // If the error log file is a relative path then turn it into an absolute one in
  // order to avoid problems in shutdown when the working directory can change.
  // (See the notes in https://www.php.net/manual/en/function.register-shutdown-function.php).
  // Check for both Windows and Unix style separators because Unix separators can be used
  // on Windows.
  $error_log = ini_get('error_log');
  if (($error_log !== '') &&
      (mb_strpos($error_log, '/') === false) &&
      (mb_strpos($error_log, '\\') === false))
  {
    ini_set('error_log', getcwd() . '/' . $error_log);
  }

  // Make sure notice errors are not reported; they can break mrbs code.
  $error_level = E_ALL & ~E_NOTICE & ~E_USER_NOTICE;

  if (defined("E_DEPRECATED"))
  {
    $error_level = $error_level & ~E_DEPRECATED;
  }

  // The Mail and Net libraries generate E_STRICT errors, so disable E_STRICT (which became
  // part of E_ALL in PHP 5.4).  E_STRICT is deprecated from PHP 8.4 (and not used since PHP 7).
  assert(version_compare(MRBS_MIN_PHP_VERSION, '8.0.0', '<'), "The if block below can be removed.");
  if (defined("E_STRICT") && (version_compare(PHP_VERSION, '8.4') < 0))
  {
    $error_level = $error_level & ~E_STRICT;
  }

  if ($debug)
  {
    $error_level = -1;
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');  // ini_set() only accepts non-string values from PHP 8.1.0
  }

  error_reporting ($error_level);
  set_error_handler(__NAMESPACE__ . "\\error_handler", $error_level);
  set_exception_handler(__NAMESPACE__ . "\\exception_handler");

  register_shutdown_function(__NAMESPACE__ . "\\shutdown_function");
}


// Translate an error constant value into the name of the constant
function get_error_name(int $errno) : string
{
  $constants = get_defined_constants(true);
  $keys = array_keys($constants['Core'], $errno);
  $keys = array_filter($keys, function($value) {
      return (mb_strpos($value, 'E_') === 0);
    });
  return implode('|', $keys); // There should only be one member of the array, all being well.
}


// Replace leading spaces in $string with non-breaking spaces
function replace_leading_spaces(string $string) : string
{
  $pattern = '/\G /';
  $result = preg_replace($pattern, '&nbsp;', $string);
  if (!isset($result))
  {
    // This should not happen
    throw new Exception("preg_replace() failed - probably because of an error in the pattern '$pattern'.");
  }
  return $result;
}


function to_html(string $string) : string
{
  $lines = explode("\n", $string);
  $lines = array_map(__NAMESPACE__ . "\\replace_leading_spaces", $lines);
  return implode("<br>\n", $lines);
}


function get_arg_string(array $args) : string
{
  $result = array();

  foreach ($args as $arg)
  {
    $type = gettype($arg);

    switch ($type)
    {
      case 'boolean';
        $result[] = ($arg) ? 'true' : 'false';
        break;

      case 'integer':
      case 'double':
      case 'string':
        $result[] = $arg;
        break;

      case 'object':
        $class = get_class($arg);
        $result[] = ($class == 'SensitiveParameterValue') ? "[$class]" : $type;
        break;

      default:
        $result[] = $type;
        break;
    }
  }

  return implode(', ', $result);
}


// Generate a backtrace.  This function allows us to format the output slightly better
// than debug_print_backtrace(), for example by replacing newlines with <br> tags.
function generate_backtrace() : string
{
  global $debug;

  $backtrace = '';
  $options = DEBUG_BACKTRACE_PROVIDE_OBJECT;
  // Unless we are debugging ignore arguments as these can give away
  // database credentials
  if (!$debug)
  {
    $options = $options | DEBUG_BACKTRACE_IGNORE_ARGS;
  }
  $calls = debug_backtrace($options);

  foreach ($calls as $i => $call)
  {
    $backtrace .= "#$i ";

    if (isset($call['class']) && isset($call['type']))
    {
      $backtrace .= $call['class'] . $call['type'];
    }

    if (isset($call['function']))
    {
      $backtrace .= $call['function'];
      $backtrace .= '(';
      // We're not interested in the args for the first two calls because they
      // are just going to repeat the error message
      if (isset($call['args']) && ($i > 1))
      {
        $backtrace .= get_arg_string($call['args']);
      }
      $backtrace .= ')';
    }

    if (isset($call['file']) && isset($call['line']))
    {
      $backtrace .= ' called at [' . $call['file'] . ':' . $call['line'] . ']';
    }

    $backtrace .= "\n";
  }

  return $backtrace;
}


function output_error(string $heading, string $body, bool $show_data=false) : void
{
  global $debug, $auth, $get, $post;

  static $default_timezone_set = false;

  // We can't start outputting any error messages unless the default timezone has been set,
  // so if we are not sure that it has been set, then set it.
  if (!$default_timezone_set)
  {
    mrbs_default_timezone_set();
    $default_timezone_set = true;
  }

  // TODO: Should we use the abstracted access to session variables through session()?
  // TODO: Or does it not matter?  If it does, how do you find all the session variables in
  // TODO: Joomla, for example?
  if ($show_data &&
      (isset($get) || isset($post) || isset($_SESSION)))
  {
    $body .= "\n";
    if (isset($get))
    {
      $body .= "MRBS GET: " . print_r($get, true);
    }
    if (isset($post))
    {
      $post_tmp = $post;
      if (!$auth['log_credentials'])
      {
        // Overwrite the username and password to stop them appearing
        // in error logs.
        foreach (array('username', 'password') as $var)
        {
          if (isset($post_tmp[$var]) && ($post_tmp[$var] !== ''))
          {
            $post_tmp[$var] = '****';
          }
        }
      }
      $body .= "MRBS POST: " . print_r($post_tmp, true);
    }
    if (isset($_SESSION))
    {
      $body .= "MRBS SESSION: " . print_r($_SESSION, true);
    }
  }

  $body .= "\n";

  if ($debug)
  {
    $body .=  generate_backtrace();
  }

  if (ini_get('display_errors'))
  {
    echo "<b>" . to_html(escape_html($heading)) . "</b>\n";
    echo to_html(escape_html($body));
  }
  if (ini_get('log_errors'))
  {
    error_log($heading . $body);
  }
}


// Converts an error into an exception
function exception_thrower(int $errno, string $errstr)
{
  throw new \Exception($errstr, $errno);
}


// "If the function returns false then the normal error handler continues."
// (https://www.php.net/manual/en/function.set-error-handler.php)
function error_handler(int $errno, string $errstr, string $errfile, int $errline) : bool
{
  // Check to see whether error reporting has been disabled by
  // the error suppression operator (@), because the custom error
  // handler is still called even if errors are suppressed.
  if (!(error_reporting() & $errno))
  {
    return true;
  }

  $heading = "\n" . get_error_name($errno) . " in $errfile at line $errline\n";

  // Show the superglobal data unless the error is minor
  $show_data = !in_array($errno, array(E_NOTICE, E_USER_NOTICE, E_USER_DEPRECATED));

  output_error($heading, $errstr, $show_data);

  return true;
}


// Logs an exception (or sends it to the browser if $debug)
function output_exception_error(Throwable $exception, bool $caught=false) : void
{
  $class = get_class($exception);

  $heading = (($caught) ? "Caught" : "Uncaught") . " exception '$class' in " . $exception->getFile() . " at line " . $exception->getLine() . "\n";
  $body = $exception->getMessage() . "\n" .
          $exception->getTraceAsString() . "\n";

  output_error($heading, $body, true);
}


// Custom exception handler.  Logs the error and then outputs
// a fatal error message
function exception_handler(Throwable $exception) : void
{
  output_exception_error($exception);

  $class = get_class($exception);

  switch ($class)
  {
    case __NAMESPACE__ . "\\DBExternalException":
      $fatal_message = get_vocab("fatal_db_ext_error");
      break;
    case __NAMESPACE__ . "\\DBException":
    case 'PDOException':
      $fatal_message = get_vocab("fatal_db_error");
      break;
    default:
      $fatal_message = get_vocab("fatal_error");
      break;
  }

  fatal_error($fatal_message);
}


// Error handler - this is used to display serious errors such as database
// errors without sending incomplete HTML pages. This is only used for
// errors which "should never happen", not those caused by bad inputs.
// Always outputs the bottom of the page and exits.
function fatal_error(string $message) : void
{
  print_simple_header();
  echo "<p>\n". escape_html($message) . "</p>\n";
  print_footer();
  exit;
}


function shutdown_function() : void
{
  $error = error_get_last();

  if (isset($error) &&
      (mb_strpos($error['message'], 'iconv()') !== false) &&
      !function_exists('iconv'))
  {
    // Help new admins understand what to do in case the iconv error occurs...
    $heading = "MRBS - iconv module not installed. ";
    $body = "The iconv module, which provides PHP support for Unicode, is not " .
            "installed on your system." .
            "Unicode gives MRBS the ability to easily support languages other " .
            "than English. Without Unicode, support for non-English-speaking " .
            "users will be crippled." .
            "To fix this error, you need to install and enable the iconv module." .
            "On a Windows server, enable php_iconv.dll in %windir%\\php.ini, and " .
            "make sure both %phpdir%\\dlls\\iconv.dll and %phpdir%\\extensions\\php_iconv.dll " .
            "are in the path. One way to do this is to copy these two files to %windir%." .
            "On a Unix server, recompile your PHP module with the appropriate option for " .
            "enabling the iconv extension. Consult your PHP server documentation for " .
            "more information about enabling iconv support.\n";

    output_error($heading, $body);
  }
}
