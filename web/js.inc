<?php
namespace MRBS;

global $view, $year, $month, $day, $area, $room;
global $multisite, $site;
global $debug;

$page = this_page(false, '.php');

// We need to construct a standard query string that can be passed to the *.js.php
// pages.  That's because some of the settings they use are area dependent.
// We also want the area to be in the query string so that the JavaScript page is
// cached if the area is the same and not otherwise.
$escaped_standard_query_string = 'area=' . htmlspecialchars($area);

if ($multisite && isset($site) && ($site !== ''))
{
  $escaped_standard_query_string .= '&site=' . urlencode($site);
}

// Load the init file now so that it gets executed as soon as possible.  The init
// file can't use jQuery.
echo "<script src=\"js/init.js.php\"></script>\n";

if ($debug)
{
  ?>
  <script src="jquery/jquery-3.5.1.js"></script>
  <?php
}
else
{
  ?>
  <script src="jquery/jquery-3.5.1.min.js"></script>
  <?php
}

// We need the migration script now that we are running jQuery 3.0+, or at least
// until we are confident that our JavaScript is not using any features that have
// been removed.   The uncompressed version will output warnings about removed and
// deprecated features to the console
if ($debug)
{
  ?>
  <script src="jquery/jquery-migrate-3.3.0.js"></script>
  <?php
}
else
{
  ?>
  <script src="jquery/jquery-migrate-3.3.0.min.js"></script>
  <?php
}


// We are using the jQuery UI library, which has been custom built to include the following components:
//   - Core
//      - as defined by dependencies in order to support the required interactions and widgets
//      - no need for jQuery 1.7 support
//
//   - Interactions
//      - Draggable
//      - Resizable
//
//   - Widgets
//      - Autocomplete
//      - Button
//      - Dialog
//      - Menu
//      - Mouse
//      - Tabs
//
//   - Sunny theme
// If you need any other components you will need to rebuild the custom UI library
// See http://jqueryui.com/


if ($debug)
{
  ?>
  <script src="jquery/ui/jquery-ui.js"></script>
  <?php
}
else
{
  ?>
  <script src="jquery/ui/jquery-ui.min.js"></script>
  <?php
}

// All pages
?>
<script src="js/functions.js.php?<?php echo $escaped_standard_query_string ?>"></script>
<script src="js/general.js.php?<?php echo $escaped_standard_query_string ?>"></script>
<script src="js/datepicker.js.php?<?php echo $escaped_standard_query_string ?>"></script>
<script src="js/flatpickr/flatpickr.min.js"></script>
<script src="jquery/select2/dist/js/select2.full.min.js"></script>
<?php
if (null !== ($select2_lang_path = get_select2_lang_path()))
{
  echo "<script src=\"${select2_lang_path}\"></script >\n";
}

if (null !== ($flatpickr_lang_path = get_flatpickr_lang_path()))
{
  echo "<script src=\"${flatpickr_lang_path}\"></script>\n";
}

// dataTables initialisation
if (in_array($page, array('admin', 'edit_users', 'pending', 'report', 'search', 'view_entry')))
{
  // Include the JavaScript for those pages that use dataTables
  // When constructing the DataTables download we want to include the following:
  //    DataTables styling framework
  //    DataTables package
  //    Buttons, and all sub-options
  //    ColReorder
  //    FixedColumns
  if ($debug)
  {
    ?>
    <script src="jquery/datatables/datatables.js"></script>
    <?php
  }
  else
  {
    ?>
    <script src="jquery/datatables/datatables.min.js"></script>
    <?php
  }
  ?>

  <script src="jquery/datatables/plugins.js"></script>

  <script src="js/datatables.js.php?<?php echo $escaped_standard_query_string ?>"></script>

  <?php
}


// Get any page specific JavaScript
if (is_readable("js/$page.js"))
{
  ?>
  <script src="js/<?php echo htmlspecialchars($page)?>.js"></script>
  <?php
}
if (is_readable("js/$page.js.php"))
{
  ?>
  <script src="js/<?php echo htmlspecialchars($page)?>.js.php?<?php echo $escaped_standard_query_string ?>"></script>
  <?php
}

// The day, week and month views do refresh by Ajax.
// We need the resizable bookings for the day and week views
if ($page == 'index')
{
  ?>
  <script src="js/refresh.js.php?<?php echo $escaped_standard_query_string ?>"></script>
  <script src="js/resizable.js.php?<?php echo $escaped_standard_query_string ?>"></script>
  <?php
}
?>

<!--[if lte IE 8]>
  <?php
  // Include the HTML5 Shiv so that elements such as <header> etc can be styled in IE8 and below.
  // (Even though we don't support IE8 and below, we still need the HTML5 Shiv for styling the
  // page which says we don't support them!).
  if ($debug)
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
