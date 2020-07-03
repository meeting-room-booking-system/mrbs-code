<?php
namespace MRBS;

use MRBS\Form\Form;
use MRBS\Form\ElementFieldset;
use MRBS\Form\ElementInputHidden;
use MRBS\Form\FieldDiv;
use MRBS\Form\FieldInputDatalist;
use MRBS\Form\FieldInputDate;
use MRBS\Form\FieldInputRadioGroup;
use MRBS\Form\FieldInputSubmit;
use MRBS\Form\FieldInputText;
use MRBS\Form\FieldSelect;


require "defaultincludes.inc";


function get_field_from_date($data)
{
  $value = format_iso_date($data['from_year'], $data['from_month'], $data['from_day']);
  $field = new FieldInputDate();
  $field->setAttribute('id', 'div_report_start')
        ->setLabel(get_vocab('report_start'))
        ->setControlAttributes(array('name'     => 'from_date',
                                     'value'    => $value,
                                     'required' => true));
  return $field;
}


function get_field_to_date($data)
{
  $value = format_iso_date($data['to_year'], $data['to_month'], $data['to_day']);
  $field = new FieldInputDate();
  $field->setAttribute('id', 'div_report_end')
        ->setLabel(get_vocab('report_end'))
        ->setControlAttributes(array('name'     => 'to_date',
                                     'value'    => $value,
                                     'required' => true));
  return $field;
}


function get_field_areamatch($data)
{
  $field = new FieldInputDatalist();
  $options = get_area_names($all=true);
  $field->setAttribute('id', 'div_areamatch')
        ->setLabel(get_vocab('match_area'))
        ->setControlAttributes(array('name'  => 'areamatch',
                                     'value' => $data['areamatch']))
        ->addDatalistOptions($options, false);

  return $field;
}


function get_field_roommatch($data)
{
  $field = new FieldInputDatalist();

  // (We need DISTINCT because it's possible to have two rooms of the same name
  // in different areas)
  $sql = "SELECT DISTINCT room_name FROM " . _tbl('room');

  // Don't show the invisible rooms
  $invisible_room_ids = get_invisible_room_ids();
  if (count($invisible_room_ids) > 0)
  {
    $sql .= " WHERE id NOT IN (" . implode(',', $invisible_room_ids) . ")";
  }

  $sql .=  " ORDER BY room_name";

  $options = db()->query_array($sql);

  $field->setAttribute('id', 'div_roommatch')
        ->setLabel(get_vocab('match_room'))
        ->setControlAttributes(array('name'  => 'roommatch',
                                     'value' => $data['roommatch']))
        ->addDatalistOptions($options, false);

  return $field;
}


function get_field_typematch($data)
{
  $options = get_type_options(true);

  if (count($options) < 2)
  {
    return null;
  }

  $field = new FieldSelect();
  $field->setAttributes(array('id'    => 'div_typematch',
                              'class' => 'multiline'))
        ->setLabel(get_vocab('match_type'))
        ->setLabelAttribute('title', get_vocab('ctrl_click_type'))
        ->setControlAttributes(array('id'       => 'typematch',
                                     'name'     => 'typematch[]',
                                     'multiple' => true,
                                     'size'     => '5'))
        ->addSelectOptions($options, $data['typematch'], true);

  return $field;
}


function get_field_match_private($data)
{
  global $user_level, $private_somewhere;

  // Only show this part of the form if there are areas that allow private bookings
  if (!$private_somewhere)
  {
    return null;
  }

  // If they're not logged in then there's no point in showing this part of the form because
  // they'll only be able to see public bookings anyway (and we don't want to alert them to
  // the existence of private bookings)
  if (empty($user_level))
  {
    $field = new ElementInputHidden();
    $field->setAttributes(array('name'  => 'match_private',
                                'value' => BOOLEAN_MATCH_FALSE));
  }
  else
  {
    $options = array(BOOLEAN_MATCH_BOTH  => get_vocab('both'),
                     BOOLEAN_MATCH_FALSE => get_vocab('default_public'),
                     BOOLEAN_MATCH_TRUE  => get_vocab('default_private'));
    $field = new FieldInputRadioGroup();
    $field->setAttribute('id', 'div_privacystatus')
          ->setLabel(get_vocab('privacy_status'))
          ->addRadioOptions($options, 'match_private', $data['match_private'], true);
  }

  return $field;
}


function get_field_match_confirmed($data)
{
  global $confirmation_somewhere;

  // Only show this part of the form if there are areas that allow tentative bookings
  if (!$confirmation_somewhere)
  {
    return null;
  }

  $options = array(BOOLEAN_MATCH_BOTH  => get_vocab('both'),
                   BOOLEAN_MATCH_TRUE  => get_vocab('confirmed'),
                   BOOLEAN_MATCH_FALSE => get_vocab('tentative'));
  $field = new FieldInputRadioGroup();
  $field->setAttribute('id', 'div_confirmationstatus')
        ->setLabel(get_vocab('confirmation_status'))
        ->addRadioOptions($options, 'match_confirmed', $data['match_confirmed'], true);

  return $field;
}


function get_field_match_approved($data)
{
  global $approval_somewhere;

  // Only show this part of the form if there are areas that require approval
  if (!$approval_somewhere)
  {
    return null;
  }

  $options = array(BOOLEAN_MATCH_BOTH  => get_vocab('both'),
                   BOOLEAN_MATCH_TRUE  => get_vocab('approved'),
                   BOOLEAN_MATCH_FALSE => get_vocab('awaiting_approval'));
  $field = new FieldInputRadioGroup();
  $field->setAttribute('id', 'div_approvalstatus')
        ->setLabel(get_vocab('approval_status'))
        ->addRadioOptions($options, 'match_approved', $data['match_approved'], true);

  return $field;
}


function get_field_custom($data, $key)
{
  $var = "match_$key";
  global $$var;

  global $field_natures, $field_lengths;

  $name = $var;
  $label = get_loc_field_name(_tbl('entry'), $key);

  // Output a radio group if it's a boolean or integer <= 2 bytes (which we will
  // assume are intended to be booleans)
  if (($field_natures[$key] == 'boolean') ||
      (($field_natures[$key] == 'integer') && isset($field_lengths[$key]) && ($field_lengths[$key] <= 2)) )
  {
    $options = array(BOOLEAN_MATCH_BOTH  => get_vocab('both'),
                     BOOLEAN_MATCH_TRUE  => get_vocab('with'),
                     BOOLEAN_MATCH_FALSE => get_vocab('without'));
    $value = (isset($$var)) ? $$var : BOOLEAN_MATCH_BOTH;
    $field = new FieldInputRadioGroup();
    $field->setAttribute('id', "div_$name")
          ->setLabel($label)
          ->addRadioOptions($options, $name, $value, true);
  }
  else
  {
    $params = array('label' => $label,
                    'name'  => $name,
                    'value' => (isset($$var)) ? $$var : null,
                    'field' => "entry.$key");
    $field = get_field_report_input($params);
  }

  return $field;
}


