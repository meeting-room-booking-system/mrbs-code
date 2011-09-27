<?php
// $Id$

require_once "defaultincludes.inc";

// Constant definitions for the value of the summarize parameter.   These are used
// for bit-wise comparisons.    For example summarize=3 means produce both
// a report and a summary; summaraize=5 means produce a report as a CSV file
define('REPORT',      0x01);
define('SUMMARY',     0x02);
// a series of constants defining the ouput format.   These are in the same
// bit series as the output contents above, though not all combinations are sensible
define('OUTPUT_HTML', 0x04);
define('OUTPUT_CSV',  0x08);
define('OUTPUT_ICAL', 0x10);

// Constants for booking privacy matching
define('PRIVATE_NO',   0);
define('PRIVATE_YES',  1);
define('PRIVATE_BOTH', 2);  // Can be anything other than 0 or 1

// Constants for booking confirmation matching
define('CONFIRMED_NO',   0);
define('CONFIRMED_YES',  1);
define('CONFIRMED_BOTH', 2);  // Can be anything other than 0 or 1

// Constants for booking approval matching
define('APPROVED_NO',   0);
define('APPROVED_YES',  1);
define('APPROVED_BOTH', 2);  // Can be anything other than 0 or 1

// Constants for mode
define('MODE_TIMES',   1);
define('MODE_PERIODS', 2);

// Formats for sprintf
define('FORMAT_TIMES',   "%.2f");
define('FORMAT_PERIODS', "%d");


// Escape a string for either HTML or CSV output
function escape($string)
{
  global $output_as_csv;
  if ($output_as_csv)
  {
    $string = str_replace('"', '""', $string);
  }
  else
  {
    $string = mrbs_nl2br(htmlspecialchars($string));
  }
  return $string;
}


// Output the first row (header row) for CSV reports
function report_header()
{
  global $output_as_csv, $output_as_html, $ajax;
  global $csv_row_sep, $csv_col_sep;
  global $custom_fields, $tbl_entry;
  global $approval_somewhere, $confirmation_somewhere;

  // Don't do anything if this is an Ajax request: we only want to send the data
  if ($ajax)
  {
    return;
  }
  
  // Build an array of values to go into the header row
  $values = array();
  
  $values[] = get_vocab("area") . ' - ' . get_vocab("room");
  $values[] = get_vocab("namebooker");
  $values[] = get_vocab("start_date");
  $values[] = get_vocab("end_date");
  $values[] = get_vocab("duration");
  $values[] = get_vocab("fulldescription_short");
  $values[] = get_vocab("type"); 
  $values[] = get_vocab("createdby");
  if ($confirmation_somewhere)
  {
    $values[] = get_vocab("confirmation_status");
  }
  if ($approval_somewhere)
  {
    $values[] = get_vocab("approval_status");
  }
  // Now do the custom fields
  foreach ($custom_fields as $key => $value)
  {
    $values[] = get_loc_field_name($tbl_entry, $key);
  }
  $values[] = get_vocab("lastupdate");
  
  
  if ($output_as_csv)
  {
    // Remove any HTML entities from the values, and escape the strings
    $n_values = count($values);
    $charset = get_charset();
    // Find out what the non-breaking space is in this character set
    $nbsp = mrbs_entity_decode('&nbsp;', ENT_NOQUOTES, $charset);
    for ($i=0; $i < $n_values; $i++)
    {
      $values[$i] = mrbs_entity_decode($values[$i], ENT_NOQUOTES, $charset);
      // Trim non-breaking spaces from the string
      $values[$i] = trim($values[$i], $nbsp);
      // And do an ordinary trim
      $values[$i] = trim($values[$i]);
      $values[$i] = escape($values[$i]);
    }
  
    // Now turn the array of values into a CSV row
    $line = '"';
    $line .= implode("\"$csv_col_sep\"", $values);
    $line .= '"' . $csv_row_sep;
  
    // Output the row
    echo $line;
  }
  elseif ($output_as_html)
  {
    $html .= "<colgroup>";
    foreach ($values as $value)
    {
      $html .= "<col>";
    }
    $html .= "</colgroup>\n";
    $html = "<thead>\n";
    $html .= "<tr>\n";
    foreach ($values as $value)
    {
      // We don't use htmlspecialchars() here because the vocab strings are trusted.
      // And some of them contain HTML entities such as &nbsp; on purpose
      $html .= "<th>$value</th>\n";
    }
    $html .= "</tr>\n";
    $html .= "</thead>\n<tbody>\n";
    echo $html;
  }
}


function open_report()
{
  global $output_as_html, $ajax;
  
  if ($output_as_html && !$ajax)
  {
    echo "<div id=\"report_output\" class=\"datatable_container\">\n";
    echo "<table class=\"admin_table display\" id=\"report_table\">\n";
  }
  report_header();
}


function close_report()
{
  global $output_as_html, $ajax, $json_data;
  
  // If this is an Ajax request, we can now send the JSON data
  if ($ajax)
  {
    echo json_encode($json_data);
  }
  elseif ($output_as_html)
  {
    echo "</tbody>\n";
    echo "</table>\n";
    echo "</div>\n";
  }
}


