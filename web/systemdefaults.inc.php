<?php

// $Id$

/**************************************************************************
 *   MRBS system defaults file
 *
 * DO _NOT_ MODIFY THIS FILE YOURSELF. IT FOR _INTERNAL_ USE ONLY.
 *
 * TO CONFIGURE MRBS FOR YOUR SYSTEM ADD CONFIGURATION PARAMETERS FROM
 * THIS FILE INTO config.inc.php, DO _NOT_ EDIT THIS FILE.
 *
 **************************************************************************/

// The timezone your meeting rooms run in. It is especially important
// to set this if you're using PHP 5 on Linux. In this configuration
// if you don't, meetings in a different DST than you are currently
// in are offset by the DST offset incorrectly.
//
// When upgrading an existing installation, this should be set to the
// timezone the web server runs in.
//
//$timezone = "Europe/London";

/*******************
 * Database settings
 ******************/
// Which database system: "pgsql"=PostgreSQL, "mysql"=MySQL,
// "mysqli"=MySQL via the mysqli PHP extension
$dbsys = "mysql";
// Hostname of database server. For pgsql, can use "" instead of localhost
// to use Unix Domain Sockets instead of TCP/IP.
$db_host = "localhost";
// Database name:
$db_database = "mrbs";
// Database login user name:
$db_login = "mrbs";
// Database login password:
$db_password = 'mrbs-password';
// Prefix for table names.  This will allow multiple installations where only
// one database is available
$db_tbl_prefix = "mrbs_";
// Uncomment this to NOT use PHP persistent (pooled) database connections:
// $db_nopersist = 1;

// Field lengths in the database tables
// NOTE:  these must be kept in step with the database.   If you change the field
// lengths in the database then you should change the values here, and vice versa.
$maxlength['entry.name']       = 80;  // characters   (name field in entry table)
$maxlength['area.area_name']   = 30;  // characters   (area_name field in area table)
$maxlength['room.room_name']   = 25;  // characters   (room_name field in room table)
$maxlength['room.description'] = 60;  // characters   (description field in room table)
$maxlength['users.name']       = 30;  // characters   (name field in users table)
$maxlength['users.email']      = 75;  // characters   (email field in users table)
// other values for the users table need to follow the $maxlength['users.fieldname'] pattern


/*********************************
 * Site identification information
 *********************************/
$mrbs_admin = "Your Administrator";
$mrbs_admin_email = "admin_email@your.org";  // NOTE:  there are more email addresses in $mail_settings below

// The company name is mandatory.   It is used in the header and also for email notifications.
// The company logo, additional information and URL are all optional.

$mrbs_company = "Your Company";   // This line must always be uncommented ($mrbs_company is used in various places)

// Uncomment this next line to use a logo instead of text for your organisation in the header
//$mrbs_company_logo = "your_logo.gif";    // name of your logo file.   This example assumes it is in the MRBS directory

// Uncomment this next line for supplementary information after your company name or logo
//$mrbs_company_more_info = "You can put additional information here";  // e.g. "XYZ Department"

// Uncomment this next line to have a link to your organisation in the header
//$mrbs_company_url = "http://www.your_organisation.com/";

// This is to fix URL problems when using a proxy in the environment.
// If links inside MRBS appear broken, then specify here the URL of
// your MRBS root directory, as seen by the users. For example:
// $url_base =  "http://webtools.uab.ericsson.se/oam";
// It is also recommended that you set this if you intend to use email
// notifications, to ensure that the correct URL is displayed in the
// notification.
$url_base = "";


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


/*******************
 * Calendar settings
 *******************/

// Note: Be careful to avoid specify options that displays blocks overlaping
// the next day, since it is not properly handled.

// This setting controls whether to use "clock" based intervals (FALSE and
// the default) or user defined periods (TRUE).  If user-defined periods
// are used then $resolution, $morningstarts, $eveningends,
// $eveningends_minutes and $twentyfourhour_format are ignored.
$enable_periods = FALSE;


// TIMES SETTINGS
// --------------

// These settings are all set per area through MRBS.   These are the default
// settings that are used when a new area is created.

// The "Times" settings are ignored if $enable_periods is TRUE.

// Resolution - what blocks can be booked, in seconds.
// Default is half an hour: 1800 seconds.
$resolution = (30 * 60);

