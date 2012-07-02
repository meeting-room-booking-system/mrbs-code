<?php

// $Id$

global $using_rtl, $refresh_rate, $PHP_SELF;

?>
    <link href="jquery/ui/css/sunny/jquery-ui-1.8.16.custom.css" rel="stylesheet" type="text/css">
    
    <link href="jquery/datatables/css/mrbs-page.css" rel="stylesheet" type="text/css">
    <link href="jquery/datatables/css/mrbs-table.css" rel="stylesheet" type="text/css">
    <link href="jquery/datatables/css/ColReorder.css" rel="stylesheet" type="text/css">
    <link href="jquery/datatables/css/ColVis.css" rel="stylesheet" type="text/css">
    
    <link rel="stylesheet" href="css/mrbs.css.php" type="text/css">
    <?php
    if ($using_rtl)
    {
      ?>
      <link rel="stylesheet" href="css/mrbs-rtl.css.php" type="text/css">
      <?php
    }
    ?>
    <link rel="stylesheet" media="print" href="css/mrbs-print.css.php" type="text/css">
    <!--[if IE]>
    <link rel="stylesheet" href="css/mrbs-ie.css" type="text/css">
    <![endif]-->
    <?php
    if ($using_rtl)
    {
      ?>
      <!--[if IE]>
      <link rel="stylesheet" href="css/mrbs-ie-rtl.css" type="text/css">
      <![endif]-->
      <?php
    }
    ?>
    <!--[if IE 8]>
    <link rel="stylesheet" href="css/mrbs-ie8.css" type="text/css">
    <![endif]-->
    <!--[if lte IE 8]>
    <link rel="stylesheet" href="css/mrbs-ielte8.css" type="text/css">
    <![endif]-->
    <!--[if lte IE 7]>
    <link rel="stylesheet" href="css/mrbs-ielte7.css.php" type="text/css">
    <![endif]-->
    <!--[if lte IE 6]>
    <link rel="stylesheet" href="css/mrbs-ielte6.css" type="text/css">
    <![endif]-->
    
    <meta name="robots" content="noindex">
<?php
if (($refresh_rate != 0) &&
    preg_match("/(day|week|month)\.php/",$PHP_SELF))
{
  echo "    <meta http-equiv=\"Refresh\" content=\"$refresh_rate\">\n";
}
?>
