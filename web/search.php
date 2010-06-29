<?php
// $Id$

require_once "defaultincludes.inc";

// Get form variables
$day = get_form_var('day', 'int');
$month = get_form_var('month', 'int');
$year = get_form_var('year', 'int');
$area = get_form_var('area', 'int');
$room = get_form_var('room', 'int');
$search_str = get_form_var('search_str', 'string');
$search_pos = get_form_var('search_pos', 'int');
$total = get_form_var('total', 'int');
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

// Check the user is authorised for this page
checkAuthorised();

// Also need to know whether they have admin rights
$user = getUserName();
$is_admin =  (isset($user) && authGetUserLevel($user)>=2) ;

// Need all these different versions with different escaping.
if (!empty($search_str)) 
{
  $search_url = urlencode($search_str);
  $search_html = htmlspecialchars($search_str);
}

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
        <label><?php echo get_vocab("from") ?>:</label>
        <?php genDateSelector ("", $day, $month, $year) ?>
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

if (!$search_str)
{
  echo "<p class=\"error\">" . get_vocab("invalid_search") . "</p>";
  require_once "trailer.inc";
  exit;
}

// now is used so that we only display entries newer than the current time
echo "<h3>" . get_vocab("search_results") . ": \"<span id=\"search_str\">$search_html</span>\"</h3>\n";

$now = mktime(0, 0, 0, $month, $day, $year);

// This is the main part of the query predicate, used in both queries:
// NOTE: sql_syntax_caseless_contains() does the SQL escaping
    
$sql_pred = "( " . sql_syntax_caseless_contains("E.create_by", $search_str)
  . " OR " . sql_syntax_caseless_contains("E.name", $search_str)
  . " OR " . sql_syntax_caseless_contains("E.description", $search_str)
  . ") AND E.end_time > $now";

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
                        (A.private_override='none' AND (E.private=0 OR E.create_by = '" . addslashes($user) . "')) OR
                        (A.private_override='private' AND E.create_by = '" . addslashes($user) . "'))";                
  }
  else
  {
    // if the user is not logged in they can see:
    //   - all bookings, if private_override is set to 'public'
    //   - public bookings if private_override is set to 'none'
    $sql_pred .= " AND ((A.private_override='public') OR
                        (A.private_override='none' AND E.private=0))";
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
  fatal_error(0, sql_error());
}
if ($total <= 0)
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

// Now we set up the "real" query using LIMIT to just get the stuff we want.
$sql = "SELECT E.id AS entry_id, E.create_by, E.name, E.description, E.start_time, R.area_id
        FROM $tbl_entry E, $tbl_room R, $tbl_area A
        WHERE $sql_pred
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
  echo "<div id=\"record_numbers\">\n";
  echo get_vocab("records") . ($search_pos+1) . get_vocab("through") . ($search_pos+$num_records) . get_vocab("of") . $total;
  echo "</div>\n";
  
  echo "<div id=\"record_nav\">\n";
  // display a "Previous" button if necessary
  if($has_prev)
  {
    echo "<a href=\"search.php?search_str=$search_url&amp;search_pos=";
    echo max(0, $search_pos-$search["count"]);
    echo "&amp;total=$total&amp;year=$year&amp;month=$month&amp;day=$day\">";
  }

  echo get_vocab("previous");

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

  echo get_vocab("next");
  
  if ($has_next)
  {
    echo "</a>";
  }
  echo "</div>\n";
}
?>

  <table id="search_results">
    <thead>
      <tr>
        <th><?php echo get_vocab("entry") ?></th>
        <th><?php echo get_vocab("createdby") ?></th>
        <th><?php echo get_vocab("namebooker") ?></th>
        <th><?php echo get_vocab("description") ?></th>
        <th><?php echo get_vocab("start_date") ?></th>
      </tr>
    </thead>
    
    <tbody>
<?php
for ($i = 0; ($row = sql_row_keyed($result, $i)); $i++)
{
  echo "<tr>\n";
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
echo "</tbody>\n";
echo "</table>\n";
require_once "trailer.inc";
?>
