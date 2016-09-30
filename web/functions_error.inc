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
      $message = get_vocab("fatal_db_error");
      break;
    default:
      $message = get_vocab("fatal_error");
      break;
  }
  
  fatal_error(false, $message, false, false);
}


// Error handler - this is used to display serious errors such as database
// errors without sending incomplete HTML pages. This is only used for
// errors which "should never happen", not those caused by bad inputs.
// If $need_header!=0 output the top of the page too, else assume the
// caller did that. Always outputs the bottom of the page and exits.
function fatal_error($need_header, $message, $show_form_data = true, $show_debug = true)
{
  global $simple_trailer, $weekstarts, $view_week_number, $strftime_format;

  if ($need_header)
  {
    print_header(0, 0, 0, 0, "");
  }
  
  if ($show_debug)
  {
    error_log("MRBS: $message");
    
    $sql_error = db()->error();
    if (!empty($sql_error))
    {
      error_log("MRBS: $sql_error");
    }
    
    $e = new Exception();
    error_log($e->getTraceAsString());
    
    if ($show_form_data)
    {
      if (!empty($_GET))
      {
        error_log("MRBS GET: " . print_r($_GET, true));
      }
      if (!empty($_POST))
      {
        error_log("MRBS POST: " . print_r($_POST, true));
      }
    }
    if (!empty($_SESSION))
    {
      error_log("MRBS SESSION: " . print_r($_SESSION, true));
    }
  }
  
  echo "<p>$message</p>";
  output_trailer();
  exit;
}
