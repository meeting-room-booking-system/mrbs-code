<?php
namespace MRBS;

// +---------------------------------------------------------------------------+
// | Meeting Room Booking System.
// +---------------------------------------------------------------------------+
// | Grabs the names and values of the $_POST and $_GET variables.
// |---------------------------------------------------------------------------+
// | This library grabs the names and values of the variables sent or posted to
// | a script in the $_POST and $_GET variables.
// | It does the same work for other external variables used in MRBS.
// | USE : This file should be included in all files where external variables
// |       are used, preferably before other included files.
// | :WARNING: thierry_bo 030216: if any new external variable is used,
// |           it must also be added here.
// +---------------------------------------------------------------------------+
// | @author    Original Authors : PhpMyAdmin project.
// +---------------------------------------------------------------------------+


// Unfotunately, in WordPress all $_GET, $_POST, $_COOKIE and $_SERVER superglobals are
// slashed, regardless of the setting of magic_quotes.   So if we are using the 
// WordPress authentication and session scemes then this will happen when the WordPress
// files are included.  To get round this we take a local copy of $_GET and $_POST
// before the WordPress files are included.   (There's no need to do this with $_SERVER
// because we process $_SERVER when this file is included and we make sure that the
// WordPress files haven't already been included).  For more details of the problem see
// https://wordpress.org/support/topic/wp-automatically-escaping-get-and-post-etc-globals and
// https://core.trac.wordpress.org/ticket/18322



// Gets a form variable.
//    $var        The variable name
//    $var_type   The type of the variable ('int', 'string' or 'array')
//    $default    The default value for the variable
//    $source     If set, then restrict the search to this source.  Can be
//                INPUT_GET or INPUT_POST.
function get_form_var($var, $var_type='string', $default=null, $source=null)
{
  // We use some functions from here
  require_once "functions.inc";
  
  global $cli_params, $allow_cli, $get, $post;
  
  // Set the default value, and make sure it's the right type
  if ($var_type == 'array')
  {
    $value = isset($default) ? (array) $default : array();
  }
  else
  {
    $value = $default;
  }
  
   // Get the command line arguments if any (and we're allowed to),
   // otherwise get the POST variables
  if ($allow_cli && (!empty($cli_params) && isset($cli_params[$var])))
  {
    $value = $cli_params[$var];
  }
  else if ((!isset($source) || ($source === INPUT_POST)) &&
           (!empty($post) && isset($post[$var])))
  {
    $value = $post[$var];
  }
  
  // Then get the GET variables
  if ((!isset($source) || ($source === INPUT_GET)) &&
      (!empty($get) && isset($get[$var])))
  {
    $value = $get[$var];
  }
  
  // Cast to an array if necessary
  if ($var_type == 'array')
  {
    $value = (array) $value;
  }
  
  // Clean up the variable
  if ($value != NULL)
  {
    if ($var_type == 'int')
    {
      $value = intval(unslashes($value));
    }
    else if ($var_type == 'string')
    {
      $value = unslashes($value);
    }
    else if ($var_type == 'array')
    {
      foreach ($value as $arrkey => $arrvalue)
      {
        $value[$arrkey] = unslashes($arrvalue);
      }
    }
  }
  
  return $value;
}


// Check that the WordPress files haven't already been included (and therefore
// that $_POST and $_GET haven't already been tampered with).
if (defined('ABSPATH'))  // standard test for WordPress
{
  die('MRBS internal error: Wordpress files have already been included.');
}

// Take clean copies of $_POST and $_GET before WordPress alters them
$get = $_GET;
$post = $_POST;


// Get useful $_SERVER variables
$vars = array('HTTP_ACCEPT_LANGUAGE',
              'HTTP_HOST',
              'HTTP_REFERER',
              'HTTP_USER_AGENT',
              'PHP_AUTH_PW',
              'PHP_AUTH_USER',
              'PHP_SELF',
              'QUERY_STRING',
              'REMOTE_ADDR',
              'REMOTE_USER',
              'REQUEST_METHOD'
              );
              
foreach ($vars as $var)
{
  // Set the variable to null if it doesn't exist to avoid undefined variable errors
  $$var = (!empty($_SERVER) && isset($_SERVER[$var])) ? $_SERVER[$var] : null;
}



// If we're operating from the command line then build
// an associative array of the command line parameters
// (assumes they're in the form 'parameter=value')
if (!empty($argc))
{
  $cli_params = array();
  for ($i=1; $i<$argc; $i++)
  {
    parse_str($argv[$i], $param);
    $cli_params = array_merge($cli_params, $param);
  }
}

