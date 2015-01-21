<?php

// $Id$

spl_autoload_register(function ($class) {
  
  $base_dir = __DIR__ . '/';
  
  // Replace namespace separators with directory separators.
  // Append '.php'
  $file = $base_dir . str_replace('\\', '/', $class) . '.php';
  
  if (file_exists($file))
  {
    require $file;
  }
});