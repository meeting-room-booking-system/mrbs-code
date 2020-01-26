<?php
namespace MRBS;

// Returns a string containing a single line of data details, or if $as_html is set
// a table row of details.   The first column is the $label and the second column
// the $value.   $class is an optional class name which can be applied to the
// second column.
function create_details_row($label, $value, $as_html=false, $class='')
{
  $result = '';
  if ($as_html)
  {
    $result .= "<tr>\n";
    $result .= "<td>$label</td>\n";
    $result .= "<td" .
               ((!empty($class)) ? " class=\"$class\"" : "") .
               ">" . mrbs_nl2br(htmlspecialchars($value)) . "</td>\n";
    $result .= "</tr>\n";
  }
  else
  {
    // Some of the vocab strings contain &nbsp;
    $result .= str_replace('&nbsp;', ' ', $label) . ": $value\n";
  }
  return $result;
}


// Returns a string containing a set of details for $data consisting of a label/value
// pair for each data element in the array $data.   If $as_html is true then the string
// is the HTML for a table body, ie looks like "<tbody> ... </tbody>".
//    $keep_private   boolean   if true then any private fields will be given the class 'private';
//                              note that $data must already have had values substituted
//                              for private fields
//    $room_disabled  boolean   if true then a note will be added that the room is disabled
function create_details_body($data, $as_html=false, $keep_private=false, $room_disabled=false)
{
  global $enable_periods, $confirmation_enabled, $approval_enabled;
  global $is_private_field, $standard_fields;
  global $strftime_format;
  global $tbl_entry;
  global $select_options, $booking_types;
  global $edit_entry_field_order;

  // Get the duration if we haven't got it already
  if (!isset($data['duration']))
  {
    // We will translate the units later
    $d = get_duration($data['start_time'], $data['end_time'], $enable_periods, $data['area_id'], false);
    $data['duration'] = $d['duration'];
    $data['dur_units'] = $d['dur_units'];
  }

  // Set a rep_type if it hasn't been
  if (!isset($data['rep_type']))
  {
    $data['rep_type'] = REP_NONE;
  }

  // Go through each of the columns and for each of them that can be made private
  // substitute the private text if the user is not allowed to see the data
  $private_text = "[" . get_vocab("unavailable") . "]";

  foreach ($data as $key => $value)
  {
    // We could just test each column against $is_private_field["entry.$key"]
    // but restricting the test to the columns below guards against the possibility
    // that somebody has accidentally configured a 'system' field to be private
    switch ($key)
    {
      case 'name':
      case 'description':
      case 'create_by':
      case 'modified_by':
      case 'room_name':
      case 'area_name':
      case 'type':
      case 'room_id':
      case 'entry_info_time':
      case 'entry_info_user':
      case 'entry_info_text':
      case 'repeat_info_time':
      case 'repeat_info_user':
      case 'repeat_info_text':
        $data[$key] = ($keep_private && isset($is_private_field["entry.$key"]) && $is_private_field["entry.$key"]) ? $private_text : $data[$key];
        break;

      default:
        if (!in_array($key, $standard_fields['entry']))
        {
          $data[$key] = ($keep_private && isset($is_private_field["entry.$key"]) && $is_private_field["entry.$key"]) ? $private_text : $data[$key];
        }
        break;
    }
  }

  $fields = db()->field_info($tbl_entry);

  // Get the order in which to display fields
  $field_order = (isset($edit_entry_field_order)) ? $edit_entry_field_order : array();
  foreach ($fields as $field)
  {
    if (!in_array($field['name'], $field_order))
    {
      $field_order[] = $field['name'];
    }
  }

  // Add in the two special status fields
  foreach (array('approval_status', 'confirmation_status') as $key)
  {
    if (!in_array($key, $field_order))
    {
      array_push($field_order, $key);
    }
  }

  $tbody = '';
  $tbody .= ($as_html) ? "<tbody>\n" : "";

  foreach ($field_order as $key)
  {
    switch ($key)
    {
      // Ignore these
      case 'name':  // in the heading
      case 'id':
      case 'entry_type':
      case 'reminded':
      case 'repeat_id':
      case 'status':
      case 'info_time':
      case 'info_user':
      case 'info_text':
      case 'ical_uid':
      case 'ical_sequence':
      case 'ical_recur_id':
        break;

      case 'description':
        // Description
        $class = ($keep_private & !empty($is_private_field['entry.description'])) ? "private" : "";
        $tbody .= create_details_row(get_vocab("description"), $data['description'], $as_html, $class);
        break;

      case 'confirmation_status':
        // Confirmation status
        if ($confirmation_enabled)
        {
          $value = ($data['tentative']) ? get_vocab("tentative") : get_vocab("confirmed");
          $tbody .= create_details_row(get_vocab("confirmation_status"), $value, $as_html);
        }
        break;

      case 'approval_status':
        // Approval status
        if ($approval_enabled)
        {
          $value = ($data['awaiting_approval']) ? get_vocab("awaiting_approval") : get_vocab("approved");
          $tbody .= create_details_row(get_vocab("approval_status"), $value, $as_html);
        }
        break;

      case 'room_id':
        // Room
        $value = $data['area_name'] . " - " . $data['room_name'];
        if ($room_disabled)
        {
          $value .= " (" . get_vocab("disabled") . ")";
        }
        $tbody .= create_details_row(get_vocab("room"), $value, $as_html);
        break;

      case 'start_time':
        // Start date
        if ($enable_periods)
        {
          list($start_period, $start_date) =  period_date_string($data['start_time'], $data['area_id']);
        }
        else
        {
          $start_date = time_date_string($data['start_time']);
        }
        $tbody .= create_details_row(get_vocab("start_date"), $start_date, $as_html);
        // Duration
        $tbody .= create_details_row(get_vocab("duration"), $data['duration'] . " " . get_vocab($data['dur_units']), $as_html);
        break;

      case 'end_time':
        // End date
        if ($enable_periods)
        {
          list( , $end_date) =  period_date_string($data['end_time'], $data['area_id'], -1);
        }
        else
        {
          $end_date = time_date_string($data['end_time']);
        }
        $tbody .= create_details_row(get_vocab("end_date"), $end_date, $as_html);
        break;

      case 'type':
        // Type
        if (isset($booking_types) && (count($booking_types) > 1))
        {
          $type = get_type_vocab($data['type']);
          $value = (empty($type)) ? "?${data['type']}?" : $type;
          $tbody .= create_details_row(get_vocab("type"), $value, $as_html);
        }
        break;

      case 'create_by':
        // Created by
        $class = ($keep_private && !empty($is_private_field['entry.create_by'])) ? "private" : "";
        $tbody .= create_details_row(get_vocab("createdby"), $data['create_by'], $as_html, $class);
        break;

      case 'modified_by':
        // Modified by
        $class = ($keep_private && !empty($is_private_field['entry.modified_by'])) ? "private" : "";
        $tbody .= create_details_row(get_vocab("modifiedby"), $data['modified_by'], $as_html, $class);
        break;

      case 'timestamp':
        // Last updated
        $tbody .= create_details_row(get_vocab("lastupdate"), time_date_string($data['last_updated']), $as_html);
        break;

      default:

        // The custom fields
        foreach ($fields as $field)
        {
          // Get this field
          if ($field['name'] == $key)
          {
            break;
          }
        }

        // The field doesn't exist.
        if ($field['name'] != $key)
        {
          break;
        }

        $label = get_loc_field_name($tbl_entry, $key);
        // Output a yes/no if it's a boolean or integer <= 2 bytes (which we will
        // assume are intended to be booleans)
        if (($field['nature'] == 'boolean') ||
            (($field['nature'] == 'integer') && isset($field['length']) && ($field['length'] <= 2)) )
        {
          if ($keep_private && isset($is_private_field["entry.$key"]) && $is_private_field["entry.$key"])
          {
            $value = $data[$key];  // Will have been set previously
          }
          else
          {
            $value = empty($data[$key]) ? get_vocab("no") : get_vocab("yes");
          }
        }
        // Otherwise output a string
        else
        {
          if (isset($data[$key]))
          {
            // If the custom field is an associative array then we want
            // the value rather than the array key
            if (isset($select_options["entry.$key"]) &&
                is_assoc($select_options["entry.$key"]) &&
                array_key_exists($data[$key], $select_options["entry.$key"]))
            {
              $value = $select_options["entry.$key"][$data[$key]];
            }
            else
            {
              $value = $data[$key];
            }
          }
          else
          {
            $value = "";
          }
        }
        $class = ($keep_private && isset($is_private_field["entry.$key"]) && $is_private_field["entry.$key"]) ? "private" : "";
        $tbody .= create_details_row($label, $value, $as_html, $class);
        break;
    }  // switch
  }  // foreach


  // Repeat type
  $tbody .= create_details_row(get_vocab("rep_type"), get_vocab("rep_type_" . $data['rep_type']), $as_html);
  // Repeat details
  if($data['rep_type'] != REP_NONE)
  {
    switch ($data['rep_type'])
    {
      case REP_WEEKLY:
        // Repeat days
        $tbody .= create_details_row(get_vocab("rep_rep_day"), get_rep_day_list($data['rep_opt']), $as_html);
        break;
      case REP_MONTHLY:
        if (isset($data['month_absolute']))
        {
          $value = $data['month_absolute'];
        }
        elseif (isset($data['month_relative']))
        {
          // Note: this does not internationalise very well and could do with revisiting.
          // It follows the select box order in edit_entry, which is the more difficult one
          // to sort out.  It assumes all languages have the same order as English
          // eg "the second Wednesday" which is probably not true.
          list($ord, $dow) = byday_split($data['month_relative']);
          $value = get_vocab("ord_" . $ord) . " " . day_name(RFC_5545_day_to_ord($dow));
        }
        else
        {
          trigger_error("Unknown monthly repeat type, E_USER_NOTICE");
        }
        $tbody .= create_details_row(get_vocab("repeat_on"), $value, $as_html);
        break;
      default:
        break;
    }

    // Repeat interval
    $value = $data['rep_interval'] . ' ' . get_rep_interval_units($data['rep_type'], $data['rep_interval']);
    $tbody .= create_details_row(get_vocab("rep_interval"), $value, $as_html);

    // Repeat end date
    $tbody .= create_details_row(get_vocab("rep_end_date"),
                                 utf8_strftime($strftime_format['date'],
                                 $data['end_date']),
                                 $as_html);
  }

  $tbody .= ($as_html) ? "</tbody>\n" : "";

  return $tbody;
}