function report_row(&$row, $sortby)
{
  global $typel;
  global $output_as_csv, $ajax, $ajax_capable, $json_data;
  global $csv_row_sep, $csv_col_sep;
  global $custom_fields, $field_natures, $field_lengths, $tbl_entry;
  global $approval_somewhere, $confirmation_somewhere;
  global $strftime_format;
  global $select_options, $enable_periods;
  
  // If we're capable of delivering an Ajax request and this is not Ajax request,
  // then don't do anything.  We're going to save sending the data until we actually
  // get the Ajax request;  we just send the rest of the page at this stage.
  if (!$output_as_csv && $ajax_capable && !$ajax)
  {
    return;
  }
  
  $rows_output++;
  $values = array();
  
  // Area-room
  $area_room = $row['area_name'] . " - " . $row['room_name'];
  $values[] = escape($area_room);
  
  // Booking name
  $values[] = ($output_as_csv) ? escape($row['name']) : "<a href=\"view_entry.php?id=".$row['id']."\">" . htmlspecialchars($row['name']) . "</a>";
  
  // Start date
  if ($enable_periods)
  {
    list($start_period, $start_date) =  period_date_string($row['start_time']);
  }
  else
  {
    $start_date = time_date_string($row['start_time']);
  }
  if ($output_as_csv)
  {
    $values[] = escape($start_date);
  }
  else
  {
    // Include the numeric start time as a title in an empty span so
    // that the column can be sorted and filtered properly
    $values[] = "<span title=\"" . $row['start_time'] . "\"></span>" . escape($start_date);
  }
  
  // End date
  if ($enable_periods)
  {
    list( , $end_date) =  period_date_string($row['end_time'], -1);
  }
  else
  {
    $end_date = time_date_string($row['end_time']);
  }
  if ($output_as_csv)
  {
    $values[] = escape($end_date);
  }
  else
  {
    // Include the numeric end time as a title in an empty span so
    // that the column can be sorted and filtered properly
    $values[] = "<span title=\"" . $row['end_time'] . "\"></span>" . escape($end_date);
  }
  
  // Duration
  // Need the duration in seconds for sorting.  Have to correct it for DST
  // changes so that the user sees what he expects to see
  $duration_seconds = $row['end_time'] - $row['start_time'];
  $duration_seconds -= cross_dst($row['start_time'], $row['end_time']);
  $d = get_duration($row['start_time'], $row['end_time']);
  $values[] = "<span title=\"$duration_seconds\"></span>" .
              escape($d['duration'] . ' ' . $d['dur_units']);

  // Description:
  $values[] = escape($row['description']);

  // Entry Type:
  $et = empty($typel[$row['type']]) ? "?".$row['type']."?" : $typel[$row['type']];
  $values[] = escape($et);

  // Created by:
  $values[] = escape($row['create_by']);
  
  // Confirmation status
  if ($confirmation_somewhere)
  {
    // Translate the status field bit into meaningful text
    if ($row['confirmation_enabled'])
    {
      $confirmation_status = ($row['status'] & STATUS_TENTATIVE) ? get_vocab("tentative") : get_vocab("confirmed");
    }
    else
    {
      $confirmation_status = '';
    }
    $values[] = escape($confirmation_status);
  }
  
  // Approval status
  if ($approval_somewhere)
  {
    // Translate the status field bit into meaningful text
    if ($row['approval_enabled'])
    {
      $approval_status = ($row['status'] & STATUS_AWAITING_APPROVAL) ? get_vocab("awaiting_approval") : get_vocab("approved");
    }
    else
    {
      $approval_status = '';
    }
    $values[] = escape($approval_status);
  }
  
  // Now do any custom fields
  foreach ($custom_fields as $key => $value)
  {
    // Output a yes/no if it's a boolean or integer <= 2 bytes (which we will
    // assume are intended to be booleans)
    if (($field_natures[$key] == 'boolean') || 
        (($field_natures[$key] == 'integer') && isset($field_lengths[$key]) && ($field_lengths[$key] <= 2)) )
    {
      $output = empty($row[$key]) ? get_vocab("no") : get_vocab("yes");
    }
    // Otherwise output a string
    elseif (isset($row[$key]))
    {
      // If the custom field is an associative array then we want
      // the value rather than the array key
      if (is_assoc($select_options["entry.$key"]) && 
          array_key_exists($row[$key], $select_options["entry.$key"]))
      {
        $output = $select_options["entry.$key"][$row[$key]];
      }
      else
      {
        $output = $row[$key]; 
      }
    }
    else
    {
      $output = '';
    }
    $values[] = escape($output);
  }

  // Last updated:
  $last_updated = time_date_string($row['last_updated']);
  if ($output_as_csv)
  {
    $values[] = escape($last_updated);
  }
  else
  {
    // Include the numeric last updated time  as a title in an empty span so
    // that the column can be sorted and filtered properly
    $values[] = "<span title=\"" . $row['last_updated'] . "\"></span>" . escape($last_updated);
  }
  
  if ($ajax)
  {
    $json_data['aaData'][] = $values;
  }
  elseif ($output_as_csv)
  {
    $line = '"';
    $line .= implode("\"$csv_col_sep\"", $values);
    $line .= '"' . $csv_row_sep;
  }
  else
  {
    $line = "<tr>\n<td>";
    $line .= implode("</td>\n<td>", $values);
    $line .= "</td>\n</tr>\n";
  }
  
  echo $line;
}


function get_sumby_name_from_row(&$row)
{
  global $sumby, $typel;
  
  // Use brief description, created by or type as the name:
  switch( $sumby )
  {
    case 'd':
      $name = $row['name'];
      break;
    case 't':
      $name = $typel[ $row['type'] ];
      break;
    case 'c':
    default:
      $name = $row['create_by'];
      break;
  }
  return escape($name);
}


// Collect summary statistics on one entry. See below for columns in $row[].
// $sumby selects grouping on brief description (d) or created by (c).
// This also builds hash tables of all unique names and rooms. When sorted,
// these will become the column and row headers of the summary table.
function accumulate(&$row, &$count, &$hours, $report_start, $report_end,
                    &$room_hash, &$name_hash)
{
  global $output_as_csv;
  // Use brief description, created by or type as the name:
  $name = get_sumby_name_from_row($row);
  // Area and room separated by break (if not CSV):
  $room = escape($row['area_name']);
  $room .= ($output_as_csv) ? '/' : "<br>";
  $room .= escape($row['room_name']);
  // Accumulate the number of bookings for this room and name:
  @$count[$room][$name]++;
  // Accumulate hours used, clipped to report range dates:
  @$hours[$room][$name] += (min((int)$row['end_time'], $report_end)
                            - max((int)$row['start_time'], $report_start)) / 3600.0;
  $room_hash[$room] = MODE_TIMES;
  $name_hash[$name] = 1;
}

