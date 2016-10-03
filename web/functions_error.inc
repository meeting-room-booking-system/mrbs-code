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


// Define our own error handler, to allow us to format the message nicely for the screen 
// and also because it doesn't seem possible to get error_log() to output to the browser.
function error_handler($errno, $errstr, $errfile, $errline, $errcontext)
{
  $display_errors = ini_get('display_errors');
  
  $title = "\n" . get_error_name($errno) . " in $errfile at line $errline\n";
  
  if ($display_errors)
  {
    echo nl2br("<b>$title</b>");
    echo nl2br($errstr);
  }
  else
  {
    error_log($title . $errstr);
  }
}


function exception_handler($exception)
{
  $class = get_class($exception);
  
  $message = "Uncaught exception ($class) in " . $exception->getFile() . " at line " . $exception->getLine() . "\n" .
             $exception->getMessage() . "\n" .
             $exception->getTraceAsString() . "\n";
             
  trigger_error($message, E_USER_WARNING);
  
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
  global $simple_trailer, $weekstarts, $view_week_number, $strftime_format;

  print_simple_header();
    
  if ($show_debug)
  {
    trigger_error("MRBS: $message", E_USER_WARNING);
    trigger_error(print_r(debug_backtrace(), true));
    
    if ($show_form_data)
    {
      $form_data = "MRBS GET: " . print_r($_GET, true) .
                   "MRBS POST: " . print_r($_POST, true);
      $form_data = trigger_error($form_data);
    }
    if (!empty($_SESSION))
    {
      trigger_error("MRBS SESSION: " . print_r($_SESSION, true));
    }
  }
  
  echo "<p>$message</p>";
  output_trailer();
  exit;
}
