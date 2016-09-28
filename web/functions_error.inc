<?php
namespace MRBS;


// Error handler - this is used to display serious errors such as database
// errors without sending incomplete HTML pages. This is only used for
// errors which "should never happen", not those caused by bad inputs.
// If $need_header!=0 output the top of the page too, else assume the
// caller did that. Always outputs the bottom of the page and exits.
function fatal_error($need_header, $message, $show_form_data = true)
{
  global $simple_trailer, $weekstarts, $view_week_number, $strftime_format;

  if ($need_header)
  {
    print_header(0, 0, 0, 0, "");
  }
  
  error_log("MRBS: $message");
  
  $sql_error = sql_error();
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
  echo "<p>$message</p>";
  output_trailer();
  exit;
}
