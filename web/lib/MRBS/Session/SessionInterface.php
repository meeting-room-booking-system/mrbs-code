<?php
namespace MRBS\Session;

use MRBS\User;

interface SessionInterface
{

  // Returns the currently logged in user
  public function getCurrentUser() : ?User;

}