function accumulate_periods(&$row, &$count, &$hours, $report_start,
                            $report_end, &$room_hash, &$name_hash)
{
  global $periods;
  global $output_as_csv;
  
  $max_periods = count($periods);

  // Use brief description, created by or type as the name:
  $name = get_sumby_name_from_row($row);

  // Area and room separated by break (if not CSV):
  $room = escape($row['area_name']);
  $room .= ($output_as_csv) ? '/' : "<br>";
  $room .= escape($row['room_name']);
  // Accumulate the number of bookings for this room and name:
  @$count[$room][$name]++;
  // Accumulate periods used, clipped to report range dates:
  $dur = (min((int)$row['end_time'], $report_end) - max((int)$row['start_time'], $report_start))/60;
  @$hours[$room][$name] += ($dur % $max_periods) + floor( $dur/(24*60) ) * $max_periods;
  $room_hash[$room] = MODE_PERIODS;
  $name_hash[$name] = 1;
}


// Takes an array of cells and implodes them into either a CSV row
// or an HTML row, depending on the value of the $output_as_csv.
// If an HTML row, then the cells can be either <td> (the default)
// or <th> cells depending on $tag.   Additionally an attribute $attr
// can be added to the oipening tag - eg 'colspan="2"'
function implode_cells($cells, $tag='td', $attr=NULL)
{
  global $output_as_csv, $csv_col_sep;
  
  if ($output_as_csv)
  {
    $row = '"' . implode("\"$csv_col_sep\"", $cells) . '"';
  }
  else
  {
    $open_tag = $tag;
    if (!empty($attr))
    {
      $open_tag .= " $attr";
    }
    $row = "<$open_tag>" . implode("</$tag>\n<$open_tag>", $cells) . "</$tag>\n";
  }
  return $row;
}


// Takes an array of rows and implodes them into either a set of CSV rows
// or an HTML table section (<thead>, <tbody> or <tfoot>).
function implode_rows($rows, $tag='tbody')
{
  global $output_as_csv, $csv_row_sep;
  
  if ($output_as_csv)
  {
    $section = implode($csv_row_sep, $rows) . $csv_row_sep;
  }
  else
  {
    $section = "<$tag>\n<tr>\n" . implode("</tr>\n<tr>\n", $rows) . "</tr>\n</$tag>\n";
  }
  return $section;
}


// Format an entries value depending on whether it's destined for a CSV file or
// HTML output.   If it's HTML output then we enclose it in parentheses.
function entries_format($str)
{
  global $output_as_csv;
  
  if ($output_as_csv)
  {
    return $str;
  }
  else
  {
    return "($str)";
  }
}


