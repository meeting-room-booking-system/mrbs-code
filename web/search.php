<?php
// $Id$

require_once "defaultincludes.inc";


function generate_search_nav_html($search_pos, $total, $num_records, $search_str)
{
  global $day, $month, $year;
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
    $base_query_string = "search_str=" . urlencode($search_str) . "&amp;" .
                         "total=$total&amp;" .
                         "from_year=$year&amp;" .
                         "from_month=$month&amp;" .
                         "from_day=$day";
    // display a "Previous" button if necessary
    if($has_prev)
    {
      $query_string = $base_query_string . "&amp;search_pos=" . max(0, $search_pos-$search["count"]);
      $html .= "<a href=\"search.php?$query_string\">";
    }

    $html .= get_vocab("previous");

    if ($has_prev)
    {
      $html .= "</a>";
    }

    // add a separator for Next and Previous
    $html .= (" | ");

    // display a "Previous" button if necessary
    if ($has_next)
    {
      $query_string = $base_query_string . "&amp;search_pos=" . max(0, $search_pos+$search["count"]);
      $html .= "<a href=\"search.php?$query_string\">";
    }

    $html .= get_vocab("next");
  
    if ($has_next)
    {
      $html .= "</a>";
    }
    $html .= "</div>\n";
  }
  
  return $html;
}


function output_row($row)
{
  global $ajax, $json_data;
  
  $values = array();
  // booking name
  $html_name = htmlspecialchars($row['name']);
  $values[] = "<a title=\"$html_name\" href=\"view_entry.php?id=" . $row['entry_id'] . "\">$html_name</a>";
  // created by
  $values[] = htmlspecialchars($row['create_by']);
  // start time and link to day view
  $date = getdate($row['start_time']);
  $link = "<a href=\"day.php?day=$date[mday]&amp;month=$date[mon]&amp;year=$date[year]&amp;area=".$row['area_id']."\">";
  if(empty($row['enable_periods']))
  {
    $link_str = time_date_string($row['start_time']);
  }
  else
  {
    list(,$link_str) = period_date_string($row['start_time']);
  }
  $link .= "$link_str</a>";
  //    add a span with the numeric start time in the title for sorting
  $values[] = "<span title=\"" . $row['start_time'] . "\"></span>" . $link;
  // description
  $values[] = htmlspecialchars($row['description']);
  
  if ($ajax)
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
  
// Get non-standard form variables
$search_str = get_form_var('search_str', 'string');
$search_pos = get_form_var('search_pos', 'int');
$total = get_form_var('total', 'int');
$advanced = get_form_var('advanced', 'int');
$ajax = get_form_var('ajax', 'int');  // Set if this is an Ajax request
$datatable = get_form_var('datatable', 'int');  // Will only be set if we're using DataTables
// Get the start day/month/year and make them the current day/month/year
$day = get_form_var('from_day', 'int');
$month = get_form_var('from_month', 'int');
$year = get_form_var('from_year', 'int');

// If we haven't been given a sensible date then use today's
if (!isset($day) || !isset($month) || !isset($year) || !checkdate($month, $day, $year))
{
  $day   = date("d");
  $month = date("m");
  $year  = date("Y");
}

// Check the user is authorised for this page
checkAuthorised();

// Also need to know whether they have admin rights
$user = getUserName();
$is_admin =  (isset($user) && authGetUserLevel($user)>=2) ;

// Set up for Ajax.   We need to know whether we're capable of dealing with Ajax
// requests, which will only be if (a) the browser is using DataTables and (b)
// we can do JSON encoding.    We also need to initialise the JSON data array.
$ajax_capable = $datatable && function_exists('json_encode');

if ($ajax)
{
  $json_data['aaData'] = array();
}

if (!isset($search_str))
{
  $search_str = '';
}
  
if (!$ajax)
{
  print_header($day, $month, $year, $area, isset($room) ? $room : "");

  if (!empty($advanced))
  {
    ?>
    <form class="form_general" id="search_form" method="get" action="search.php">
      <fieldset>
      <legend><?php echo get_vocab("advanced_search") ?></legend>
        <div id="div_search_str">
          <label for="search_str"><?php echo get_vocab("search_for") ?>:</label>
          <input type="text" id="search_str" name="search_str">
        </div>   
        <div id="div_search_from">
          <?php
          echo "<label for=\"from_datepicker\">" . get_vocab("from") . ":</label>\n";
          genDateSelector ("from_", $day, $month, $year);
          ?>
        </div> 
        <div id="search_submit">
          <input class="submit" type="submit" value="<?php echo get_vocab("search_button") ?>">
        </div>
      </fieldset>
    </form>
    <?php
    require_once "trailer.inc";
    exit;
  }

  if (!isset($search_str) || ($search_str == ''))
  {
    echo "<p class=\"error\">" . get_vocab("invalid_search") . "</p>";
    require_once "trailer.inc";
    exit;
  }

  // now is used so that we only display entries newer than the current time
  echo "<h3>";
  echo get_vocab("search_results") . ": ";
  echo "\"<span id=\"search_str\">" . htmlspecialchars($search_str) . "</span>\"";
  echo "</h3>\n";
}  // if (!$ajax)


$now = mktime(0, 0, 0, $month, $day, $year);

// This is the main part of the query predicate, used in both queries:
// NOTE: sql_syntax_caseless_contains() does the SQL escaping
    
$sql_pred = "( " . sql_syntax_caseless_contains("E.create_by", $search_str)
  . " OR " . sql_syntax_caseless_contains("E.name", $search_str)
  . " OR " . sql_syntax_caseless_contains("E.description", $search_str);

// Also need to search custom fields (but only those with character data,
// which can include fields that have an associative array of options)
$fields = sql_field_info($tbl_entry);
foreach ($fields as $field)
{
  if (!in_array($field['name'], $standard_fields['entry']))
  {
    // If we've got a field that is represented by an associative array of options
    // then we have to search for the keys whose values match the search string
    if (is_assoc($select_options["entry." . $field['name']]))
    {
      foreach($select_options["entry." . $field['name']] as $key => $value)
      {
        // We have to use strpos() rather than stripos() because we cannot
        // assume PHP5
        if (strpos(strtolower($value), strtolower($search_str)) !== FALSE)
        {
          $sql_pred .= " OR E." . $field['name'] . "='" . addslashes($key) . "'";
        }
      }
    }
    elseif ($field['nature'] == 'character')
    {
      $sql_pred .= " OR " . sql_syntax_caseless_contains("E." . $field['name'], $search_str);
    }
  }
}

$sql_pred .= ") AND E.end_time > $now";
$sql_pred .= " AND E.room_id = R.id AND R.area_id = A.id";


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
    $sql_pred .= " AND ((A.private_override='public') OR
                        (A.private_override='none' AND ((E.status&" . STATUS_PRIVATE . "=0) OR E.create_by = '" . addslashes($user) . "')) OR
                        (A.private_override='private' AND E.create_by = '" . addslashes($user) . "'))";                
  }
  else
  {
    // if the user is not logged in they can see:
    //   - all bookings, if private_override is set to 'public'
    //   - public bookings if private_override is set to 'none'
    $sql_pred .= " AND ((A.private_override='public') OR
                        (A.private_override='none' AND (E.status&" . STATUS_PRIVATE . "=0)))";
  }
}

