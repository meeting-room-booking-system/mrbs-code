<?php
declare(strict_types=1);
namespace MRBS;

use IntlDateFormatter;

require_once 'lib/autoload.inc';

/**************************************************************************
 *   MRBS system defaults file
 *
 * DO _NOT_ MODIFY THIS FILE YOURSELF. IT IS FOR _INTERNAL_ USE ONLY.
 *
 * TO CONFIGURE MRBS FOR YOUR SYSTEM ADD CONFIGURATION PARAMETERS FROM
 * THIS FILE INTO config.inc.php, DO _NOT_ EDIT THIS FILE.
 *
 **************************************************************************/

/********
 * System
 ********/

// Set $debug = true to force MRBS to output debugging information to the browser.
// Caching of files is also disabled when $debug is set.
// WARNING!  Do not use this for production systems, as not only will it generate
// unnecessary output in the broswer, but it could also expose sensitive security
// information (eg database usernames and passwords).
$debug = false;


/**********
 * Timezone
 **********/

// The timezone your meeting rooms run in. It is especially important
// to set this if you're using PHP 5 on Linux. In this configuration
// if you don't, meetings in a different DST than you are currently
// in are offset by the DST offset incorrectly.
//
// Note that timezones can be set on a per-area basis, so strictly speaking this
// setting should be in areadefaults.inc.php, but as it is so important to set
// the right timezone it is included here.
//
// When upgrading an existing installation, this should be set to the
// timezone the web server runs in.  See the INSTALL document for more information.
//
// A list of valid timezones can be found at http://php.net/manual/timezones.php
// The following line must be uncommented by removing the '//' at the beginning
//$timezone = "Europe/London";

// If you are using iCalendar notifications of bookings (see the mail settings below)
// then the iCalendar attachment includes a definition of your timezone in
// VTIMEZONE format.   This defines the timezone, including the rules for Daylight
// Saving Time transitions.    This information is included in the MRBS distribution.
// However, as governments can change the rules periodically, MRBS will check from
// time to time to see if there is a later version available on the web.   If your
// site prevents external access to the web, this check will time out.  However
// you can avoid the timeout and stop MRBS checking for up to date versions by
// setting $zoneinfo_update = false;
$zoneinfo_update = true;

// The VTIMEZONE definitions exist in two forms - normal and Outlook compatible.
// $zoneinfo_outlook_compatible determines which ones to use.
$zoneinfo_outlook_compatible = true;

// The VTIMEZONE definitions are cached in the database with an expiry time
// of $zoneinfo_expiry seconds.   If your server does not have external internet
// access set $zoneinfo_expiry to PHP_INT_MAX to prevent MRBS from trying to
// update the VTIMEZONE definitions.
$zoneinfo_expiry = 60*60*24*7;    // 7 days

/*******************
 * Database settings
 ******************/
// Which database system: "pgsql"=PostgreSQL, "mysql"=MySQL
// ("mysqli" is also supported for historical reasons and is mapped to "mysql")
$dbsys = "mysql";
// Hostname of database server. For pgsql, can use "" instead of localhost
// to use Unix Domain Sockets instead of TCP/IP. For mysql "localhost"
// tells the system to use Unix Domain Sockets, and $db_port will be ignored;
// if you want to force TCP connection you can use "127.0.0.1".
$db_host = "localhost";
// If you need to use a non-standard port for the database connection you
// can uncomment the following line and specify the port number
// $db_port = 1234;
// Database name:
$db_database = "mrbs";
// Schema name.  This only applies to PostgreSQL and is only necessary if you have more
// than one schema in your database, and you are also using the same MRBS table names in
// multiple schemas.
//$db_schema = "public";
// Database login username:
$db_login = "mrbs";
// Database login password:
$db_password = 'mrbs-password';
// Prefix for table names.  This will allow multiple installations where only
// one database is available
$db_tbl_prefix = "mrbs_";
// Set $db_persist to true to use PHP persistent (pooled) database connections.  Note
// that persistent connections are not recommended unless your system suffers significant
// performance problems without them.   They can cause problems with transactions and
// locks (see http://php.net/manual/en/features.persistent-connections.php) and although
// MRBS tries to avoid those problems, it is generally better not to use persistent
// connections if you can.
$db_persist = false;
// The number of times to retry getting a database connection in the event that there are
// too many connections.  [MySQL only at the moment]
$db_retries = 2;
// The number of milliseconds to wait before retrying.  [MySQL only at the moment]
$db_delay = 750; // milliseconds


// MySQL driver options
// --------------------

// If you are using MySQL over SSL you may need to set some of the
// following options. (You may need to use the 'nd_pdo_mysql' extension
// instead of 'pdo_mysql'.)

// The file path to the SSL certificate authority.
$db_options['mysql']['ssl_ca'] = null;

// The file path to the directory that contains the trusted SSL CA certificates, which are stored in PEM format.
$db_options['mysql']['ssl_capath'] = null;

// The file path to the SSL certificate.
$db_options['mysql']['ssl_cert'] = null;

// A list of one or more permissible ciphers to use for SSL encryption, in a format understood by OpenSSL.
// For example: DHE-RSA-AES256-SHA:AES128-SHA
$db_options['mysql']['ssl_cipher'] = null;

// The file path to the SSL key.
$db_options['mysql']['ssl_key'] = null;

// Provides a way to disable verification of the server SSL certificate.
$db_options['mysql']['ssl_verify_server_cert'] = null;  // boolean


// PostgreSQL driver options
// -------------------------

// There are none at the moment.


/*********************************
 * Site identification information
 *********************************/

// Set to true to enable multisite operation, in which case the settings below are for the
// home site, identified by the empty string ''.   Other sites have their own supplementary
// config fies in the sites/<sitename> directory.
$multisite = false;
$default_site = '';

$mrbs_admin = "Your Administrator";
$mrbs_admin_email = "admin_email@your.org";
// NOTE:  there are more email addresses in $mail_settings below.    You can also give
// email addresses in the format 'Full Name <address>', for example:
// $mrbs_admin_email = 'Booking System <admin_email@your.org>';
// if the name section has any "peculiar" characters in it, you will need
// to put the name in double quotes, e.g.:
// $mrbs_admin_email = '"Bloggs, Joe" <admin_email@your.org>';

// The company name is mandatory.   It is used in the header and also for email notifications.
// The company logo, additional information and URL are all optional.

$mrbs_company = "Your Company";   // This line must always be uncommented ($mrbs_company is used in various places)

// Uncomment this next line to use a logo instead of text for your organisation in the header
//$mrbs_company_logo = "your_logo.gif";    // name of your logo file.   This example assumes it is in the MRBS directory

// Uncomment this next line for supplementary information after your company name or logo.
// This can contain HTML, for example if you want to include a link.
//$mrbs_company_more_info = "You can put additional information here";  // e.g. "XYZ Department"

// Uncomment this next line to have a link to your organisation in the header
//$mrbs_company_url = "http://www.your_organisation.com/";

// This is to fix URL problems when using a proxy in the environment.
// If links inside MRBS or in email notifications appear broken, then specify here the URL of
// your MRBS root directory, as seen by the users. For example:
// $url_base =  "http://example.com/mrbs";


/*******************
 * Themes
 *******************/

// Choose a theme for the MRBS.   The theme controls two aspects of the look and feel:
//   (a) the styling:  the most commonly changed colours, dimensions and fonts have been
//       extracted from the main CSS file and put into the styling.inc file in the appropriate
//       directory in the Themes directory.   If you want to change the colour scheme, you should
//       be able to do it by changing the values in the theme file.    More advanced styling changes
//       can be made by changing the rules in the CSS file.
//   (b) the header:  the header.inc file which contains the function used for producing the header.
//       This enables organisations to plug in their own header functions quite easily, in cases where
//       the desired corporate look and feel cannot be changed using the CSS alone and the mark-up
//       itself needs to be changed.
//
//  MRBS will look for the files "styling.inc" and "header.inc" in the directory Themes/$theme and
//  if it can't find them will use the files in Themes/default.    A theme directory can contain
//  a replacement styling.inc file or a replacement header.inc file or both.

// Available options are:

// "default"        Default MRBS theme
// "classic126"     Same colour scheme as MRBS 1.2.6

$theme = "default";

// Use the $custom_css_url to override the standard MRBS CSS.
//$custom_css_url = 'css/custom.css';

// Use the $custom_js_url to add your own JavaScript.
//$custom_js_url = 'js/custom.js';


/*******************
 * Calendar settings
 *******************/

// MRBS has two different modes of operation: "times" and "periods".   "Times"
// based bookings allow you to define regular consecutive booking slots, eg every
// half an hour from 7.00 am to 7.00 pm.   "Periods" based bookings are useful
// in, for example, schools where the booking slots are of different lengths
// and are not consecutive because of change-over time or breaks.

// It is not possible to swap between these two options once bookings have
// been created and to have meaningful entries.  This is due to differences
// in the way that the data is stored.

// It is however possible to configure the system so that some areas operate in
// "periods" mode and others in "times" mode.    Therefore the configuration variable
// that determines the default setting for new areas appears together with other
// variables that can be set on a per-area basis in the file areadefaults.inc.php.
// This is done to draw attention to the fact that they are default settings for new
// areas only and to avoid frustration when trying to change settings for existing
// areas: this is done by editing the settings for an area using a web browser by
// following the "Rooms" link in MRBS.


// TIMES SETTINGS
// --------------

// The times settings can all be configured on a per-area basis, so these variables
// appear in the areadefaults.inc.php file.


// PERIODS SETTINGS
// ----------------

// The "Periods" settings are used only in areas where the mode has
// been set to "Periods".


/******************
 * Booking policies
 ******************/

// Most booking policies can be configured on a per-area basis, so these variables
// appear in the areadefaults.inc.php file.

// The settings below are global policy settings

// Set the maximum *number* of bookings that can be made by any one user, in an interval,
// which can be a day, week, month or year, or else in the future.  (A week is defined
// by the $weekstarts setting).   These are global settings, but you can additionally
// configure per area settings.   This would allow you to set policies such as allowing
// a maximum of 10 bookings per month in total with a maximum of 1 per day in Area A.
$max_per_interval_global_enabled['day']    = false;
$max_per_interval_global['day'] = 1;      // max 1 booking per day in total

