<?php
namespace MRBS;

global $refresh_rate, $custom_css_url;
global $debug;

if ($debug)
{
  ?>
  <link href="jquery/ui/css/jquery-ui.structure.css" rel="stylesheet" type="text/css">
  <link href="jquery/ui/css/sunny/jquery-ui.theme.css" rel="stylesheet" type="text/css">
  <link href="jquery/datatables/datatables.css" rel="stylesheet" type="text/css">
  <?php
}
else
{
  ?>
  <link href="jquery/ui/css/jquery-ui.structure.min.css" rel="stylesheet" type="text/css">
  <link href="jquery/ui/css/sunny/jquery-ui.theme.min.css" rel="stylesheet" type="text/css">
  <link href="jquery/datatables/datatables.min.css" rel="stylesheet" type="text/css">
  <?php
}
?>

<link rel="stylesheet" href="js/flatpickr/css/flatpickr.min.css" type="text/css">
<link rel="stylesheet" href="jquery/select2/dist/css/select2.min.css" type="text/css">
<link rel="stylesheet" href="css/mrbs.css.php" type="text/css">

<?php
if (using_rtl_lang())
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
if (using_rtl_lang())
{
  ?>
  <!--[if IE]>
  <link rel="stylesheet" href="css/mrbs-ie-rtl.css" type="text/css">
  <![endif]-->
  <?php
}

if (isset($custom_css_url))
{
  ?>
  <link rel="stylesheet" href="<?php echo $custom_css_url ?>" type="text/css">
  <?php
}
?>

<meta name="robots" content="noindex, nofollow, noarchive">

<?php
if (($refresh_rate != 0) && (this_page(false, '.php') == 'index'))
{
  // If we're using JavaScript we'll do the refresh by getting a new
  // table using Ajax requests, which means we only have to download
  // the table not the whole page each time
  echo "<noscript>\n";
  echo "<meta http-equiv=\"Refresh\" content=\"$refresh_rate\">\n";
  echo "</noscript>\n";
}

