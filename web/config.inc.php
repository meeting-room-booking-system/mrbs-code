<?php // -*-mode: PHP; coding:utf-8;-*-
namespace MRBS;

/**************************************************************************
 *   MRBS Configuration File
 *   Configure this file for your site.
 *   You shouldn't have to modify anything outside this file.
 *
 *   This file has already been populated with the minimum set of configuration
 *   variables that you will need to change to get your system up and running.
 *   If you want to change any of the other settings in systemdefaults.inc.php
 *   or areadefaults.inc.php, then copy the relevant lines into this file
 *   and edit them here.   This file will override the default settings and
 *   when you upgrade to a new version of MRBS the config file is preserved.
 **************************************************************************/

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
$timezone = "Europe/London";


/*******************
 * Database settings
 ******************/
// Which database system: "pgsql"=PostgreSQL, "mysql"=MySQL
$dbsys = "mysql";
#$dbsys = "pgsql";

// Hostname of database server. For pgsql, can use "" instead of localhost
// to use Unix Domain Sockets instead of TCP/IP. For mysql "localhost"
// tells the system to use Unix Domain Sockets, and $db_port will be ignored;
// if you want to force TCP connection you can use "127.0.0.1".
$db_host = "localhost";
// If you need to use a non standard port for the database connection you
// can uncomment the following line and specify the port number
// $db_port = 1234;
// Database name:
$db_database = "mrbs";
// Schema name.  This only applies to PostgreSQL and is only necessary if you have more
// than one schema in your database and also you are using the same MRBS table names in
// multiple schemas.
//$db_schema = "public";
// Database login user name:
$db_login = "mrbs";
// Database login password:
$db_password = 'mrbs-password';
// Prefix for table names.  This will allow multiple installations where only
// one database is available
$db_tbl_prefix = "mrbs_";
// Set $db_persist to TRUE to use PHP persistent (pooled) database connections.  Note
// that persistent connections are not recommended unless your system suffers significant
// performance problems without them.   They can cause problems with transactions and
// locks (see http://php.net/manual/en/features.persistent-connections.php) and although
// MRBS tries to avoid those problems, it is generally better not to use persistent
// connections if you can.
$db_persist = FALSE;


/* Add lines from systemdefaults.inc.php and areadefaults.inc.php below here
   to change the default configuration. Do _NOT_ modify systemdefaults.inc.php
   or areadefaults.inc.php.  */

/* db_ext testing */
//$auth['db_ext']['password_format'] = 'password_hash';
$auth['db_ext']['password_format'] = 'crypt';
$auth['db_ext']['column_name_level'] = 'mrbs_level';
//$auth['type'] = 'db_ext';

$auth['type'] = 'db';
//$auth['type'] = 'db';

$auth["session"] = "php";
//$auth["session"] = "cookie";

//$auth['deny_public_access'] = true;

// Really short inactivity expiry time, for testing - 1 minute!
//$auth["session_php"]["inactivity_expire_time"] = 60;
  
#$auth['type'] = 'ldap';
$ldap_host = "localhost";
$ldap_user_attrib = "cn";
$ldap_debug = true;
$ldap_base_dn = 'dc=theberaneks,dc=org,dc=uk';
$ldap_dn_search_attrib = "uid";
$ldap_dn_search_dn = 'uid=queryuser,ou=people,dc=theberaneks,dc=org,dc=uk';
$ldap_dn_search_password = 'uFelobu0';
$ldap_debug = true;

/* Email settings */
$mrbs_admin_email = 'john@redux.org.uk';
$mail_settings['admin_on_bookings']      = true;

$url_base = "https://home.redux.org.uk/mrbs/";

$mail_settings['details']   = TRUE; // Set to TRUE if you want full booking details;
                                     // otherwise you just get a link to the entry
$mail_settings['html']      = TRUE; // Set to true if you want HTML mail
$mail_settings['icalendar'] = TRUE; // Set to TRUE to include iCalendar details

// Set the name of the backend used to transport your mails. Either 'mail',
// 'smtp' or 'sendmail'. Default is 'mail'.
$mail_settings['admin_backend'] = 'mail';
#$mail_settings['admin_backend'] = 'sendmail';
#$mail_settings['admin_backend'] = 'smtp';

$mail_settings['debug'] = TRUE;

// Set the path of the Sendmail program (only used with "sendmail" backend).
// Default is '/usr/bin/sendmail'
$sendmail_settings['path'] = '/usr/sbin/sendmail';
// Set additional Sendmail parameters (only used with "sendmail" backend).
// (example "-t -i"). Default is ''
$sendmail_settings['args'] = '-t -i';

/*******************
 * SMTP settings
 */
  
// Local
$smtp_settings['host'] = 'home.redux.org.uk';  // SMTP server
$smtp_settings['port'] = 25;           // SMTP port number
//$smtp_settings['port'] = 465;           // SMTP port number
$smtp_settings['auth'] = false;        // Whether to use SMTP authentication
$smtp_settings['username'] = 'jberanek';
$smtp_settings['password'] = 'password';
$smtp_settings['secure'] = '';      // Encryption method: '', 'tls' or 'ssl'
//$smtp_settings['disable_opportunistic_tls'] = true;
//$smtp_settings['ssl_verify_peer'] = false;
//$smtp_settings['ssl_verify_peer_name'] = false;
//$smtp_settings['ssl_allow_self_signed'] = false;

// GMail
//$smtp_settings['host'] = 'smtp.gmail.com';  // SMTP server
//$smtp_settings['port'] = 587;           // SMTP port number
//$smtp_settings['auth'] = true;        // Whether to use SMTP authentication
//$smtp_settings['username'] = 'john.redux@gmail.com';
//$smtp_settings['password'] = 'password';
//$smtp_settings['secure'] = '';      // Encryption method: '', 'tls' or 'ssl'
//$smtp_settings['disable_opportunistic_tls'] = false;

// Set the email address of the From field. Default is 'admin_email@your.org'
//$mail_settings['from'] = 'john@redux.org.uk';
$mail_settings['from'] = 'webmaster@redux.org.uk';

// The address to be used for the ORGANIZER in an iCalendar event.   Do not make
// this email address the same as the admin email address or the recipients
// email address because on some mail systems, eg IBM Domino, the iCalendar email
// notification is silently discarded if the organizer's email address is the same
// as the recipient's.  On other systems you may get a "Meeting not found" message.
$mail_settings['organizer'] = 'webmaster@redux.org.uk';

// Set the recipient email. Default is 'admin_email@your.org'. You can define
// more than one recipient like this "john@doe.com,scott@tiger.com"
//$mail_settings['recipients'] = 'johnb@press.net';
$mail_settings['recipients'] = 'john@redux.org.uk';

// Set email address of the Carbon Copy field. Default is ''. You can define
// more than one recipient (see 'recipients')
$mail_settings['cc'] = '';

$select_options['entry.selectfield'] =
  array('a' => "foo",
        'b' => "blah",
        'c' => "blah2");

unset($periods);    // Include this line when copying to config.inc.php
$periods[] = "Period < £€";
$periods[] = "百度新闻 \"";

//$custom_css_url = 'css/custom.css';

$hidden_days = array(1,2);

$auth['only_admin_can_see_other_users'] = true;

$default_language_tokens = "en";
//$disable_automatic_language_changing = 0;
//$override_locale = "sl_SI.UTF-8";
$vocab_override["en"]["type.I"] = 'whizz bang';

$area_list_format = "select";

$display_mincals = true;
