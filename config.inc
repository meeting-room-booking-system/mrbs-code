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



# Language

# Change the en below to the code for your language - if 
# there is a language file for it.
include 'lang.en';

# These two lines import a language based on what the client
# is using. Comment them out to disable this.
$lang_file = "lang.$HTTP_ACCEPT_LANGUAGE";
if (file_exists($lang_file)) {include $lang_file;}

?>
