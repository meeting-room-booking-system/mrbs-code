<?php
namespace MRBS;

require "defaultincludes.inc";
require_once "version.inc";

// Check the user is authorised for this page
checkAuthorised();

$user = getUserName();
$is_admin = (authGetUserLevel($user) >= $max_level);

print_header($day, $month, $year, $area, isset($room) ? $room : null);

echo "<h3>" . get_vocab("about_mrbs") . "</h3>\n";

if (!$is_admin)
{
  echo "<table class=\"details list\">\n";
  echo "<tr><td><a href=\"http://mrbs.sourceforge.net\">" . get_vocab("mrbs") . "</a></td><td>" . get_mrbs_version() . "</td></tr>\n";
  echo "</table>\n";
}
else
{
  // Restrict the configuration and server details to admins, for security reasons.
  echo "<table class=\"details has_caption list\">\n";
  echo "<caption>" . get_vocab("config_details") . "</caption>\n";
  echo "<tr><td>" . get_vocab("mrbs_version") . "</td><td>" . get_mrbs_version() . "</td></tr>\n";
  echo '<tr><td>$auth[\'type\']</td><td>' . htmlspecialchars($auth['type']) . "</td></tr>\n";
  echo '<tr><td>$auth[\'session\']</td><td>' . htmlspecialchars($auth['session']) . "</td></tr>\n";
  echo "</table>\n";


  echo "<table class=\"details has_caption list\">\n";
  echo "<caption>" . get_vocab("server_details") . "</caption>\n";
  echo "<tr><td>" . get_vocab("database") . "</td><td>" . db()->version() . "</td></tr>\n";
  echo "<tr><td>" . get_vocab("system") . "</td><td>" . php_uname() . "</td></tr>\n";
  echo "<tr><td>" . get_vocab("servertime") . "</td><td>" .
       utf8_strftime($strftime_format['datetime'], time()) .
       "</td></tr>\n";
  echo "<tr><td>" . get_vocab("server_software") . "</td><td>" . htmlspecialchars(get_server_software()) . "</td></tr>\n";
  echo "<tr><td>PHP</td><td>" . phpversion() . "</td></tr>\n";
  echo "</table>\n";
}


echo "<p>\n" . get_vocab("browserlang") .":\n";

echo htmlspecialchars(implode(", ", array_keys(get_language_qualifiers())));

echo "\n</p>\n";

echo "<h3>" . get_vocab("help") . "</h3>\n";
echo "<p>\n";
echo get_vocab("please_contact") . '<a href="mailto:' . rawurlencode($mrbs_admin_email)
  . '">' . htmlspecialchars($mrbs_admin)
  . "</a> " . get_vocab("for_any_questions") . "\n";
echo "</p>\n";
 
require_once "site_faq/site_faq" . $faqfilelang . ".html";

output_trailer();

