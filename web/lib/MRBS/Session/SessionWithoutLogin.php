<?php
namespace MRBS\Session;


// An abstract class for those session schemes where no login form is implemented
// because the username is already known
abstract class SessionWithoutLogin implements SessionInterface
{
  
  abstract public function getCurrentUser();

}
