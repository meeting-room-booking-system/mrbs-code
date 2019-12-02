<?php

spl_autoload_register(function ($class) {

  $base_dir = __DIR__ . '/';

  // Replace namespace separators with directory separators.
  // Append '.php'
  $file = str_replace('\\', '/', $class) . '.php';

  // Special treatment for some vendors
  $pathinfo = pathinfo($file);
  if ($pathinfo['dirname'] == 'PHPMailer/PHPMailer')
  {
    $file = 'PHPMailer/src/' . $pathinfo['basename'];
  }

  $file = $base_dir . $file;
  if (is_readable($file))
  {
    require $file;
  }
});