// Generates a text input field of some kind.   If $select_options or $datalist_options
// is set then it will be a datalist, otherwise it will be a simple input field
//   $params  an array indexed by 'label', 'name', 'value' and 'field'
function get_field_report_input($params)
{
  global $select_options, $datalist_options;

  // If $select_options is defined we want to force a <datalist> and not a
  // <select>.  That's because if we have options such as
  // ('tea', 'white coffee', 'black coffee') we want the user to be able to type
  // 'coffee' which will match both 'white coffee' and 'black coffee'.
  if (isset($params['field']))
  {
    if (!empty($select_options[$params['field']]))
    {
      $options = $select_options[$params['field']];
    }
    elseif (!empty($datalist_options[$params['field']]))
    {
      $options = $datalist_options[$params['field']];
    }
  }

  if (isset($options))
  {
    // Remove any element with an empty key.  This will just be associated with a
    // required select element and the value will be meaningless for a search.
    unset($options['']);
    // Remove any elements with an empty value.   These will be meaningless for a search.
    $options = array_diff($options, array(''));
    $field = new FieldInputDatalist();
    // We force the values to be used and not the keys.   We will convert
    // back to values when we construct the SQL query.
    $field->addDatalistOptions($options, false);
  }
  else
  {
    $field = new FieldInputText();
  }

  $field->setAttribute('id', 'div_' . $params['name'])
        ->setLabel($params['label'])
        ->setControlAttributes(array('name'  => $params['name'],
                                     'value' => $params['value']));

  return $field;
}


function get_fieldset_search_criteria($data)
{
  global $report_search_field_order;

  $fieldset = new ElementFieldset();
  $fieldset->addLegend(get_vocab('search_criteria'));

  foreach ($report_search_field_order as $key)
  {
    switch ($key)
    {
      case 'report_start':
        $fieldset->addElement(get_field_from_date($data));
        break;

      case 'report_end':
        $fieldset->addElement(get_field_to_date($data));
        break;

      case 'areamatch':
        $fieldset->addElement(get_field_areamatch($data));
        break;

      case 'roommatch':
        $fieldset->addElement(get_field_roommatch($data));
        break;

      case 'typematch':
        $fieldset->addElement(get_field_typematch($data));
        break;

      case 'namematch':
        $params = array('label' => get_vocab('match_entry'),
                        'name'  => 'namematch',
                        'value' => $data['namematch'],
                        'field' => 'entry.name');
        $fieldset->addElement(get_field_report_input($params));
        break;

      case 'descrmatch':
        $params = array('label' => get_vocab('match_descr'),
                        'name'  => 'descrmatch',
                        'value' => $data['descrmatch'],
                        'field' => 'entry.description');
        $fieldset->addElement(get_field_report_input($params));
        break;

      case 'creatormatch':
        $params = array('label' => get_vocab('createdby'),
                        'name'  => 'creatormatch',
                        'value' => $data['creatormatch'],
                        'field' => 'entry.create_by');
        $fieldset->addElement(get_field_report_input($params));
        break;

      case 'match_private':
        $fieldset->addElement(get_field_match_private($data));
        break;

      case 'match_confirmed':
        $fieldset->addElement(get_field_match_confirmed($data));
        break;

      case 'match_approved':
        $fieldset->addElement(get_field_match_approved($data));
        break;

      default:
        // Must be a custom field
        $fieldset->addElement(get_field_custom($data, $key));
        break;
    } // switch
  } // foreach

  return $fieldset;
}


function get_field_output($data)
{
  $options = array(REPORT  => get_vocab('report'),
                   SUMMARY => get_vocab('summary'));
  $field = new FieldInputRadioGroup();
  $field->setLabel(get_vocab('output'))
        ->addRadioOptions($options, 'output', $data['output'], true);
  return $field;
}


function get_field_output_format($data)
{
  global $times_somewhere;

  // Note that it's useful to have the server-side CSV option here even though we've also got the
  // DataTables client-side CSV button, because report.php can be run as a cron job to produce regular
  // reports in CSV format.
  $options = array(OUTPUT_HTML => get_vocab('html'),
                   OUTPUT_CSV  => get_vocab('csv'));
  // The iCal output button
  if ($times_somewhere) // We can't do iCalendars for periods yet
  {
    $options[OUTPUT_ICAL] = get_vocab('ical');
  }

  $field = new FieldInputRadioGroup();
  $field->setLabel(get_vocab('format'))
        ->addRadioOptions($options, 'output_format', $data['output_format'], true);
  return $field;
}


function get_field_sortby($data)
{
  $options = array('r' => get_vocab('sort_room'),
                   's' => get_vocab('sort_rep_time'));
  $field = new FieldInputRadioGroup();
  $field->setLabel(get_vocab('sort_rep'))
        ->addRadioOptions($options, 'sortby', $data['sortby'], true);
  return $field;
}


function get_field_sumby($data)
{
  $options = array('d' => get_vocab('sum_by_descrip'),
                   'c' => get_vocab('sum_by_creator'),
                   't' => get_vocab('sum_by_type'));
  $field = new FieldInputRadioGroup();
  $field->setLabel(get_vocab('summarize_by'))
        ->addRadioOptions($options, 'sumby', $data['sumby'], true);
  return $field;
}


function get_fieldset_presentation_options($data)
{
  global $report_presentation_field_order;

  $fieldset = new ElementFieldset();
  $fieldset->addLegend(get_vocab('presentation_options'));

  foreach ($report_presentation_field_order as $key)
  {
    switch ($key)
    {
      case 'output':
        $fieldset->addElement(get_field_output($data));
        break;

      case 'output_format':
        $fieldset->addElement(get_field_output_format($data));
        break;

      case 'sortby':
        $fieldset->addElement(get_field_sortby($data));
        break;

      case 'sumby':
        $fieldset->addElement(get_field_sumby($data));
        break;

      default:
        break;
    } // switch
  } // foreach

  return $fieldset;
}


