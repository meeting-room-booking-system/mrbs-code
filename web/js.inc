<?php 

// $Id$


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
//   - Dialog
//   - Tabs
//   - Datepicker
//   - Sunny theme
// If you need any other components you will need to rebuild the custom UI library
// See http://jqueryui.com/
?>
    
<script type="text/javascript" src="jquery/jquery-1.6.4.min.js"></script>
<script type="text/javascript" src="jquery/ui/jquery-ui-1.8.16.custom.min.js"></script>
<script type="text/javascript" src="jquery/ui/jquery-ui-i18n.js"></script>
<?php
// The English (-en) and English-US (-en-US) files are non-standard additions,
// which do not exist explicitly.   Although datepicker defaults to US English, it
// helps when choosing the regional settings to have explicit settings.  A ticket
// (#6682) has been raised to request their inclusion.    The files have been kept
// separate to avoid problems with merging new releases of UI.
//
?>
<script type="text/javascript" src="jquery/ui/jquery-ui-datepicker-en.js"></script>
<script type="text/javascript" src="jquery/ui/jquery-ui-datepicker-en-US.js"></script>
     
<?php // Work out if we're using IE6 or below ?>
<script type="text/javascript">
  //<![CDATA[
  var lteIE6 = false;
  //]]>
</script>
<!--[if lte IE 6]>
<script type="text/javascript">
  //<![CDATA[
  var lteIE6 = true;
  //]]>
</script>
<![endif]-->
    
<?php // Work out if we're using IE7 or below ?>
<script type="text/javascript">
  //<![CDATA[
  var lteIE7 = false;
  //]]>
</script>
<!--[if lte IE 7]>
<script type="text/javascript">
  //<![CDATA[
  var lteIE7 = true;
  //]]>
</script>
<![endif]-->
    
<?php // Work out if we're using IE8 or below ?>
<script type="text/javascript">
  //<![CDATA[
  var lteIE8 = false;
  //]]>
</script>
<!--[if lte IE 8]>
<script type="text/javascript">
  //<![CDATA[
  var lteIE8 = true;
  //]]>
</script>
<![endif]-->
    
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
// dataTables initialisation
if (in_array($page, array('admin', 'edit_users', 'pending', 'report', 'search')))
{
  // Include the JavaScript for those pages that use dataTables
  ?>
  <script type="text/javascript" src="jquery/datatables/js/jquery.dataTables.min.js"></script>
  <script type="text/javascript" src="jquery/datatables/js/ColReorder.min.js"></script>
  <?php
  // We use a customised version of ColVis which fixes a problem to do with the width of the
  // ColVis collection div meaning that when you have long column names the collection
  // buttons spill over onto multiple lines.   This problem has been reported in the
  // DataTables forum and hopefully the fix will appear in a future release (or else an
  // alternative solution suggested).  In the meantime we use the mrbs version of ColVis,
  // which is kept separate so that we can identify the differences.
  /*
  <script type="text/javascript" src="jquery/datatables/js/ColVis.min.js"></script>
  */
  ?>
  <script type="text/javascript" src="jquery/datatables/js/ColVis.mrbs.min.js"></script>
  <?php
  // We also have a customised version of FixedColumns which fixes a problem to do with the
  // resizing of windows in IE7/8.   The problem has been reported.
  /*
  <script type="text/javascript" src="jquery/datatables/js/FixedColumns.min.js"></script>
  */
  ?>
  <script type="text/javascript" src="jquery/datatables/js/FixedColumns.mrbs.js"></script>
  <script type="text/javascript" src="jquery/datatables/js/plugins.js"></script>
      
  <script type="text/javascript" src="js/datatables.js.php?<?php echo $standard_query_string ?>"></script>

  <?php
}
    
    
// Get any page specific JavaScript
if (file_exists("js/$page.js"))
{
  ?>
  <script type="text/javascript" src="js/<?php echo $page?>.js"></script>
  <?php
}
if (file_exists("js/$page.js.php"))
{
  ?>
  <script type="text/javascript" src="js/<?php echo $page?>.js.php?<?php echo $standard_query_string ?>"></script>
  <?php
}
    
    
// We need the resizable booking and the multiple bookings code for the day and week pages
if (in_array($page, array('day', 'week')))
{
  ?>
  <script type="text/javascript" src="js/resizable.js.php?<?php echo $standard_query_string ?>"></script>
  <script type="text/javascript" src="js/multiple.js.php?<?php echo $standard_query_string ?>"></script>
  <?php
}
    
if (in_array($page, array('day', 'week', 'month')))
{
  ?>
  <script type="text/javascript" src="js/cell_click.js.php?<?php echo $standard_query_string ?>"></script>
  <?php
}
    
    
// All pages
?>
<script type="text/javascript" src="js/functions.js.php?<?php echo $standard_query_string ?>"></script>
<script type="text/javascript" src="js/datepicker.js.php?<?php echo $standard_query_string ?>"></script>
    
<script type="text/javascript">

//<![CDATA[





$(window).load(function() {

  var args = {day: '<?php echo $day?>',
              month: '<?php echo $month?>',
              year: '<?php echo $year?>',
              area: '<?php echo $area?>',
              room: '<?php echo $room?>',
              page: '<?php echo $page?>'};
  init(args);

});


//]]>
</script>