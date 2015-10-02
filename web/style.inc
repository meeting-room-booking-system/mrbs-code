<?php

// $Id$

global $using_rtl, $refresh_rate, $custom_css_url, $PHP_SELF;

if (DEBUG)
{
  ?>
  <link href="jquery/ui/css/jquery-ui.structure.css" rel="stylesheet" type="text/css">
  <link href="jquery/ui/css/sunny/jquery-ui.theme.css" rel="stylesheet" type="text/css">
  <link href="jquery/datatables/css/jquery.dataTables.css" rel="stylesheet" type="text/css">
  <link href="jquery/datatables/css/buttons.dataTables.css" rel="stylesheet" type="text/css">
  <link href="jquery/datatables/css/fixedColumns.dataTables.css" rel="stylesheet" type="text/css">
  <link href="jquery/datatables/css/colReorder.dataTables.css" rel="stylesheet" type="text/css">
  <?php
}
else
{
  ?>
  <link href="jquery/ui/css/jquery-ui.structure.min.css" rel="stylesheet" type="text/css">
  <link href="jquery/ui/css/sunny/jquery-ui.theme.min.css" rel="stylesheet" type="text/css">
  <link href="jquery/datatables/css/jquery.dataTables.min.css" rel="stylesheet" type="text/css">
  <link href="jquery/datatables/css/buttons.dataTables.css" rel="stylesheet" type="text/css">
  <link href="jquery/datatables/css/fixedColumns.dataTables.min.css" rel="stylesheet" type="text/css">
  <link href="jquery/datatables/css/colReorder.dataTables.css" rel="stylesheet" type="text/css">
  <?php
}
?>
    
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
    <?php
    if (isset($custom_css_url))
    {
      ?>
      <link rel="stylesheet" href="<?php echo $custom_css_url ?>" type="text/css">
      <?php
    }
    ?>
    <meta name="robots" content="noindex, nofollow">
<?php
if (($refresh_rate != 0) && preg_match("/(day|week|month)\.php/", $PHP_SELF))
{
  // If we're using JavaScript and on the day or week pages we'll do the refresh by 
  // getting a new table using Ajax requests, which means we only have to download
  // the table not the whole page each time
  $ajax_capable = preg_match("/(day|week)\.php/", $PHP_SELF);
  echo ($ajax_capable) ? "<noscript>\n" : '';
  echo "<meta http-equiv=\"Refresh\" content=\"$refresh_rate\">\n";
  echo ($ajax_capable) ? "</noscript>\n" : '';
}

