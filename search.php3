<?
include "config.inc";
include "functions.inc";
include "connect.inc";
include "mrbs_auth.inc";

load_user_preferences();

$search_str = stripSlashes($search_str);

print_header($day, $month, $year, $area);

mysql_connect($mysql_host, $mysql_login, $mysql_password);
mysql_select_db($mysql_database);

if(!$search_str)
{
   echo("<H3>Empty or invalid search string.</H3>");
   include "trailer.inc";
   echo "</BODY>";
   echo "</HTML>";
   exit;
}

# now is used so that we only display entries newer than the current time
echo("<H4>Search Results for: \"<font color=\"blue\">$search_str</font>\"</H4>\n");

$now = time();

# the first time the search is called, we get the total
# number of matches.  This is passed along to subsequent
# searches so that we don't have to run it for each page.
if(!isset($total))
{
   $sql = "SELECT id, create_by, name, description, start_time
           FROM mrbs_entry
           WHERE ( create_by   LIKE '%$search_str%' OR
                   name        LIKE '%$search_str%' OR
                   description LIKE '%$search_str%'    ) AND
                   start_time > '$now'
           ORDER BY start_time desc";

   $res   = mysql_query($sql);
   $total = mysql_num_rows($res);
}

if($total == 0)
{
   echo("<b>No matching entries found</b>\n");
   include "trailer.inc";
   echo "</BODY>";
   echo "</HTML>";
   exit;
}

$first = (!$search_pos || $search_pos <= 0);
$last  = ( $search_pos && $search_pos > ($total - $search[count]));

if($first)
	$search_pos = 0;

if($last)
	$search_pos = $total - $search[count];

# Now we set up the "real" query using LIMIT to just get the stuff we want
$sql = "SELECT id, create_by, name, description, start_time
        FROM mrbs_entry
        WHERE ( create_by   LIKE '%$search_str%' OR
                name        LIKE '%$search_str%' OR
                description LIKE '%$search_str%'    )AND
                start_time > '$now'
       ORDER BY start_time asc
       LIMIT " . $search_pos . ", " . $search[count];

# this is a flag to tell us not to display a "Next" link
$result = mysql_query($sql);
$num_records = mysql_num_rows($result);

$has_prev = $search_pos > 0;
$has_next = $search_pos < ($total-$search[count]);

if($has_prev || $has_next)
{
  echo "<B>Records " . ($search_pos+1) . " through " . ($search_pos+$num_records) . " of " . $total . "<BR>";
  
  # display a "Previous" button if necessary
  if($has_prev)
  {
    echo "<A HREF=\"search.php3?search_str=$search_str&search_pos=";
    echo max(0, $search_pos-$search[count]);
    echo "&total=$total&year=$year&month=$month&day=$day\">";
  }
  
  echo "<B>Previous</B>";
  
  if($has_prev)
    echo "</A>";
  
  # print a separator for Next and Previos
  echo(" | ");
  
  # display a "Previous" button if necessary
  if($has_next)
  {
    echo "<A HREF=\"search.php3?search_str=$search_str&search_pos=";
    echo max(0, $search_pos+$search[count]);
    echo "&total=$total&year=$year&month=$month&day=$day\">";
  }
  
  echo "<B>Next</B>";
  
  if($has_next)
    echo "</A>";
}
?>
  <P>
  <TABLE BORDER=2 BORDERCOLOR="#000000" CELLSPACING=0 CELLPADDING=3>
   <TR>
    <TH BGCOLOR="#000000">Entry</TH>
    <TH BGCOLOR="#000000">Created By</TH>
    <TH BGCOLOR="#000000">Name</TH>
    <TH BGCOLOR="#000000">Description</TH>
    <TH BGCOLOR="#000000">Start Time</TH>
   </TR>
<?
while($row = mysql_fetch_row($result))
{
?>
   <TR>
    <TD><A HREF="view_entry.php3?id=<? echo $row[0] ?>">View</A></TD>
    <TD><? echo $row[1] ?></TD>
    <TD><? echo $row[2] ?></TD>
    <TD><? echo $row[3] ?></TD>
    <TD><? echo strftime('%X - %A %d %B %Y', $row[4]) ?></TD>
   </TR>
<?
}
?>
  </TABLE>
<?
include "trailer.inc";
echo "</BODY>";
echo "</HTML>";
?>
