<?php
namespace MRBS;


function link($href, $add_version=false, $media=null)
{
  echo '<link rel="stylesheet" type="text/css" href="';
  $value = ($add_version) ? add_version($href) : $href;
  echo htmlspecialchars($value) . '"';
  if (isset($media))
  {
    echo " media=\"$media\"";
  }
  echo ">\n";
}


global $custom_css_url;
global $debug;

link(($debug) ? 'jquery/ui/css/jquery-ui.structure.css' : 'jquery/ui/css/jquery-ui.structure.min.css', true);
link(($debug) ? 'jquery/ui/css/sunny/jquery-ui.theme.css' : 'jquery/ui/css/sunny/jquery-ui.theme.min.css', true);
link(($debug) ? 'jquery/datatables/datatables.css' : 'jquery/datatables/datatables.min.css', true);
link('js/flatpickr/css/flatpickr.min.css', true);
link('jquery/select2/dist/css/select2.min.css', true);
link(multisite('css/mrbs.css.php'), true);

if (using_rtl_lang())
{
  link(multisite('css/mrbs-rtl.css.php'), true);
}

link(multisite('css/mrbs-print.css.php'), true, 'print');

echo "<!--[if IE]>\n";
link('css/mrbs-ie.css', true);
if (using_rtl_lang())
{
  link('css/mrbs-ie-rtl.css', true);
}
echo "<![endif]-->\n";

if (isset($custom_css_url))
{
  link($custom_css_url, true);
}
