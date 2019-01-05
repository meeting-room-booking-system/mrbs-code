<?php
namespace MRBS\Auth;

abstract class Auth
{
  abstract public function getUser($username);
}