$max_per_interval_global_enabled['week']   = false;
$max_per_interval_global['week'] = 5;     // max 5 bookings per week in total

$max_per_interval_global_enabled['month']  = false;
$max_per_interval_global['month'] = 10;   // max 10 bookings per month in total

$max_per_interval_global_enabled['year']   = false;
$max_per_interval_global['year'] = 50;    // max 50 bookings per year in total

$max_per_interval_global_enabled['future'] = false;
$max_per_interval_global['future'] = 100; // max 100 bookings in the future in total

// Set the maximum total *length* of bookings that can be made by any one user, in an interval,
// which can be a day, week, month or year, or else in the future.  (A week is defined
// by the $weekstarts setting).   These are global settings, but you can additionally
// configure per area settings.   This would allow you to set policies such as allowing a
// maximum of 10 hours per week in total with a maximum of 1 hour per day in Area A.
// These settings only apply to areas in "times" mode.

$max_secs_per_interval_global_enabled['day']    = false;
$max_secs_per_interval_global['day'] = 60*60*2;      // max 2 hours per day in total

$max_secs_per_interval_global_enabled['week']   = false;
$max_secs_per_interval_global['week'] = 60*60*10;    // max 10 hours per week in total

$max_secs_per_interval_global_enabled['month']  = false;
$max_secs_per_interval_global['month'] = 60*60*25;   // max 25 hours per month in total

$max_secs_per_interval_global_enabled['year']   = false;
$max_secs_per_interval_global['year'] = 60*60*100;   // max 100 hours per year in total

$max_secs_per_interval_global_enabled['future'] = false;
$max_secs_per_interval_global['future'] = 60*60*100; // max 100 hours in the future in total

// Set the latest date for which you can make a booking.    This can be useful if you
// want to set an absolute date, eg the end of term, beyond which bookings cannot be made.
// If you want to set a relative date, eg no more than a week away, then this can be done
// using the area settings.   Note that it is possible to have both a relative and absolute
// date, eg "no more than a week away and in any case not past the end of term".
// Note that bookings are allowed on the $max_booking_date, but not after it.
$max_booking_date_enabled = false;
$max_booking_date = "2012-07-23";  // Must be a string in the format "yyyy-mm-dd"

// Set the earliest date for which you can make a booking.    This can be useful if you
// want to set an absolute date, eg the beginning of term, before which bookings cannot be made.
// If you want to set a relative date, eg no more than a week away, then this can be done
// using the area settings.   Note that it is possible to have both a relative and absolute
// date, eg "no earlier than a week away and in any case not before the beginning of term".
// Note that bookings are allowed on the $min_booking_date, but not before it.
$min_booking_date_enabled = false;
$min_booking_date = "2012-04-23";  // Must be a string in the format "yyyy-mm-dd"

// Set this to true if you want to prevent users editing or deleting approved bookings.
// Note that this setting only applies if booking approval is in force for the area.
// If it isn't in force you can prevent bookings being edited or deleted by using the
// min and max delete ahead settings.
$approved_bookings_cannot_be_changed = false;

// Set this to true if you want to prevent users having a booking for multiple rooms
// at the same time.
$prevent_simultaneous_bookings = false;

// The maximum number of simultaneous bookings allowed if $prevent_simultaneous_bookings is true.
$max_simultaneous_bookings = 1;

// Whether to count simultaneous bookings just in the area concerned (true), or globally (false).
// NOTE: it only makes sense to count globally if all the enabled areas are in "times" mode; or
// they are in "periods" mode and the periods in each area correspond to the same time; or there
// is only one area.
$simultaneous_ignore_other_areas = false;

// Set this to true if you want to prevent bookings of a type that is invalid for a room or day of the week
$prevent_invalid_types = true;

// Provided $prevent_invalid_types is set to true, this can be used to specify a set of days of the
// week (0 = Sunday) that are invalid for a certain type.  For example
// $invalid_types_days['I'] = [1, 3] means that bookings of type 'I' cannot be made on Mondays or Wednesdays.
$invalid_types_days = array();

// When setting max_create_ahead and max_delete_ahead policies, the time interval is normally
// measured to the end time of the booking.  This is to prevent users cheating the system by
// booking a very long slot with the start time just inside the limit and then either not using
// the early part of the booking, or else editing it down to what they actually need later.
// However this is not very intuitive for users who might expect the measurement to be relative
// to the start time, in which case this can be achieved by changing this setting to true.
$measure_max_to_start_time = false;

// By default, bookings cannot be made on days that are designated holidays (see $holidays).
$prevent_booking_on_holidays = true;

// Set this to true to prevent bookings being made on weekends (see $weekdays).
$prevent_booking_on_weekends = false;


/******************
 * Display settings
 ******************/

// [These are all variables that control the appearance of pages and could in time
//  become per-user settings]

// Start of week: 0 for Sunday, 1 for Monday, etc.
$weekstarts = 0;

// Days of the week that are weekdays
$weekdays = array(1, 2, 3, 4, 5);

// Set this to true to add styling to weekend days
$style_weekends = false;

// A two-dimensional array of holidays in yyyy-mm-dd format, indexed first by year, for example
// $holidays[2022] = array('2022-01-01', '2022-11-24');  // New Year's Day and US Thanksgiving 2022
// Dates can include ranges in the form 'yyyy-mm-dd..yyyy-mm-dd', eg
// $holidays[2022] = array('2022-01-01', '2022-07-01..2022-07-31');  // New Year's Day and all of July
// By default, bookings cannot be made on days that are designated holidays (see $prevent_booking_on_holidays).
// Holidays are styled differently in the main calendar views.
$holidays = array();

// Days of the week that should be hidden from display
// 0 for Sunday, 1 for Monday, etc.
// For example, if you want Saturdays and Sundays to be hidden set $hidden_days = array(0,6);
//
// By default the hidden days will be removed completely from the main table in the week and month
// views.   You can alternatively arrange for them to be shown as narrow, greyed-out columns
// by defining some custom CSS for the .hidden_day class.
$hidden_days = array();

// Whether to display the timezone
$display_timezone = false;

// Whether to scroll automatically so that the current time slot is in view
$autoscroll = true;

// Results per page for searching:
$search["count"] = 20;

// Page refresh time (in seconds). Set to 0 to disable.
// (Note that if MRBS detects that a client is on a metered network
// connection it will disable page refresh for that client.)
$refresh_rate = 0;

// Refresh rate (in seconds) for Ajax checking of valid bookings on the edit_entry page
// Set to 0 to disable.
$ajax_refresh_rate = 10;

// Refresh rate for page pre-fetches in the calendar views.   MRBS tries to improve
// performance of navigation between pages in the calendar view by pre-fetching some
// pages.   This setting determines how often (in seconds) the pre-fetches should be
// refreshed in order to keep them from getting out of date.  Set to 0 to disable.
$prefetch_refresh_rate = 30;

// Refresh rate (in seconds) when in kiosk mode
$kiosk_refresh_rate = 300; // 5 minutes

// Whether kiosk mode is enabled
$kiosk_mode_enabled = false;

// Default mode for kiosk mode.  Can be 'room' or 'area'.
$kiosk_default_mode = 'room';

// Whether to show a QR code in kiosk mode
// Note that PHP 7.4 or greater and the mbstring extension are required for a QR code
$kiosk_QR_code = true;

// Timeout if the exit kiosk mode dialog is not acted upon
$kiosk_exit_dialog_timeout = 10; // seconds

// Timeout if there is no activity on the kiosk exit page
$kiosk_exit_page_timeout = 10; // seconds

// Entries in monthly view can be shown as start/end slot, brief description or
// both. Set to "description" for brief description, "slot" for time slot and
// "both" for both. Default is "both", but 6 entries per day are shown instead
// of 12.
$monthly_view_entries_details = "both";

// To show week numbers in the main calendar, set this to true. The week
// numbers are only displayed if you set $weekstarts to start on the first
// day of the week in your locale and area's timezone.  (This assumes that
// the PHP IntlCalendar class is available; if not, the week is assumed to
// start on Mondays, ie the ISO stanard.)
$view_week_number = false;

// To display week numbers in the mini-calendars, set this to true. The week
// numbers are only displayed if you set $weekstarts to the start of the week.
// See the comment about when the week starts above.
$mincals_week_numbers = false;

// Whether or not the mini-calendars are displayed.  (Note that mini-calendars are only
// displayed anyway if the window is wide enough.)
$display_mincals = true;

// If the window is too narrow the mini-calendars are normally not displayed.  However by
// setting the following variable to true they will be displayed above the main calendar,
// provided the window is high enough.
$display_mincals_above = false;

// To display the endtime in the slot description, eg '09:00-09:30' instead of '09:00', set
// this to true.
$show_slot_endtime = false;

// To display the row labels (times, rooms or days) on the right hand side as well as the
// left hand side in the day and week views, set to true;
// (was called $times_right_side in earlier versions of MRBS)
$row_labels_both_sides = false;

// To display the column headers (times, rooms or days) on the bottom of the table as
// well as the top in the day and week views, set to true;
$column_labels_both_ends = false;

// Show a line in the day and week views corresponding to the current time(
$show_timeline = true;  // normal mode
$show_timeline_kiosk = false;  // kiosk mode

// For bookings that allow registration, show the number of people that have
// registered and, if there is one, the registration limit.  This will typically
// be appended to the description in the calendar view, eg "Lecture [12/40]".
// The way the registration level is presented can be changed with a
// $vocab_override config setting.
$show_registration_level = true;

// Define default starting view (month, week or day)
// Default is day
$default_view = "day";

// The default setting for the week and month views: whether to view all the
// rooms (true) or not (false).
$default_view_all = true;

// If there's only one room in an area, the view_all option will not normally
// be offered.  This can be overridden by setting the variable below to true.
$always_offer_view_all = false;

// Define default room to start with (used by index.php)
// Room numbers can be determined by looking at the Edit or Delete URL for a
// room on the admin page.
// Default is 0
$default_room = 0;

