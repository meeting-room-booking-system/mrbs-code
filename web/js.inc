<?php 
namespace MRBS;

global $day, $month, $year, $room, $area;

$page = this_page('.php');


// We need to construct a standard query string that can be passed to the *.js.php
// pages.  That's because some of the settings they use are area dependent.
// We also want the area to be in the queey string so that the JavaScript page is
// cached if the area is the same and not otherwise.
$standard_query_string = "area=$area";
  
  
  
// We are using the jQuery UI library, which has been custom built to include the following components:
//   - Core
//   - Widget
//   - Mouse
//   - Position
//   - Draggable
//   - Resizable
//   - Autocomplete
//   - Button
//   - Datepicker
//   - Dialog
//   - Menu
//   - Tabs
//   - Sunny theme
// If you need any other components you will need to rebuild the custom UI library
// See http://jqueryui.com/

if (DEBUG)
{
  ?>
  <script type="text/javascript" src="jquery/jquery-3.2.1.js"></script>
  <?php
}
else
{
  ?>
  <script type="text/javascript" src="jquery/jquery-3.2.1.min.js"></script>
  <?php
}

// We need the migration script now that we are running jQuery 3.0+, or at least
// until we are confident that our JavaScript is not using any features that have
// been removed.   The uncompressed version will output warnings about removed and
// deprecated features to the console
if (DEBUG)
{
  ?>
  <script type="text/javascript" src="jquery/jquery-migrate-3.0.0.js"></script>
  <?php
}
else
{
  ?>
  <script type="text/javascript" src="jquery/jquery-migrate-3.0.0.min.js"></script>
  <?php
}

if (DEBUG)
{
  ?>
  <script type="text/javascript" src="jquery/ui/jquery-ui.js"></script>
  <?php
}
else
{
  ?>
  <script type="text/javascript" src="jquery/ui/jquery-ui.min.js"></script>
  <?php
}

if (NULL !== ($datepicker_lang_file = get_datepicker_lang_file('jquery/ui/i18n')))
{
  echo "<script type=\"text/javascript\" src=\"${datepicker_lang_file}\"></script>";
}
?>
    
<script type="text/javascript">
  //<![CDATA[
  <?php
  // Create an empty init() function.   This will get "extended" if necessary
  // by the page specific JavaScript files in the js directory
  ?>
  function init(params)
  {
  }
  //]]>
</script>

<?php
// All pages
?>
<script type="text/javascript" src="js/functions.js.php?<?php echo $standard_query_string ?>"></script>
<script type="text/javascript" src="js/datepicker.js.php?<?php echo $standard_query_string ?>"></script>
<script type="text/javascript" src="js/general.js.php?<?php echo $standard_query_string ?>"></script>

    
<?php
// dataTables initialisation
if (in_array($page, array('admin', 'edit_users', 'pending', 'report', 'search')))
{
  // Include the JavaScript for those pages that use dataTables
  if (DEBUG)
  {
    ?>
    <script type="text/javascript" src="jquery/datatables/js/jquery.dataTables.js"></script>
    <script type="text/javascript" src="jquery/datatables/js/dataTables.buttons.js"></script>
    <script type="text/javascript" src="jquery/datatables/js/buttons.colVis.js"></script>
    <script type="text/javascript" src="jquery/datatables/js/dataTables.fixedColumns.js"></script>
    <script type="text/javascript" src="jquery/datatables/js/dataTables.colReorder.js"></script>
    <?php
  }
  else
  {
    ?>
    <script type="text/javascript" src="jquery/datatables/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" src="jquery/datatables/js/dataTables.buttons.min.js"></script>
    <script type="text/javascript" src="jquery/datatables/js/buttons.colVis.min.js"></script>
    <script type="text/javascript" src="jquery/datatables/js/dataTables.fixedColumns.min.js"></script>
    <script type="text/javascript" src="jquery/datatables/js/dataTables.colReorder.min.js"></script>
    <?php
  }
  ?>
  
  <script type="text/javascript" src="jquery/datatables/js/plugins.js"></script>
      
  <script type="text/javascript" src="js/datatables.js.php?<?php echo $standard_query_string ?>"></script>

  <?php
}
    
    
// Get any page specific JavaScript
if (is_readable("js/$page.js"))
{
  ?>
  <script type="text/javascript" src="js/<?php echo $page?>.js"></script>
  <?php
}
if (is_readable("js/$page.js.php"))
{
  ?>
  <script type="text/javascript" src="js/<?php echo $page?>.js.php?<?php echo $standard_query_string ?>"></script>
  <?php
}

// The day, week and month views do refresh by Ajax.
if (in_array($page, array('day', 'week', 'month')))
{
  ?>
  <script type="text/javascript" src="js/refresh.js.php?<?php echo $standard_query_string ?>"></script>
  <?php
}


// We need the resizable booking and multiple bookings for the day and week pages
if (in_array($page, array('day', 'week')))
{
  ?>
  <script type="text/javascript" src="js/resizable.js.php?<?php echo $standard_query_string ?>"></script>
  <script type="text/javascript" src="js/multiple.js.php?<?php echo $standard_query_string ?>"></script>
  <?php
}

?>

<!--[if lte IE 8]>
  <?php
  // Include the HTML5 Shiv so that elements such as <header> etc can be styled in IE8 and below.
  // (Even though we don't support IE8 and below, we still need the HTML5 Shiv for styling the 
  // page which says we don't support them!).
  if (DEBUG)
  {
    ?>
    <script src="js/html5shiv.js"></script>
    <?php
  }
  else
  {
    ?>
    <script src="js/html5shiv.min.js"></script>
    <?php
  }
  ?>
<![endif]-->
    
<script type="text/javascript">

//<![CDATA[


$(window).on('load', function() {

  var args = {day: '<?php echo $day?>',
              month: '<?php echo $month?>',
              year: '<?php echo $year?>',
              area: '<?php echo $area?>',
              room: '<?php echo $room?>',
              page: '<?php echo $page?>'};
  <?php
  // We need $timetohighlight for the day and week pages
  $timetohighlight = get_form_var('timetohighlight', 'int');
  if (isset($timetohighlight))
  {
    ?>
    args.timetohighlight = '<?php echo $timetohighlight?>';
    <?php
  }
  ?>
  init(args);

});


//]]>
</script>