<?php
# $Id$

include "config.inc";
include "functions.inc";
include "$dbsys.inc";

#If we dont know the right date then make it up 
if(!isset($day) or !isset($month) or !isset($year))
{
	$day   = date("d");
	$month = date("m");
	$year  = date("Y");
}

if(empty($area))
	$area = get_default_area();

# Need all these different versions with different escaping.
# search_str must be left as the html-escaped version because this is
# used as the default value for the search box in the header.
$search_text = unslashes($search_str);
$search_url = urlencode($search_text);
$search_str = htmlspecialchars($search_text);

print_header($day, $month, $year, $area);

if(!$search_str)
{
	echo "<H3>" . $lang["invalid_search"] . "</H3>";
	include "trailer.inc";
	exit;
}

# now is used so that we only display entries newer than the current time
echo "<H3>" . $lang["search_results"] . " \"<font color=\"blue\">$search_str</font>\"</H3>\n";

$now = mktime(0, 0, 0, $month, $day, $year);

# This is the main part of the query predicate, used in both queries:
$sql_pred = "( " . sql_syntax_caseless_contains("E.create_by", $search_text)
		. " OR " . sql_syntax_caseless_contains("E.name", $search_text)
		. " OR " . sql_syntax_caseless_contains("E.description", $search_text)
		. ") AND E.end_time > $now";

# The first time the search is called, we get the total
# number of matches.  This is passed along to subsequent
# searches so that we don't have to run it for each page.
if(!isset($total))
	$total = sql_query1("SELECT count(*) FROM mrbs_entry E WHERE $sql_pred");

if($total <= 0)
{
	echo "<B>" . $lang["nothing_found"] . "</B>\n";
	include "trailer.inc";
	exit;
}

if(!isset($search_pos) || ($search_pos <= 0))
	$search_pos = 0;
elseif($search_pos >= $total)
	$search_pos = $total - ($total % $search["count"]);

# Now we set up the "real" query using LIMIT to just get the stuff we want.
$sql = "SELECT E.id, E.create_by, E.name, E.description, E.start_time, R.area_id
        FROM mrbs_entry E, mrbs_room R
        WHERE $sql_pred
        AND E.room_id = R.id
        ORDER BY E.start_time asc "
    . sql_syntax_limit($search["count"], $search_pos);

# this is a flag to tell us not to display a "Next" link
$result = sql_query($sql);
if (! $result) fatal_error(0, sql_error());
$num_records = sql_count($result);

$has_prev = $search_pos > 0;
$has_next = $search_pos < ($total-$search["count"]);

if($has_prev || $has_next)
{
	echo "<B>" . $lang["records"] . ($search_pos+1) . $lang["through"] . ($search_pos+$num_records) . $lang["of"] . $total . "</B><BR>";

	# display a "Previous" button if necessary
	if($has_prev)
	{
		echo "<A HREF=\"search.php?search_str=$search_url&search_pos=";
		echo max(0, $search_pos-$search["count"]);
		echo "&total=$total&year=$year&month=$month&day=$day\">";
	}

	echo "<B>" . $lang["previous"] . "</B>";

	if($has_prev)
		echo "</A>";

	# print a separator for Next and Previous
	echo(" | ");

	# display a "Previous" button if necessary
	if($has_next)
	{
		echo "<A HREF=\"search.php?search_str=$search_url&search_pos=";
		echo max(0, $search_pos+$search["count"]);
		echo "&total=$total&year=$year&month=$month&day=$day\">";
	}

	echo "<B>". $lang["next"] ."</B>";

	if($has_next)
		echo "</A>";
}
?>
  <P>
  <TABLE BORDER=2 CELLSPACING=0 CELLPADDING=3>
   <TR>
    <TH><? echo $lang["entry"]       ?></TH>
    <TH><? echo $lang["createdby"]   ?></TH>
    <TH><? echo $lang["namebooker"]  ?></TH>
    <TH><? echo $lang["description"] ?></TH>
    <TH><? echo $lang["start_date"]  ?></TH>
   </TR>
<?
for ($i = 0; ($row = sql_row($result, $i)); $i++)
{
	echo "<TR>";
	echo "<TD><A HREF=\"view_entry.php?id=$row[0]\">$lang[view]</A></TD>\n";
	echo "<TD>" . htmlspecialchars($row[1]) . "</TD>\n";
	echo "<TD>" . htmlspecialchars($row[2]) . "</TD>\n";
	echo "<TD>" . htmlspecialchars($row[3]) . "</TD>\n";
	// generate a link to the day.php
	$link = getdate($row[4]);
	echo "<TD><A HREF=\"day.php?day=$link[mday]&month=$link[mon]&year=$link[year]&area=$row[5]\">"
	.  strftime('%X - %A %d %B %Y', $row[4]) . "</A></TD>";
	echo "</TR>\n";
}

echo "</TABLE>\n";
include "trailer.inc";
?>