// Output the summary table (a "cross-tab report"). $count and $hours are
// 2-dimensional sparse arrays indexed by [area/room][name].
// $room_hash & $name_hash are arrays with indexes naming unique rooms and names.
function do_summary(&$count, &$hours, &$room_hash, &$name_hash)
{
  global $output_as_csv, $csv_col_sep;
  global $times_somewhere, $periods_somewhere;
        
  // Sort the room and name arrays
  ksort($room_hash);
  ksort($name_hash);
  // Initialise grand total counters
  foreach (array(MODE_TIMES, MODE_PERIODS) as $m)
  {
    $grand_count_total[$m] = 0;
    $grand_hours_total[$m] = 0;
  }
  
  
  // TABLE HEAD
  // ----------
  $head_rows = array();
  $row1_cells = array();
  $row2_cells = array();

  foreach ($room_hash as $room => $mode)
  {
    $col_count_total[$room] = 0;
    $col_hours_total[$room] = 0.0;
    $mode_text = ($mode == MODE_TIMES) ? get_vocab("mode_times") : get_vocab("mode_periods");
    if ($output_as_csv)
    {
      $row1_cells[] = $room . ' - ' . get_vocab("entries");
      $row1_cells[] = $room . ' - ' .
                      (($mode == MODE_PERIODS) ? get_vocab("periods") : get_vocab("hours"));
      $row2_cells[] = $mode_text;
      $row2_cells[] = $mode_text;
    }
    else
    {
      $row1_cells[] = $room;
      $row2_cells[] = $mode_text;
    }
  }
  // Add the total column(s) onto the end
  if ($output_as_csv)
  {
    if ($times_somewhere)
    {
      $row1_cells[] = get_vocab("mode_times") . ": " . 
                      get_vocab("total") . ' - ' . 
                      get_vocab("entries");
      $row1_cells[] = get_vocab("mode_times") . ": " .
                      get_vocab("total") . ' - ' .
                      get_vocab("hours");
      $row2_cells[] = '';
      $row2_cells[] = '';
    }
    if ($periods_somewhere)
    {
      $row1_cells[] = get_vocab("mode_periods") . ": " . 
                      get_vocab("total") . ' - ' . 
                      get_vocab("entries");
      $row1_cells[] = get_vocab("mode_periods") . ": " .
                      get_vocab("total") . ' - ' .
                      get_vocab("hours");
      $row2_cells[] = '';
      $row2_cells[] = '';
    }
  }
  else
  {
    if ($times_somewhere)
    {
      $row1_cells[] = get_vocab("total") . "<br>" . get_vocab("mode_times");
      $row2_cells[] = "&nbsp;";
    }
    if ($periods_somewhere)
    {
      $row1_cells[] = get_vocab("total") . "<br>" . get_vocab("mode_periods");
      $row2_cells[] = "&nbsp;";
    }
  }
  // Implode the cells and add a label column on to the beginning (we have to
  // do it this way because the head is a bit more complicated than the body and
  // the foot as it has cells which span two columns)
  if ($output_as_csv)
  {
    $row1 = '""' . $csv_col_sep . implode_cells($row1_cells);
    $row2 = '"Mode"' . $csv_col_sep . implode_cells($row2_cells);
  }
  else
  {
    $row1  = "<th>&nbsp;</th>\n";
    $row1 .= implode_cells($row1_cells, 'th', 'colspan="2"');
    $row2  = "<th>" . get_vocab("mode") . "</th>\n";
    $row2 .= implode_cells($row2_cells, 'th', 'colspan="2"'); 
  }
  $head_rows[] = $row1;
  // Only use the second row if we need to, that is if we have both times and periods
  if ($times_somewhere && $periods_somewhere)
  {
    $head_rows[] = $row2;
  }
  $head = implode_rows($head_rows, 'thead');
  

  // TABLE BODY
  // ----------
  $body_rows = array();
  foreach ($name_hash as $name => $is_present)
  {
    foreach (array(MODE_TIMES, MODE_PERIODS) as $m)
    {
      $row_count_total[$m] = 0;
      $row_hours_total[$m] = 0;
    }
    $cells = array();
    $cells[] = $name;
    foreach ($room_hash as $room => $mode)
    {
      if (isset($count[$room][$name]))
      {
        $count_val = $count[$room][$name];
        $hours_val = $hours[$room][$name];
        $cells[] = entries_format($count_val);
        $format = ($mode == MODE_TIMES) ? FORMAT_TIMES : FORMAT_PERIODS;
        $cells[] = sprintf($format, $hours_val);
        $row_count_total[$mode] += $count_val;
        $row_hours_total[$mode] += $hours_val;
        $col_count_total[$room] += $count_val;
        $col_hours_total[$room] += $hours_val;
      }
      else
      {
        $cells[] = ($output_as_csv) ? '' : "&nbsp;";
        $cells[] = ($output_as_csv) ? '' : "&nbsp;";
      }
    }
    // Add the total column(s) onto the end
    if ($times_somewhere)
    {
      $cells[] = entries_format($row_count_total[MODE_TIMES]);
      $cells[] = sprintf(FORMAT_TIMES, $row_hours_total[MODE_TIMES]);
    }
    if ($periods_somewhere)
    {
      $cells[] = entries_format($row_count_total[MODE_PERIODS]);
      $cells[] = sprintf(FORMAT_PERIODS, $row_hours_total[MODE_PERIODS]);
    }
    $body_rows[] = implode_cells($cells, 'td');
    foreach (array(MODE_TIMES, MODE_PERIODS) as $m)
    {
      $grand_count_total[$m] += $row_count_total[$m];
      $grand_hours_total[$m] += $row_hours_total[$m];
    }
  }
  $body = implode_rows($body_rows, 'tbody');
  
  
  // TABLE FOOT
  // ----------
  $foot_rows = array();
  $cells = array();
  $cells[] = get_vocab("total");
  foreach ($room_hash as $room => $mode)
  {
    $cells[] = entries_format($col_count_total[$room]);
    $format = ($mode == MODE_TIMES) ? FORMAT_TIMES : FORMAT_PERIODS;
    $cells[] = sprintf($format, $col_hours_total[$room]);
  }
  // Add the total column(s) onto the end
  if ($times_somewhere)
  {
    $cells[] = entries_format($grand_count_total[MODE_TIMES]);
    $cells[] = sprintf(FORMAT_TIMES, $grand_hours_total[MODE_TIMES]);
  }
  if ($periods_somewhere)
  {
    $cells[] = entries_format($grand_count_total[MODE_PERIODS]);
    $cells[] = sprintf(FORMAT_PERIODS, $grand_hours_total[MODE_PERIODS]);
  }
  $foot_rows[] = implode_cells($cells, 'th');
  $foot = implode_rows($foot_rows, 'tfoot');
  
  
  // OUTPUT THE TABLE
  // ----------------
  if ($output_as_csv)
  {
    echo $head;
    echo $body;
    echo $foot;
  }
  else
  {
    echo "<div id=\"div_summary\">\n";
    echo "<h1>";
    if ($times_somewhere)
    {
      echo ($periods_somewhere) ?  get_vocab("summary_header_both") : get_vocab("summary_header");
    }
    else
    {
      echo get_vocab("summary_header_per");
    }
    echo "</h1>\n";
    echo "<table>\n";
    echo $head;
    echo $foot;  // <tfoot> has to come before <tbody>
    echo $body;
    echo "</table>\n";
    echo "</div>\n";
  }
}


// Work out whether we are running from the command line
$cli_mode = is_cli();

if ($cli_mode)
{
  // Need to set include path if we're running in CLI mode
  // (because otherwise PHP looks in the current directory rather
  // than the directory from which the script was called)
  ini_set("include_path", dirname($PHP_SELF));
}

$to_date = getdate(mktime(0, 0, 0, $month, $day + $default_report_days, $year));

// Get non-standard form variables
$from_day = get_form_var('from_day', 'int', $day);
$from_month = get_form_var('from_month', 'int', $month);
$from_year = get_form_var('from_year', 'int', $year);
$to_day = get_form_var('to_day', 'int', $to_date['mday']);
$to_month = get_form_var('to_month', 'int', $to_date['mon']);
$to_year = get_form_var('to_year', 'int', $to_date['year']);
$creatormatch = get_form_var('creatormatch', 'string');
$areamatch = get_form_var('areamatch', 'string');
$roommatch = get_form_var('roommatch', 'string');
$namematch = get_form_var('namematch', 'string');
$descrmatch = get_form_var('descrmatch', 'string');
$summarize = get_form_var('summarize', 'int', (($cli_mode) ? REPORT + OUTPUT_CSV : REPORT + OUTPUT_HTML));
$typematch = get_form_var('typematch', 'array');
$sortby = get_form_var('sortby', 'string', 'r');  // $sortby: r=room, s=start date/time.
$sumby = get_form_var('sumby', 'string', 'd');  // $sumby: d=by brief description, c=by creator, t=by type.
$match_approved = get_form_var('match_approved', 'int', APPROVED_BOTH);
$match_confirmed = get_form_var('match_confirmed', 'int', CONFIRMED_BOTH);
$match_private = get_form_var('match_private', 'int', PRIVATE_BOTH);
$phase = get_form_var('phase', 'int', 1);
$ajax = get_form_var('ajax', 'int');  // Set if this is an Ajax request
$datatable = get_form_var('datatable', 'int');  // Will only be set if we're using DataTables