// Default duration - default length (in seconds) of a booking.
// Defaults to (60 * 60) seconds, i.e. an hour
$default_duration = (60 * 60);

// Start and end of day.
// NOTE:  The time between the beginning of the last and first
// slots of the day must be an integral multiple of the resolution,
// and obviously >=0.


// The default settings below (along with the 30 minute resolution above)
// give you 24 half-hourly slots starting at 07:00, with the last slot
// being 18:30 -> 19:00

// The beginning of the first slot of the day
$morningstarts         = 7;   // must be integer in range 0-23
$morningstarts_minutes = 0;   // must be integer in range 0-59

// The beginning of the last slot of the day
$eveningends           = 18;  // must be integer in range 0-23
$eveningends_minutes   = 30;   // must be integer in range 0-59

// Example 1.
// If resolution=3600 (1 hour), morningstarts = 8 and morningstarts_minutes = 30 
// then for the last period to start at say 4:30pm you would need to set eveningends = 16
// and eveningends_minutes = 30

// Example 2.
// To get a full 24 hour display with 15-minute steps, set morningstarts=0; eveningends=23;
// eveningends_minutes=45; and resolution=900.

// This is the maximum number of rows (timeslots or periods) that one can expect to see in the day
// and week views.    It is used by mrbs.css.php for creating classes.    It does not matter if it
// is too large, except for the fact that more CSS than necessary will be generated.  (The variable
// is ignored if $times_along_top is set to TRUE).
$max_slots = 60;


// PERIODS SETTINGS
// ----------------

// The "Periods" settings are ignored if $enable_periods is FALSE.

// Define the name or description for your periods in chronological order
// For example:
// $periods[] = "Period&nbsp;1"
// $periods[] = "Period&nbsp;2"
// ...
// or
// $periods[] = "09:15&nbsp;-&nbsp;09:50"
// $periods[] = "09:55&nbsp;-&nbsp;10:35"
// ...
// &nbsp; is used to ensure that the name or description is not wrapped
// when the browser determines the column widths to use in day and week
// views
//
// NOTE:  MRBS assumes that the descriptions are valid HTML and can be output
// directly without any encoding.    Please ensure that any special characters
// are encoded, eg '&' to '&amp;', '>' to '&gt;', lower case e acute to 
// '&eacute;' or '&#233;', etc.

// NOTE:  The maximum number of periods is 60.   Do not define more than this.
unset($periods);    // Include this line when copying to config.inc.php
$periods[] = "Period&nbsp;1";
$periods[] = "Period&nbsp;2";
// NOTE:  The maximum number of periods is 60.   Do not define more than this.


/******************
 * Display settings
 ******************/

// [These are all variables that control the appearance of pages and could in time
//  become per-user settings]

// Start of week: 0 for Sunday, 1 for Monday, etc.
$weekstarts = 0;

// Days of the week that should be hidden from display
// 0 for Sunday, 1 for Monday, etc.
// For example, if you want Saturdays and Sundays to be hidden set $hidden_days = array(0,6);
//
// By default the hidden days will be removed completely from the main table in the week and month
// views.   You can alternatively arrange for them to be shown as narrow, greyed-out columns
// by editing the CSS file.   Look for $column_hidden_width in mrbs.css.php.
//
// [Note that although they are hidden from display in the week and month views, they 
// can still be booked from the edit_entry form and you can display the bookings by
// jumping straight into the day view from the date selector.]
$hidden_days = array();

// Trailer date format: 0 to show dates as "Jul 10", 1 for "10 Jul"
$dateformat = 0;

// Time format in pages. 0 to show dates in 12 hour format, 1 to show them
// in 24 hour format
$twentyfourhour_format = 1;

// Results per page for searching:
$search["count"] = 20;

// Page refresh time (in seconds). Set to 0 to disable
$refresh_rate = 0;

// Trailer type.   FALSE gives a trailer complete with links to days, weeks and months before
// and after the current date.    TRUE gives a simpler trailer that just has links to the
// current day, week and month.
$simple_trailer = FALSE;

// should areas be shown as a list or a drop-down select box?
$area_list_format = "list";
//$area_list_format = "select";

// Entries in monthly view can be shown as start/end slot, brief description or
// both. Set to "description" for brief description, "slot" for time slot and
// "both" for both. Default is "both", but 6 entries per day are shown instead
// of 12.
$monthly_view_entries_details = "both";

