<?

# $Id$

include "config.inc";
include "$dbsys.inc";
include "functions.inc";

$mrbs_version = "MRBS 1.0-pre2";

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
echo "<P><a href=\"http://mrbs.sourceforge.net\">$vocab[mrbs]</a> - $mrbs_version\n";
echo "<BR>Database: " . sql_version() . "\n";
$uname = posix_uname();
echo "<BR>System: $uname[sysname] $uname[release] $uname[machine]\n";
echo "<BR>PHP: " . phpversion() . "\n";

echo "<H3>Help</H3>\n";
echo 'Please contact <a href="mailto:' . $mrbs_admin_email
	. '">' . $mrbs_admin
	. "</a> for any questions that aren't answered here.\n";
 
include "site_faq.html";

include "trailer.inc";
?>
