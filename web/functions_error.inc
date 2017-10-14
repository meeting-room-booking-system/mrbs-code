<?php
namespace MRBS;


// Translate an error constant value into the name of the constant
function get_error_name($errno)
{
  $constants = get_defined_constants(true);
  $keys = array_keys($constants['Core'], $errno);
  $keys = array_filter($keys, function($value) {
      return (strpos($value, 'E_') === 0);
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


function output_error($heading, $body, $show_data=false)
{
  static $default_timezone_set = false;
  
  // We can't start outputting any error messages unless the default timezone has been set,
  // so if we are not sure that it has been set, then set it.
  if (!$default_timezone_set)
  {
    mrbs_default_timezone_set();
    $default_timezone_set = true;
  }
  
  if ($show_data)
  {
      if (isset($_GET))
      {
        $body .= "MRBS GET: " . print_r($_GET, true);
      }
      if (isset($_POST))
      {
        $body .= "MRBS POST: " . print_r($_POST, true);
      }
      if (isset($_SESSION))
      {
        $body .= "MRBS SESSION: " . print_r($_SESSION, true);
      }
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


function error_handler($errno, $errstr, $errfile, $errline, $errcontext)
{
  // Check to see whether error reporting has been disabled by
  // the error suppression operator (@), because the custom error
  // handler is still called even if errors are suppressed.
  if (error_reporting(0) === 0)
  {
    return;
  }
  
  $heading = "\n" . get_error_name($errno) . " in $errfile at line $errline\n";
  
  // Show the superglobal data unless the error is minor
  $show_data = !in_array($errno, array(E_NOTICE, E_USER_NOTICE, E_USER_DEPRECATED));
  
  output_error($heading, $errstr . "\n", $show_data);
}


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
  output_trailer();
  exit;
}
