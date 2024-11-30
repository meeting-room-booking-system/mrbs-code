<?php
declare(strict_types=1);
namespace MRBS;

require "defaultincludes.inc";
require_once "version.inc";

// Check the user is authorised for this page
checkAuthorised(this_page());

$context = array(
    'view'      => $view,
    'view_all'  => $view_all,
    'year'      => $year,
    'month'     => $month,
    'day'       => $day,
    'area'      => $area,
    'room'      => $room ?? null
  );

print_header($context);

echo "<h3>" . get_vocab("about_mrbs") . "</h3>\n";

if (!is_admin())
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
  echo "<tr><td>" . get_vocab("db_schema_version") . "</td><td>" . DB::DB_SCHEMA_VERSION . "</td></tr>\n";
  echo "<tr><td>" . get_vocab("db_local_schema_version") . "</td><td>" . DB::DB_SCHEMA_VERSION_LOCAL . "</td></tr>\n";
  echo '<tr><td>$auth[\'type\']</td><td>' . htmlspecialchars($auth['type']) . "</td></tr>\n";
  echo '<tr><td>$auth[\'session\']</td><td>' . htmlspecialchars($auth['session']) . "</td></tr>\n";
  echo "</table>\n";


  echo "<table class=\"details has_caption list\">\n";
  echo "<caption>" . get_vocab("server_details") . "</caption>\n";
  echo "<tr><td>" . get_vocab("database") . "</td><td>" . db()->version() . "</td></tr>\n";
  echo "<tr><td>" . get_vocab("system") . "</td><td>" . System::info() . "</td></tr>\n";
  echo "<tr><td>" . get_vocab("servertime") . "</td><td>" .
       datetime_format($datetime_formats['date_and_time_help'], time()) .
       "</td></tr>\n";
  echo "<tr><td>" . get_vocab("server_software") . "</td><td>" .
       htmlspecialchars(get_server_software()) . "</td></tr>\n";
  echo "<tr><td>PHP</td><td>" . phpversion() . "</td></tr>\n";

  // The PHP extensions loaded, particularly intl and mbstring, are useful for debugging.
  $extensions = get_loaded_extensions();
  asort($extensions);
  echo "<tr><td>" . get_vocab("extensions") . "</td><td>" .
        htmlspecialchars(implode(', ', $extensions)) . "</td></tr>\n";

  echo "</table>\n";
}


echo "<p>\n" . get_vocab("browserlang") .":\n";

if (isset($server['HTTP_ACCEPT_LANGUAGE']))
{
  echo htmlspecialchars(implode(', ', get_browser_langs($server['HTTP_ACCEPT_LANGUAGE'])));
}

echo "\n</p>\n";

echo "<h3>" . get_vocab("help") . "</h3>\n";
echo "<p>\n";
// Obfuscate the email address
$html = '<a href="mailto:' . rawurlencode($mrbs_admin_email) . '">' . htmlspecialchars($mrbs_admin) . '</a>';
$contact = '<span class="contact" data-html="' . base64_encode($html) . '">' . htmlspecialchars($mrbs_admin) . '</span>';
echo get_vocab("please_contact", $contact) . "\n";
echo "</p>\n";

$faqfile = $faqfilelang ?? '';

// Older versions of MRBS required an underscore in front of the language
// in the config setting.  In order to maintain backwards compatibility we
// cater for both old (eg "_fr") and new (eg "fr") styles.
if (($faqfile !== '') && !str_starts_with($faqfile, '_'))
{
  $faqfile = '_' . $faqfile;
}

require_once "site_faq/site_faq" . $faqfile . ".html";

print_footer();
