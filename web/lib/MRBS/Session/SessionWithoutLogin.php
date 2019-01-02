<?php
namespace MRBS\Session;


// An abstract class for those session schemes where no login form is implemented
// because the username is already known
abstract class SessionWithoutLogin implements SessionInterface
{
  
  public function authGet()
  {
  }
  
  
  abstract public function getUsername();
  
  
  public function getLogonFormParams()
  {
  }
  
  
  public function getLogoffFormParams()
  {
  }
  

}
