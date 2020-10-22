<?php
namespace MRBS;


// Translate an error constant value into the name of the constant
function get_error_name($errno)
{
  $constants = get_defined_constants(true);
  $keys = array_keys($constants['Core'], $errno);
  $keys = array_filter($keys, function($value) {
      return (utf8_strpos($value, 'E_') === 0);
    });
  return implode('|', $keys); // There should only be one member of the array, all being well.
}


// Replace leading spaces in $string with non-breaking spaces
function replace_leading_spaces($string)
{
  return preg_replace('/\G /', '&nbsp;', $string);
}


function to_html($string)
{
  $lines = explode("\n", $string);
  $lines = array_map(__NAMESPACE__ . "\\replace_leading_spaces", $lines);
  return implode("<br>\n", $lines);
}


function get_arg_string($args)
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

      default:
        $result[] = $type;
        break;
    }
  }

  return implode(', ', $result);
}


// Generate a backtrace.  This function allows us to format the output slightly better
// than debug_print_backtrace(), for example by replacing newlines with <br> tags.
function generate_backtrace()
{
  $backtrace = '';
  $calls = debug_backtrace();

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


function output_error($heading, $body, $show_data=false)
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
    echo "<b>" . to_html(htmlspecialchars($heading)) . "</b>\n";
    echo to_html(htmlspecialchars($body));
  }
  if (ini_get('log_errors'))
  {
    error_log($heading . $body);
  }
}


function error_handler($errno, $errstr, $errfile, $errline)
{
  // Check to see whether error reporting has been disabled by
  // the error suppression operator (@), because the custom error
  // handler is still called even if errors are suppressed.
  if (!(error_reporting() & $errno))
  {
    return;
  }

  $heading = "\n" . get_error_name($errno) . " in $errfile at line $errline\n";

  // Show the superglobal data unless the error is minor
  $show_data = !in_array($errno, array(E_NOTICE, E_USER_NOTICE, E_USER_DEPRECATED));

  output_error($heading, $errstr, $show_data);
}


// As long as we are supporting PHP5 we cannot have a type declaration for $exception.
// In PHP5 we could use \Exception and in PHP7 \Throwable, but if we are supporting
// both then no type declaration is the only option.
function exception_handler($exception)
{
  $class = get_class($exception);

  $heading = "Uncaught exception '$class' in " . $exception->getFile() . " at line " . $exception->getLine() . "\n";
  $body = $exception->getMessage() . "\n" .
          $exception->getTraceAsString() . "\n";

  output_error($heading, $body, $show_data=true);

  switch ($class)
  {
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
function fatal_error($message)
{
  print_simple_header();
  echo "<p>\n". htmlspecialchars($message) . "</p>\n";
  print_footer();
  exit;
}


function shutdown_function()
{
  $error = error_get_last();

  if (isset($error) &&
      (utf8_strpos($error['message'], 'iconv()') !== false) &&
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

    output_error($heading, $body, false);
  }
}
