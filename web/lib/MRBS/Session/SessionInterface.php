<?php
namespace MRBS\Session;

interface SessionInterface
{
  
  // Returns the username of the currently logged in user
  public function getCurrentUser();

}