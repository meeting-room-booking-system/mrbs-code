<?

// Site-specific settings.
// Configure these for your site.
// You shouldn't have to modify anything outside of this file.
//
/////////////////////////////////////////////////////////////////


// MySQL settings
$mysql_host = "localhost";
$mysql_login = "root";
$mysql_password = "secret";
$mysql_database = "mrbs";


// DEFAULT SETTINGS: everything below here can be customized by the
// --------------------------------------------------------------------

// user in the Preferences page.
// Default color settings.  Users can override these.
$BGCOLOR = "#C0C0C0"; // document background color
$H2COLOR = "#000000"; // color of page titles
$CELLBG = "#C0C0C0"; // color of table cells in month view
$TODAYCELLBG = "#E0E0E0"; // color of table cells of current day in month view
$UNAPPROVED_COLOR = "#008000"; // color of unapproved events

// Preferences for time.
// Options are "24", "12"
$TIME_FORMAT = "24";

// Display unapproved events in the calendar.
// If set to "N", then they must be approved before they are displayed.
$DISPLAY_UNAPPROVED = "Y";

?>
