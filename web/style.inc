<?php
namespace MRBS;


function link(string $href, bool $add_version=false, string $media=null) : string
{
  $html = '<link rel="stylesheet" type="text/css" href="';
  $value = ($add_version) ? add_version($href) : $href;
  $html .= htmlspecialchars($value) . '"';
  if (isset($media))
  {
    $html .= " media=\"$media\"";
  }
  $html .= ">\n";

  return $html;
}


global $custom_css_url;
global $debug;

echo link(($debug) ? 'jquery/ui/css/jquery-ui.structure.css' : 'jquery/ui/css/jquery-ui.structure.min.css', true);
echo link(($debug) ? 'jquery/ui/css/cupertino/jquery-ui.theme.css' : 'jquery/ui/css/cupertino/jquery-ui.theme.min.css', true);
echo link(($debug) ? 'jquery/datatables/datatables.css' : 'jquery/datatables/datatables.min.css', true);
echo link('js/flatpickr/css/flatpickr.min.css', true);
echo link('jquery/select2/dist/css/select2.min.css', true);
echo link(multisite('css/mrbs.css.php'), true);

if (using_rtl_lang())
{
  echo link(multisite('css/mrbs-rtl.css.php'), true);
}

echo link(multisite('css/mrbs-print.css.php'), true, 'print');

echo "<!--[if IE]>\n";
echo link('css/mrbs-ie.css', true);
if (using_rtl_lang())
{
  echo link('css/mrbs-ie-rtl.css', true);
}
echo "<![endif]-->\n";

if (isset($custom_css_url))
{
  echo link($custom_css_url, true);
}
