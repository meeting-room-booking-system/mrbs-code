<?php

// $Id$

require "defaultincludes.inc";
require_once "version.inc";

// Check the user is authorised for this page
checkAuthorised();

print_header($day, $month, $year, $area, isset($room) ? $room : "");

echo "<h3>" . get_vocab("about_mrbs") . "</h3>\n";
echo "<table id=\"version_info\">\n";
echo "<tr><td><a href=\"http://mrbs.sourceforge.net\">" . get_vocab("mrbs") . "</a>:</td><td>" . get_mrbs_version() . "</td></tr>\n";
echo "<tr><td>" . get_vocab("database") . ":</td><td>" . sql_version() . "</td></tr>\n";
echo "<tr><td>" . get_vocab("system") . ":</td><td>" . php_uname() . "</td></tr>\n";
echo "<tr><td>" . get_vocab("servertime") . ":</td><td>" .
     utf8_strftime($strftime_format['datetime'], time()) .
     "</td></tr>\n";
echo "<tr><td>PHP:</td><td>" . phpversion() . "</td></tr>\n";
echo "</table>\n";

echo "<p>\n" . get_vocab("browserlang") .":\n";

echo implode(", ", array_keys($langs));

echo "\n</p>\n";

echo "<h3>" . get_vocab("help") . "</h3>\n";
echo "<p>\n";
echo get_vocab("please_contact") . '<a href="mailto:' . htmlspecialchars($mrbs_admin_email)
  . '">' . htmlspecialchars($mrbs_admin)
  . "</a> " . get_vocab("for_any_questions") . "\n";
echo "</p>\n";
 
require_once "site_faq" . $faqfilelang . ".html";

output_trailer();
?>