// Define clipping behaviour for the cells in the day and week views.
// Set to true if you want the cells in the day and week views to be clipped.   This
// gives a table where all the rows have the same height, regardless of content.
// Alternatively set to false if you want the cells to expand to fit the content.
// (false not supported in IE6 and IE7 due to their incomplete CSS support)
$clipped = true;

// Define clipping behaviour for the cells in the month view.
// Set to true if you want all entries to have the same height. The
// short description may be clipped in this case. If set to false,
// each booking entry will be large enough to display all information.
$clipped_month = true;

// Set to true if you want the cells in the month view to scroll if there are too
// many bookings to display; set to false if you want the table cell to expand to
// accommodate the bookings.
$month_cell_scrolling = true;

// Define the maximum length of a string that can be displayed in an admin table cell
// (eg the rooms and users lists) before it is truncated.  (This is necessary because
// you don't want a cell to contain for example a 2 kbyte text string, which could happen
// with user defined fields).
$max_content_length = 20;  // characters

// The maximum length of a database field for which a text input can be used on a form
// (eg when editing a user or room).  If longer than this a text area will be used.
$text_input_max = 70;  // characters

// For inputs that have autocomplete options, eg the area and room match inputs on
// the report page, we can define how many characters need to be input before the
// options are displayed.  This enables us to prevent a huge long list of options
// being presented.   We define the breakpoints in an array.   For example if we set
// $autocomplete_length_breaks = array(25, 250, 2500); this means that if the number of options
// is less than 25 then they will be displayed when 0 characters are input, ie the input
// receives focus.   If the number of options is less than 250 then they will be displayed
// when 1 character is input and so on.    The array can be as long as you like.   If it
// is empty then the options are displayed when 0 characters are input.

// [Note: this variable is only applicable to older browsers that do not support the
// <datalist> element and instead fall back to a JavaScript emulation.   Browsers that
// support <datalist> present the options in a scrollable select box]
$autocomplete_length_breaks = array(25, 250, 2500);

// The default orientation for Excel output
// Options: 'portrait' or 'landscape'
$excel_default_orientation = 'portrait';

// The default paper size for Excel output
// Options: 'A3', 'A4', 'A5', 'LEGAL', 'LETTER' or 'TABLOID'
// You can instead, provided the size is supported in your version of Excel, use any of the integers defined in
// https://learn.microsoft.com/en-us/dotnet/api/documentformat.openxml.spreadsheet.pagesetup?view=openxml-2.8.1
// For example 43 for Japanese double postcard (200 mm by 148 mm)
$excel_default_paper = 'A4';

// The default orientation for PDF output
// Options: 'portrait' or 'landscape'
$pdf_default_orientation = 'portrait';

// The default paper size for PDF output
// Options: 'A3', 'A4', 'A5', 'LEGAL', 'LETTER' or 'TABLOID'
$pdf_default_paper = 'A4';

// Enable or disable state saving (eg pagination position, display length, filtering and sorting) for
// data tables, eg the users table or report output.
$state_save = true;

// The validity duration of the saved state for data tables.
// This option is also used to indicate to DataTables if localStorage or sessionStorage should be used
// for storing the table's state. When set to -1 sessionStorage will be used, while for 0 or greater
// localStorage will be used.  The difference between the two storage APIs is that sessionStorage retains
// data only for the current session (i.e. the current browser window).  Please note that the value is
// given in seconds. The value 0 is a special value as it indicates that the state can be stored and
// retrieved indefinitely with no time limit.
$state_duration = 0;

// Whether to sort users by their last names or not
$sort_users_by_last_name = false;

// When viewing all rooms in the week or month views, it can be very difficult to pick out an individual
// slot, which could be just one pixel wide.  Therefore, the user is taken to the day view first unless
// there's only one slot per day.  If $view_all_always_go_to_day_view is set to true, then we always go to
// the day view first, regardless of the number of slots.
$view_all_always_go_to_day_view = false;


/***********************
 * Date and time formats
 ***********************/

// MRBS uses PHP's IntlDateFormatter and IntlDatePatternGenerator classes for formatting
// dates and times in the user's locale.  On systems where the 'intl' extension is not
// enabled, MRBS emulates those two classes and uses the strftime() function. However,
// strftime() is deprecated from PHP 8.1 onwards, and you are recommended to ensure that
// the 'intl' extension is enabled.
//
// The formats used by MRBS are specified using the $datetime_formats configuration
// settings below. Each setting is an associative array, indexed by four possible keys:
//
//    'date_type'   one of the IntlDateFormatter constants (ie FULL, LONG, MEDIUM, SHORT
//                  or NONE; default FULL).  Note that the RELATIVE_ constants are not
//                  supported by the emulation.
//    'time_type'   one of the IntlDateFormatter constants (ie FULL, LONG, MEDIUM, SHORT
//                  or NONE; default FULL).
//    'skeleton'    a "skeleton".  See
//                  https://unicode-org.github.io/icu/userguide/format_parse/datetime/#datetimepatterngenerator.
//                  Note that not all skeletons are emulated.
//    'pattern'     a "pattern".  See https://unicode-org.github.io/icu/userguide/format_parse/datetime/
//
//    If a 'skeleton' is specified and a pattern can be generated from the skeleton, that
//    will be used; otherwise the 'pattern' is used.  If no skeleton or pattern are specified
//    then the appropriate date and time representations for 'date_type' and 'time_type' will
//    be used.
//
//    Note that IntlDateFormatter automatically determines whether a 12 or 24-hour clock should
//    be used based on the locale. If you need to override this and force a 12 or 24-hour clock
//    then you will need to override the settings below in your config file with formats using
//    patterns instead of date_type and time_type.  For example, the en-AU locale will use a 12-
//    hour clock by default.  To force a 24-hour clock for time set
//
//    $datetime_formats['time'] = array(
//      'pattern' => 'HH:mm'
//    );
//
//    and similarly for the other formats involving time.  The files in intl/types are useful for
//    seeing what the default pattern is for a locale.

// The format used for dates
$datetime_formats['date'] = array(
  'date_type' => IntlDateFormatter::FULL,
  'time_type' => IntlDateFormatter::NONE
);

// By default the datepickers use a date format appropriate to the locale.  If you want to
// override this, set 'pattern' as required, eg to 'y-MM-dd' for ISO8601 format.  Note: only
// the 'pattern' key is recognised.
$datetime_formats['datepicker'] = array(
  'pattern' => null
);

// The format used for dates with times
$datetime_formats['date_and_time'] = array(
  'date_type' => IntlDateFormatter::FULL,
  'time_type' => IntlDateFormatter::SHORT
);

// The format used for dates with times on the Help page
$datetime_formats['date_and_time_help'] = array(
  'date_type' => IntlDateFormatter::FULL,
  'time_type' => IntlDateFormatter::LONG
);

// The format used for dates with times on the Report
$datetime_formats['date_and_time_report'] = array(
  'date_type' => IntlDateFormatter::FULL,
  'time_type' => IntlDateFormatter::SHORT
);

// Used in policy violation reports for holidays and weekends
$datetime_formats['date_holiday'] = array(
  'date_type' => IntlDateFormatter::SHORT,
  'time_type' => IntlDateFormatter::NONE
);

// Used on the Search page
$datetime_formats['date_search'] = array(
  'date_type' => IntlDateFormatter::SHORT,
  'time_type' => IntlDateFormatter::NONE
);

// The default format for day names
$datetime_formats['day_name'] = array(
  'pattern' => 'cccc'
);

// The format used for the weekly repeat day name on edit_entry.php
$datetime_formats['day_name_edit'] = array(
  'pattern' => 'ccc'
);

// The format for ranges with both dates and times
// Note: this setting only accepts 'date_type' and 'time_type' keys
// and ignores 'pattern' and 'skeleton' keys.
$datetime_formats['range_datetime'] = array(
  'date_type' => IntlDateFormatter::MEDIUM,
  'time_type' => IntlDateFormatter::SHORT
);

// The format used for times
$datetime_formats['time'] = array(
  'date_type' => IntlDateFormatter::NONE,
  'time_type' => IntlDateFormatter::SHORT
);

// The format used for timezones
$datetime_formats['timezone'] = array(
  'pattern' => 'z'
);

// The title of the day view calendar
$datetime_formats['view_day'] = array(
  'date_type' => IntlDateFormatter::FULL,
  'time_type' => IntlDateFormatter::NONE
);

// The title of the month view calendar
$datetime_formats['view_month'] = array(
  'skeleton' => 'MMMMy',
  'pattern' => 'MMMM y'
);

// The day and month as used in the header row of the week view
$datetime_formats['view_week_day_month'] = array(
  'skeleton' => 'dMMM',
  'pattern' => 'MMM d'
);

// The day and month as used in the header column of the week view
$datetime_formats['view_week_day_date_month'] = array(
  'skeleton' => 'dEMMM',
  'pattern' => 'EEE, MMM d'
);

// The title of the week view calendar
// Note: this setting only accepts 'date_type' and 'time_type' keys
// and ignores 'pattern' and 'skeleton' keys.
$datetime_formats['view_week'] = array(
  'date_type' => IntlDateFormatter::LONG,
  'time_type' => IntlDateFormatter::NONE
);

// Week number
$datetime_formats['week_number'] = array(
  'pattern' => 'w'
);

// Sometimes if the server's ICU library is out of date and cannot easily be updated
// it can be better to use the IntlDateFormatter emulation and strftime(), even if the
// 'intl' extension is installed.  To do this set the variable below to true.
$force_srtftime = false;


/***************
 * ICU overrides
 * *************/

// Sometimes we may want to override the standard ICU library settings,
// for example if the ICU library on the server is out of date and can't
// be updated.  This can be done by setting:
//
// $icu_override[<locale>]['first_day_of_week'] and/or
// $icu_override[<locale>]['minimal_days_in_first_week']
//
// where <locale> is a valid locale in BCP 47 format and both settings take
// integer values in the range 1..7 (IntlCalendar days start with Sunday = 1).

// For example:
//
// $icu_override['en-AU']['first_day_of_week'] = 2; // Monday
// $icu_override['en-AU']['minimal_days_in_first_week'] = 1;


/************************
 * Miscellaneous settings
 ************************/

// Default booking duration when using periods.  (The default duration when using
// times is specified in areadefaults.inc.php.)
$default_duration_periods = 1; // Number of periods

