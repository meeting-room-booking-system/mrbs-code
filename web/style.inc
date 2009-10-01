<?php

// $Id$

// NOTE:  The filename for the stylesheet for <= IE6 must contain the string 'ielte6'.
// The href strings in the <link> tags below are used by InitActiveCell in xbLib.js to 
// test whether the browser is IE6 or below - and therefore whether to use JavaScript
// highlighting instead of CSS highlighting.   In particular the href string for the
// stylesheet for browsers IE6 or below must contain the string 'ielte6'.
?>
    <link rel="stylesheet" href="mrbs.css.php" type="text/css">
    <link rel="stylesheet" media="print" href="mrbs-print.css.php" type="text/css">
    <?php
    // JavaScript overrides first, followed by the IE overrides.   This allows
    // the IE overrides to override the JavaScript overrides.
    ?>
    <script src="mrbs-js-overrides.js" type="text/javascript"></script>
    <!--[if IE]>
    <link rel="stylesheet" href="mrbs-ie.css" type="text/css">
    <![endif]-->
    <!--[if IE 8]>
    <link rel="stylesheet" href="mrbs-ie8.css" type="text/css">
    <![endif]-->
    <!--[if lte IE 8]>
    <link rel="stylesheet" href="mrbs-ielte8.css" type="text/css">
    <![endif]-->
    <!--[if lte IE 7]>
    <link rel="stylesheet" href="mrbs-ielte7.css.php" type="text/css">
    <![endif]-->
    <!--[if lte IE 6]>
    <link rel="stylesheet" href="mrbs-ielte6.css" type="text/css">
    <![endif]-->

    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo get_charset(); ?>">
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
