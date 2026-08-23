<?php

spl_autoload_register(function ($class) {

  $base_dir = __DIR__ . '/';

  // Replace namespace separators with directory separators and append '.php'
  $file = str_replace('\\', '/', $class) . '.php';

  // Special treatment for the Email class where the standard class only works on PHP 8.1+.  There is a version
  // in the Email/PHP71 directory that works on PHP 7.1+ (but < 8.1).
  // [Not a particularly elegant solution - is there a better way?]
  if (version_compare(PHP_VERSION, '8.1', '<'))
  {
    $parts = pathinfo($file);
    if ($parts['dirname'] == 'Email')
    {
      $file = 'Email/PHP71/' . $parts['basename'];
    }
  }

  $file = $base_dir . $file;
  if (is_readable($file))
  {
    require $file;
  }
});