// Maximum repeating entries (max needed +1):
$max_rep_entrys = 365 + 1;

// Default report span in days:
$default_report_days = 60;

// Whether to include the name of the person who made the registration, if different, in
// the list of registrants in reports
$include_registered_by = true;
// Whether to include the registrant's username as well as displayname in the list of
// registrants in reports.
$include_registrant_username = false;

$show_plus_link = false;   // Change to true to always show the (+) link as in
                           // MRBS 1.1.

// Determines whether MRBS should get all the display names at once when
// asked to get a single display name.  MRBS converts usernames to display
// names when displaying bookings and in reports.  This can be an expensive
// operation when using an external authentication type, eg 'db_ext', 'ldap'
// or 'wix', and it is usually much faster to retrieve all the names at once
// when getting the first name, especially when producing large reports.  However
// sometimes retrieving all the names can take a very long time, eg when
// working with a very large LDAP directory, and it can be better just to retrieve
// each name when needed.
$get_display_names_all_at_once = true;

// HTML tags that are allowed to be used in the message above the calendar.
// This should be an array of tags, eg ['a', 'span'].
$message_allowed_tags = [];

// PRIVATE BOOKINGS SETTINGS

// Note:  some settings for private bookings can be set on a per-area basis and
// so appear in the areadefaults.inc.php file

// Choose which fields should be private by setting
// $is_private_field['tablename.columnname'] = true
// At the moment only fields in the entry and user tables can be marked as private,
// including custom fields, but with the exception of the following entry table fields:
// start_time, end_time, entry_type, repeat_id, room_id, timestamp, type, status,
// reminded, info_time, info_user, info_text.
$is_private_field['entry.name'] = true;
$is_private_field['entry.description'] = true;
$is_private_field['entry.create_by'] = true;
$is_private_field['entry.modified_by'] = true;


// SETTINGS FOR APPROVING BOOKINGS - PER-AREA

// These settings can all be configured on a per-area basis, so these variables
// appear in the areadefaults.inc.php file.


// SETTINGS FOR APPROVING BOOKINGS - GLOBAL

// These settings are system-wide and control the behaviour in all areas.

// Interval before reminders can be issued (in seconds).   Only
// working days (see below) are included in the calculation
$reminder_interval = 60*60*24*2;  // 2 working days

// Days of the week that are working days (Sunday = 0, etc.)
$working_days = array(1,2,3,4,5);  // Mon-Fri

// SETTINGS FOR BOOKING CONFIRMATION

// These settings can all be be configured on a per-area basis, so these variables
// appear in the areadefaults.inc.php file.


/***********************************************
 * Form values
 ***********************************************/

 $select_options  = array();
// It is possible to constrain some form values to be selected from a drop-
// down select box, rather than allowing free form input.   This is done by
// putting the permitted options in an array as part of the $select_options
// two-dimensional array.   The first index specifies the form field in the
// format tablename.columnname.    For example to restrict the name of a booking
// to 'Physics', 'Chemistry' or 'Biology' uncomment the line below.

//$select_options['entry.name'] = array('Physics', 'Chemistry', 'Biology');

// At the moment $select_options is only supported as follows:
//     - Entry table: name, description and custom fields
//     - User table:  custom fields

// For custom fields only (will be extended later) it is also possible to use
// an associative array for $select_options, for example

//$select_options['entry.catering'] = array('c' => 'Coffee',
//                                          's' => 'Sandwiches',
//                                          'h' => 'Hot Lunch');

// In this case the key (eg 'c') is stored in the database, but the value
// (eg 'Coffee') is displayed and can be searched for using Search and Report.
// This allows you to alter the displayed values, for example changing 'Coffee'
// to 'Coffee, Tea and Biscuits', without having to alter the database.   It can also
// be useful if the database table is being shared with another application.
// MRBS will auto-detect whether the array is associative.
//
// Note that an array such as
//
// $select_options['entry.catering'] = array('2' => 'Coffee',
//                                           '4' => 'Sandwiches',
//                                           '5' => 'Hot Lunch');
//
// will be treated as a simple indexed array rather than as an associative array.
// That's because (a) strictly speaking PHP does not distinguish between indexed
// and associative arrays and (b) PHP will cast any string key that looks like a
// valid integer into an integer.
//
// If you want to make the select field a mandatory field (see below) then include
// an empty string as one of the values, eg
//
//$select_options['entry.catering'] = array(''  => 'Please select one option',
//                                          'c' => 'Coffee',
//                                          's' => 'Sandwiches',
//                                          'h' => 'Hot Lunch');


$datalist_options = array();
// Instead of restricting the user to a fixed set of options using $select_options,
// you can provide a list of options which will be used as suggestions, but the
// user will also be able to type in their own input.   (MRBS presents these using
// an HTML5 <datalist> element in browsers that support it, falling back to a
// JavaScript emulation in browsers that don't - except for IE6 and below where
// an ordinary text input field is presented).
//
// As with $select_options, the array can be either a simple indexed array or an
// associative array, eg array('AL' => 'Alabama', 'AK' => 'Alaska', etc.).   However
// some users might find an associative array confusing as the key is entered in the input
// field when the corresponding value is selected.
//
// At the moment $datalist_options is only supported for the same fields as
// $select_options (see above for details)


$is_mandatory_field = array();
// You can define custom entry fields and some of the standard fields (description
// and type) to be mandatory by setting items in the array $is_mandatory_field.
// (Note that making a checkbox field mandatory means that the box must be checked.)
// For example:

// $is_mandatory_field['entry.type'] = true;
// $is_mandatory_field['entry.terms_and_conditions'] = true;

$is_mandatory_field['user.display_name'] = true;

// You can also enter regular expressions for validating text field input using
// the pattern attribute.  At the moment this is limited to custom fields in the
// user table.  For example the following could be used to ensure a valid US ZIP
// code (you might want to have a better regex - this is just for illustration):

// $pattern['user.zip_code'] = "^[0-9]{5}(?:-[0-9]{4})?$";

// You would probably also want to enter a custom error message by using
// $vocab_override, with the tag consisting of "table.field.oninvalid" eg

// $vocab_override['user.zip_code.oninvalid']['en'] = "Please enter a valid ZIP code, eg '12345' or '12345-6789'";


// Set this to false if you do not want to have the ability to create events for which
// other people can register.
$enable_registration = true;
// By default only admins are allowed to create registration bookings.  If you want
// ordinary users to be able to do so as well then you need to set this to true.
// However note that you will have to set $enable_registration to true as well.
$enable_registration_users = false;

// The default setting for new entries
$allow_registration_default = false;
// Whether a limit on the number of registrants is set by default
$registrant_limit_enabled_default = true;
// The default maximum number of registrations allowed
$registrant_limit_default = 1;
// Whether the registration opens time is enabled by default
$registration_opens_enabled_default = false;
// The default time (in seconds) in advance of the start time when registration opens
$registration_opens_default = 60*60*24*14; // 2 weeks
// Whether the registration closes time is enabled by default
$registration_closes_enabled_default = false;
// The default time (in seconds) in advance of the start time when registration closes
$registration_closes_default = 0;


// Set $skip_default to true if you want the "Skip past conflicts" box
// on the edit_entry form to be checked by default.  (This will mean that
// if you make a repeat booking and some of the repeat dates are already
// booked, MRBS will just skip past those).
$skip_default = false;

// $edit_entry_field_order can be used to change the order of fields in the
// edit_entry page. This is useful to insert custom fields somewhere other than
// the end.  The same order is used for the view_entry page.

// For example: To place a custom field 'in_charge' directly after the
// booking name, set the following in config.inc.php:
//
// $edit_entry_field_order = array('name', 'in_charge');
//
// Valid entries in this array are: 'create_by', 'name', 'description', 'start_time',
// 'end_time', 'room_id', 'type', 'confirmation_status', 'privacy_status',
// plus any custom fields you may have defined. Fields that are not
// mentioned in the array are appended at the end, in their usual order.
$edit_entry_field_order = array();

// You can so the same for the fields in the Search Criteria section of the report
// form.  Valid entries in this array are 'report_start', 'report_end', 'areamatch',
// 'roommatch', 'typematch', 'namematch', 'descrmatch', 'creatormatch', 'match_private',
// 'match_confirmed', 'match_approved', plus any custom fields you may have defined.
$report_search_field_order = array();

// And the same for the fields in the Presentation Options section of the report form.
// Valid entries in this array are 'output', 'output_format', 'sortby' and 'sumby'.
$report_presentation_field_order = array();


/***********************************************
 * Authentication settings - read AUTHENTICATION
 ***********************************************/

// NOTE: if you are using the 'joomla', 'saml' or 'wordpress' authentication type,
// then you must use the corresponding session scheme.

$auth["type"] = "db"; // How to validate the user/password. One of
                      // "auth_basic", "cas", "config", "crypt", "db", "db_ext", "idcheck",
                      // "imap", "imap_php", "joomla", "ldap", "none", "nw", "pop3",
                      // "saml", "wix" or "wordpress".

$auth["session"] = "php"; // How to get and keep the user ID. One of
                          // "cas", "cookie", "host", "http", "ip", "joomla", "nt",
                          // "omni", "php", "remote_user", "saml" or "wordpress".

// Configuration parameters for 'cookie' session scheme

// The encryption secret key for the session tokens. You are strongly
// advised to change this if you use this session scheme
$auth["session_cookie"]["secret"] = "This isn't a very good secret!";
// The expiry time of a session, in seconds. Set to 0 to use session cookies
$auth["session_cookie"]["session_expire_time"] = (60*60*24*30); // 30 days
// Whether to include the user's IP address in their session cookie.
// Increases security, but could cause problems with proxies/dynamic IP
// machines
$auth["session_cookie"]["include_ip"] = true;
// The hash algorithm to use, must be supported by your version of PHP,
// see http://php.net/manual/en/function.hash-algos.php
$auth["session_cookie"]["hash_algorithm"] = 'sha512';

$csrf_cookie["hash_algorithm"] = 'sha512';
$csrf_cookie["secret"] = "This still isn't a very good secret!";

// Configuration parameters for 'php' session scheme

