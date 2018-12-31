<?php
namespace MRBS\Session;

use \phpCAS;


class SessionCas extends Session
{
  
  public static function authGet()
  {
    // Useless Method
  }
  
  
  public static function getUsername()
  {
    return (phpCAS::isAuthenticated()) ? phpCAS::getUser() : null;
  }
}