// The first time the search is called, we get the total
// number of matches.  This is passed along to subsequent
// searches so that we don't have to run it for each page.
if (!isset($total))
{
  $total = sql_query1("SELECT count(*)
                       FROM $tbl_entry E, $tbl_room R, $tbl_area A
                       WHERE $sql_pred");
}
if ($total < 0)
{
  trigger_error(sql_error(), E_USER_WARNING);
  fatal_error(FALSE, get_vocab("fatal_db_error"));
}
if (($total <= 0) && !$ajax)
{
  echo "<p id=\"nothing_found\">" . get_vocab("nothing_found") . "</p>\n";
  require_once "trailer.inc";
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

// If we're Ajax capable and this is not an Ajax request then don't ouput
// the table body, because that's going to be sent later in response to
// an Ajax request - so we don't need to do the query
if (!$ajax_capable || $ajax)
{
  // Now we set up the "real" query
  $sql = "SELECT E.id AS entry_id, E.create_by, E.name, E.description, E.start_time,
                 R.area_id, A.enable_periods
            FROM $tbl_entry E, $tbl_room R, $tbl_area A
           WHERE $sql_pred
        ORDER BY E.start_time asc";
  // If it's an Ajax query we want everything.  Otherwise we use LIMIT to just get
  // the stuff we want.
  if (!$ajax)
  {
    $sql .= " " . sql_syntax_limit($search["count"], $search_pos);
  }


  // this is a flag to tell us not to display a "Next" link
  $result = sql_query($sql);
  if (! $result)
  {
    trigger_error(sql_error(), E_USER_WARNING);
    fatal_error(FALSE, get_vocab("fatal_db_error"));
  }
  $num_records = sql_count($result);
}

if (!$ajax_capable)
{
  echo generate_search_nav_html($search_pos, $total, $num_records, $search_str);
}

if (!$ajax)
{
  echo "<div id=\"search_output\" class=\"datatable_container\">\n";
  echo "<table id=\"search_results\" class=\"admin_table display\">\n";
  echo "<thead>\n";
  echo "<tr>\n";
  echo "<th>" . get_vocab("namebooker") . "</th>\n";
  echo "<th>" . get_vocab("createdby") . "</th>\n";
  echo "<th>" . get_vocab("start_date") . "</th>\n";
  echo "<th>" . get_vocab("description") . "</th>\n";
  echo "</tr>\n";
  echo "</thead>\n";
  echo "<tbody>\n";
}

// If we're Ajax capable and this is not an Ajax request then don't ouput
// the table body, because that's going to be sent later in response to
// an Ajax request
if (!$ajax_capable || $ajax)
{
  for ($i = 0; ($row = sql_row_keyed($result, $i)); $i++)
  {
    output_row($row);
  }
}

if ($ajax)
{
  echo json_encode($json_data);
}
else
{
  echo "</tbody>\n";
  echo "</table>\n";
  echo "</div>\n";
  require_once "trailer.inc";
}
?>