// The session name
// Unset this in your config file if you want to use the default session name
$auth["session_php"]["session_name"] = 'MRBS_SESSID';

// The expiry time of a session cookie, in seconds.  Set it to 0 for the
// session to expire when the browser is closed.
// Note:
// (1) The expiration timestamp is set relative to the server time, which
//     is not necessarily the same as the time in the client's browser.
// (2) If session.gc_maxlifetime is less than the expiry time, MRBS will
//     set it to the expiry time.
$auth["session_php"]["session_expire_time"] = (60*60*24*30); // 30 days

// Set this to the expiry time for a session after a period of inactivity
// in seconds.   Setting to zero means that the session will not expire after
// a period of activity - but note that it will expire if the session cookie
// happens to expire (see above).  Note that if you have $refresh_rate set and
// your system is not capable of doing Ajax refreshes but instead uses a <meta>
// tag to do the refresh, then these refreshes will count as activity - this
// be the case if you have JavaScript disabled on the client.
$auth["session_php"]["inactivity_expire_time"] = 0; // seconds

// Normally, provided the server is running PHP 7.3 or above,  the session cookies
// are issued with SameSite attribute of "Strict", unless the session type requires
// "Lax", eg for CAS and Saml. However, this can be inconvenient for users who might
// access MRBS from more than one site and expect their login status to be retained.
// By setting the variable below to true, the attribute can be relaxed to "Lax",
// although this does trade off some security.
$cookie_samesite_lax = false;

// Cookie path override. If this value is set it will be used by the
// 'php' and 'cookie' session schemes to override the default behaviour
// of automatically determining the cookie path to use
//$cookie_path_override = '/mrbs/';

// The list of administrators (can modify other peoples settings).
//
// This list is not needed when using the 'db' authentication scheme EXCEPT
// when upgrading from a pre-MRBS 1.4.2 system that used db authentication.
// Pre-1.4.2 the 'db' authentication scheme did need this list.   When running
// edit_user.php for the first time in a 1.4.2 system or later, with an existing
// users list in the database, the system will automatically add a field to
// the table for access rights and give admin rights to those users in the database
// for whom admin rights are defined here.   After that this list is ignored.
unset($auth["admin"]);              // Include this when copying to config.inc.php
$auth["admin"][] = "127.0.0.1";     // localhost IP address. Useful with IP sessions.
$auth["admin"][] = "administrator"; // A username from the user list. Useful
                                    // with most other session schemes.
//$auth["admin"][] = "10.0.0.1";
//$auth["admin"][] = "10.0.0.2";
//$auth["admin"][] = "10.0.0.3";

// 'auth_config' user database
// Format: $auth["user"]["name"] = "password";
unset($auth["user"]);              // Include this when copying to config.inc.php
$auth["user"]["administrator"] = "secret";
$auth["user"]["alice"] = "a";
$auth["user"]["bob"] = "b";

// 'session_http' configuration settings
$auth["realm"]  = "mrbs";

// 'session_remote_user' configuration settings
//$auth['remote_user']['login_link'] = '/login/link.html';
//$auth['remote_user']['logout_link'] = '/logout/link.html';

// 'auth_ext' configuration settings
$auth["prog"]   = "";
$auth["params"] = "";

// 'auth_db' configuration settings
// The highest level of access (0=none; 1=user; 2+=admin).    Used in edit_user.php
// In the future we might want a higher level of granularity, eg to distinguish between
// different levels of admin
$max_level = 2;
// The lowest level of admin allowed to view other users
$min_user_viewing_level = 2;
// The lowest level of admin allowed to edit other users
$min_user_editing_level = 2;
// The lowest level of admin allowed to edit other bookings
$min_booking_admin_level = 2;


// Password policy.  Uncomment the variables and set them to the
// required values as appropriate.
// $pwd_policy['length']  = 6;  // Minimum length
// $pwd_policy['alpha']   = 1;  // Minimum number of alpha characters
// $pwd_policy['lower']   = 1;  // Minimum number of lower case characters
// $pwd_policy['upper']   = 1;  // Minimum number of upper case characters
// $pwd_policy['numeric'] = 1;  // Minimum number of numeric characters
// $pwd_policy['special'] = 1;  // Minimum number of special characters (not alphanumeric)

// 'cas' configuration settings
$auth['cas']['host']    = 'cas.example.com';  // Full hostname of your CAS Server
$auth['cas']['port']    = 443;  // CAS server port (integer). Normally for a https server it's 443
$auth['cas']['context'] = '/cas';  // Context of the CAS Server
// The "real" hosts of clustered cas server that send SAML logout messages
// Assumes the cas server is load balanced across multiple hosts.
// Failure to restrict SAML logout requests to authorized hosts could
// allow denial of service attacks where at the least the server is
// tied up parsing bogus XML messages.
//$auth['cas']['real_hosts'] = array('cas-real-1.example.com', 'cas-real-2.example.com');

// Client config for the required domain name, should be protocol, hostname and port
//$auth['cas']['client_service_name'];

// For production use set the CA certificate that is the issuer of the certificate
// on the CAS server
$auth['cas']['ca_cert_path'] = '/path/to/cachain.pem';

// For quick testing you can disable SSL validation of the CAS server.
// THIS SETTING IS NOT RECOMMENDED FOR PRODUCTION.
// VALIDATING THE CAS SERVER IS CRUCIAL TO THE SECURITY OF THE CAS PROTOCOL!
$auth['cas']['no_server_validation'] = false;

// Filtering by attribute
// The next two settings allow you to use CAS attributes to require that a user must have certain
// attributes, otherwise their access level will be zero.  In other words unless they have the required
// attributes they will be able to log in successfully, but then won't have any more rights than an
// unlogged in user.
// $auth['cas']['filter_attr_name'] = ''; // eg 'department'
// $auth['cas']['filter_attr_values'] = ''; // eg 'DEPT01', or else an array, eg array('DEPT01', 'DEPT02');

$auth['cas']['debug']   = false;  // Set to true to enable debug output. Disable for production.


// 'auth_db' configuration settings
// List of fields which only admins can edit.   By default these are the
// user level (ie admin/user) and the username.   Custom fields can be added
// as required.  To protect the password field use 'password_hash' - useful
// for public demo sites.
$auth['db']['protected_fields'] = array('level', 'name', 'display_name', 'roles');
// Expiry time for a password reset key
$auth['db']['reset_key_expiry'] = 60*60*24; // seconds
// Set this to true if you want to prevent users that appear in some form in
// bookings - as creators, modifiers or registrants - from being deleted.
// This will stop their associated information such as display name and email
// address from being lost.
$auth['db']['prevent_deletion_of_users_in_bookings'] = false;


// 'auth_db_ext' configuration settings
// The 'db_system' variable is equivalent to the core MRBS $dbsys variable,
// and allows you to use any of MRBS's database abstraction layers for
// db_ext authentication.
$auth['db_ext']['db_system'] = 'mysql';
// Hostname of external database server. For pgsql, can use "" instead of localhost
// to use Unix Domain Sockets instead of TCP/IP. For mysql "localhost"
// tells the system to use Unix Domain Sockets, and $db_port will be ignored;
// if you want to force TCP connection you can use "127.0.0.1".
$auth['db_ext']['db_host'] = 'localhost';
// If you need to use a non-standard port for the database connection you
// can uncomment the following line and specify the port number
//$auth['db_ext']['db_port'] = 1234;
$auth['db_ext']['db_username'] = 'authuser';
$auth['db_ext']['db_password'] = 'authpass';
$auth['db_ext']['db_name'] = 'authdb';
$auth['db_ext']['db_table'] = 'users';
$auth['db_ext']['column_name_username'] = 'name';
$auth['db_ext']['column_name_display_name'] = 'display_name';  // optional
$auth['db_ext']['column_name_password'] = 'password';
$auth['db_ext']['column_name_email'] = 'email';
// Below is an example if you want to put the MRBS user level in the DB
//$auth['db_ext']['column_name_level'] = 'mrbs_level';
// Can be 'password_hash', 'crypt', 'plaintext' or any algorithm supported
// by the PHP hash() function, eg 'md5', 'sha1', 'sha256'.
$auth['db_ext']['password_format'] = 'md5';

// 'auth_ldap' configuration settings

// Many of the LDAP parameters can be specified as arrays, in order to
// specify multiple LDAP directories to search within. Each item below
// will specify whether the item can be specified as an array. If any
// parameter is specified as an array, then EVERY array configuration
// parameter must have the same number of elements. You can specify a
// parameter as an array as in the following example:
//
// $ldap_host = array('localhost', 'otherhost.example.com');

// Where is the LDAP server.   This should ideally consist of a scheme and
// a host, eg "ldap://foo.com" or "ldaps://bar.com", but just a hostname
// is supported for backwards compatibility.
// This can be an array.
//$ldap_host = "localhost";

// If you have a non-standard LDAP port, you can define it here.
// This can be an array.
//$ldap_port = 389;

// If you do not want to use LDAP v3, change the following to false.
// This can be an array.
$ldap_v3 = true;

// If you want to use TLS, change the following to true.
// This can be an array.
$ldap_tls = false;

// Support configuring a TLS client certificate/key from within MRBS.
// Requires PHP 7.1.0 or later
//$ldap_client_cert = 'path-to-cert.crt';
//$ldap_client_key = 'path-to-key.key';

// LDAP base distinguish name.
// This can be an array.
//$ldap_base_dn = "ou=organizationalunit,dc=example,dc=com";

// Attribute within the base dn that contains the username.
// In Microsoft AD directories this is "sAMAccountName".
// This can be an array.
//$ldap_user_attrib = "uid";

// If you need to search the directory to find the user's DN to bind
// with, set the following to the attribute that holds the user's
// "username". In Microsoft AD directories this is "sAMAccountName"
// This can be an array.
//$ldap_dn_search_attrib = "sAMAccountName";

// If you need to bind as a particular user to do the search described
// above, specify the DN and password in the variables below
// These two parameters can be arrays.
// $ldap_dn_search_dn = "cn=Search User,ou=Users,dc=example,dc=com"; // Any compliant LDAP
// $ldap_dn_search_dn = "searchuser@example.com"; // A form which could work for AD LDAP
// $ldap_dn_search_password = "some-password";

