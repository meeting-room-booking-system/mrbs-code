<?
include "config.inc";
include "connect.inc";
include "functions.inc";

load_user_preferences ();

#If we dont know the right date then make it up 
if(!isset($day) or !isset($month) or !isset($year))
{
        $day   = date("d");
        $month = date("m");
        $year  = date("Y");
}

if(!isset($area))
        $area = 1;

print_header($day, $month, $year, $area);

?>
<h3>Help</h3>
Please contact <a href="mailto:<?echo $mrbs_admin_email?>?subject=<? echo $lang[mrbs] ?>"><?echo $mrbs_admin?></a> for any questions that aren't answered here.
<?
include "site_faq.html";
?>


<?include "trailer.inc"; ?>

<?echo "</BODY>";
echo "</HTML>";
?>

