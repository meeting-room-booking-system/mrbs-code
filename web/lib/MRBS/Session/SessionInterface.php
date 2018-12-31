<?php
namespace MRBS\Session;

interface SessionInterface
{
  // Gets the username and password.  Returns: Nothing
  public static function authGet();
  
  // Returns the username of the currently logged in user
  public static function getUsername();
}