function get_fieldset_submit_buttons()
{
  $fieldset = new ElementFieldset();

  $field = new FieldInputSubmit();
  $field->setControlAttribute('value', get_vocab('submitquery'));

  $fieldset->addElement($field);

  return $fieldset;
}


// Works out whether the machine architecture is little-endian
function is_little_endian()
{
  static $result;

  if (!isset($result))
  {
    $testint = 0x00FF;
    $p = pack('S', $testint);
    $result = ($testint===current(unpack('v', $p)));
  }

  return $result;
}


// Converts a string from the standard MRBS character set to the character set
// to be used for CSV files
function csv_conv($string)
{
  $in_charset = utf8_strtoupper(get_charset());
  $out_charset = utf8_strtoupper(get_csv_charset());

  // Use iconv() if it exists because it's faster than our own code and also it's
  // standard (though it has the disadvantage that it adds in BOMs which we have to remove)
  if (function_exists('iconv'))
  {
    if ($out_charset == 'UTF-16')
    {
      // If the endian-ness hasn't been specified, then state it explicitly, because
      // Windows and Unix will use different defaults on the same architecture.
      $out_charset .= (is_little_endian()) ? 'LE' : 'BE';
    }

    $result = iconv($in_charset, $out_charset, $string);
    // iconv() will add in a BOM if the output encoding requires one, but as we are only
    // dealing with parts of a file we don't want any BOMs because we add them separately
    // at the beginning of the file.  So strip off anything that looks like a BOM.
    $boms = array("\xFE\xFF", "\xFF\xFE");
    foreach ($boms as $bom)
    {
      if (strpos($result, $bom) === 0)
      {
        $result = substr($result, strlen($bom));
      }
    }
  }
  elseif ($in_charset == $out_charset)
  {
    $result = $string;
  }
  elseif (($in_charset == 'UTF-8') && ($out_charset == 'UTF-16'))
  {
    $result = utf8_to_utf16($string);
  }
  else
  {
    trigger_error("Cannot convert from $in_charset to $out_charset", E_USER_NOTICE);
    $result = FALSE;
  }

  return $result;
}


// Escape a string for output
function escape($string)
{
  global $output_format;

  switch ($output_format)
  {
    case OUTPUT_HTML:
      $string = mrbs_nl2br(htmlspecialchars($string));
      break;
    case OUTPUT_CSV:
      $string = str_replace('"', '""', $string);
      break;
    default:  // do nothing
      break;
  }

  return $string;
}


// Wraps $string in a span with a data-type value of $data_type - but only for HTML output
function type_wrap($string, $data_type)
{
  global $output_format;

  if ($output_format == OUTPUT_HTML)
  {
    return '<span class="normal" data-type="' . $data_type . '">' . $string . '</span>';
  }
  else
  {
    return $string;
  }
}


// Output the first row (header row) for CSV reports
function report_header()
{
  global $output_format, $is_ajax;
  global $custom_fields;
  global $approval_somewhere, $confirmation_somewhere;
  global $field_order_list, $booking_types;

  // Don't do anything if this is an Ajax request: we only want to send the data
  if ($is_ajax)
  {
    return;
  }

  // Build an array of values to go into the header row
  $values = array();

  foreach ($field_order_list as $field)
  {
    // We give some columns a type data value so that the JavaScript knows how to sort them
    switch ($field)
    {
      case 'name':
        $values[] = get_vocab("namebooker");
        break;
      case 'area_name':
        $values[] = get_vocab("area");
        break;
      case 'room_name':
        $values[] = get_vocab("room");
        break;
      case 'start_time':
        $values[] = type_wrap(get_vocab("start_date"), 'title-numeric');
        break;
      case 'end_time':
        $values[] = type_wrap(get_vocab("end_date"), 'title-numeric');
        $values[] = type_wrap(get_vocab("duration"), 'title-numeric');
        break;
      case 'description':
        $values[] = get_vocab("fulldescription_short");
        break;
      case 'type':
        if (isset($booking_types) && (count($booking_types) > 1))
        {
          $values[] = get_vocab("type");
        }
        break;
      case 'create_by':
        $values[] = get_vocab("createdby");
        break;
      case 'confirmation_enabled':
        if ($confirmation_somewhere)
        {
          $values[] = get_vocab("confirmation_status");
        }
        break;
      case 'approval_enabled':
        if ($approval_somewhere)
        {
          $values[] = get_vocab("approval_status");
        }
        break;
      case 'last_updated':
        $values[] = type_wrap(get_vocab("lastupdate"), 'title-numeric');
        break;
      default:
        // the custom fields
        if (array_key_exists($field, $custom_fields))
        {
          $values[] = get_loc_field_name(_tbl('entry'), $field);
        }
        break;
    }  // switch
  }  // foreach


  // Find out what the non-breaking space is in this character set
  $charset = get_charset();
  $nbsp = html_entity_decode('&nbsp;', ENT_NOQUOTES, $charset);
  for ($i=0; $i < count($values); $i++)
  {
    if ($output_format != OUTPUT_HTML)
    {
      // Remove any HTML entities from the values
      $values[$i] = html_entity_decode($values[$i], ENT_NOQUOTES, $charset);
      // Trim non-breaking spaces from the string
      $values[$i] = trim($values[$i], $nbsp);
      // And do an ordinary trim
      $values[$i] = trim($values[$i]);
      // We don't escape HTML output here because the vocab strings are trusted.
      // And some of them contain HTML entities such as &nbsp; on purpose
      $values[$i] = escape($values[$i]);
    }

  }

  $head_rows = array();
  $head_rows[] = $values;
  output_head_rows($head_rows, $output_format);
}


function open_report()
{
  global $output_format, $is_ajax;

  if ($output_format == OUTPUT_HTML && !$is_ajax)
  {
    echo "<div id=\"report_output\" class=\"datatable_container\">\n";
    echo "<table class=\"admin_table display\" id=\"report_table\">\n";
  }
}


function close_report()
{
  global $output_format, $is_ajax, $json_data;

  // If this is an Ajax request, we can now send the JSON data
  if ($is_ajax)
  {
    http_headers(array("Content-Type: application/json"));
    echo json_encode($json_data);
  }
  elseif ($output_format == OUTPUT_HTML)
  {
    echo "</table>\n";
    echo "</div>\n";
  }
}


