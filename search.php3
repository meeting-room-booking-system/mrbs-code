<?
include "config.inc";
include "functions.inc";
include "connect.inc";
include "mrbs_auth.inc";

#If we dont know the right date then make it up 
if(!isset($day) or !isset($month) or !isset($year))
{
        $day   = date("d");
        $month = date("m");
        $year  = date("Y");
}

if(!isset($area))
        $area = 1;

load_user_preferences();

$search_str = stripSlashes($search_str);

print_header($day, $month, $year, $area);

mysql_connect($mysql_host, $mysql_login, $mysql_password);
mysql_select_db($mysql_database);

if(!$search_str)
{
   echo "<H3>" . $lang["invalid_search"] . "</H3>";
   include "trailer.inc";
   echo "</BODY>";
   echo "</HTML>";
   exit;
}

# now is used so that we only display entries newer than the current time
echo "<H4>" . $lang["search_results"] . " \"<font color=\"blue\">$search_str</font>\"</H4>\n";

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
   echo "<B>" . $lang["nothing_found"] . "</B>\n";
   include "trailer.inc";
   echo "</BODY>";
   echo "</HTML>";
   exit;
}

if(!isset($search_pos) || ($search_pos <= 0))
	$search_pos = 0;
else
{
	if($search_pos >= $total)
		$search_pos = $total - ($total % $search[count]);
}

# Now we set up the "real" query using LIMIT to just get the stuff we want
$sql = "SELECT id, create_by, name, description, start_time
        FROM mrbs_entry
        WHERE ( create_by   LIKE '%$search_str%' OR
                name        LIKE '%$search_str%' OR
                description LIKE '%$search_str%'    )AND
                start_time > '$now'
       ORDER BY start_time asc
       LIMIT " . $search_pos . ", " . $search["count"];

# this is a flag to tell us not to display a "Next" link
$result = mysql_query($sql);
$num_records = mysql_num_rows($result);

$has_prev = $search_pos > 0;
$has_next = $search_pos < ($total-$search["count"]);

if($has_prev || $has_next)
{
  echo "<B>" . $lang["records"] . ($search_pos+1) . $lang["through"] . ($search_pos+$num_records) . $lang["of"] . $total . "<BR>";
  
  # display a "Previous" button if necessary
  if($has_prev)
  {
    echo "<A HREF=\"search.php3?search_str=$search_str&search_pos=";
    echo max(0, $search_pos-$search["count"]);
    echo "&total=$total&year=$year&month=$month&day=$day\">";
  }
  
  echo "<B>" . $lang["previous"] . "</B>";
  
  if($has_prev)
    echo "</A>";
  
  # print a separator for Next and Previos
  echo(" | ");
  
  # display a "Previous" button if necessary
  if($has_next)
  {
    echo "<A HREF=\"search.php3?search_str=$search_str&search_pos=";
    echo max(0, $search_pos+$search["count"]);
    echo "&total=$total&year=$year&month=$month&day=$day\">";
  }
  
  echo "<B>". $lang["next"] ."</B>";
  
  if($has_next)
    echo "</A>";
}
?>
  <P>
  <TABLE BORDER=2 BORDERCOLOR="#000000" CELLSPACING=0 CELLPADDING=3>
   <TR>
    <TH BGCOLOR="#000000"><? echo $lang["entry"]       ?></TH>
    <TH BGCOLOR="#000000"><? echo $lang["createdby"]   ?></TH>
    <TH BGCOLOR="#000000"><? echo $lang["namebooker"]  ?></TH>
    <TH BGCOLOR="#000000"><? echo $lang["description"] ?></TH>
    <TH BGCOLOR="#000000"><? echo $lang["start_date"]  ?></TH>
   </TR>
<?
while($row = mysql_fetch_row($result))
{
?>
   <TR>
    <TD><A HREF="view_entry.php3?id=<? echo $row[0] . "\">" . $lang["view"] ?></A></TD>
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
