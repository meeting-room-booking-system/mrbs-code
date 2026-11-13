<?php
declare(strict_types=1);
namespace MRBS;

// Only the screen stylesheet can switch its palette in the browser.  Keep the
// default static colours for email and print stylesheets.
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') !== 'mrbs.css.php')
{
  return;
}

$ambience_colors = array(
  'body_background_color'          => '#f9fbfc',
  'standard_font_color'            => '#0B263B',
  'header_font_color'              => '#ffffff',
  'highlight_font_color'           => '#ff0066',
  'color_key_font_color'           => '#0B263B',
  'banner_back_color'              => '#1976D2',
  'banner_border_color'            => '#f9fbfc',
  'banner_font_color'              => '#ffffff',
  'banner_nav_hover_color'         => 'darkblue',
  'minical_view_color'             => '#2b3a42',
  'minical_today_color'            => '#bdd4de',
  'header_back_color'              => '#1976D2',
  'flatpickr_highlight_color'      => '#1976D2',
  'admin_table_header_back_color'  => '#1976D2',
  'admin_table_header_sep_color'   => '#f9fbfc',
  'admin_table_header_font_color'  => '#ffffff',
  'admin_table_border_color'       => '#C3CCD3',
  'main_table_border_color'        => '#dddddd',
  'main_table_header_border_color' => '#dddddd',
  'main_table_body_h_border_color' => '#ffffff',
  'main_table_body_v_border_color' => '#e4e4e4',
  'main_table_month_color'         => '#ffffff',
  'main_table_month_weekend_color' => '#f4f4f4',
  'main_table_month_holiday_color' => '#e8e8e8',
  'main_table_month_weekend_holiday_color' => '#dfdfdf',
  'main_table_month_invalid_color' => '#d1d9de',
  'main_table_slot_invalid_color'  => '#d1d9de',
  'main_table_slot_private_type_color' => '#d1d9de',
  'main_table_labels_back_color'   => '#1976D2',
  'timeline_color'                 => '#1976D2',
  'report_table_border_color'      => '#0B263B',
  'report_h2_border_color'         => '#1976D2',
  'report_h3_border_color'         => '#879AA8',
  'search_table_border_color'      => '#0B263B',
  'site_faq_entry_border_color'    => '#C3CCD3',
  'anchor_link_color'              => '#0B263B',
  'anchor_visited_color'           => '#0B263B',
  'anchor_hover_color'             => '#0B263B',
  'anchor_link_color_banner'       => '#ffffff',
  'anchor_visited_color_banner'    => '#ffffff',
  'anchor_hover_color_banner'      => '#ffffff',
  'anchor_link_color_header'       => '#0B263B',
  'anchor_visited_color_header'    => '#0B263B',
  'anchor_hover_color_header'      => '#0B263B',
  'column_hidden_color'            => '#d1d9de',
  'calendar_hidden_color'          => '#dae0e4',
  'row_highlight_color'            => '#1976D2',
  'row_even_color'                 => '#ffffff',
  'row_odd_color'                  => '#efefef',
  'row_even_color_weekend'         => '#f4f4f4',
  'row_odd_color_weekend'          => '#e4e4e4',
  'row_even_color_holiday'         => '#e8e8e8',
  'row_odd_color_holiday'          => '#d8d8d8',
  'row_even_color_weekend_holiday' => '#dfdfdf',
  'row_odd_color_weekend_holiday'  => '#cfcfcf',
  'zebra_even_color'               => '#ffffff',
  'zebra_odd_color'                => '#e2e4ff',
  'help_highlight_color'           => '#ffe6f0',
  'button_inset_color'             => 'darkblue',
  'outstanding_color'              => '#FFF36C',
  'pending_header_back_color'      => '#1976D2',
  'series_entry_back_color'        => '#FFFCDA',
  'pending_control_color'          => '#FFF36C',
  'attention_color'                => 'darkorange'
);

foreach ($ambience_colors as $name => $fallback)
{
  $$name = 'var(--mrbs-' . str_replace('_', '-', $name) . ', ' . $fallback . ')';
}

$button_color_stops = array(
  'var(--mrbs-button-color-stop-start, #eeeeee)',
  'var(--mrbs-button-color-stop-end, #cccccc)'
);

foreach ($color_types as $type => $fallback)
{
  $color_types[$type] = 'var(--mrbs-booking-type-' . strtolower($type) . ', ' . $fallback . ')';
}