function open_summary()
{
  global $output_format, $times_somewhere, $periods_somewhere;

  if ($output_format == OUTPUT_HTML)
  {
    echo "<div id=\"div_summary\" class=\"js_hidden\">\n";
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
  }
}


function close_summary()
{
  global $output_format;

  if ($output_format == OUTPUT_HTML)
  {
    echo "</table>\n";
    echo "</div>\n";
  }
}


// Output a table row.
function output_row($values, $output_format, $body_row = TRUE)
{
  global $json_data, $is_ajax, $csv_col_sep, $csv_row_sep;

  if ($is_ajax && $body_row)
  {
    $json_data['aaData'][] = $values;
  }
  else
  {
    if ($output_format == OUTPUT_CSV)
    {
      $line = '"';
      $line .= implode("\"$csv_col_sep\"", $values);
      $line .= '"' . $csv_row_sep;
      $line = csv_conv($line);
    }
    elseif ($output_format == OUTPUT_HTML)
    {
      $line = '';
      $cell_tag = ($body_row) ? 'td' : 'th';
      $line .= "<tr>\n<$cell_tag>";
      $line .= implode("</$cell_tag>\n<$cell_tag>", $values);
      $line .= "</$cell_tag>\n</tr>\n";
    }
    echo $line;
  }
}


function output_head_rows($rows, $format)
{
  if (count($rows) == 0)
  {
    return;
  }

  if ($format == OUTPUT_HTML)
  {
    echo "<colgroup>";
    foreach ($rows[0] as $cell)
    {
      echo "<col>";
    }
    echo "</colgroup>\n";
  }
  echo ($format == OUTPUT_HTML) ? "<thead>\n" : "";
  foreach ($rows as $row)
  {
    output_row($row, $format, FALSE);
  }
  echo ($format == OUTPUT_HTML) ? "</thead>\n" : "";
}


function output_body_rows($rows, $format)
{
  global $is_ajax;

  if (count($rows) == 0)
  {
    return;
  }

  echo (($format == OUTPUT_HTML) && !$is_ajax) ? "<tbody>\n" : "";
  foreach ($rows as $row)
  {
    output_row($row, $format, TRUE);
  }
  echo (($format == OUTPUT_HTML) && !$is_ajax) ? "</tbody>\n" : "";
}


function output_foot_rows($rows, $format)
{
  if (count($rows) == 0)
  {
    return;
  }

  echo ($format == OUTPUT_HTML) ? "<tfoot>\n" : "";
  foreach ($rows as $row)
  {
    output_row($row, $format, FALSE);
  }
  echo ($format == OUTPUT_HTML) ? "</tfoot>\n" : "";
}


function report_row(&$rows, $data)
{
  global $output_format, $is_ajax, $ajax_capable;
  global $custom_fields, $field_natures, $field_lengths;
  global $approval_somewhere, $confirmation_somewhere;
  global $select_options, $booking_types;
  global $field_order_list;

  // If we're capable of delivering an Ajax request and this is not Ajax request,
  // then don't do anything.  We're going to save sending the data until we actually
  // get the Ajax request;  we just send the rest of the page at this stage.
  if (($output_format == OUTPUT_HTML) && $ajax_capable && !$is_ajax)
  {
    return;
  }

  $values = array();

  foreach ($field_order_list as $field)
  {
    $value = $data[$field];

    // Some fields need some special processing to turn the raw value into something
    // more meaningful
    switch ($field)
    {
      case 'end_time':
        // Calculate the duration and then fall through to calculating the end date
        // Need the duration in seconds for sorting.  Have to correct it for DST
        // changes so that the user sees what he expects to see
        $duration_seconds = $data['end_time'] - $data['start_time'];
        $duration_seconds -= cross_dst($data['start_time'], $data['end_time']);
        $d = get_duration($data['start_time'], $data['end_time'], $data['enable_periods'], $data['area_id']);
        $d_string = $d['duration'] . ' ' . $d['dur_units'];
        $d_string = escape($d_string);
      case 'start_time':
        $mod_time = ($field == 'start_time') ? 0 : -1;
        if ($data['enable_periods'])
        {
          list( , $date) =  period_date_string($value, $data['area_id'], $mod_time);
        }
        else
        {
          $date = time_date_string($value);
        }
        $value = $date;
        break;
      case 'type':
        $value = get_type_vocab($value);
        break;
      case 'confirmation_enabled':
        // Translate the status field bit into meaningful text
        if ($data['confirmation_enabled'])
        {
          $value = ($data['tentative']) ? get_vocab('tentative') : get_vocab('confirmed');
        }
        else
        {
          $value = '';
        }
        break;
      case 'approval_enabled':
        // Translate the status field bit into meaningful text
        if ($data['approval_enabled'])
        {
          $value = ($data['awaiting_approval']) ? get_vocab('awaiting_approval') : get_vocab('approved');
        }
        else
        {
          $value = '';
        }
        break;
      case 'last_updated':
        $value = time_date_string($value);
        break;
      default:
        // Custom fields
        if (array_key_exists($field, $custom_fields))
        {
          // Output a yes/no if it's a boolean or integer <= 2 bytes (which we will
          // assume are intended to be booleans)
          if (($field_natures[$field] == 'boolean') ||
              (($field_natures[$field] == 'integer') && isset($field_lengths[$field]) && ($field_lengths[$field] <= 2)) )
          {
            $value = empty($value) ? get_vocab("no") : get_vocab("yes");
          }
          // Otherwise output a string
          elseif (isset($value))
          {
            // If the custom field is an associative array then we want
            // the value rather than the array key (provided the key is not
            // an empty string)
            if (isset($select_options["entry.$field"]) &&
                is_assoc($select_options["entry.$field"]) &&
                array_key_exists($value, $select_options["entry.$field"]) &&
                ($value !== ''))
            {
              $value = $select_options["entry.$field"][$value];
            }
          }
          else
          {
            $value = '';
          }
        }
        break;
    }
    $value = escape($value);

    // For HTML output we take special action for some fields
    if ($output_format == OUTPUT_HTML)
    {
      switch ($field)
      {
        case 'name':
          // Add a link to the entry and also a data-id value for the Bulk Delete JavaScript
          $value = "<a href=\"view_entry.php?id=" . $data['id'] . "\"" .
                   " data-id=\"" . $data['id'] . "\"" .
                   " title=\"$value\">$value</a>";
          break;
        case 'end_time':
          // Process the duration and then fall through to the end_time
          // Include the duration in a seconds as a title in an empty span so
          // that the column can be sorted and filtered properly
          $d_string = "<span title=\"$duration_seconds\"></span>$d_string";
        case 'start_time':
        case 'last_updated':
          // Include the numeric time as a title in an empty span so
          // that the column can be sorted and filtered properly
          $value = "<span title=\"${data[$field]}\"></span>$value";
          break;
        default:
          break;
      }
    }

    // Add the value to the array.   We don't bother with some fields if
    // they are going to be irrelevant
    if (($confirmation_somewhere || ($field != 'confirmation_enabled')) &&
        ($approval_somewhere || ($field != 'approval_enabled')) &&
        ((isset($booking_types) && (count($booking_types) > 1)) || ($field != 'type')))
    {
      $values[] = $value;
    }
    // Special action for the duration
    if ($field == 'end_time')
    {
      $values[] = $d_string;
    }

  }  // foreach

  $rows[] = $values;
}


