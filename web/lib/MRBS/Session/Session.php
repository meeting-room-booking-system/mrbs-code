<?php
namespace MRBS\Session;

use MRBS\User;

abstract class Session
{

  // Returns the currently logged in user
  abstract public function getCurrentUser() : ?User;

  // Allows this to be extended with strategies for getting the referer when
  // HTTP_REFERER is going to be unreliable, eg when the Referrer-Policy is
  // set to strict-origin.
  public function getReferrer() : ?string
  {
    global $server;

    return $server['HTTP_REFERER'] ?? null;
  }

}
