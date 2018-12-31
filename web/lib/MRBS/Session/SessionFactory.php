<?php
namespace MRBS\Session;

class SessionFactory
{
  
  public static function create($type)
  
  {
    // Transform the session type from lowercase_separated to LowercaseSeparated
    $parts = explode('_', $type);
    $parts = array_map('ucfirst', $parts);   
    $class = __NAMESPACE__  . "\\Session" . implode('', $parts);
    return new $class;
  }
  
}