<?php
declare(strict_types=1);
namespace MRBS;


include 'defaultincludes.inc';
require_once 'functions_test.inc';

error_reporting(-1);
ini_set('display_errors', '1');

$color_fail = 'pink';
$color_pass = 'palegreen';

$loaded_extensions = get_loaded_extensions();

echo "PHP version: " . PHP_VERSION;
echo "<br>\n";
echo "mbstring enabled: " . var_export(in_array('mbstring', $loaded_extensions), true);
echo "<br>\n";
echo "intl enabled: " . var_export(in_array('intl', $loaded_extensions), true);
echo "<br>\n";
echo "<br>\n";

if (!in_array('intl', $loaded_extensions))
{
  die("This test needs the 'intl' PHP extension to be loaded.");
}

test_constants('NumberFormatter');
