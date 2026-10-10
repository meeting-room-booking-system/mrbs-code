<?php
declare(strict_types=1);
namespace MRBS;

function print_theme_footer()
{
  $src = escape_html(add_version('Themes/ambience/theme.js'));

  echo "</div>\n";  // closing the contents div, opened in print_theme_header()
  echo "<script src=\"$src\"></script>\n";
  echo "</body>\n";
  echo "</html>\n";
}
