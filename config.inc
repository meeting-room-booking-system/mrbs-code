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

# Site information
$mrbs_admin = "Your Administrator";
$mrbs_admin_email = "admin_email@your.org";
$mrbs_company = "Your Company";

# Start and end of day, NOTE: this is an hour value only (0-23).
$morningstarts = 7;
$eveningends   = 19;

# This in in here as a sanity check only, please feel free to remove if the
# lines above are valid
if(($morningstarts < 0) || ($morningstarts > 23) ||
   ($eveningends   < 0) || ($eveningends   > 23)   )
{
?>
  Please ensure that <CODE>$morningstarts</CODE> and <CODE>$eveningends</CODE>
  in <CODE>config.inc</CODE> are valid.
<?
  exit;
}

# Start of week Sunday=0, Monday=1 (anything else same as 0)
$weekstarts = 0;

# Show dates as "Jul 10" (default) or "10 Jul" ($dateformat = 1)
$dateformat = 0;

# Searching parameters
$search[count] = 20;

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

# 'auth_ldap' configuration settings
# Where is the LDAP server
$ldap_host = "localhost";
# LDAP base distinguish names
$ldap_base_dn[] = "ou=organizationalunit1,o=organization,c=MY";
$ldap_base_dn[] = "ou=organizationalunit2,o=organization,c=MY";
$ldap_base_dn[] = "ou=organizationalunit3,o=organization,c=MY";

# Resolution - ie what blocks can be booked.
# Default is half an hour - 1800 seconds
$resolution = 1800;

# Maximum repeating entrys (max needed +1)
$max_rep_entrys = 365 + 1;

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

# This won't work if multiple languages are selected. Every language is then
# separated by ", ".
#$locale = $HTTP_ACCEPT_LANGUAGE;

# This will get the primary selected language
list ($locale, $not_used) = split(",", $HTTP_ACCEPT_LANGUAGE, 2);

if (strlen($locale) == 2) {
# This will set locale for Sweden to "sv_SV" whereas it should be "sv" or "sv_SE"... 
   $locale = strtolower($locale)."_".strtoupper($locale);
} else {
   $locale = strtolower(substr($locale,0,2))."_".strtoupper(substr($locale,3,2));
}
setlocale('LC_ALL',$locale);

?>
