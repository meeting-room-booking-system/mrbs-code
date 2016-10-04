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


function output_error($heading, $body, $show_data=false)
{
  if ($show_data)
  {
    $body .= "MRBS GET: " . print_r($_GET, true) .
             "MRBS POST: " . print_r($_POST, true) .
             "MRBS SESSION: " . print_r($_SESSION, true);
  }
  
  if (ini_get('display_errors'))
  {
    echo nl2br("<b>$heading</b>");
    echo nl2br($body);
  }
  if (ini_get('log_errors'))
  {
    error_log($heading . $body);
  }
}


function error_handler($errno, $errstr, $errfile, $errline, $errcontext)
{
  $heading = "\n" . get_error_name($errno) . " in $errfile at line $errline\n";
  
  // Show the superglobal data unless the error is minor
  $show_data = !in_array($errno, array(E_NOTICE, E_USER_NOTICE, E_USER_DEPRECATED));
  
  output_error($heading, $errstr, $show_data);
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
  
  fatal_error($fatal_message, false, false);
}


// Error handler - this is used to display serious errors such as database
// errors without sending incomplete HTML pages. This is only used for
// errors which "should never happen", not those caused by bad inputs.
// Always outputs the bottom of the page and exits.
function fatal_error($message, $show_form_data = true, $show_debug = true)
{
  print_simple_header();
  echo "<p>$message</p>";
  output_trailer();
  exit;
}