// 'auth_ldap' extra configuration for ldap configuration of who can use
// the system
// If it's set, the $ldap_filter will be used to determine whether a
// user will be granted access to MRBS
// This can be an array.
// An example for Microsoft AD:
//$ldap_filter = "memberof=cn=whater,ou=whatver,dc=example,dc=com";

// If you need to filter a user by the group a user is in with an LDAP
// directory which stores group membership in the group object
// (like OpenLDAP) then you need to search for the groups they are
// in. If you want to do this, define the following two variables, an
// an appropriate $ldap_filter. e.g.:
// $ldap_filter_base_dn = "ou=Groups,dc=example,dc=com";
$ldap_filter_user_attr = "memberuid";
// $ldap_filter = "cn=MRBS Users";

// If you need to disable client referrals, this should be set to true.
// Note: Active Directory for Windows 2003 forward requires this.
// $ldap_disable_referrals = true;

// LDAP option for dereferencing aliases
// LDAP_DEREF_NEVER = 0 - (default) aliases are never dereferenced.
// LDAP_DEREF_SEARCHING = 1 - aliases should be dereferenced during the search
//      but not when locating the base object of the search.
// LDAP_DEREF_FINDING = 2 - aliases should be dereferenced when locating the base object but not during the search.
// LDAP_DEREF_ALWAYS = 3 - aliases should be dereferenced always.
//$ldap_deref = LDAP_DEREF_ALWAYS;

// Set to true to tell MRBS to look up a user's email address in LDAP.
// Utilises $ldap_email_attrib below
$ldap_get_user_email = false;
// The LDAP attribute which holds a user's email address
// This can be an array.
$ldap_email_attrib = 'mail';
// The LDAP attribute which holds a user's name. Another common attribute
// to use (with Active Directory) is 'displayname'.
// This can be an array.
// The name attribute can also be a composite attribute consisting of individual
// LDAP attributes separated by spaces, eg 'givenName sn'.
$ldap_name_attrib = 'cn';

// The DN of the LDAP group that MRBS admins must be in. If this is defined
// then the $auth["admin"] is not used.
// This can be an array.
// $ldap_admin_group_dn = 'cn=admins,ou=whoever,dc=example,dc=com';

// The LDAP attribute that holds group membership details. Used with
// $ldap_admin_group_dn, above.
// This can be an array.
$ldap_group_member_attrib = 'memberof';

// Set to true if you want MRBS to call ldap_unbind() between successive
// attempts to bind. Unbinding while still connected upsets some
// LDAP servers
$ldap_unbind_between_attempts = false;

// By default MRBS will suppress "invalid credentials" error messages when binding
// in order to avoid the log filling up with warning messages when a user enters
// an incorrect username/password combination.  Set this to FALSE if you want these
// errors to be logged, eg in order to be able spot brute force attack attempts.
$ldap_suppress_invalid_credentials = true;

// Output debugging information for LDAP actions
$ldap_debug = false;

// Output debugging information for LDAP actions and also attribute names and values.
// A higher level of debugging, useful for discovering attribute names.
$ldap_debug_attributes = false;

// 'auth_imap' configuration settings
// See AUTHENTICATION for details of how check against multiple servers
// Where is the IMAP server
$imap_host = "imap-server-name";
// The IMAP server port
$imap_port = "143";

// 'auth_imap_php' configuration settings
$auth["imap_php"]["hostname"] = "localhost";
// You can also specify any of the following options:
// Specifies the port number to connect to
//$auth["imap_php"]["port"] = 993;
// Use SSL
//$auth["imap_php"]["ssl"] = true;
// Use TLS
//$auth["imap_php"]["tls"] = true;
// Turn off SSL/TLS certificate validation
//$auth["imap_php"]["novalidate-cert"] = true;

// Restrict authentication to usernames from a particular domain.  Useful
// when authenticating against a server such as outlook.office365.com which
// supports usernames from many domains.
//$auth['imap_php']['user_domain'] = 'example.com';

// 'auth_pop3' configuration settings
// See AUTHENTICATION for details of how check against multiple servers
// Where is the POP3 server
$pop3_host = "pop3-server-name";
// The POP3 server port
$pop3_port = "110";

// 'auth_smtp' configuration settings
$auth['smtp']['server'] = 'myserver.example.org';


// 'auth_joomla' configuration settings
$auth['joomla']['rel_path'] = '..';   // Path to the Joomla! installation relative to MRBS.
// Be sure to set the cookie path in your Joomla administrator Global Configuration Site settings
// to cover both the Joomla and MRBS installations, eg '/'.

// [Note that although in Joomla! access levels are solely used for what users are allowed to *see*, we use
// them in MRBS to determine what they can see and do, ie we map them onto MRBS user levels.  While this
// does not strictly follow the Joomla! access control model, it does make it much simpler to give users
// MRBS permissions.]

// List of Joomla! viewing access level ids that have MRBS Admin capabilities.  You can if you wish use
// the existing viewing access levels.  However we recommend creating a new access level, eg
// "MRBS Administrator" and assigning that to user groups, as it will then be clearer which groups
// have what kind of access to MRBS.
$auth['joomla']['admin_access_levels'] = array(); // Can either be a single integer, or an array of integers.
// As above, but for ordinary user rights.  Create for example a viewing access level called "MRBS User"
// and assign that level to user groups as appropriate.
$auth['joomla']['user_access_levels'] = array(); // Can either be a single integer, or an array of integers.


// 'auth_saml' configuration settings
// (assuming Active Directory attributes):
$auth['saml']['ssp_path'] = '/opt/simplesamlphp';  // must be an absolute and not a relative path
$auth['saml']['authsource'] = 'default-sp';
$auth['saml']['attr']['username'] = 'sAMAccountName';
$auth['saml']['attr']['mail'] = 'mail';
$auth['saml']['attr']['givenName'] = 'givenname';
$auth['saml']['attr']['surname'] = 'sn';
// If you want to configure admins in the config file rather than by using SAML
// attributes, then add the line
// unset($auth['saml']['admin']);
// to your config file.
$auth['saml']['admin']['memberOf'] = ['CN=Domain Admins,CN=Users,DC=example,DC=com'];
// Optional access control filter
//$auth['saml']['user']['memberOf'] = ['CN=Calendar Users,CN=Users,DC=example,DC=com'];
// MRBS session initialisation can interfere with session handling in some
// SAML libraries.  If so, set this to true.
$auth['saml']['disable_mrbs_session_init'] = false;

// This scheme assumes that you've already configured SimpleSamlPhp,
// and that you have set up aliases in your webserver so that SimpleSamlPhp
// can handle incoming assertions.  Refer to the SimpleSamlPhp documentation
// for more information on how to do that.
//
// https://simplesamlphp.org/docs/stable/simplesamlphp-install
// https://simplesamlphp.org/docs/stable/simplesamlphp-sp


// 'auth_wix' configuration settings
$auth['wix']['site_url'] = "https://example.com/";  // The URL of your WIX site

// The API key that you generated and saved in your Wix secrets manager.
$auth['wix']['mrbs_api_key'] = "";

// The name of the secret in your Wix secrets manager
$auth['wix']['mrbs_api_key_secret_name'] = "MRBS_API_key";

// The name (title) of the badge that determines whether a member is an
// MRBS admin.  Note that badge names are case-sensitive.  You can also
// configure admins in the config file by using
// $auth['admin'][] = "someone@example.com";
$auth['wix']['admin_badge'] = "MRBS Admin";

// The name of the member property to be used for the display name.
// Typically either 'name' or 'nickname'.
$auth['wix']['display_name_property'] = 'name';

// The number of results to be found at a time in the Wix backend when getting
// a list of all members.  This is a configuration setting that is passed to
// the Wix backend code as part of the request.  It is just used internally in
// the backend and doesn't affect the size of the list returned to MRBS.
$auth['wix']['limit'] = 500;

// Setting this to true will cause debug information to be written to the PHP
// error log.
$auth['wix']['debug'] = false;

// 'auth_wordpress' configuration settings
$auth['wordpress']['rel_path'] = '..';   // Path to the WordPress installation relative to MRBS.
// List of WordPress roles that have MRBS Admin capabilities.  The default is 'administrator'.
// Note that these role names are the keys used to store the name, which are typically in lower case
// English, eg 'administrator', and not the values which are displayed on the dashboard form, which will
// generally start with a capital and be translated, eg 'Administrator' or 'Administrateur' (French),
// depending on the site language you have chosen for WordPress.
// You can define more than one WordPress role that maps to the MRBS Admin role by using
// an array.   The comment below assumes that you have created a new WordPress role (probably by using
// a WordPress plugin) called "MRBS Admin", which will typically (depending on the plugin) have a key of
// 'mrbs_admin', and that you assigned that role to those users that you want to be MRBS admins.
$auth['wordpress']['admin_roles'] = 'administrator';  // can also be an array, eg = array('administrator', 'mrbs_admin');
// List of WordPress roles that have MRBS User capabilities.  This allows you to have some WordPress users
// who are authorised to use MRBS and some who are not.
$auth['wordpress']['user_roles'] = array('subscriber', 'contributor', 'author', 'editor', 'administrator');
// List of WordPress roles that are blacklisted.  In other words if a user has a blacklisted role then they
// will be assigned MRBS access level 0, even if they also have a user or admin role.   This feature can be
// useful for disabling MRBS access for certain users by assigning them a WordPress role.
$auth['wordpress']['blacklisted_roles'] = array();

// Note - you are also advised to set the following in your wp-config.php so that the auth
// cookies can be shared between MRBS and WordPress:

/*
// Define cookie paths so that login cookies can be shared with MRBS
$domain_name = 'example.com';  // Set to your domain name
define('COOKIEPATH', '/');
define('SITECOOKIEPATH', '/');
// In the definition below the '.' is necessary for older browsers (see
// http://php.net/manual/en/function.setcookie.php).
define('COOKIE_DOMAIN', ".$domain_name");
define('COOKIEHASH', md5($domain_name));
*/


// General settings

