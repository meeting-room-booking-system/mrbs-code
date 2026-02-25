<?php
declare(strict_types=1);
namespace MRBS;


// Generates the HTML for a <link> element

// We use \MRBS\Intl\Locale rather than \Locale because (a) we use isRightToLeft() method
// which is only available from PHP 8.5, and (b) because we can't guarantee that the intl
// extension is installed.
use MRBS\Intl\Locale;

function link_html(string $href, bool $add_version=false, ?string $media=null) : string
{
  $html = '<link rel="stylesheet" type="text/css" href="';
  $value = ($add_version) ? add_version($href) : $href;
  $html .= escape_html($value) . '"';
  if (isset($media))
  {
    $html .= " media=\"$media\"";
  }
  $html .= ">\n";

  return $html;
}


global $custom_css_url, $theme;
global $debug;

echo link_html(($debug) ? 'jquery/ui/css/jquery-ui.structure.css' : 'jquery/ui/css/jquery-ui.structure.min.css', true);
echo link_html(($debug) ? 'jquery/ui/css/cupertino/jquery-ui.theme.css' : 'jquery/ui/css/cupertino/jquery-ui.theme.min.css', true);
echo link_html(($debug) ? 'jquery/datatables/datatables.css' : 'jquery/datatables/datatables.min.css', true);
echo link_html('js/flatpickr/css/flatpickr.min.css', true);
echo link_html('jquery/select2/dist/css/select2.min.css', true);
echo link_html(multisite('css/mrbs.css.php'), true);

$using_rtl_lang = Locale::isRightToLeft(Language::getInstance()->getWebLang());
if ($using_rtl_lang)
{
  echo link_html(multisite('css/mrbs-rtl.css.php'), true);
}

echo link_html(multisite('css/mrbs-print.css.php'), true, 'print');

echo "<!--[if IE]>\n";
echo link_html('css/mrbs-ie.css', true);
if ($using_rtl_lang)
{
  echo link_html('css/mrbs-ie-rtl.css', true);
}
echo "<![endif]-->\n";

// Load the theme CSS, if any
$theme_css = "Themes/$theme/theme.css";
if (is_readable($theme_css))
{
  echo link_html($theme_css, true);
}

// Load the custom CSS, if any
if (isset($custom_css_url))
{
  echo link_html($custom_css_url, true);
}