// To view weeks in the bottom (trailer.inc) as week numbers (42) instead of
// 'first day of the week' (13 Oct), set this to TRUE
$view_week_number = FALSE;

// To display week numbers in the mini-calendars, set this to true. The week
// numbers are only accurate if you set $weekstarts to 1, i.e. set the
// start of the week to Monday
$mincals_week_numbers = FALSE;

// To display times on the x-axis (along the top) and rooms or days on the y-axis (down the side)
// set to TRUE;   the default/traditional version of MRBS has rooms (or days) along the top and
// times along the side.    Transposing the table can be useful if you have a large number of
// rooms and not many time slots.
$times_along_top = FALSE;

// To display the row labels (times, rooms or days) on the right hand side as well as the 
// left hand side in the day and week views, set to TRUE;
// (was called $times_right_side in earlier versions of MRBS)
$row_labels_both_sides = FALSE;

// Define default starting view (month, week or day)
// Default is day
$default_view = "day";

// Define default room to start with (used by index.php)
// Room numbers can be determined by looking at the Edit or Delete URL for a
// room on the admin page.
// Default is 0
$default_room = 0;

// Define clipping behaviour for the cells in the day and week views.
// Set to TRUE if you want the cells in the day and week views to be clipped.   This
// gives a table where all the rows have the same hight, regardless of content.
// Alternatively set to FALSE if you want the cells to expand to fit the content.
// (FALSE not supported in IE6 and IE7 due to their incomplete CSS support)
$clipped = TRUE;                

// Define clipping behaviour for the cells in the month view.                           
// Set to TRUE if you want the cells in the month view to scroll if there are too
// many bookings to display; set to FALSE if you want the table cell to expand to
// accommodate the bookings.   (NOTE: (1) scrolling doesn't work in IE6 and so the table
// cell will always expand in IE6.  (2) In IE8 Beta 2 scrolling doesn't work either and
// the cell content is clipped when $month_cell_scrolling is set to TRUE.)
$month_cell_scrolling = TRUE;   
                                


/************************
 * Miscellaneous settings
 ************************/

// Maximum repeating entrys (max needed +1):
$max_rep_entrys = 365 + 1;

// Default report span in days:
$default_report_days = 60;

// Control the active cursor in day/week/month views.   By default, highlighting
// is implemented using the CSS :hover pseudo-class.    For old browers such as
// IE6, this is not supported and MRBS will automatically switch over to use 
// JavaScript highlighting - for which there are three different modes: 'bgcolor',
// 'class' and 'hybrid'.  If clients have VERY old browsers, then you may even want
// to disable the JavaScript highlighting by setting $javascript_cursor to false.
$javascript_cursor = TRUE; // Change to FALSE if clients have very old browsers
                           // incompatible with JavaScript.
$show_plus_link = FALSE;   // Change to TRUE to always show the (+) link as in
                           // MRBS 1.1.
$highlight_method = "hybrid"; // One of "bgcolor", "class", "hybrid".   "hybrid" is recommended as it is
                              // faster in old browsers such as IE6 - which is the only time that
                              // JavaScript highlighting is used anyway.    The rest of the time CSS
                              // highlighting is used, whether or not $javascript_cursor is set.


// PRIVATE BOOKINGS SETTINGS

// These settings are all set per area through MRBS.   These are the default
// settings that are used when a new area is created.

// Only administrators or the person who booked a private event can see
// details of the event.  Everyone else just sees that the time/period
// is booked on the schedule.
$private_enabled = FALSE;  // Display checkbox in entry page to make
           // the booking private.  

$private_default = FALSE;  // Set default value for "Private" flag on
           // new/edited entries.  Used even if checkbox is not displayed.

$private_mandatory = FALSE; // If TRUE all new/edited entries will 
           // use the value from $private_default when saved.
           // If checkbox is displayed it will be disabled.

$private_override = "none"; // Override default privacy behavior. 
           // "none" - Private flag on entry is used
           // "private" - ALL entries are treated as private regardless
           //             of private flag on the entry.
           // "public" - NO entry is treated as private, regardless of
           //            private flag on the entry.
           // Overrides $private_default and $private_mandatory
           // Consider your users' expectations of privacy before
           // changing to "public" or from "private" to "none"

