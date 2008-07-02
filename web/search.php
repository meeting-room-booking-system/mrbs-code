<?php
// $Id$

require_once "grab_globals.inc.php";
include "config.inc.php";
include "functions.inc";
include "$dbsys.inc";
include "mrbs_auth.inc";

// Get form variables
$day = get_form_var('day', 'int');
$month = get_form_var('month', 'int');
$year = get_form_var('year', 'int');
$area = get_form_var('area', 'int');
$search_str = get_form_var('search_str', 'string');
$advanced = get_form_var('advanced', 'int');

// If we dont know the right date then make it up 
if (!isset($day) or !isset($month) or !isset($year))
{
  $day   = date("d");
  $month = date("m");
  $year  = date("Y");
}

if (empty($area))
{
  $area = get_default_area();
}

// Need all these different versions with different escaping.
// search_str must be left as the html-escaped version because this is
// used as the default value for the search box in the header.
if (!empty($search_str)) 
{
  $search_url = urlencode($search_str);
  $search_str = htmlspecialchars($search_str);
}

print_header($day, $month, $year, $area);

if (!empty($advanced))
{
  echo "<h3>" . get_vocab("advanced_search") . "</h3>";
  echo "<form method=\"get\" action=\"search.php\">";
  echo get_vocab("search_for") . " <input type=\"text\" size=\"25\" name=\"search_str\"><br>";
  echo get_vocab("from"). " ";
  genDateSelector ("", $day, $month, $year);
  echo "<br><input type=\"submit\" value=\"" . get_vocab("search_button") ."\">";
  echo "</form>";
  include "trailer.inc";
  exit;
}

if (!$search_str)
{
  echo "<h3>" . get_vocab("invalid_search") . "</h3>";
  include "trailer.inc";
  exit;
}

// now is used so that we only display entries newer than the current time
echo "<h3>" . get_vocab("search_results") . ": \"<font color=\"blue\">$search_str</font>\"</h3>\n";

$now = mktime(0, 0, 0, $month, $day, $year);

// This is the main part of the query predicate, used in both queries:
$sql_pred = "( " . sql_syntax_caseless_contains("E.create_by", $search_str)
  . " OR " . sql_syntax_caseless_contains("E.name", $search_str)
  . " OR " . sql_syntax_caseless_contains("E.description", $search_str)
  . ") AND E.end_time > $now";

// The first time the search is called, we get the total
// number of matches.  This is passed along to subsequent
// searches so that we don't have to run it for each page.
if (!isset($total))
{
  $total = sql_query1("SELECT count(*) FROM $tbl_entry E WHERE $sql_pred");
}

if ($total <= 0)
{
  echo "<b>" . get_vocab("nothing_found") . "</b>\n";
  include "trailer.inc";
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

// Now we set up the "real" query using LIMIT to just get the stuff we want.
$sql = "SELECT E.id AS entry_id, E.create_by, E.name, E.description, E.start_time, R.area_id
        FROM $tbl_entry E, $tbl_room R
        WHERE $sql_pred
        AND E.room_id = R.id
        ORDER BY E.start_time asc "
  . sql_syntax_limit($search["count"], $search_pos);

// this is a flag to tell us not to display a "Next" link
$result = sql_query($sql);
if (! $result)
{
  fatal_error(0, sql_error());
}
$num_records = sql_count($result);

$has_prev = $search_pos > 0;
$has_next = $search_pos < ($total-$search["count"]);

if ($has_prev || $has_next)
{
  echo "<b>" . get_vocab("records") . ($search_pos+1) . get_vocab("through") . ($search_pos+$num_records) . get_vocab("of") . $total . "</b><br>";

  // display a "Previous" button if necessary
  if($has_prev)
  {
    echo "<a href=\"search.php?search_str=$search_url&amp;search_pos=";
    echo max(0, $search_pos-$search["count"]);
    echo "&amp;total=$total&amp;year=$year&amp;month=$month&amp;day=$day\">";
  }

  echo "<b>" . get_vocab("previous") . "</b>";

  if ($has_prev)
  {
    echo "</a>";
  }

  // print a separator for Next and Previous
  echo(" | ");

  // display a "Previous" button if necessary
  if ($has_next)
  {
    echo "<a href=\"search.php?search_str=$search_url&amp;search_pos=";
    echo max(0, $search_pos+$search["count"]);
    echo "&amp;total=$total&amp;year=$year&amp;month=$month&amp;day=$day\">";
  }

  echo "<b>". get_vocab("next") ."</b>";
  
  if ($has_next)
  {
    echo "</a>";
  }
}
?>

  <table border="2" cellspacing="0" cellpadding="3">
   <tr>
    <th><?php echo get_vocab("entry") ?></th>
    <th><?php echo get_vocab("createdby") ?></th>
    <th><?php echo get_vocab("namebooker") ?></th>
    <th><?php echo get_vocab("description") ?></th>
    <th><?php echo get_vocab("start_date") ?></th>
   </tr>
<?php
for ($i = 0; ($row = sql_row_keyed($result, $i)); $i++)
{
  echo "<tr>";
  echo "<td><a href=\"view_entry.php?id=".$row['entry_id']."\">".get_vocab("view")."</a></td>\n";
  echo "<td>" . htmlspecialchars($row['create_by']) . "</td>\n";
  echo "<td>" . htmlspecialchars($row['name']) . "</td>\n";
  echo "<td>" . htmlspecialchars($row['description']) . "</td>\n";
  // generate a link to the day.php
  $link = getdate($row['start_time']);
  echo "<td><a href=\"day.php?day=$link[mday]&amp;month=$link[mon]&amp;year=$link[year]&amp;area=".$row['area_id']."\">";
  if(empty($enable_periods))
  {
    $link_str = time_date_string($row['start_time']);
  }
  else
  {
    list(,$link_str) = period_date_string($row['start_time']);
  }
  echo "$link_str</a></td>";
  echo "</tr>\n";
}

echo "</table>\n";
include "trailer.inc";
?>
