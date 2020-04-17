<?php
namespace MRBS;

use MRBS\Form\Form;
use MRBS\Form\ElementFieldset;
use MRBS\Form\ElementInputSubmit;
use MRBS\Form\FieldInputDate;
use MRBS\Form\FieldInputSearch;
use MRBS\Form\FieldInputSubmit;

require "defaultincludes.inc";


function get_search_nav_button(array $hidden_inputs, $value, $disabled=false)
{
  $html = '';

  $form = new Form();
  $form->setAttributes(array('action' => multi_site(this_page()),
                             'method' => 'post'));
  $form->addHiddenInputs($hidden_inputs);
  $submit = new ElementInputSubmit();
  $submit->setAttributes(array('value'    => $value,
                               'disabled' => $disabled));
  $form->addElement($submit);
  $html .= $form->toHTML();

  return $html;
}


function generate_search_nav_html($search_pos, $total, $num_records, $search_str)
{
  global $from_date;
  global $search;

  $html = '';

  $has_prev = $search_pos > 0;
  $has_next = $search_pos < ($total-$search["count"]);

  if ($has_prev || $has_next)
  {
    $html .= "<div id=\"record_numbers\">\n";
    $html .= get_vocab("records") . ($search_pos+1) . get_vocab("through") . ($search_pos+$num_records) . get_vocab("of") . $total;
    $html .= "</div>\n";

    $html .= "<div id=\"record_nav\">\n";

    // display "Previous" and "Next" buttons
    $hidden_inputs = array('search_str' => $search_str,
                           'total'      => $total,
                           'from_date'  => $from_date);

    $hidden_inputs['search_pos'] = max(0, $search_pos - $search['count']);
    $html .= get_search_nav_button($hidden_inputs , get_vocab('previous'), !$has_prev);

    $hidden_inputs['search_pos'] = max(0, $search_pos + $search['count']);
    $html .= get_search_nav_button($hidden_inputs , get_vocab('next'), !$has_next);

    $html .= "</div>\n";
  }

  return $html;
}


function output_row($row, $returl)
{
  global $is_ajax, $json_data, $view;

  $vars = array('id'     => $row['entry_id'],
                'returl' => $returl);

  $query = http_build_query($vars, '', '&');

  $values = array();
  // booking name
  $html_name = htmlspecialchars($row['name']);
  $values[] = "<a title=\"$html_name\" href=\"view_entry.php?" . htmlspecialchars($query) . "\">$html_name</a>";
  // created by
  $values[] = htmlspecialchars(get_compound_name($row['create_by']));
  // start time and link to day view
  $date = getdate($row['start_time']);

  $vars = array('view'  => $view,
                'year'  => $date['year'],
                'month' => $date['mon'],
                'day'   => $date['mday'],
                'area'  => $row['area_id'],
                'room'  => $row['room_id']);

  $query = http_build_query($vars, '', '&');

  $link = '<a href="index.php?' . htmlspecialchars($query) . '">';

  if(empty($row['enable_periods']))
  {
    $link_str = time_date_string($row['start_time']);
  }
  else
  {
    list(,$link_str) = period_date_string($row['start_time'], $row['area_id']);
  }
  $link .= htmlspecialchars($link_str) ."</a>";
  //    add a span with the numeric start time in the title for sorting
  $values[] = "<span title=\"" . $row['start_time'] . "\"></span>" . $link;
  // description
  $values[] = htmlspecialchars($row['description']);

  if ($is_ajax)
  {
    $json_data['aaData'][] = $values;
  }
  else
  {
    echo "<tr>\n<td>\n";
    echo implode("</td>\n<td>", $values);
    echo "</td>\n</tr>\n";
  }
}

$is_ajax = is_ajax();

// Get non-standard form variables
$search_str = get_form_var('search_str', 'string');
$search_pos = get_form_var('search_pos', 'int');
$total = get_form_var('total', 'int');
$datatable = get_form_var('datatable', 'int');  // Will only be set if we're using DataTables
// Get the start day/month/year and make them the current day/month/year
$from_date = get_form_var('from_date', 'string');

if (isset($from_date))
{
  list($year, $month, $day) = split_iso_date($from_date);
}

// If we haven't been given a sensible date then use today's
if (!isset($day) || !isset($month) || !isset($year) || !checkdate($month, $day, $year))
{
  $day   = date("d");
  $month = date("m");
  $year  = date("Y");
}

// Reconstruct the from_date using the, possibly new, values of year/month/day
$from_date = format_iso_date($year, $month, $day);

