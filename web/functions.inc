<?php

# $Id$

function print_header($day, $month, $year, $area)
{
	global $lang, $mrbs_company, $search_str;

	# If we dont know the right date then make it up 
	if(!$day)
		$day   = date("d");
	if(!$month)
		$month = date("m");
	if(!$year)
		$year  = date("Y");
	if (empty($search_str))
		$search_str = "";

?>
<HTML>
 <HEAD>
  <TITLE><? echo $lang["mrbs"] ?></TITLE>
<?
   include "style.inc";
?>
 </HEAD>
 <BODY BGCOLOR="#ffffed" TEXT=black LINK="#5B69A6" VLINK="#5B69A6" ALINK=red>
	   <? if ( $GLOBALS["pview"] != 1 ) { ?>
   <TABLE WIDTH="100%">
    <TR>
      <TD BGCOLOR="#5B69A6">
       <TABLE WIDTH="100%" BORDER=0>
        <TR>
         <TD CLASS="banner" BGCOLOR="#C0E0FF">
          <FONT SIZE=4><B><? echo $mrbs_company ?></B><BR>
           <A HREF="index.php"><? echo $lang["mrbs"] ?></A>
          </FONT>
         </TD>
         <TD CLASS="banner" BGCOLOR="#C0E0FF">
          <FORM ACTION="day.php" METHOD=GET>
           <FONT SIZE=2>
<?
   genDateSelector("", $day, $month, $year);
   if (!empty($area))
        echo "<INPUT TYPE=HIDDEN NAME=area VALUE=$area>"
 
?>
	    <INPUT TYPE=SUBMIT VALUE="<? echo $lang["goto"] ?>">
           </FONT>
          </FORM>
         </TD>
         <TD CLASS="banner" BGCOLOR="#C0E0FF" ALIGN=CENTER>
          <A HREF="help.php?day=<? echo $day ?>&month=<? echo $month ?>&year=<? echo $year ?>"><? echo $lang["help"] ?></A>
         </TD>
         <TD CLASS="banner" BGCOLOR="#C0E0FF" ALIGN=CENTER>
          <A HREF="admin.php?day=<? echo $day ?>&month=<? echo $month ?>&year=<? echo $year ?>"><? echo $lang["admin"] ?></A>
         </TD>
         <TD CLASS="banner" BGCOLOR="#C0E0FF" ALIGN=CENTER>
          <A HREF="report.php"><? echo $lang["report"] ?></A>
         </TD>
         <TD CLASS="banner" BGCOLOR="#C0E0FF" ALIGN=CENTER>
          <FORM METHOD=GET ACTION="search.php">
           <FONT SIZE=2><? echo $lang["search"] ?> </FONT>
           <INPUT TYPE=TEXT   NAME="search_str" VALUE="<? echo $search_str ?>" SIZE=10>
           <INPUT TYPE=HIDDEN NAME=day        VALUE="<? echo $day        ?>"        >
           <INPUT TYPE=HIDDEN NAME=month      VALUE="<? echo $month      ?>"        >
           <INPUT TYPE=HIDDEN NAME=year       VALUE="<? echo $year       ?>"        >
<?
   if (!empty($area))
        echo "<INPUT TYPE=HIDDEN NAME=area VALUE=$area>"
?>
          </FORM>
         </TD>
        </TR>
       </TABLE>
      </TD>
     </TR>
    </TABLE>
<? } ?>
<?
}

function toTimeString(&$dur, &$units)
{
	global $lang;
	if($dur >= 60)
	{
		$dur /= 60;
		
		if($dur >= 60)
		{
			$dur /= 60;
			
			if(($dur >= 24) && ($dur % 24 == 0))
			{
				$dur /= 24;
				
				if(($dur >= 7) && ($dur % 7 == 0))
				{
					$dur /= 7;
					
					if(($dur >= 52) && ($dur % 52 == 0))
					{
						$dur  /= 52;
						$units = $lang["years"];
					}
					else
						$units = $lang["weeks"];
				}
				else
					$units = $lang["days"];
			}
			else
				$units = $lang["hours"];
		}
		else
			$units = $lang["minutes"];
	}
	else
		$units = $lang["seconds"];
}

