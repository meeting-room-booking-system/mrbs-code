<?php
// +---------------------------------------------------------------------------+
// | Meeting Room Booking System.
// +---------------------------------------------------------------------------+
// | Grabs the names and values of the '$HTTP_*_VARS' variables.
// |---------------------------------------------------------------------------+
// | This library grabs the names and values of the variables sent or posted to
// | a script in the '$HTTP_*_VARS' and new globals arrays defined with
// | php 4.1+ and sets simple globals variables from them.
// | It does the same work for other external variables used in MRBS.
// | USE : This file should be included in all files where external variables
// |       are used, preferably before other included files.
// | :WARNING: thierry_bo 030216: if any new external variable is used,
// |           it must also be added here.
// +---------------------------------------------------------------------------+
// | @author    Original Authors : PhpMyAdmin project.
// | @author    thierry_bo.
// | @version   $Revision: 797 $.
// +---------------------------------------------------------------------------+
//
// $Id$


// Gets a form variable.   Takes an optional third parameter which
// is the default value if nothing is found from the form.
function get_form_var($variable, $type = 'string')
{
  // We use some functions from here
  require_once "functions.inc";
  
  global $cli_params, $allow_cli;
  
  // Set the default value, and make sure it's the right type
  if (func_num_args() > 2)
  {
    $value = func_get_arg(2);
    $value = ($type == 'array') ? (array)$value : $value;
  }
  else
  {
    $value = ($type == 'array') ? array() : NULL;
  }
  
   // Get the command line arguments if any (and we're allowed to),
   // otherwise get the POST variables
  if ($allow_cli && (!empty($cli_params) && isset($cli_params[$variable])))
  {
    $value = $cli_params[$variable];
  }
  else if (!empty($_POST) && isset($_POST[$variable]))
  {
    $value = $_POST[$variable];
  }
  else if (!empty($HTTP_POST_VARS) && isset($HTTP_POST_VARS[$variable]))
  {
    $value = $HTTP_POST_VARS[$variable];
  }
  
  // Then get the GET variables
  if (!empty($_GET) && isset($_GET[$variable]))
  {
    $value = $_GET[$variable];
  }
  else if (!empty($HTTP_GET_VARS) && isset($HTTP_GET_VARS[$variable]))
  {
    $value = $HTTP_GET_VARS[$variable];
  }
  
  // Cast to an array if necessary
  if ($type == 'array')
  {
    $value = (array)$value;
  }
  
  // Clean up the variable
  if ($value != NULL)
  {
    if ($type == 'int')
    {
      $value = intval(unslashes($value));
    }
    else if ($type == 'string')
    {
      $value = unslashes($value);
    }
    else if ($type == 'array')
    {
      foreach ($value as $arrkey => $arrvalue)
      {
        $value[$arrkey] = unslashes($arrvalue);
      }
    }
  }
  return $value;
}


$vars = array('PHP_SELF',
              'PHP_AUTH_USER',
              'PHP_AUTH_PW',
              'REMOTE_USER',
              'REMOTE_ADDR',
              'QUERY_STRING',
              'HTTP_ACCEPT_LANGUAGE',
              'HTTP_REFERER',
              'HTTP_USER_AGENT',
              'HTTP_HOST');
              
foreach ($vars as $var)
{
  if (!empty($_SERVER) && isset($_SERVER[$var]))
  {
    $$var = $_SERVER[$var];
  }
  else if (!empty($HTTP_SERVER_VARS) && isset($HTTP_SERVER_VARS[$var]))
  {
    $$var = $HTTP_SERVER_VARS[$var];
  }
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

