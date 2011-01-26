<?php

// $Id$

// Returns a string containg a single line of data details, or if $as_html is set
// a table row of details.   The first column is the $label and the second column
// the $value.   $class is an optional class name which can be applied to the 
// second column.
function create_details_row($label, $value, $as_html=FALSE, $class='')
{
  $result = '';
  if ($as_html)
  {
    $result .= "<tr>\n";
    $result .= "<td>$label:</td>\n";
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


// Returns a string containg a set of details for $data consisting of a label/value
// pair for each data element in the array $data.   If $as_html is TRUE then the string
// is the HTML for a table body, ie looks like "<tbody> ... </tbody>".
//    $keep_private   boolean   if TRUE then any private fields will be given the class 'private';
//                              note that $data must already have had values substituted
//                              for private fields
//    $room_disabled  boolean   if TRUE then a note will be added that the room is disabled
function create_details_body($data, $as_html=FALSE, $keep_private=FALSE, $room_disabled=FALSE)
{
  global $enable_periods, $confirmation_enabled, $approval_enabled;
  global $is_private_field, $standard_fields, $typel;
  global $strftime_format;
  
  // Get the duration if we haven't got it already
  if (!isset($data['duration']))
  {
    // We will translate the units later
    $d = get_duration($data['start_time'], $data['end_time'], FALSE);
    $data['duration'] = $d['duration'];
    $data['dur_units'] = $d['dur_units'];
  }
  
  // Set a rep_type if it hasn't been
  if (!isset($data['rep_type']))
  {
    $data['rep_type'] = REP_NONE;
  }
  
  // Go throuh each of the columns and for each of them that can be made private
  // substitute the private text if the user is not allowed to see the data
  $private_text = "[" . get_vocab("private") . "]";
  
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
        $data[$key] = ($keep_private && $is_private_field["entry.$key"]) ? $private_text : $data[$key];
        break;
      
      default:
        if (!in_array($key, $standard_fields['entry']))
        {
          $data[$key] = ($keep_private && $is_private_field["entry.$key"]) ? $private_text : $data[$key];
        }
        break;
    }
  }

  
  $tbody = '';
  $tbody .= ($as_html) ? "<tbody>\n" : "";
  // Description
  $class = ($keep_private & $is_private_field['entry.description']) ? "private" : "";
  $tbody .= create_details_row(get_vocab("description"), $data['description'], $as_html, $class);
  // Confirmation status
  if ($confirmation_enabled)
  {
    $value = ($data['status'] & STATUS_TENTATIVE) ? get_vocab("tentative") : get_vocab("confirmed");
    $tbody .= create_details_row(get_vocab("confirmation_status"), $value, $as_html);
  }
  // Approval status
  if ($approval_enabled)
  {
    $value = ($data['status'] & STATUS_AWAITING_APPROVAL) ? get_vocab("awaiting_approval") : get_vocab("approved");
    $tbody .= create_details_row(get_vocab("approval_status"), $value, $as_html);
  }
  // Room
  $value = $data['area_name'] . " - " . $data['room_name'];
  if ($room_disabled)
  {
    $value .= ($as_html) ? "<span class=\"note\">" : "";
    $value .= " (" . get_vocab("disabled") . ")";
    $value .= ($as_html) ? "</span>" : "";
  }
  $tbody .= create_details_row(get_vocab("room"), $value, $as_html);
  // Start date
  if ($enable_periods)
  {
    list($start_period, $start_date) =  period_date_string($data['start_time']);
  }
  else
  {
    $start_date = time_date_string($data['start_time']);
  }
  $tbody .= create_details_row(get_vocab("start_date"), $start_date, $as_html);
  // Duration
  $tbody .= create_details_row(get_vocab("duration"), $data['duration'] . " " . get_vocab($data['dur_units']), $as_html);
  // End date
  if ($enable_periods)
  {
    list( , $end_date) =  period_date_string($data['end_time'], -1);
  }
  else
  {
    $end_date = time_date_string($data['end_time']);
  }
  $tbody .= create_details_row(get_vocab("end_date"), $end_date, $as_html);
  // Type
  $value = (empty($typel[$data['type']])) ? "?${data['type']}?" : $typel[$data['type']];
  $tbody .= create_details_row(get_vocab("type"), $value, $as_html);
  // Created by
  $class = ($keep_private && $is_private_field['entry.create_by']) ? "private" : "";
  $tbody .= create_details_row(get_vocab("createdby"), $data['create_by'], $as_html, $class);
  // Last updated
  $tbody .= create_details_row(get_vocab("lastupdate"), time_date_string($data['last_updated']), $as_html);
  // The custom fields
  $fields = sql_field_info($tbl_entry);
  foreach ($fields as $field)
  {
    $key = $field['name'];
    if (!in_array($key, $standard_fields['entry']))
    {
      $label = get_loc_field_name($tbl_entry, $key);
      // Output a yes/no if it's a boolean or integer <= 2 bytes (which we will
      // assume are intended to be booleans)
      if (($field['nature'] == 'boolean') || 
          (($field['nature'] == 'integer') && isset($field['length']) && ($field['length'] <= 2)) )
      {
        if ($keep_private && $is_private_field["entry.$key"])
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
        $value = (isset($data[$key])) ? $data[$key] : "&nbsp;"; 
      }
      $class = ($keep_private && $is_private_field["entry.$key"]) ? "private" : "";
      $tbody .= create_details_row($label, $value, $as_html, $class);
    }
  }
  // Repeat type
  $tbody .= create_details_row(get_vocab("rep_type"), get_vocab("rep_type_" . $data['rep_type']), $as_html);
  // Repeat details
  if($data['rep_type'] != REP_NONE)
  {
    if (($data['rep_type'] == REP_WEEKLY) || ($data['rep_type'] == REP_N_WEEKLY))
    {
      if ($data['rep_type'] == REP_N_WEEKLY)
      {
        // Repeat number of weeks
        $tbody .= create_details_row(get_vocab("rep_num_weeks")." ".get_vocab("rep_for_nweekly"), $data['rep_num_weeks'], $as_html);
      }
      // Repeat days
      $tbody .= create_details_row(get_vocab("rep_rep_day"), get_rep_day_list($data['rep_opt']), $as_html);
    }
    // Repeat end date
    $tbody .= create_details_row(get_vocab("rep_end_date"),
                                 utf8_strftime($strftime_format['date'],
                                 $data['end_date']),
                                 $as_html);
  }
  $tbody .= ($as_html) ? "</tbody>\n" : "";
  
  return $tbody;
}

?>