<?

// Site-specific settings.
// Configure these for your site.
// You shouldn't have to modify anything outside of this file.
//
/////////////////////////////////////////////////////////////////


# MySQL settings
$mysql_host = "localhost";
$mysql_login = "root";
$mysql_password = "secret";
$mysql_database = "mrbs";


# Start and end of day (default is 7-19)
$morningstarts = 7;
$eveningends   = 19;

# Authentication settings - read AUTHENTICATION
$auth[realm]  = "";
$auth[type]   = "ip";
$auth[prog]   = "";
$auth[params] = "";

# The various level two users (can modify other peoples settings)
$auth[admin][] = "10.10.10.1";
$auth[admin][] = "10.10.10.2";
$auth[admin][] = "10.10.10.3";
$auth[admin][] = "10.10.10.4";

# Resolution - ie what blocks can be booked.
# Default is half an hour - 1800 seconds
$resolution = 1800;

# Language

# Change the en below to the code for your language - if 
# there is a language file for it.
include 'lang.en';

# These two lines import a language based on what the client
# is using. Comment them out to disable this.
$lang_file = "lang.$HTTP_ACCEPT_LANGUAGE";
if (file_exists($lang_file)) {include $lang_file;}


# We also want to do locales for Dates/Times etc.
# Use the setlocale() function for this.

# Define the default locale here. For a list of supported
# locales on your system do "locale -a"
setlocale('LC_ALL','C');

# We attempt to make up a sensible locale from
# the HTTP_ACCEPT_LANGUAGE environment variable
# comment this out to disable it
$locale = $HTTP_ACCEPT_LANGUAGE;
if (strlen($locale) == 2) {
   $locale = strtolower($locale)."_".strtoupper($locale);
} else {
   $locale = strtolower(substr($locale,0,2))."_".strtoupper(substr($locale,3,2));
}
setlocale('LC_ALL',$locale);

?>