/***********************************************
 * Authentication settings - read AUTHENTICATION
 ***********************************************/

$auth["session"] = "php"; // How to get and keep the user ID. One of
           // "http" "php" "cookie" "ip" "host" "nt" "omni"
           // "remote_user"

$auth["type"] = "config"; // How to validate the user/password. One of "none"
                          // "config" "db" "db_ext" "pop3" "imap" "ldap" "nis"
                          // "nw" "ext".

// Configuration parameters for 'cookie' session scheme

// The encryption secret key for the session tokens. You are strongly
// advised to change this if you use this session scheme
$auth["session_cookie"]["secret"] = "This isn't a very good secret!";
// The expiry time of a session, in seconds
$auth["session_cookie"]["session_expire_time"] = (60*60*24*30); // 30 days
// Whether to include the user's IP address in their session cookie.
// Increases security, but could cause problems with proxies/dynamic IP
// machines
$auth["session_cookie"]["include_ip"] = TRUE;


// Configuration parameters for 'php' session scheme

// The expiry time of a session, in seconds
// N.B. Long session expiry times rely on PHP not retiring the session
// on the server too early. If you only want session cookies to be used,
// set this to 0.
$auth["session_php"]["session_expire_time"] = (60*60*24*30); // 30 days


// Cookie path override. If this value is set it will be used by the
// 'php' and 'cookie' session schemes to override the default behaviour
// of automatically determining the cookie path to use
$cookie_path_override = '';

// The list of administrators (can modify other peoples settings).
//
// This list is not needed when using the 'db' authentication scheme EXCEPT
// when upgrading from a pre-MRBS 1.4.2 system that used db authentication.
// Pre-1.4.2 the 'db' authentication scheme did need this list.   When running
// edit_users.php for the first time in a 1.4.2 system or later, with an existing
// users list in the database, the system will automatically add a field to
// the table for access rights and give admin rights to those users in the database
// for whom admin rights are defined here.   After that this list is ignored.
unset($auth["admin"]);              // Include this when copying to config.inc.php
$auth["admin"][] = "127.0.0.1";     // localhost IP address. Useful with IP sessions.
$auth["admin"][] = "administrator"; // A user name from the user list. Useful 
                                    // with most other session schemes.
//$auth["admin"][] = "10.0.0.1";
//$auth["admin"][] = "10.0.0.2";
//$auth["admin"][] = "10.0.0.3";

// 'auth_config' user database
// Format: $auth["user"]["name"] = "password";
$auth["user"]["administrator"] = "secret";
$auth["user"]["alice"] = "a";
$auth["user"]["bob"] = "b";

// 'session_http' configuration settings
$auth["realm"]  = "mrbs";

// 'session_remote_user' configuration settings
//$auth['remote_user']['logout_link'] = '/logout/link.html';

// 'auth_ext' configuration settings
$auth["prog"]   = "";
$auth["params"] = "";

// 'auth_db' configuration settings
// The highest level of access (0=none; 1=user; 2+=admin).    Used in edit_users.php
// In the future we might want a higher level of granularity, eg to distinguish between
// different levels of admin
$max_level = 2;
// The lowest level of admin allowed to edit other users
$min_user_editing_level = 2;

// 'auth_db_ext' configuration settings
// The 'db_system' variable is equivalent to the core MRBS $dbsys variable,
// and allows you to use any of MRBS's database abstraction layers for
// db_ext authentication.
$auth['db_ext']['db_system'] = 'mysql';
$auth['db_ext']['db_host'] = 'localhost';
$auth['db_ext']['db_username'] = 'authuser';
$auth['db_ext']['db_password'] = 'authpass';
$auth['db_ext']['db_name'] = 'authdb';
$auth['db_ext']['db_table'] = 'users';
$auth['db_ext']['column_name_username'] = 'name';
$auth['db_ext']['column_name_password'] = 'password';
// Either 'md5', 'sha1', 'crypt' or 'plaintext'
$auth['db_ext']['password_format'] = 'md5';