// Check the user is authorised for this page
if ($cli_mode)
{
  $is_admin = TRUE;
}
else
{
  checkAuthorised();
  // Also need to know whether they have admin rights
  $user = getUserName();
  $user_level = authGetUserLevel($user);
  $is_admin =  ($user_level >= 2);
}

// If we're running in CLI mode we're passing the parameters in from the command line
// not the form and we want to go straight to Phase 2 (producing the report)
if ($cli_mode)
{
  $phase = 2;
}

// Set up for Ajax.   We need to know whether we're capable of dealing with Ajax
// requests, which will only be if (a) the browser is using DataTables and (b)
// we can do JSON encoding.    We also need to initialise the JSON data array.
$ajax_capable = $datatable && function_exists('json_encode');

if ($ajax)
{
  $json_data['aaData'] = array();
}

$private_somewhere = some_area('private_enabled') || some_area('private_mandatory');
$approval_somewhere = some_area('approval_enabled');
$confirmation_somewhere = some_area('confirmation_enabled');
$times_somewhere = (sql_query1("SELECT COUNT(*) FROM $tbl_area WHERE enable_periods=0") > 0);
$periods_somewhere = (sql_query1("SELECT COUNT(*) FROM $tbl_area WHERE enable_periods!=0") > 0);

$output_as_csv = $summarize & OUTPUT_CSV;
$output_as_ical = $summarize & OUTPUT_ICAL;
$output_as_html = ($summarize & OUTPUT_HTML) || !($output_as_csv || $output_as_ical);

// Get information about custom fields
$fields = sql_field_info($tbl_entry);
$custom_fields = array();
$field_natures = array();
$field_lengths = array();
foreach ($fields as $field)
{
  if (!in_array($field['name'], $standard_fields['entry']))
  {
    $custom_fields[$field['name']] = '';
  }
  $field_natures[$field['name']] = $field['nature'];
  $field_lengths[$field['name']] = $field['length'];
}

// Get the custom form inputs
foreach ($custom_fields as $key => $value)
{
  $var = "match_$key";
  if (($field_natures[$key] == 'integer') && ($field_lengths[$key] > 2))
  {
    $var_type = 'int';
  }
  else
  {
    $var_type = 'string';
  }
  $$var = get_form_var($var, $var_type);
}

