<?php

// $Id$

?>
    <link href="jquery/ui/css/sunny/jquery-ui-1.8.16.custom.css" rel="stylesheet" type="text/css">
    
    <link href="jquery/datatables/css/mrbs-page.css" rel="stylesheet" type="text/css">
    <link href="jquery/datatables/css/mrbs-table.css" rel="stylesheet" type="text/css">
    <link href="jquery/datatables/css/ColReorder.css" rel="stylesheet" type="text/css">
    <link href="jquery/datatables/css/ColVis.css" rel="stylesheet" type="text/css">
    
    <link rel="stylesheet" href="mrbs.css.php" type="text/css">
    <link rel="stylesheet" media="print" href="mrbs-print.css.php" type="text/css">

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