function genDateSelector($prefix, $day, $month, $year)
{
	if($day   == 0) $day = date("d");
	if($month == 0) $month = date("m");
	if($year  == 0) $year = date("Y");
	
	echo "<SELECT NAME=\"${prefix}day\">\n";
	
	for($i = 1; $i <= 31; $i++)
		echo "<OPTION" . ($i == $day ? " SELECTED" : "") . ">$i\n";
	
	echo "</SELECT>";
	echo "<SELECT NAME=\"${prefix}month\">\n";
	
	for($i = 1; $i <= 12; $i++)
	{
		$m = strftime("%b", mktime(0, 0, 0, $i, 1, $year));
		
		print "<OPTION VALUE=\"$i\"" . ($i == $month ? " SELECTED" : "") . ">$m\n";
	}
	
	echo "</SELECT>";
	echo "<SELECT NAME=\"${prefix}year\">\n";
	
	$min = min($year, date("Y")) - 5;
	$max = max($year, date("Y")) + 5;
	
	for($i = $min; $i <= $max; $i++)
		print "<OPTION" . ($i == $year ? " SELECTED" : "") . ">$i\n";
	
	echo "</SELECT>";
}

# Error handler - this is used to display serious errors such as database
# errors without sending incomplete HTML pages. This is only used for
# errors which "should never happen", not those caused by bad inputs.
# If $need_header!=0 output the top of the page too, else assume the
# caller did that. Alway outputs the bottom of the page and exits.
function fatal_error($need_header, $message)
{
	global $lang;
	if ($need_header) print_header(0, 0, 0, 0);
	echo $message;
	include "trailer.inc";
	exit;
}

# Apply backslash-escape quoting unless PHP is configured to do it
# automatically. Use this for GET/POST form parameters, since we
# cannot predict if the PHP configuration file has magic_quotes_gpc on.
function slashes($s)
{
	if (get_magic_quotes_gpc()) return $s;
	else return addslashes($s);
}

# Remove backslash-escape quoting if PHP is configured to do it with
# magic_quotes_gpc. Use this whenever you need the actual value of a GET/POST
# form parameter (which might have special characters) regardless of PHP's
# magic_quotes_gpc setting.
function unslashes($s)
{
	if (get_magic_quotes_gpc()) return stripslashes($s);
	else return $s;
}

# Return a default area; used if no area is already known. This returns the
# lowest area ID in the database (no guaranty there is an area 1).
# This could be changed to implement something like per-user defaults.
function get_default_area()
{
	$area = sql_query1("SELECT MIN(id) FROM mrbs_area");
	return ($area < 0 ? 0 : $area);
}

# Get the local day name based on language. Note 2000-01-02 is a Sunday.
function day_name($daynumber)
{
	return strftime("%A", mktime(0,0,0,1,2+$daynumber,2000));
}

# Output a start table cell tag <td> with color class and fallback color.
# $colclass is an entry type (A-J), "white" for empty, or "red" for highlighted.
# The colors for CSS browsers can be found in the style sheet. The colors
# in the array below are fallback for non-CSS browsers only.
function tdcell($colclass)
{
	# This should be 'static $ecolors = array(...)' but that crashes PHP3.0.12!
	static $ecolors;
	if (!isset($ecolors)) $ecolors = array("A"=>"#FFCCFF", "B"=>"#99CCCC",
		"C"=>"#FF9999", "D"=>"#FFFF99", "E"=>"#C0E0FF", "F"=>"#FFCC99",
		"G"=>"#FF6666", "H"=>"#66FFFF", "I"=>"#DDFFDD", "J"=>"#CCCCCC",
		"red"=>"#FFF0F0", "white"=>"#FFFFFF");
	if (isset($ecolors[$colclass]))
		echo "<td class=\"$colclass\" bgcolor=\"$ecolors[$colclass]\">";
	else
		echo "<td class=\"$colclass\">";
}

# Display the entry-type color key. This has up to 2 rows, up to 5 columns.
function show_colour_key()
{
	global $typel;
	echo "<table border=0><tr>\n";
	$nct = 0;
	for ($ct = "A"; $ct <= "J"; $ct++)
	{
		if (!empty($typel[$ct]))
		{
			if (++$nct > 5)
			{
				$nct = 0;
				echo "</tr><tr>";
			}
			tdcell($ct);
			echo "$typel[$ct]</td>\n";
		}
	}
	echo "</tr></table>\n";
}

# Round time down to the nearest resolution
function round_t_down($t, $resolution)
{
       return (int)$t - (int)$t % $resolution;
}

# Round time up to the nearest resolution
function round_t_up($t, $resolution)
{
       if ($t % $resolution != 0)
       {
               return $t + $resolution - $t % $resolution;
       }
       else
       {
               return $t;
       }
}

?>