// PHASE 2:  SQL QUERY.  We do the SQL query now to see if there's anything there
if ($phase == 2)
{
  // Start and end times are also used to clip the times for summary info.
  $report_start = mktime(0, 0, 0, $from_month+0, $from_day+0, $from_year+0);
  $report_end = mktime(0, 0, 0, $to_month+0, $to_day+1, $to_year+0);
  
  // Construct the SQL query
  $sql = "SELECT E.*, "
       .  sql_syntax_timestamp_to_unix("E.timestamp") . " AS last_updated, "
       . "A.area_name, R.room_name, "
       . "A.approval_enabled, A.confirmation_enabled, A.enable_periods";
  if ($output_as_ical)
  {
    // If we're producing an iCalendar then we'll also need the repeat
    // information in order to construct the recurrence rule
    $sql .= ", T.rep_type, T.end_date, T.rep_opt, T.rep_num_weeks";
  }
  $sql .= " FROM $tbl_area A, $tbl_room R, $tbl_entry E";
  if ($output_as_ical)
  {
    // We do a LEFT JOIN because we still want the single entries, ie the ones
    // that won't have a match in the repeat table
    $sql .= " LEFT JOIN $tbl_repeat T ON E.repeat_id=T.id";
  }
  $sql .= " WHERE E.room_id=R.id AND R.area_id=A.id"
        . " AND E.start_time < $report_end AND E.end_time > $report_start";
  if ($output_as_ical)
  {
    // We can't export periods in an iCalendar yet
    $sql .= " AND A.enable_periods=0";
  }
  
  if (!empty($areamatch))
  {
    // sql_syntax_caseless_contains() does the SQL escaping
    $sql .= " AND" .  sql_syntax_caseless_contains("A.area_name", $areamatch);
  }
  if (!empty($roommatch))
  {
    // sql_syntax_caseless_contains() does the SQL escaping
    $sql .= " AND" .  sql_syntax_caseless_contains("R.room_name", $roommatch);
  }
  if (!empty($typematch))
  {
    $sql .= " AND ";
    if ( count( $typematch ) > 1 )
    {
      $or_array = array();
      foreach ( $typematch as $type )
      {
        $or_array[] = "E.type = '".addslashes($type)."'";
      }
      $sql .= "(". implode( " OR ", $or_array ) .")";
    }
    else
    {
      $sql .= "E.type = '".addslashes($typematch[0])."'";
    }
  }
  if (!empty($namematch))
  {
    // sql_syntax_caseless_contains() does the SQL escaping
    $sql .= " AND" .  sql_syntax_caseless_contains("E.name", $namematch);
  }
  if (!empty($descrmatch))
  {
    // sql_syntax_caseless_contains() does the SQL escaping
    $sql .= " AND" .  sql_syntax_caseless_contains("E.description", $descrmatch);
  }
  if (!empty($creatormatch))
  {
    // sql_syntax_caseless_contains() does the SQL escaping
    $sql .= " AND" .  sql_syntax_caseless_contains("E.create_by", $creatormatch);
  }
  
  // (In the next three cases, you will get the empty string if that part
  // of the form was not displayed - which means that you need all bookings)
  // Note that although you can say eg "status&STATUS_PRIVATE" in MySQL, you get
  // an error in PostgreSQL as the expression is of the wrong type.
  
  // Match the privacy status
  if (($match_private != PRIVATE_BOTH) && ($match_private != ''))
  {
    $sql .= " AND ";
    $sql .= "(status&" . STATUS_PRIVATE;
    $sql .= ($match_private) ? "!=0)" : "=0)";  // Note that private works the other way round to the next two
  }
  // Match the confirmation status
  if (($match_confirmed != CONFIRMED_BOTH) && ($match_confirmed != ''))
  {
    $sql .= " AND ";
    $sql .= "(status&" . STATUS_TENTATIVE;
    $sql .= ($match_confirmed) ? "=0)" : "!=0)";
  }
  // Match the approval status
  if (($match_approved != APPROVED_BOTH) && ($match_approved != ''))
  {
    $sql .= " AND ";
    $sql .= "(status&" . STATUS_AWAITING_APPROVAL;
    $sql .= ($match_approved) ? "=0)" : "!=0)";
  }
  
  // Now do the custom fields
  foreach ($custom_fields as $key => $value)
  {
    $var = "match_$key";
    // Associative arrays (we can't just test for the string, because the database
    // contains the keys, not the values.   So we have to go through each key testing
    // for a possible match)
    if (!empty($$var) && is_assoc($select_options["entry.$key"]))
    {
      $sql .= " AND ";
      $or_array = array();
      foreach($select_options["entry.$key"] as $option_key => $option_value)
      {
        // We have to use strpos() rather than stripos() because we cannot
        // assume PHP5
        if (strpos(strtolower($option_value), strtolower($$var)) !== FALSE)
        {
          $or_array[] = "E.$key='" . addslashes($option_key) . "'";
        }
      }
      if (count($or_array) > 0)
      {
        $sql .= "(". implode( " OR ", $or_array ) .")";
      }
      else
      {
        $sql .= "FALSE";
      }
    }
    // Booleans (or integers <= 2 bytes which we assume are intended to be booleans)
    elseif (($field_natures[$key] == 'boolean') || 
       (($field_natures[$key] == 'integer') && isset($field_lengths[$key]) && ($field_lengths[$key] <= 2)) )
    {
      if (!empty($$var))
      {
        $sql .= " AND E.$key!=0";
      }
    }
    // Integers
    elseif (($field_natures[$key] == 'integer') && isset($field_lengths[$key]) && ($field_lengths[$key] > 2))
    {
      if (isset($$var) && $$var !== '')  // get_form_var() returns an empty string if no input
      {
        $sql .= " AND E.$key=" . $$var;
      }
    }
    // Strings
    else
    {
      if (!empty($$var))
      {
        $sql .= " AND" . sql_syntax_caseless_contains("E.$key", $$var);
      }
    }
  }

  // If we're not an admin (they are allowed to see everything), then we need
  // to make sure we respect the privacy settings.  (We rely on the privacy fields
  // in the area table being not NULL.   If they are by some chance NULL, then no
  // entries will be found, which is at least safe from the privacy viewpoint)
  if (!$is_admin)
  {
    if (isset($user))
    {
      // if the user is logged in they can see:
      //   - all bookings, if private_override is set to 'public'
      //   - their own bookings, and others' public bookings if private_override is set to 'none'
      //   - just their own bookings, if private_override is set to 'private'
      $sql .= " AND ((A.private_override='public') OR
                     (A.private_override='none' AND ((E.status&" . STATUS_PRIVATE . "=0) OR E.create_by = '" . addslashes($user) . "')) OR
                     (A.private_override='private' AND E.create_by = '" . addslashes($user) . "'))";                
    }
    else
    {
      // if the user is not logged in they can see:
      //   - all bookings, if private_override is set to 'public'
      //   - public bookings if private_override is set to 'none'
      $sql .= " AND ((A.private_override='public') OR
                     (A.private_override='none' AND (E.status&" . STATUS_PRIVATE . "=0)))";
    }
  }
  
  if ($summarize & OUTPUT_ICAL)
  {
    // If we're producing an iCalendar then we'll want the entries ordered by
    // repeat_id and then recurrence_id
    $sql .= " ORDER BY repeat_id, ical_recur_id";
  }
  elseif ($sortby == "r")
  {
    // Order by Area, Room, Start date/time
    $sql .= " ORDER BY area_name, sort_key, start_time";
  }
  else
  {
    // Order by Start date/time, Area, Room
    $sql .= " ORDER BY start_time, area_name, sort_key";
  }

  // echo "<p>DEBUG: SQL: <tt> $sql </tt></p>\n";

  $res = sql_query($sql);
  if (! $res)
  {
    trigger_error(sql_error(), E_USER_WARNING);
    fatal_error(FALSE, get_vocab("fatal_db_error"));
  }
  $nmatch = sql_count($res);
}

// print the page header
if ($ajax)
{
  // don't do anything if this is an Ajax request:  we only want the data
}
elseif ($output_as_html || (empty($nmatch) && !$cli_mode))
{
  print_header($day, $month, $year, $area, isset($room) ? $room : "");
}
elseif ($output_as_csv)
{
  $filename = ($summarize & REPORT) ? $report_filename : $summary_filename;
  header("Content-Type: text/csv; charset=" . get_charset());
  header("Content-Disposition: attachment; filename=\"$filename\"");
}
else // Assumed to be output_as_ical
{
  require_once "functions_ical.inc";
  header("Content-Type: application/ics;  charset=" . get_charset(). "; name=\"" . $mail_settings['ics_filename'] . ".ics\"");
  header("Content-Disposition: attachment; filename=\"" . $mail_settings['ics_filename'] . ".ics\"");
}