function get_sumby_name_from_row($row)
{
  global $sumby;

  // Use brief description, created by or type as the name:
  switch( $sumby )
  {
    case 'd':
      $name = $row['name'];
      break;
    case 't':
      $name = get_type_vocab($row['type']);
      break;
    case 'c':
    default:
      $name = $row['create_by'];
      break;
  }
  return escape($name);
}


// Increments a two dimensional array by $increment
function increment_count(&$array, $index1, $index2, $increment)
{
  if (!isset($array[$index1]))
  {
    $array[$index1] = array();
  }
  if (!isset($array[$index1][$index2]))
  {
    $array[$index1][$index2] = 0;
  }
  $array[$index1][$index2] += $increment;
}

// Collect summary statistics on one entry.
// This also builds hash tables of all unique names and rooms. When sorted,
// these will become the column and row headers of the summary table.
function accumulate(&$row, &$count, &$hours, $report_start, $report_end,
                    &$room_hash, &$name_hash)
{
  global $output_format, $periods;

  $periods_per_day = count($periods);

  $row['enable_periods']; ////////////////////////
  // Use brief description, created by or type as the name:
  $name = get_sumby_name_from_row($row);
  // Area and room separated by break (if HTML):
  $room = escape($row['area_name']);
  $room .= ($output_format == OUTPUT_HTML) ? "<br>" : '/';
  $room .= escape($row['room_name']);
  // Accumulate the number of bookings for this room and name:
  increment_count($count, $room, $name, 1);
  // Accumulate hours/periods used, clipped to report range dates:
  if ($row['enable_periods'])
  {
    // We need to set the start and end of the report to the start and end of periods
    // on those days.   Otherwise if the report starts or ends in the middle of a multi-day
    // booking we'll get all those spurious minutes before noon or between the end of
    // the last period and midnight

    // Need to use the MRBS version of DateTime to get round a bug in modify()
    // in PHP before 5.3.6.  As we are in the MRBS namespace we will get the MRBS version.
    $startDate = new DateTime();
    $startDate->setTimestamp($report_start)->modify('12:00');

    $endDate = new DateTime();
    $endDate->setTimestamp($report_end)->modify('12:00');
    $endDate->sub(new \DateInterval('P1D'));  // Go back one day because the $report_end is at 00:00 the day after
    $endDate->add(new \DateInterval('PT' . $periods_per_day . 'M'));

    $increment = get_period_interval(max($row['start_time'], $startDate->getTimestamp()),
                                     min($row['end_time'], $endDate->getTimestamp()));
    $room_hash[$room] = MODE_PERIODS;
  }
  else
  {
    $increment = (min((int)$row['end_time'], $report_end) -
                  max((int)$row['start_time'], $report_start)) / SECONDS_PER_HOUR;
    $room_hash[$room] = MODE_TIMES;
  }
  increment_count($hours, $room, $name, $increment);
  $name_hash[$name] = 1;
}


// Format an entries value depending on whether it's destined for a CSV file or
// HTML output.   If it's HTML output then we enclose it in parentheses.
function entries_format($str)
{
  global $output_format;

  if ($output_format == OUTPUT_HTML)
  {
    return "($str)";
  }
  else
  {
    return $str;
  }
}


// Output the summary table (a "cross-tab report"). $count and $hours are
// 2-dimensional sparse arrays indexed by [area/room][name].
// $room_hash & $name_hash are arrays with indexes naming unique rooms and names.
function do_summary($count, $hours, &$room_hash, &$name_hash)
{
  global $output_format, $csv_col_sep;
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
  $row1_cells = array('');
  $row2_cells = array('');

  foreach ($room_hash as $room => $mode)
  {
    $col_count_total[$room] = 0;
    $col_hours_total[$room] = 0.0;
    $mode_text = ($mode == MODE_TIMES) ? get_vocab("mode_times") : get_vocab("mode_periods");
    $row1_cells[] = $room;
    $row1_cells[] = '';  // The cell before is really spanning two columns.   We'll sort it out with JavaScript
    $row2_cells[] = get_vocab("entries");
    $row2_cells[] = ($mode == MODE_PERIODS) ? get_vocab("periods") : get_vocab("hours");
  }

  // Add the total column(s) onto the end
  if ($times_somewhere)
  {
    $row1_cells[] = get_vocab("total") . " (" . get_vocab("mode_times") . ")";
    $row1_cells[] = '';
    $row2_cells[] = get_vocab("entries");
    $row2_cells[] = get_vocab("hours");
  }
  if ($periods_somewhere)
  {
    $row1_cells[] = get_vocab("total") . " (" . get_vocab("mode_periods") . ")";
    $row1_cells[] = '';
    $row2_cells[] = get_vocab("entries");
    $row2_cells[] = get_vocab("periods");
  }

  // Add the rows to the array of header rows, for output later
  $head_rows[] = $row1_cells;
  $head_rows[] = $row2_cells;


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
        $cells[] = ($output_format == OUTPUT_HTML) ? "&nbsp;" : '';
        $cells[] = ($output_format == OUTPUT_HTML) ? "&nbsp;" : '';
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
    $body_rows[] = $cells;
    foreach (array(MODE_TIMES, MODE_PERIODS) as $m)
    {
      $grand_count_total[$m] += $row_count_total[$m];
      $grand_hours_total[$m] += $row_hours_total[$m];
    }
  }


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
  $foot_rows[] = $cells;


  // OUTPUT THE TABLE
  // ----------------
  if ($output_format == OUTPUT_HTML)
  {
    // <tfoot> has to come before <tbody>
    output_head_rows($head_rows, $output_format);
    output_foot_rows($foot_rows, $output_format);
    output_body_rows($body_rows, $output_format);
  }
  else
  {
    output_head_rows($head_rows, $output_format);
    output_body_rows($body_rows, $output_format);
    output_foot_rows($foot_rows, $output_format);
  }
}


