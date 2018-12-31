<?php
namespace MRBS\Session;

interface SessionInterface
{
  // Gets the username and password.  Returns: Nothing
  public static function authGet();
  
  // Returns the username of the currently logged in user
  public static function getUsername();
  
  // Returns the parameters ('method', 'action' and 'hidden_inputs') for a
  // Logon form.  Returns an array.
  public static function getLogonFormParams();
  
  // Returns the parameters ('method', 'action' and 'hidden_inputs') for a
  // Logon form.  Returns an array.
  public static function getLogoffFormParams();
}