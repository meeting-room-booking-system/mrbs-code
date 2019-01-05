<?php
namespace MRBS;


class User
{
  public $username;
  public $display_name;
  public $email;
  
  public function __construct($username)
  {
    $this->username = $username;
  }
  
}