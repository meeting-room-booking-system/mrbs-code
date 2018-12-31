<?php
namespace MRBS\Session;

class SessionPhp extends Session
{
  
  public static function getUsername()
  {
    if (isset($_SESSION["UserName"]) && ($_SESSION["UserName"] !== ''))
    {
      return $_SESSION["UserName"];
    }

    return null;
  }
}