// If we're going to be doing something then check the CSRF token
if (isset($search_str) && ($search_str !== ''))
{
  Form::checkToken(true);
}

// Check the user is authorised for this page
checkAuthorised(this_page());

$user = session()->getUsername();

// Set up for Ajax.   We need to know whether we're capable of dealing with Ajax
// requests, which will only be if the browser is using DataTables.  We also need
// to initialise the JSON data array.
$ajax_capable = $datatable;

if ($is_ajax)
{
  $json_data['aaData'] = array();
}

if (!isset($search_str))
{
  $search_str = '';
}

$search_start_time = mktime(0, 0, 0, $month, $day, $year);

if (!$is_ajax)
{
  print_header($view, $view_all, $year, $month, $day, $area, isset($room) ? $room : null);

  $form = new Form();
  $form->setAttributes(array('class'  => 'standard',
                             'id'     => 'search_form',
                             'method' => 'post',
                             'action' => multisite(this_page())));

  $fieldset = new ElementFieldset();
  $fieldset->addLegend(get_vocab('search'));

  // Search string
  $field = new FieldInputSearch();
  $field->setLabel(get_vocab('search_for'))
        ->setControlAttributes(array('name'      => 'search_str',
                                     'value'     => (isset($search_str)) ? $search_str : '',
                                     'required'  => true,
                                     'autofocus' => true));
  $fieldset->addElement($field);

  // From date
  $field = new FieldInputDate();
  $field->setLabel(get_vocab('from'))
        ->setControlAttributes(array('name'      => 'from_date',
                                     'value'     => $from_date,
                                     'required'  => true));
  $fieldset->addElement($field);

  // Submit button
  $field = new FieldInputSubmit();
  $field->setControlAttribute('value', get_vocab('search_button'));
  $fieldset->addElement($field);

  $form->addElement($fieldset);

  $form->render();

  if (!isset($search_str) || ($search_str === ''))
  {
    echo "<p class=\"error\">" . get_vocab("invalid_search") . "</p>";
    print_footer();
    exit;
  }

  echo '<h3 class="search_results">';
  echo get_vocab("search_results",
                 htmlspecialchars($search_str),
                 htmlspecialchars(utf8_strftime($strftime_format['date_short'], $search_start_time)));
  echo "</h3>\n";
}  // if (!$is_ajax)


// This is the main part of the query predicate, used in both queries:
// NOTE: syntax_caseless_contains() modifies our SQL params for us

$sql_params = array();
$sql_pred = "(( " . db()->syntax_caseless_contains("E.create_by", $search_str, $sql_params)
  . ") OR (" . db()->syntax_caseless_contains("E.name", $search_str, $sql_params)
  . ") OR (" . db()->syntax_caseless_contains("E.description", $search_str, $sql_params). ")";

// Also need to search custom fields (but only those with character data,
// which can include fields that have an associative array of options)
$fields = db()->field_info($tbl_entry);
foreach ($fields as $field)
{
  if (!in_array($field['name'], $standard_fields['entry']))
  {
    // If we've got a field that is represented by an associative array of options
    // then we have to search for the keys whose values match the search string
    if (isset($select_options["entry." . $field['name']]) &&
        is_assoc($select_options["entry." . $field['name']]))
    {
      foreach($select_options["entry." . $field['name']] as $key => $value)
      {
        // We have to use strpos() rather than stripos() because we cannot
        // assume PHP5
        if (($key !== '') && (strpos(utf8_strtolower($value), utf8_strtolower($search_str)) !== false))
        {
          $sql_pred .= " OR (E." . db()->quote($field['name']) . "=?)";
          $sql_params[] = $key;
        }
      }
    }
    elseif ($field['nature'] == 'character')
    {
      $sql_pred .= " OR (" . db()->syntax_caseless_contains("E." . db()->quote($field['name']), $search_str, $sql_params).")";
    }
  }
}

$sql_pred .= ") AND (E.end_time > ?)";
$sql_params[] = $search_start_time;
$sql_pred .= " AND (E.room_id = R.id) AND (R.area_id = A.id)";

// We only want the bookings for rooms that are visible
$invisible_room_ids = get_invisible_room_ids();
if (count($invisible_room_ids) > 0)
{
  $sql_pred .= " AND (E.room_id NOT IN (" . implode(',', $invisible_room_ids) . "))";
}


