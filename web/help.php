<?
include "config.inc";
include "$dbsys.inc";
include "functions.inc";

$mrbs_version = "MRBS 1.0-pre1";

#If we dont know the right date then make it up
if(!isset($day) or !isset($month) or !isset($year))
{
	$day   = date("d");
	$month = date("m");
	$year  = date("Y");
}
if(empty($area))
	$area = get_default_area();

print_header($day, $month, $year, $area);

echo "<H3>About MRBS</H3>\n";
$uname = posix_uname();
$dbms = sql_version();
echo "<P><a href=\"http://mrbs.sourceforge.net\">$lang[mrbs]</a> - $mrbs_version\n";
echo "<P>Database: $dbms\n";
echo "<P>System: $uname[sysname] $uname[release] $uname[machine]\n";

echo "<H3>Help</H3>\n";
echo 'Please contact <a href="mailto:' . $mrbs_admin_email
	. '">' . $mrbs_admin
	. "</a> for any questions that aren't answered here.\n";
 
include "site_faq.html";

include "trailer.inc";
?>