// 'auth_ldap' configuration settings
// Where is the LDAP server
//$ldap_host = "localhost";
// If you have a non-standard LDAP port, you can define it here
//$ldap_port = 389;
// If you do not want to use LDAP v3, change the following to false
$ldap_v3 = true;
// If you want to use TLS, change the following to true
$ldap_tls = false;
// LDAP base distinguish name
// See AUTHENTICATION for details of how check against multiple base dn's
//$ldap_base_dn = "ou=organizationalunit,dc=my-domain,dc=com";
// Attribute within the base dn that contains the username
//$ldap_user_attrib = "uid";
// If you need to search the directory to find the user's DN to bind
// with, set the following to the attribute that holds the user's
// "username". In Microsoft AD directories this is "sAMAccountName"
//$ldap_dn_search_attrib = "sAMAccountName";
// If you need to bind as a particular user to do the search described
// above, specify the DN and password in the variables below
// $ldap_dn_search_dn = "cn=Search User,ou=Users,dc=some,dc=company";
// $ldap_dn_search_password = "some-password";

// 'auth_ldap' extra configuration for ldap configuration of who can use
// the system
// If it's set, the $ldap_filter will be combined with the value of
// $ldap_user_attrib like this:
//   (&($ldap_user_attrib=username)($ldap_filter))
// After binding to check the password, this check is used to see that
// they are a valid user of mrbs.
//$ldap_filter = "mrbsuser=y";

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
//$auth["imap_php"]["ssl"] = TRUE;
// Use TLS
//$auth["imap_php"]["tls"] = TRUE;
// Turn off SSL/TLS certificate validation
//$auth["imap_php"]["novalidate-cert"] = TRUE;

// 'auth_pop3' configuration settings
// See AUTHENTICATION for details of how check against multiple servers
// Where is the POP3 server
$pop3_host = "pop3-server-name";
// The POP3 server port
$pop3_port = "110";

// 'auth_smtp' configuration settings
$auth['smtp']['server'] = 'myserver.example.org';


/**********************************************
 * Email settings
 **********************************************/

// You can override the charset used in emails if $unicode_encoding is 1
// (utf-8) if you like, but be sure the charset you choose can handle all
// the characters in the translation and that anyone may use in a
// booking description
//$mail_charset = "iso-8859-1";

// Set to TRUE if you want to be notified when entries are booked. Default is
// FALSE
$mail_settings['admin_on_bookings'] = FALSE;

// Set to TRUE if you want AREA ADMIN to be notified when entries are booked.
// Default is FALSE. Area admin emails are set in room_area admin page.
$mail_settings['area_admin_on_bookings'] = FALSE;

// Set to TRUE if you want ROOM ADMIN to be notified when entries are booked.
// Default is FALSE. Room admin emails are set in room_area admin page.
$mail_settings['room_admin_on_bookings'] = FALSE;

// Set to TRUE if you want ADMIN to be notified when entries are deleted. Email
// will be sent to mrbs admin, area admin and room admin as per above settings,
// as well as to booker if 'booker' is TRUE (see below).
$mail_settings['admin_on_delete'] = FALSE;

// Set to TRUE if you want to be notified on every change (i.e, on new entries)
// but also each time they are edited. Default is FALSE (only new entries)
$mail_settings['admin_all'] = FALSE;

// Set to TRUE is you want to show entry details in email, otherwise only a
// link to view_entry is provided. Irrelevant for deleted entries. Default is
// FALSE.
$mail_settings['details'] = FALSE;

// Set to TRUE if you want BOOKER to receive a copy of his entries as well any
// changes (depends on 'admin_all', see below). Default is FALSE. To know
// how to set mrbs to send emails to users/bookers, see INSTALL.
$mail_settings['booker'] = FALSE;

// If 'booker' is set to TRUE (see above) and you use an authentication
// scheme other than 'auth_db', you need to provide the mail domain that will
// be appended to the username to produce a valid email address (ie.
// "@domain.com").
$mail_settings['domain'] = '';

// If you use $mail_settings['domain'] above and username returned by mrbs contains extra
// strings appended like domain name ('username.domain'), you need to provide
// this extra string here so that it will be removed from the username.
$mail_settings['username_suffix'] = '';

// Set the name of the Backend used to transport your mails. Either 'mail',
// 'smtp' or 'sendmail'. Default is 'mail'. See INSTALL for more details.
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
 * SMTP settings
 */

// Set smtp server to connect. Default is 'localhost' (only used with "smtp"
// backend).
$smtp_settings['host'] = 'localhost';