// Allow users just to enter the local-part of their email address (provided that
// the authentication type supports validation by local-part).   For example, if user
// with username 'john' has email address 'jsmith@example.com', then he would be able
// to enter either 'john', 'jsmith' or 'jsmith@example.com' when logging in.
// Only supported for the 'db' authentication type.
$auth['allow_local_part_email'] = false;

// Set this to true if you want users to be able to make bookings without logging in.
$auth['allow_anonymous_booking'] = false;

// If you want only administrators to be able to make and delete bookings,
// set this variable to true
$auth['only_admin_can_book'] = false;

// This allows you to set a date (and time) before which only admins can make
// bookings.  This is useful if you want to set a "go live" date and time for MRBS.
// The variable should be set to a valid date/time format as described in
// https://www.php.net/manual/en/datetime.formats.php.   For example set
// $auth['only_admin_can_book_before'] = "2020-12-31 18:00";
// if you want booking to go live at 6pm on 31 Dec 2020.
// Note that $auth['only_admin_can_book_before'] will only be considered if
// $auth['only_admin_can_book'] is false.
$auth['only_admin_can_book_before'] = false;

// If you want only administrators to be able to make repeat bookings,
// set this variable to true
$auth['only_admin_can_book_repeat'] = false;

// If you want only administrators to be able to make bookings spanning
// more than one day, set this variable to true.
$auth['only_admin_can_book_multiday'] = false;

// If you want only administrators to be able to select multiple rooms
// on the booking form then set this to true.  (It doesn't stop ordinary users
// making separate bookings for the same time slot, but it does slow them down).
$auth['only_admin_can_select_multiroom'] = false;

// Set this to true if you want to restrict the ability to use the "Copy" button on
// the view_entry page to ordinary users viewing their own entries and to admins.
$auth['only_admin_can_copy_others_entries'] = false;

// If you don't want ordinary users to be able to see the other users'
// details then set this to true.  Used by the 'db' authentication scheme to determine
// whether to show other users to non-admins, and also generally to determine whether
// to create mailto: links, eg when viewing booking details.
$auth['only_admin_can_see_other_users'] = false;

// For events that allow registration, the other registrants' names are by default
// not shown unless you have write access to the booking.
$auth['show_registrant_names'] = false;

// For events that allow registration you can also show the registrants' names in
// the calendar view, whether or not you have write access to the booking.
// NOTE: you also need $show_registration_level = true; for this to work.
$auth['show_registrant_names_in_calendar'] = false;

// You can additionally choose whether to show the registrants' names in the calendar
// if the calendar is open to the public and the user is not logged in or has level 0 access.
// NOTE: you also need $auth['show_registrant_names_in_calendar'] = true; for this to work
$auth['show_registrant_names_in_public_calendar'] = false;

// Set this to true if you want ordinary users to be able to register others.
$auth['users_can_register_others'] = false;

// Set this to true if you don't want admins to be able to make bookings
// on behalf of other users
$auth['admin_can_only_book_for_self'] = false;

// An array of booking types for admin use only
$auth['admin_only_types'] = array();  // eg array('E');

// If you want to prevent the public (ie un-logged in users) from
// being able to view bookings completely, set this variable to true
$auth['deny_public_access'] = false;

// Or else you can allow them to see that there is a booking, but the
// details will be shown as private if you set this to true.
$auth['force_private_for_guests'] = false;

// Set to true if you want admins to be able to perform bulk deletions
// on the Report page.  (It also only shows up if JavaScript is enabled)
$auth['show_bulk_delete'] = false;

// Allow admins to insert custom HTML on the area and room pages.  This can be useful for
// displaying information about an area or room, eg with a picture or a map.   But it
// also presents a security risk as the HTML is output as is, and could therefore contain
// malicious scripts.   Only set $auth['allow_custom_html'] to true if you trust your admins.
$auth['allow_custom_html'] = false;

// Set to true if you want to allow MRBS to be run from the command line, for example
// if you want to produce reports from a cron job.   (It is set to false by default
// as a security measure, because when running from the CLI you are assumed to have
// full admin access).
$allow_cli = false;

// Set to true if you want usernames and passwords submitted in the login form to be
// recorded in the error log as part of the $_POST variable.  Otherwise they are
// replaced by '****', unless they are the empty string ''.
$auth['log_credentials'] = false;


/**********************************************
 * Email settings
 **********************************************/

// BASIC SETTINGS
// --------------

// Set the email address of the From field. Default is 'admin_email@your.org'
$mail_settings['from'] = 'admin_email@your.org';

// By default MRBS will send some emails (eg booking approval emails) as though they have come from
// the user, rather than the From address above.   However some email servers will not allow this in
// order to prevent email spoofing.   If this is the case then set this to true in order that the
// From address above is used for all emails.
$mail_settings['use_from_for_all_mail'] = false;

// By default MRBS will set a Reply-To address and use current user's email address.  Set this to
// false in order not to set a Reply-To address.
$mail_settings['use_reply_to'] = true;

// The address to be used for the ORGANIZER in an iCalendar event.  The address should
// be an RFC822-style address of the form "display name <address>" or just "address".
// Do not make this email address the same as the admin email address or the recipients
// email address because on some mail systems, eg IBM Domino, the iCalendar email
// notification is silently discarded if the organizer's email address is the same
// as the recipient's.  On other systems you may get a "Meeting not found" message.
$mail_settings['organizer'] = 'mrbs@your.org';

// Set the recipient email. Default is 'admin_email@your.org'. You can define
// more than one recipient like this "john@doe.com,scott@tiger.com"
$mail_settings['recipients'] = 'admin_email@your.org';

// Set email address of the Carbon Copy field. Default is ''. You can define
// more than one recipient (see 'recipients')
$mail_settings['cc'] = '';

// Set to true if you want the cc addresses to be appended to the to line.
// (Some email servers are configured not to send emails if the cc or bcc
// fields are set)
$mail_settings['treat_cc_as_to'] = false;



// WHO TO EMAIL
// ------------
// The following settings determine who should be emailed when a booking is made,
// edited or deleted (though the latter two events depend on the "When" settings below).
// Set to true or false as required
// (Note:  the email addresses for the area and room administrators are set from the
// edit_area.php and edit_room.php pages in MRBS)
$mail_settings['admin_on_bookings']      = false;  // the addresses defined by $mail_settings['recipients'] below
$mail_settings['area_admin_on_bookings'] = false;  // the area administrator
$mail_settings['room_admin_on_bookings'] = false;  // the room administrator
$mail_settings['booker']                 = false;  // the person making the booking
$mail_settings['book_admin_on_approval'] = false;  // the booking administrator when booking approval is enabled
                                                   // (which is the MRBS admin, but this setting allows MRBS
                                                   // to be extended to have separate booking approvers)

// WHEN TO EMAIL
// -------------
// These settings determine when an email should be sent.
// Set to true or false as required
//
// (Note:  (a) the variables $mail_settings['admin_on_delete'] and
// $mail_settings['admin_all'], which were used in MRBS versions 1.4.5 and
// before are now deprecated.   They are still supported for reasons of backward
// compatibility, but they may be withdrawn in the future.  (b)  the default
// value of $mail_settings['on_new'] is true for compatibility with MRBS 1.4.5
// and before, where there was no explicit config setting, but mails were always sent
// for new bookings if there was somebody to send them to)

$mail_settings['on_new']    = true;   // when an entry is created
$mail_settings['on_change'] = false;  // when an entry is changed
$mail_settings['on_delete'] = false;  // when an entry is deleted

// It is also possible to allow all users or just admins to choose not to send an
// email when creating or editing a booking.  This can be useful if an inconsequential
// change is being made, or many bookings are being made at the beginning of a term or season.
$mail_settings['allow_no_mail']        = false;
$mail_settings['allow_admins_no_mail'] = false;  // Ignored if 'allow_no_mail' is true
$mail_settings['no_mail_default'] = false; // Default value for the 'no mail' checkbox.
                                           // true for checked (ie don't send mail),
                                           // false for unchecked (ie do send mail)


// WHAT TO EMAIL
// -------------
// These settings determine what should be included in the email
// Set to true or false as required
$mail_settings['details']   = false; // Set to true if you want full booking details;
                                     // otherwise you just get a link to the entry
$mail_settings['html']      = false; // Set to true if you want HTML mail
$mail_settings['icalendar'] = false; // Set to true to include iCalendar details
                                     // which can be imported into a calendar.  (Note:
                                     // iCalendar details will not be sent for areas
                                     // that use periods as there isn't a mapping between
                                     // periods and time of day, so the calendar would not
                                     // be able to import the booking)

// HOW TO EMAIL - LANGUAGE
// -----------------------------------------

// Set the language used for emails.  This should be in the form of a BCP 47
// language tag, eg 'en-GB'.  MRBS will use the language tag to set the locale
// for date and time formats, and find the best match in the lang.* files for
// translations.  For example, setting the admin_lang to 'en' will give English
// text and am/pm style times; setting it to 'en-GB' will give English text with
// 24-hour times.
$mail_settings['admin_lang'] = 'en';   // Default is 'en'.


// HOW TO EMAIL - ADDRESSES
// ------------------------
// The email addresses of the MRBS administrator are set in the config file, and those of
// the room and area administrators are set though the edit_area.php and edit_room.php
// pages in MRBS.  But if you have set $mail_settings['booker'] above to true, MRBS will
// need the email addresses of ordinary users.   If you are using the "db"
// authentication method then MRBS will be able to get them from the user table.  But
// if you are using any other authentication scheme then the following settings allow
// you to specify a domain name that will be appended to the username to produce a
// valid email address (eg "@domain.com").  MRBS will add the '@' character for you.
$mail_settings['domain'] = '';
// If you use $mail_settings['domain'] above and the username returned by mrbs contains extra
// strings appended like the domain name ('username.domain'), you need to provide
// this extra string here so that it will be removed from the username.
$mail_settings['username_suffix'] = '';


// HOW TO EMAIL - BACKEND
// ----------------------
// Set the name of the backend used to transport your mails. Either 'mail',
// 'smtp', 'sendmail' or 'qmail'. Default is 'mail'.
$mail_settings['admin_backend'] = 'mail';

/*******************
 * Sendmail settings
 */

