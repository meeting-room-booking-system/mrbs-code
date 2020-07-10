<?php
namespace MRBS\Auth;

class AuthFactory
{
  
  public static function create($type)
  
  {
    // Transform the authentication type from lowercase_separated to LowercaseSeparated
    $parts = explode('_', $type);
    $parts = array_map('ucfirst', $parts);   
    $class = __NAMESPACE__  . "\\Auth" . implode('', $parts);
    return new $class;
  }
  
}