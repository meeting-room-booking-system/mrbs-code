<?php

// $Id$

global $unicode_encoding;
global $vocab; // outside of scope; needs to be denote as global

// The titles in the <link> tags below are used by InitActiveCell in xbLib.js to 
// test whether the browser is IE6 or below - and therefore whether to use JavaScript
// highlighting instead of CSS highlighting.
?>
    <link rel="stylesheet" href="mrbs.css.php" type="text/css" title="standard">
    <link rel="stylesheet" media="print" href="mrbs-print.css" type="text/css" title="print">
    <!--[if IE]>
    <link rel="stylesheet" href="mrbs-ie.css" type="text/css" title="ie">
    <![endif]-->
    <!--[if IE 8]>
    <link rel="stylesheet" href="mrbs-ie8.css" type="text/css" title="ie8">
    <![endif]-->
    <!--[if lte IE 6]>
    <link rel="stylesheet" href="mrbs-ielte6.css" type="text/css" title="ielte6">
    <![endif]-->
    <meta http-equiv="Content-Type" content="text/html; charset=<?php

if ($unicode_encoding)
{
  echo "utf-8";
}
else
{
  // We use $vocab directly instead of get_vocab() because we have
  // no requirement to convert the vocab text, we just output
  // the charset
  echo $vocab["charset"];
}
?>">
    <meta name="robots" content="noindex">
<?php

global $refresh_rate;
global $PHP_SELF;

if (($refresh_rate != 0) &&
    preg_match("/(day|week|month)\.php/",$PHP_SELF))
{
  echo "    <meta http-equiv=\"Refresh\" content=\"$refresh_rate\">\n";
}
?>