// If we're not an admin (they are allowed to see everything), then we need
// to make sure we respect the privacy settings.  (We rely on the privacy fields
// in the area table being not NULL.   If they are by some chance NULL, then no
// entries will be found, which is at least safe from the privacy viewpoint)
if (!is_book_admin())
{
  if (isset($user))
  {
    // if the user is logged in they can see:
    //   - all bookings, if private_override is set to 'public'
    //   - their own bookings, and others' public bookings if private_override is set to 'none'
    //   - just their own bookings, if private_override is set to 'private'
    $sql_pred .= " AND (
                        (A.private_override='public') OR
                        (A.private_override='none') AND
                        (
                         (E.status&" . STATUS_PRIVATE . "=0) OR
                         (E.create_by = ?) OR
                         (
                          (A.private_override='private') AND (E.create_by = ?)
                         )
                        )
                       )";
    $sql_params[] = $user;
    $sql_params[] = $user;
  }
  else
  {
    // if the user is not logged in they can see:
    //   - all bookings, if private_override is set to 'public'
    //   - public bookings if private_override is set to 'none'
    $sql_pred .= " AND (
                        (A.private_override='public') OR
                        (
                         (A.private_override='none') AND (E.status&" . STATUS_PRIVATE . "=0)
                        )
                       )";
  }
}

// The first time the search is called, we get the total
// number of matches.  This is passed along to subsequent
// searches so that we don't have to run it for each page.
if (!isset($total))
{
  $sql = "SELECT count(*)
          FROM $tbl_entry E, $tbl_room R, $tbl_area A
          WHERE $sql_pred";
  $total = db()->query1($sql, $sql_params);
}

if (($total <= 0) && !$is_ajax)
{
  echo "<p id=\"nothing_found\">" . get_vocab("nothing_found") . "</p>\n";
  print_footer();
  exit;
}

if(!isset($search_pos) || ($search_pos <= 0))
{
  $search_pos = 0;
}
else if($search_pos >= $total)
{
  $search_pos = $total - ($total % $search["count"]);
}

// If we're Ajax capable and this is not an Ajax request then don't output
// the table body, because that's going to be sent later in response to
// an Ajax request - so we don't need to do the query
if (!$ajax_capable || $is_ajax)
{
  // Now we set up the "real" query
  $sql = "SELECT E.id AS entry_id, E.create_by, E.name, E.description, E.start_time,
                 E.room_id, R.area_id, A.enable_periods
            FROM $tbl_entry E, $tbl_room R, $tbl_area A
           WHERE $sql_pred
        ORDER BY E.start_time asc";
  // If it's an Ajax query we want everything.  Otherwise we use LIMIT to just get
  // the stuff we want.
  if (!$is_ajax)
  {
    $sql .= " " . db()->syntax_limit($search["count"], $search_pos);
  }

  // this is a flag to tell us not to display a "Next" link
  $result = db()->query($sql, $sql_params);
  $num_records = $result->count();
}

if (!$ajax_capable)
{
  echo generate_search_nav_html($search_pos, $total, $num_records, $search_str);
}

if (!$is_ajax)
{
  echo "<div id=\"search_output\" class=\"datatable_container\">\n";
  echo "<table id=\"search_results\" class=\"admin_table display\"";

  // Put the search parameters as data attributes so that the JavaScript can use them
  echo ' data-search_str="' . htmlspecialchars($search_str) . '"';
  echo ' data-from_date="' . htmlspecialchars($from_date) . '"';

  echo ">\n";
  echo "<thead>\n";
  echo "<tr>\n";
  // We give some columns a type data value so that the JavaScript knows how to sort them
  echo "<th>" . get_vocab("namebooker") . "</th>\n";
  echo "<th>" . get_vocab("createdby") . "</th>\n";
  echo "<th><span class=\"normal\" data-type=\"title-numeric\">" . get_vocab("start_date") . "</span></th>\n";
  echo "<th>" . get_vocab("description") . "</th>\n";
  echo "</tr>\n";
  echo "</thead>\n";
  echo "<tbody>\n";
}

// If we're Ajax capable and this is not an Ajax request then don't output
// the table body, because that's going to be sent later in response to
// an Ajax request
if (!$ajax_capable || $is_ajax)
{
  $returl = this_page() . "?search_str=$search_str&from_date=$from_date";

  while (false !== ($row = $result->next_row_keyed()))
  {
    output_row($row, $returl);
  }
}

if ($is_ajax)
{
  http_headers(array("Content-Type: application/json"));
  echo json_encode($json_data);
}
else
{
  echo "</tbody>\n";
  echo "</table>\n";
  echo "</div>\n";
  print_footer();
}

