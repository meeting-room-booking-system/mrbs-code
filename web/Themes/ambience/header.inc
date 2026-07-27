<?php
declare(strict_types=1);
namespace MRBS;

// Reuse the standard MRBS header and add only the Ambience-specific controls.
require_once __DIR__ . '/../default/header.inc';


// Print the light/automatic/dark colour scheme picker.  The setting is stored
// in the browser, so it also works for anonymous users.
function print_theme_header_controls() : void
{
  $schemes = array(
    'light' => '<circle cx="12" cy="12" r="3.5"></circle>' .
               '<path d="M12 2v2M12 20v2M4.93 4.93l1.42 1.42M17.65 17.65l1.42 1.42' .
               'M2 12h2M20 12h2M4.93 19.07l1.42-1.42M17.65 6.35l1.42-1.42"></path>',
    'auto'  => '<rect x="3" y="4" width="18" height="14" rx="2"></rect>' .
               '<path d="M8 22h8M12 18v4"></path>' .
               '<path class="scheme-icon-fill" d="M12 8a3 3 0 1 0 0 6V8z"></path>' .
               '<circle cx="12" cy="11" r="3"></circle>',
    'dark'  => '<path d="M20.2 15.3A8.5 8.5 0 0 1 8.7 3.8a8.5 8.5 0 1 0 11.5 11.5z"></path>'
  );

  echo '<div class="color-scheme-picker" data-color-scheme-picker role="group"' .
       ' aria-label="' . escape_html(get_vocab('color_scheme')) . "\">\n";

  foreach ($schemes as $scheme => $icon)
  {
    $label = get_vocab("color_scheme_$scheme");
    echo '<button type="button" data-color-scheme="' . $scheme . '" aria-pressed="false"' .
         ' title="' . escape_html($label) . '">';
    echo '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">' . $icon . '</svg>';
    echo '<span>' . escape_html($label) . "</span></button>\n";
  }

  echo "</div>\n";
}