// Set smtp port to connect. Default is '25' (only used with "smtp" backend).
$smtp_settings['port'] = 25;

// Set whether or not to use SMTP authentication. Default is 'FALSE'
$smtp_settings['auth'] = FALSE;

// Set the username to use for SMTP authentication. Default is ''
$smtp_settings['username'] = '';

// Set the password to use for SMTP authentication. Default is ''
$smtp_settings['password'] = '';

/**********************
 * Miscellaneous settings
 */

// Set the language used for emails (choose an available lang.* file).
// Default is 'en'.
$mail_settings['admin_lang'] = 'en';

// Set the email address of the From field. Default is 'admin_email@your.org'
$mail_settings['from'] = 'admin_email@your.org';

// Set the recipient email. Default is 'admin_email@your.org'. You can define
// more than one recipient like this "john@doe.com,scott@tiger.com"
$mail_settings['recipients'] = 'admin_email@your.org';

// Set email address of the Carbon Copy field. Default is ''. You can define
// more than one recipient (see 'recipients')
$mail_settings['cc'] = '';

/**********
 * Language
 **********/

// Set this to 1 to use UTF-8 in all pages and in the database, otherwise
// text gets entered in the database in different encodings, dependent
// on the users' language
$unicode_encoding = 1;

// Set this to 1 to disable the automatic language changing MRBS performs
// based on the user's browser language settings. It will ensure that
// the language displayed is always the value of $default_language_tokens,
// as specified below
$disable_automatic_language_changing = 0;

// Set this to a different language specifier to default to different
// language tokens. This must equate to a lang.* file in MRBS.
// e.g. use "fr" to use the translations in "lang.fr" as the default
// translations.  [NOTE: it is only necessary to change this if you
// have disabled automatic language changing above]
$default_language_tokens = "en";

// Set this to a valid locale (for the OS you run the MRBS server on)
// if you want to override the automatic locale determination MRBS
// performs
$override_locale = "";

// faq file language selection. IF not set, use the default english file.
// IF your language faq file is available, set $faqfilelang to match the
// end of the file name, including the underscore (ie. for site_faq_fr.html
// use "_fr"
$faqfilelang = ""; 


/*************
 * Reports
 *************/
 
// Default CSV file names
$report_filename  = "report.csv";
$summary_filename = "summary.csv";

// CSV format
$csv_row_sep = "\n";  // Separator between rows/records
$csv_col_sep = ",";   // Separator between columns/fields


/*************
 * Entry Types
 *************/

// This array maps entry type codes (letters A through J) into descriptions.
//
// This is a basic default array which ensures there are at least some types defined.
// The proper type definitions should be made in config.inc.php:  they have to go there
// because they use get_vocab which requires language.inc which uses settings which might
// be made in config.inc.php.
//
// Each type has a color which is defined in the array $color_types in the Themes
// directory - just edit whichever include file corresponds to the theme you
// have chosen in the config settings. (The default is default.inc, unsurprisingly!)
//
// The value for each type is a short (one word is best) description of the
// type. The values must be escaped for HTML output ("R&amp;D").
// Please leave I and E alone for compatibility.
// If a type's entry is unset or empty, that type is not defined; it will not
// be shown in the day view color-key, and not offered in the type selector
// for new or edited entries.

// $typel["A"] = "A";
// $typel["B"] = "B";
// $typel["C"] = "C";
// $typel["D"] = "D";
$typel["E"] = "E";
// $typel["F"] = "F";
// $typel["G"] = "G";
// $typel["H"] = "H";
$typel["I"] = "I";
// $typel["J"] = "J";


/***************************************
 * DOCTYPE - internal use, do not change
 ***************************************/

 // Records which DOCTYPE is being used.    Do not change - it will not change the DOCTYPE
 // that is used;  it is merely used when the code needs to know the DOCTYPE, for example
 // in calls to nl2br.   TRUE means XHTML, FALSE means HTML.
 define("IS_XHTML", FALSE);
 

/********************************************************
 * PHP System Configuration - internal use, do not change
 ********************************************************/

// Disable magic quoting on database returns:
set_magic_quotes_runtime(0);

// Make sure notice errors are not reported, they can break mrbs code:
error_reporting (E_ALL ^ E_NOTICE);

?>