// Upper part: The form.
if (!$ajax && ($output_as_html || (empty($nmatch) && !$cli_mode)))
{
  ?>
  <div class="screenonly">
 
    <form class="form_general" id="report_form" method="post" action="report.php">
      <fieldset>
      <legend><?php echo get_vocab("report_on");?></legend>
      
        <fieldset>
        <legend><?php echo get_vocab("search_criteria");?></legend>
      
        <div id="div_report_start">
          <?php
          echo "<label for=\"from_datepicker\">" . get_vocab("report_start") . ":</label>\n";
          genDateSelector("from_", $from_day, $from_month, $from_year);
          ?>
        
        </div>
      
        <div id="div_report_end">
          <?php
          echo "<label for=\"to_datepicker\">" . get_vocab("report_end") . ":</label>\n";
          genDateSelector("to_", $to_day, $to_month, $to_year);
          ?>
        </div>
      
        <div id="div_areamatch">                  
          <label for="areamatch"><?php echo get_vocab("match_area");?>:</label>
          <input type="text" id="areamatch" name="areamatch" value="<?php echo htmlspecialchars($areamatch); ?>">
        </div>   
      
        <div id="div_roommatch">
          <label for="roommatch"><?php echo get_vocab("match_room");?>:</label>
          <input type="text" id="roommatch" name="roommatch" value="<?php echo htmlspecialchars($roommatch); ?>">
        </div>
      
        <div id="div_typematch">
          <label for="typematch"><?php echo get_vocab("match_type")?>:</label>
          <select id="typematch" name="typematch[]" multiple="multiple" size="5">
            <?php
            foreach ( $typel as $key => $val )
            {
              if (!empty($val) )
              {
                echo "                  <option value=\"$key\"" .
                (is_array($typematch) && in_array ($key, $typematch) ? " selected" : "") .
                ">$val</option>\n";
              }
            }
          ?>
          </select>
          <span><?php echo get_vocab("ctrl_click_type") ?></span>
        </div>
      
        <div id="div_namematch">     
          <label for="namematch"><?php echo get_vocab("match_entry");?>:</label>
          <input type="text" id="namematch" name="namematch" value="<?php echo htmlspecialchars($namematch); ?>">
        </div>   
      
        <div id="div_descrmatch">
          <label for="descrmatch"><?php echo get_vocab("match_descr");?>:</label>
          <input type="text" id="descrmatch" name="descrmatch" value="<?php echo htmlspecialchars($descrmatch); ?>">
        </div>
      
        <div id="div_creatormatch">
          <label for="creatormatch"><?php echo get_vocab("createdby");?>:</label>
          <input type="text" id="creatormatch" name="creatormatch" value="<?php echo htmlspecialchars($creatormatch); ?>">
        </div>
        
        <?php
        // Privacy status
        // Only show this part of the form if there are areas that allow private bookings
        if ($private_somewhere)
        {
          // If they're not logged in then there's no point in showing this part of the form because
          // they'll only be able to see public bookings anyway (and we don't want to alert them to
          // the existence of porivate bookings)
          if (empty($user_level))
          {
            echo "<input type=\"hidden\" name=\"match_private\" value=\"" . PRIVATE_NO . "\">\n";
          }
          // Otherwise give them the radio buttons
          else
          {
            echo "<div id=\"div_privacystatus\">\n";
            echo "<label>" . get_vocab("privacy_status") . ":</label>\n";
            echo "<div class=\"group\">\n";   
            $options = array(PRIVATE_BOTH => 'both', PRIVATE_NO => 'default_public', PRIVATE_YES => 'default_private');
            foreach ($options as $option => $token)
            {
              echo "<label>";
              echo "<input class=\"radio\" type=\"radio\" name=\"match_private\" value=\"$option\"" .          
                   (($match_private == $option) ? " checked=\"checked\"" : "") .
                   ">" . get_vocab($token);
              echo "</label>\n";
            }
            echo "</div>\n";
            echo "</div>\n";
          }
        }
        
        // Confirmation status
        // Only show this part of the form if there are areas that require approval
        if ($confirmation_somewhere)
        {
          echo "<div id=\"div_confirmationstatus\">\n";
          echo "<label>" . get_vocab("confirmation_status") . ":</label>\n";
          echo "<div class=\"group\">\n";   
          $options = array(CONFIRMED_BOTH => 'both', CONFIRMED_YES => 'confirmed', CONFIRMED_NO => 'tentative');
          foreach ($options as $option => $token)
          {
            echo "<label>";
            echo "<input class=\"radio\" type=\"radio\" name=\"match_confirmed\" value=\"$option\"" .          
                 (($match_confirmed == $option) ? " checked=\"checked\"" : "") .
                 ">" . get_vocab($token);
            echo "</label>\n";
          }
          echo "</div>\n";
          echo "</div>\n";
        }
        
        // Approval status
        // Only show this part of the form if there are areas that require approval
        if ($approval_somewhere)
        {
          echo "<div id=\"div_approvalstatus\">\n";
          echo "<label>" . get_vocab("approval_status") . ":</label>\n";
          echo "<div class=\"group\">\n";   
          $options = array(APPROVED_BOTH => 'both', APPROVED_YES => 'approved', APPROVED_NO => 'awaiting_approval');
          foreach ($options as $option => $token)
          {
            echo "<label>";
            echo "<input class=\"radio\" type=\"radio\" name=\"match_approved\" value=\"$option\"" .          
                 (($match_approved == $option) ? " checked=\"checked\"" : "") .
                 ">" . get_vocab($token);
            echo "</label>\n";
          }
          echo "</div>\n";
          echo "</div>\n";
        }
        

        // Now do the custom fields
        foreach ($custom_fields as $key => $value)
        {
          $var = "match_$key";
          echo "<div>\n";
          echo "<label for=\"$var\">" . get_loc_field_name($tbl_entry, $key) . ":</label>\n";
          // Output a checkbox if it's a boolean or integer <= 2 bytes (which we will
          // assume are intended to be booleans)
          if (($field_natures[$key] == 'boolean') || 
              (($field_natures[$key] == 'integer') && isset($field_lengths[$key]) && ($field_lengths[$key] <= 2)) )
          {
            echo "<input type=\"checkbox\" class=\"checkbox\" " .
                  "id=\"$var\" name=\"$var\" value=\"1\" " .
                  ((!empty($$var)) ? " checked=\"checked\"" : "") .
                  ">\n";
          }
          // Otherwise output a text input
          else
          {
            echo "<input type=\"text\" " .
                  "id=\"$var\" name=\"$var\" " .
                  "value=\"" . htmlspecialchars($$var) . "\"" .
                  ">\n";
          }
          echo "</div>\n";
        }
        ?>
        </fieldset>
      
        <fieldset>
        <legend><?php echo get_vocab("presentation_options");?></legend>  
        <div id="div_summarize">
          <label><?php echo get_vocab("include");?>:</label>
          <?php
          // Radio buttons to choose the value of the summarize parameter
          // Set up an array of arrays mapping the button value to the description
          // Each outer array represents a different group of buttons
          $buttons = array();
          // The HTML output buttons
          $buttons[] = array(REPORT + OUTPUT_HTML           => "report_only",
                             SUMMARY + OUTPUT_HTML          => "summary_only",
                             REPORT + SUMMARY + OUTPUT_HTML => "report_and_summary");
          // The CSV output buttons
          $buttons[] = array(REPORT + OUTPUT_CSV            => "report_as_csv",
                             SUMMARY + OUTPUT_CSV           => "summary_as_csv");
          // The iCal output button
          if ($times_somewhere) // We can't do iCalendars for periods yet
          {
            $buttons[] = array(REPORT + OUTPUT_ICAL           => "report_as_ical");
          }
          
          echo "<div class=\"group_container\">\n";
          foreach ($buttons as $button_group)
          {
            echo "<div class=\"group\">\n";
            // Output each radio button
            foreach ($button_group as $value => $token)
            {
              echo "<label>";
              echo "<input class=\"radio\" type=\"radio\" name=\"summarize\" value=\"$value\"";          
              if ($summarize == $value) echo " checked=\"checked\"";
              echo ">" . get_vocab($token);
              echo "</label>\n";
            }
            echo "</div>\n";
          }
          echo "</div>\n";
          ?>
        </div>
      
        <div id="div_sortby"> 
          <label><?php echo get_vocab("sort_rep");?>:</label>
          <div class="group">
            <label>
              <input class="radio" type="radio" name="sortby" value="r"
              <?php 
              if ($sortby=="r") echo " checked=\"checked\"";
              echo ">". get_vocab("room");?>
            </label>
            <label>
              <input class="radio" type="radio" name="sortby" value="s"
              <?php 
              if ($sortby=="s") echo " checked=\"checked\"";
              echo ">". get_vocab("sort_rep_time");?>
            </label>
          </div>
        </div>
      
        <div id="div_sumby">
          <label><?php echo get_vocab("summarize_by");?>:</label>
          <div class="group">
            <label>
              <input class="radio" type="radio" name="sumby" value="d"
              <?php 
              if ($sumby=="d") echo " checked=\"checked\"";
              echo ">" . get_vocab("sum_by_descrip");
              ?>
            </label>
            <label>
              <input class="radio" type="radio" name="sumby" value="c"
              <?php 
              if ($sumby=="c") echo " checked=\"checked\"";
              echo ">" . get_vocab("sum_by_creator");
              ?>
            </label>
            <label>
              <input class="radio" type="radio" name="sumby" value="t"
              <?php 
              if ($sumby=="t") echo " checked=\"checked\"";
              echo ">" . get_vocab("sum_by_type");
              ?>
            </label>
          </div>
        </div>
        </fieldset>
      
        <div id="report_submit">
          <input type="hidden" name="phase" value="2">
          <input class="submit" type="submit" value="<?php echo get_vocab("submitquery") ?>">
        </div>
        
      
      </fieldset>
    </form>
  </div>
  <?php
}