function get_match_condition($full_column_name, $match, &$sql_params)
{
  global $select_options, $field_natures, $field_lengths;

  $sql = '';

  // First simple case: no match required
  if (!isset($match) || $match === '')
  {
    return $sql;
  }

  // Get the table name and alias
  $aliases = array('area'  => 'A',
                   'entry' => 'E',
                   'room'  => 'R');

  list($table_alias, $column) = explode('.', $full_column_name, 2);
  $table = array_search($table_alias, $aliases);

  // Next simple case: for tables other than the entry table we are not going to
  // bother with complicated things such as custom fields or select_options
  if ($table != 'entry')
  {
    // syntax_caseless_contains() modifies the SQL params array too
    $sql .= " AND" . db()->syntax_caseless_contains("$full_column_name", $match, $sql_params);
    return $sql;
  }

  // More complicated cases:

  // (1) Associative arrays (we can't just test for the string, because the database
  // contains the keys, not the values.   So we have to go through each key testing
  // for a possible match)
  if (isset($select_options["$table.$column"]) &&
      is_assoc($select_options["$table.$column"]))
  {
    $sql .= " AND ";
    $or_array = array();
    foreach($select_options["$table.$column"] as $option_key => $option_value)
    {
      // We have to use strpos() rather than stripos() because we cannot
      // assume PHP5
      if (($option_key !== '') &&
          (strpos(utf8_strtolower($option_value), utf8_strtolower($match)) !== FALSE))
      {
        $or_array[] = "$full_column_name=?";
        $sql_params[] = $option_key;
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
  // (2) Booleans (or integers <= 2 bytes which we assume are intended to be booleans)
  elseif (($field_natures[$column] == 'boolean') ||
          (($field_natures[$column] == 'integer') && isset($field_lengths[$column]) && ($field_lengths[$column] <= 2)) )
  {
    if (($match != BOOLEAN_MATCH_BOTH) && ($match !== ''))
    {
      $sql .= " AND ($full_column_name ";
      $sql .= ($match == BOOLEAN_MATCH_TRUE) ? "IS NOT NULL AND $full_column_name != 0" : "IS NULL OR $full_column_name = 0";
      $sql .= ")";
    }
  }
  // (3) Integers
  elseif (($field_natures[$column] == 'integer') &&
           isset($field_lengths[$column]) &&
           ($field_lengths[$column] > 2))
  {
    $sql .= " AND $full_column_name=" . $match;
  }
  // (4) Strings
  else
  {
    // syntax_caseless_contains() modifies the SQL params array too
    $sql .= " AND" . db()->syntax_caseless_contains("$full_column_name", $match, $sql_params);
  }

  return $sql;
}

// Work out whether we are running from the command line
$cli_mode = is_cli();

if ($cli_mode)
{
  // Need to set include path if we're running in CLI mode
  // (because otherwise PHP looks in the current directory rather
  // than the directory from which the script was called)
  ini_set("include_path", dirname(url_path()));
}

$default_from_time = mktime(0, 0, 0, $month, $day, $year);
$default_to_time = mktime(0, 0, 0, $month, $day + $default_report_days, $year);
$default_from_date = date('Y-m-d', $default_from_time);
$default_to_date = date('Y-m-d', $default_to_time);

// Get non-standard form variables
$from_date = get_form_var('from_date', 'string', $default_from_date);
$to_date = get_form_var('to_date', 'string', $default_to_date);
$creatormatch = get_form_var('creatormatch', 'string');
$areamatch = get_form_var('areamatch', 'string');
$roommatch = get_form_var('roommatch', 'string');
$namematch = get_form_var('namematch', 'string');
$descrmatch = get_form_var('descrmatch', 'string');
$output = get_form_var('output', 'int', REPORT);
$output_format = get_form_var('output_format', 'int', (($cli_mode) ? OUTPUT_CSV : OUTPUT_HTML));
$typematch = get_form_var('typematch', 'array');
$sortby = get_form_var('sortby', 'string', 'r');  // $sortby: r=room, s=start date/time.
$sumby = get_form_var('sumby', 'string', 'd');  // $sumby: d=by brief description, c=by creator, t=by type.
$match_approved = get_form_var('match_approved', 'int', BOOLEAN_MATCH_BOTH);
$match_confirmed = get_form_var('match_confirmed', 'int', BOOLEAN_MATCH_BOTH);
$match_private = get_form_var('match_private', 'int', BOOLEAN_MATCH_BOTH);
$phase = get_form_var('phase', 'int', 1);
$datatable = get_form_var('datatable', 'int');  // Will only be set if we're using DataTables

list($from_year, $from_month, $from_day) = split_iso_date($from_date);
list($to_year, $to_month, $to_day) = split_iso_date($to_date);

$is_ajax = is_ajax();

if ($cli_mode)
{
  // If we're running in CLI mode we're passing the parameters in from the command line
  // not the form and we want to go straight to Phase 2 (producing the report)
  $phase = 2;
}
else
{
  // Check the CSRF token if this is Phase 2
  if ($phase == 2)
  {
    Form::checkToken(true);
  }

  // Check the user is authorised for this page
  checkAuthorised(this_page());
  $user = getUserName();
  $user_level = authGetUserLevel($user);
}

// Set up for Ajax.   We need to know whether we're capable of dealing with Ajax
// requests, which will only be if the browser is using DataTables.  We also need
// to initialise the JSON data array.
$ajax_capable = $datatable;

if ($is_ajax)
{
  $json_data['aaData'] = array();
}

$private_somewhere = some_area('private_enabled') || some_area('private_mandatory');
$approval_somewhere = some_area('approval_enabled');
$confirmation_somewhere = some_area('confirmation_enabled');
$times_somewhere = (db()->query1("SELECT COUNT(*) FROM " . _tbl('area') . " WHERE enable_periods=0") > 0);
$periods_somewhere = (db()->query1("SELECT COUNT(*) FROM " . _tbl('area') . " WHERE enable_periods!=0") > 0);


// Build the report search field order
$report_presentation_fields = array('output', 'output_format', 'sortby', 'sumby');

foreach ($report_presentation_fields as $field)
{
  if (!in_array($field, $report_presentation_field_order))
  {
    $report_presentation_field_order[] = $field;
  }
}

// Build the report search field order
$report_search_fields = array('report_start', 'report_end',
                              'areamatch', 'roommatch',
                              'typematch', 'namematch', 'descrmatch', 'creatormatch',
                              'match_private', 'match_confirmed', 'match_approved');

foreach ($report_search_fields as $field)
{
  if (!in_array($field, $report_search_field_order))
  {
    $report_search_field_order[] = $field;
  }
}

// Get information about custom fields
$fields = db()->field_info(_tbl('entry'));
$custom_fields = array();
$field_natures = array();
$field_lengths = array();
foreach ($fields as $field)
{
  $key = $field['name'];
  if (!in_array($key, $standard_fields['entry']))
  {
    $custom_fields[$key] = '';
    // Add the field to the end of the report search field order, if it's
    // not already present
    if (!in_array($key, $report_search_field_order))
    {
      $report_search_field_order[] = $key;
    }
  }
  $field_natures[$key] = $field['nature'];
  $field_lengths[$key] = $field['length'];
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

// Set the field order list
$field_order_list = array('name', 'area_name', 'room_name', 'start_time', 'end_time',
                          'description', 'type', 'create_by', 'confirmation_enabled',
                          'approval_enabled');
foreach ($custom_fields as $key => $value)
{
  $field_order_list[] = $key;
}
$field_order_list[] = 'last_updated';



// PHASE 2:  SQL QUERY.  We do the SQL query now to see if there's anything there
if ($phase == 2)
{
  // Start and end times are also used to clip the times for summary info.
  $report_start = mktime(0, 0, 0, $from_month+0, $from_day+0, $from_year+0);
  $report_end = mktime(0, 0, 0, $to_month+0, $to_day+1, $to_year+0);

  // Construct the SQL query
  $sql_params = array();
  $sql = "SELECT E.*, "
       .  db()->syntax_timestamp_to_unix("E.timestamp") . " AS last_updated, "
       . "A.area_name, R.room_name, R.area_id, "
       . "A.approval_enabled, A.confirmation_enabled, A.enable_periods";
  if ($output_format == OUTPUT_ICAL)
  {
    // If we're producing an iCalendar then we'll also need the repeat
    // information in order to construct the recurrence rule
    $sql .= ", T.rep_type, T.end_date, T.rep_opt, T.rep_interval, T.month_absolute, T.month_relative";
  }
  $sql .= " FROM " . _tbl('area') . " A, " . _tbl('room') . " R, " . _tbl('entry') . " E";
  if ($output_format == OUTPUT_ICAL)
  {
    // We do a LEFT JOIN because we still want the single entries, ie the ones
    // that won't have a match in the repeat table
    $sql .= " LEFT JOIN " . _tbl('repeat') . " T ON E.repeat_id=T.id";
  }
  $sql .= " WHERE E.room_id=R.id AND R.area_id=A.id"
        . " AND E.start_time < ? AND E.end_time > ?";
  $sql_params[] = $report_end;
  $sql_params[] = $report_start;
  if ($output_format == OUTPUT_ICAL)
  {
    // We can't export periods in an iCalendar yet
    $sql .= " AND A.enable_periods=0";
  }

  // Get the match conditions for the simple cases
  $match_columns = array('A.area_name'    => $areamatch,
                         'R.room_name'    => $roommatch,
                         'E.name'         => $namematch,
                         'E.description'  => $descrmatch,
                         'E.create_by'    => $creatormatch);

  foreach ($match_columns as $column => $match)
  {
    $sql .= get_match_condition($column, $match, $sql_params);
  }

  // Then do the special cases
  if (!empty($typematch))
  {
    $sql .= " AND ";
    $or_array = array();
    foreach ( $typematch as $type )
    {
      // syntax_casesensitive_equals() modifies our SQL params array for us
      $or_array[] = db()->syntax_casesensitive_equals('E.type', $type, $sql_params);
    }
    $sql .= "(". implode(" OR ", $or_array ) .")";
  }

  // (In the next three cases, you will get the empty string if that part
  // of the form was not displayed - which means that you need all bookings)
  // Note that although you can say eg "status&STATUS_PRIVATE" in MySQL, you get
  // an error in PostgreSQL as the expression is of the wrong type.

  // Match the privacy status
  if (($match_private != BOOLEAN_MATCH_BOTH) && ($match_private !== ''))
  {
    $sql .= " AND ";
    $sql .= "(E.status&" . STATUS_PRIVATE;
    $sql .= ($match_private) ? "!=0)" : "=0)";  // Note that private works the other way round to the next two
  }

  // Match the confirmation status
  if (($match_confirmed != BOOLEAN_MATCH_BOTH) && ($match_confirmed !== ''))
  {
    $sql .= " AND ";
    $sql .= "(E.status&" . STATUS_TENTATIVE;
    $sql .= ($match_confirmed) ? "=0)" : "!=0)";
  }
  // Match the approval status
  if (($match_approved != BOOLEAN_MATCH_BOTH) && ($match_approved !== ''))
  {
    $sql .= " AND ";
    $sql .= "(E.status&" . STATUS_AWAITING_APPROVAL;
    $sql .= ($match_approved) ? "=0)" : "!=0)";
  }

  // Then do the custom fields
  foreach ($custom_fields as $key => $value)
  {
    $var = "match_$key";
    $sql .= get_match_condition("E.$key", $$var, $sql_params);
  }

  // We only want the bookings for rooms that are visible
  $invisible_room_ids = get_invisible_room_ids();
  if (count($invisible_room_ids) > 0)
  {
    $sql .= " AND (E.room_id NOT IN (" . implode(',', $invisible_room_ids) . "))";
  }

  // If we're not an admin (they are allowed to see everything), then we need
  // to make sure we respect the privacy settings.  (We rely on the privacy fields
  // in the area table being not NULL.   If they are by some chance NULL, then no
  // entries will be found, which is at least safe from the privacy viewpoint)
  if (!$cli_mode && !is_book_admin())
  {
    if (isset($user))
    {
      // if the user is logged in they can see:
      //   - all bookings, if private_override is set to 'public'
      //   - their own bookings, and others' public bookings if private_override is set to 'none'
      //   - just their own bookings, if private_override is set to 'private'
      $sql .= " AND ((A.private_override='public') OR
                     ((A.private_override='none') AND ((E.status&" . STATUS_PRIVATE . "=0) OR (E.create_by = ?))) OR
                     ((A.private_override='private') AND (E.create_by = ?)))";
      $sql_params[] = $user;
      $sql_params[] = $user;
    }
    else
    {
      // if the user is not logged in they can see:
      //   - all bookings, if private_override is set to 'public'
      //   - public bookings if private_override is set to 'none'
      $sql .= " AND ((A.private_override='public') OR
                     ((A.private_override='none') AND (E.status&" . STATUS_PRIVATE . "=0)))";
    }
  }

  if ($output_format == OUTPUT_ICAL)
  {
    // If we're producing an iCalendar then we'll want the entries ordered by
    // repeat_id and then recurrence_id
    $sql .= " ORDER BY repeat_id, ical_recur_id";
  }
  elseif ($sortby == "r")
  {
    // Order by Area, Room, Start date/time
    $sql .= " ORDER BY A.sort_key, R.sort_key, start_time";
  }
  else
  {
    // Order by Start date/time, Area, Room
    $sql .= " ORDER BY start_time, A.sort_key, R.sort_key";
  }

  // echo "<p>DEBUG: SQL: <tt> $sql </tt></p>\n";

  $res = db()->query($sql, $sql_params);
  $nmatch = $res->count();
}

$combination_not_supported = ($output == SUMMARY) && ($output_format == OUTPUT_ICAL);

$output_form = (($output_format == OUTPUT_HTML) && !$is_ajax &&!$cli_mode) ||
               $combination_not_supported;


// print the page header
if ($is_ajax)
{
  // don't do anything if this is an Ajax request:  we only want the data
}
elseif ($output_form)
{
  print_header($view, $view_all, $year, $month, $day, $area, isset($room) ? $room : null);
}
else
{
  $filename = ($output == REPORT) ? $report_filename : $summary_filename;
  switch ($output_format)
  {
    case OUTPUT_CSV:
      $filename .= '.csv';
      $content_type = "text/csv; charset=" . get_csv_charset();
      break;
    default:
      require_once "functions_ical.inc";
      $filename .= '.ics';
      $content_type = "application/ics; charset=" . get_charset() . "; name=\"$filename\"";
      break;
  }
  if (!$cli_mode)
  {
    http_headers(array("Content-Type: $content_type",
                       "Content-Disposition: attachment; filename=\"$filename\""));
  }

  if (($output_format == OUTPUT_CSV) && $csv_bom)
  {
    echo get_bom(get_csv_charset());
  }
}


// Upper part: The form.
if ($output_form)
{
  echo "<div class=\"screenonly\">\n";

  $form = new Form();

  // Search variables
  $search_var_keys = array('from_day', 'from_month', 'from_year',
                           'to_day', 'to_month', 'to_year',
                           'areamatch', 'roommatch',
                           'typematch', 'namematch', 'descrmatch', 'creatormatch',
                           'match_private', 'match_confirmed', 'match_approved',
                           'custom_fields');
  $search_vars = array();
  foreach($search_var_keys as $var)
  {
    $search_vars[$var] = $$var;
  }

  // Presentation variables
  $presentation_var_keys = array('output', 'output_format',
                                 'sortby', 'sumby');
  $presentation_vars = array();
  foreach($presentation_var_keys as $var)
  {
    $presentation_vars[$var] = $$var;
  }

  $attributes = array('id'     => 'report_form',
                      'class'  => 'standard',
                      'action' => multisite(this_page()),
                      'method' => 'post');

  $form->setAttributes($attributes)
       ->addHiddenInput('phase', '2');

  $outer_fieldset = new ElementFieldset();
  $outer_fieldset->addLegend(get_vocab('report_on'))
                 ->addElement(get_fieldset_search_criteria($search_vars))
                 ->addElement(get_fieldset_presentation_options($presentation_vars))
                 ->addElement(get_fieldset_submit_buttons());

  $form->addElement($outer_fieldset);

  $form->render();

  echo "</div>\n";
}

// PHASE 2:  Output the results, if called with parameters:
if ($phase == 2)
{
  if (($nmatch == 0) && !$cli_mode && ($output_format == OUTPUT_HTML))
  {
    if ($is_ajax)
    {
      http_headers(array("Content-Type: application/json"));
      echo json_encode($json_data);
    }
    else
    {
      echo "<p class=\"report_entries\">" . get_vocab("nothing_found") . "</p>\n";
    }
    unset($res);
  }
  elseif ($combination_not_supported)
  {
    echo "<p>" . get_vocab("combination_not_supported") . "</p>\n";
    unset($res);
  }
  else
  {
    if ($output_format == OUTPUT_ICAL)
    {
      // We set $keep_private to FALSE here because we excluded all private
      // events in the SQL query
      export_icalendar($res, FALSE, $report_end);
      exit;
    }

    if (($output_format == OUTPUT_HTML) && !$is_ajax)
    {
      echo "<p class=\"report_entries\"><span id=\"n_entries\">" . $nmatch . "</span> "
      . ($nmatch == 1 ? get_vocab("entry_found") : get_vocab("entries_found"))
      .  "</p>\n";
    }

    // Report
    if ($output == REPORT)
    {
      open_report();
      report_header();
      $body_rows = array();
      while (false !== ($row = $res->next_row_keyed()))
      {
        unpack_status($row);
        report_row($body_rows, $row);
      }
      output_body_rows($body_rows, $output_format);
      close_report();
    }
    // Summary
    else
    {
      open_summary();
      if ($nmatch > 0)
      {
        while (false !== ($row = $res->next_row_keyed()))
        {
          unpack_status($row);
          accumulate($row, $count, $hours,
                     $report_start, $report_end,
                     $room_hash, $name_hash);
        }
        do_summary($count, $hours, $room_hash, $name_hash);
      }
      else
      {
        // Excel doesn't seem to like an empty file with just a BOM, so give
        // it an empty row as well to keep it happy
        $values = array();
        output_row($values, $output_format);
      }
      close_summary();
    }
  }
}

if ($cli_mode)
{
  exit(0);
}

if ($output_form)
{
  print_footer();
}