// Set the path of the Sendmail program (only used with "sendmail" backend).
// Default is '/usr/bin/sendmail'
$sendmail_settings['path'] = '/usr/bin/sendmail';
// Set additional Sendmail parameters (only used with "sendmail" backend).
// (example "-t -i"). Default is ''
$sendmail_settings['args'] = '';

/*******************
 * Qmail settings
 */

/* Configures the path to 'qmail-inject', if unset defaults to '/var/qmail/bin/qmail-inject' */
$mail_settings['qmail']['qmail-inject-path'] = '/usr/bin/qmail-inject';

/*******************
 * SMTP settings
 */

// These settings are only used with the "smtp" backend
$smtp_settings['host'] = 'localhost';  // SMTP server
$smtp_settings['port'] = 25;           // SMTP port number
$smtp_settings['auth'] = false;        // Whether to use SMTP authentication
$smtp_settings['secure'] = '';         // Encryption method: '', 'tls' or 'ssl' - note that 'tls' means TLS is used even if the SMTP
                                       // server doesn't advertise it. Conversely if you specify '' and the server advertises TLS, TLS
                                       // will be used, unless the 'disable_opportunistic_tls' configuration parameter shown below is
                                       // set to true.
$smtp_settings['username'] = '';       // Username (if using authentication)
$smtp_settings['password'] = '';       // Password (if using authentication)

// The hostname to use in the Message-ID header and as default HELO string.
// If empty, PHPMailer attempts to find one with, in order,
// $_SERVER['SERVER_NAME'], gethostname(), php_uname('n'), or the value
// 'localhost.localdomain'.
$smtp_settings['hostname'] = '';

// The SMTP HELO/EHLO name used for the SMTP connection.
// Default is $smtp_settings['hostname']. If $smtp_settings['hostname'] is empty, PHPMailer attempts to find
// one with the same method described above for $smtp_settings['hostname'].
$smtp_settings['helo'] = '';

$smtp_settings['disable_opportunistic_tls'] = false; // Set this to true to disable
                                                     // opportunistic TLS
                                                     // https://github.com/PHPMailer/PHPMailer/wiki/Troubleshooting#opportunistic-tls
// If you're having problems with sending email to a TLS-enabled SMTP server *which you trust* you can change the following
// settings, which reduce TLS security.
// See https://github.com/PHPMailer/PHPMailer/wiki/Troubleshooting#php-56-certificate-verification-failure
$smtp_settings['ssl_verify_peer'] = true;
$smtp_settings['ssl_verify_peer_name'] = true;
$smtp_settings['ssl_allow_self_signed'] = false;

// EMAIL - MISCELLANEOUS
// ---------------------

// The filename to be used for iCalendar attachments.   Will always have the
// extension '.ics'
$mail_settings['ics_filename'] = "booking";

// The rate at which emails can be sent out can be throttled if necessary in order to help
// keep within a mail server's limits.  Note that the throttle only applies to emails being
// sent by one user.  If another user is generating email notifications at the same time
// then these won't be taken into account.   Note also that if the email is going to n
// different addresses then this counts as n emails, as that is how most servers operate.
// A setting of zero disables throttling.
$mail_settings['rate_limit'] = 0;  // emails per second (float or int)

// Set this to true if you want MRBS to output debug information when you are sending email.
// If you are not getting emails it can be helpful by telling you (a) whether the mail functions
// are being called in the first place (b) whether there are addresses to send email to and (c)
// the result of the mail sending operation.
$mail_settings['debug'] = false;
// Where to send the debug output.  Can be 'browser' or 'log' (for the error_log)
$mail_settings['debug_output'] = 'log';

// Set this to true if you do not want any email sent, whatever the rest of the settings.
// This is a global setting that will override anything else.   Useful when testing MRBS.
$mail_settings['disabled'] = false;


/**********
 * Language
 **********/

// Set this to true to disable the automatic language changing MRBS performs
// based on the user's browser language settings. It will ensure that
// the language displayed is always the value of $default_language_tokens,
// as specified below
$disable_automatic_language_changing = false;

// Set this to a different language specifier to default to different
// language tokens. This must equate to a lang.* file in MRBS.
// e.g. use "fr" to use the translations in "lang.fr" as the default
// translations.  [NOTE: it is only necessary to change this if you
// have disabled automatic language changing above]
$default_language_tokens = "en";

// Set this to a valid locale that is supported on the OS you run the
// MRBS server on if you want to override the automatic locale determination
// MRBS performs.  The locale should be in the form of a BCP 47 language
// tag, eg 'en-GB', or 'sr-Latn-RS'.   Note that MRBS will convert this into
// a format suitable for your OS, eg by adding '.utf-8' or changing it to 'eng'.
$override_locale = "";

// FAQ file language selection. If not set, use the default English file.
// If your language faq file is available, set $faqfilelang to match the
// end of the file name, excluding the underscore (eg for site_faq_fr.html
// use "fr").  For compatibility with older versions of MRBS settings with
// the underscore, eg "_fr" are supported, but deprecated.
$faqfilelang = "";

// Language selection when run from the command line
$cli_language = "en";

// Set to true to get debug information on languages and locales written to the
// error log.
$language_debug = false;

// Vocab overrides
// ---------------

// You can override the text strings that appear in the lang.* files by setting
// $vocab_override[LANG][TOKEN] in your config file, where LANG is the language,
// for example 'en' and TOKEN is the key of the $vocab array.  For example to
// alter the string "Meeting Room Booking System" in English set
//
// $vocab_override['en']['mrbs'] = "My Resource Booking System";
//
// Applying vocab overrides in the config file rather than editing the lang files
// mean that your changes will be preserved when you upgrade to the next version of
// MRBS and you won't have to re-edit the lang file.

/*************
 * Reports
 *************/

// Default form options

// Sort report by 'r' for room, 's' for start time.
$default_sortby = 'r';

// Summary: sum by 'd' for brief description, 'c' for creator, 't' for type
$default_sumby = 'd';

// Default file names
$report_filename  = "report";
$search_filename  = "search";
$summary_filename = "summary";

// CSV format
// By default Excel expects a tab as the column separator, so if you are opening
// CSV files with Excel you may want to change $csv_col_sep to be "\t" (note that
// the double quotes are important to ensure this is interpreted as a tab character).
$csv_row_sep = "\n";  // Separator between rows/records
$csv_col_sep = ",";   // Separator between columns/fields

// CSV charset
// Set the character set to be used for CSV files.   If $csv_charset is not set
// then CSV files are written using the MRBS default charset (utf-8).
// Earlier versions of Microsoft Excel (at least up to Excel 2010 on Windows and 2011
// on Mac) are not guaranteed to recognise utf-8, but do recognise utf-16, so for those
// versions try setting $csv_charset to 'utf-16' and $csv_bom to true.
$csv_charset = 'utf-8';
$csv_bom = false;

// UNAUTHENTICATED GET REQUESTS TO REPORT.PHP
// These allow calendar programs to subscribe to MRBS, by using for example
// path_to_mrbs/report.php?phase=2&output_format=2

// Set this to true to allow unauthenticated GET requests to report.php
$report_unauthenticated_get_enabled = false;

// Set this to TRUE to require a key in the query string for unauthenticated
// GET requests to report.php.  For example report.php?phase=2&output_format=2&key=secret
$report_keys_enabled = false;

// An array of valid keys
$report_keys = [];  // An array of strings

// If unauthenticated GET requests are allowed then only bookings in rooms
// or areas that are open will be shown.
$report_open_areas = []; // An array of integer area ids
$report_open_rooms = []; // An array of integer room ids

/*************
 * iCalendar
 *************/

// The default delimiter for separating the area and room in the LOCATION property
// of an iCalendar event.   Note that no escaping of the delimiter is provided so
// it must not occur in room or area names.
$default_area_room_delimiter = '/';

// Set the default source type for imports.  Can be 'file' or 'url'
$default_import_source = 'file';

// Default setting for importing past events
$default_import_past = true;

// By default iCalendar notifications will be sent with the PARTSTAT property set to
// "NEEDS-ACTION".  If you set this variable to true then it will be set to "ACCEPTED".
// This will change how the notification is treated by your email/calendar client.
// See RFC 5545 for more details.
$partstat_accepted = false;


/*************
 * Entry Types
 *************/

// This array lists the configured entry type codes. The values map to a
// single char in the MRBS database, and so can be any permitted PHP array
// character.
//
// The default descriptions of the entry types are held in the language files
// as "type.X" where 'X' is the entry type.  If you want to change the description
// you can override the default descriptions by setting the $vocab_override config
// variable.   For example, if you add a new booking type 'C' the minimum you need
// to do is add a line to config.inc.php like:
//
// $vocab_override["en"]["type.C"] =     "New booking type";
//
// Below is a basic default array which ensures there are at least some types defined.
// The proper type definitions should be made in config.inc.php.
//
// Each type has a color which is defined in the array $color_types in the styling.inc
// file in the Themes directory

unset($booking_types);    // Include this line when copying to config.inc.php
$booking_types[] = "E";
$booking_types[] = "I";

// If you don't want to use types then uncomment the following line.  (The booking will
// still have a type associated with it in the database, which will be the default type.)
// unset($booking_types);

// Default brief description for new bookings
$default_name = "";

// Set this to true if you want the booking name (brief description) to
// default to the current user's display name.  If set, this setting overrides
// $default_name.
$default_name_display_name = false;

// Default long description for new bookings
$default_description = "";

/***********************
 * Ajax request settings
 ***********************/

// We send Ajax requests to ajax/del_entries.php with data as an array of ids.
// In order to stop the POST request getting too large and triggering a 406
// error, or else exceeding the maximum size of an SQL query, we split the request
// into batches with the maximum number of ids in the array defined below.
$del_entries_ajax_batch_size = 5000;
// The maximum number of parallel requests to ajax/del_entries.php. Increasing
// this number will increase the speed of processing, but if it's too large will
// increase the load on the server and possibly cause errors.
$del_entries_parallel_requests = 2;


// Only required if your MRBS installation runs from a Git repository
// and you want the "Help" page to show the Git commit ID you are on. Default
// should work if "git" is in your search path, on Windows you may need to specify the
// full path to your "git" executable, e.g.:
// "c:/Program Files/TortoiseGit/git.exe"
$git_command = "git";