// PHASE 2:  Output the results, if called with parameters:
if ($phase == 2)
{
  if (($nmatch == 0) && !$cli_mode)
  {
    if ($ajax)
    {
      echo json_encode($json_data);
    }
    elseif ($output_as_html)
    {
      echo "<p class=\"report_entries\">" . get_vocab("nothing_found") . "</p>\n";
    }
    sql_free($res);
  }
  else
  {
    if ($output_as_ical)
    {
      // We set $keep_private to FALSE here because we excluded all private
      // events in the SQL query
      export_icalendar($res, FALSE, $report_end);
      exit;
    }
    
    if ($output_as_html & !$ajax)
    {
      echo "<p class=\"report_entries\"><span id=\"n_entries\">" . $nmatch . "</span> "
      . ($nmatch == 1 ? get_vocab("entry_found") : get_vocab("entries_found"))
      .  "</p>\n";
    }
    
    if ($summarize & REPORT)
    {
      open_report();
    }

    for ($i = 0; ($row = sql_row_keyed($res, $i)); $i++)
    {
      if ($summarize & REPORT)
      {
        report_row($row, $sortby);
      }

      if (!$ajax && ($summarize & SUMMARY))
      {
        (empty($row['enable_periods']) ?
         accumulate($row, $count, $hours, $report_start, $report_end,
                    $room_hash, $name_hash) :
         accumulate_periods($row, $count, $hours, $report_start, $report_end,
                            $room_hash, $name_hash)
          );
      }
    }
    
    if ($summarize & REPORT)
    {
      close_report();
    }
    
    if (!$ajax && ($summarize & SUMMARY))
    {
      do_summary($count, $hours, $room_hash, $name_hash);
    }
  }
}

if ($cli_mode)
{
  exit(0);
}

if (($output_as_html || empty($nmatch)) & !$ajax)
{
  require_once "trailer.inc";
}
?>
