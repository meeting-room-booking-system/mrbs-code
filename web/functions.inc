<?php

# $Id$

# probably a bad place to put this, but for error reporting purposes
# $pview must be defined. if it's not then there's errors generated all
# over the place. so we test to see if it is set, and if not then set
# it.
if (!isset($pview)) {
	$pview = 0;
}

function print_header($day, $month, $year, $area)
{
	global $mrbs_company, $search_str, $locale_warning;

	# If we dont know the right date then make it up 
	if(!$day)
		$day   = date("d");
	if(!$month)
		$month = date("m");
	if(!$year)
		$year  = date("Y");
	if (empty($search_str))
		$search_str = "";

	if ($unicode_encoding)
	{
		header("Content-Type: text/html; charset=utf-8");
	}
	else
	{
		# We use $vocab directly instead of get_vocab() because we have
		# no requirement to convert the vocab text, we just output
		# the charset
		header("Content-Type: text/html; charset=".$vocab["charset"]);
	}

	header("Pragma: no-cache");                          // HTTP 1.0
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
                      "http://www.w3.org/TR/html4/loose.dtd">
<HTML>
  <HEAD>
<?php
   include "style.inc";
?>
    <TITLE><?php echo get_vocab("mrbs") ?></TITLE>
    <script type="text/javascript">

<!-- Begin

/*   Script inspired by "True Date Selector"
     Created by: Lee Hinder, lee.hinder@ntlworld.com 
     
     Tested with Windows IE 6.0
     Tested with Linux Opera 7.21, Mozilla 1.3, Konqueror 3.1.0
     
*/

function daysInFebruary (year){
  // February has 28 days unless the year is divisible by four,
  // and if it is the turn of the century then the century year
  // must also be divisible by 400 when it has 29 days
  return (((year % 4 == 0) && ( (!(year % 100 == 0)) || (year % 400 == 0))) ? 29 : 28 );
}

//function for returning how many days there are in a month including leap years
function DaysInMonth(WhichMonth, WhichYear)
{
  var DaysInMonth = 31;
  if (WhichMonth == "4" || WhichMonth == "6" || WhichMonth == "9" || WhichMonth == "11")
    DaysInMonth = 30;
  if (WhichMonth == "2")
    DaysInMonth = daysInFebruary( WhichYear );
  return DaysInMonth;
}

//function to change the available days in a months
function ChangeOptionDays(formObj, prefix)
{
  var DaysObject = eval("formObj." + prefix + "day");
  var MonthObject = eval("formObj." + prefix + "month");
  var YearObject = eval("formObj." + prefix + "year");

  if (DaysObject.selectedIndex && DaysObject.options)
    { // The DOM2 standard way
    // alert("The DOM2 standard way");
    var DaySelIdx = DaysObject.selectedIndex;
    var Month = parseInt(MonthObject.options[MonthObject.selectedIndex].value);
    var Year = parseInt(YearObject.options[YearObject.selectedIndex].value);
    }
  else if (DaysObject.selectedIndex && DaysObject[DaysObject.selectedIndex])
    { // The legacy MRBS way
    // alert("The legacy MRBS way");
    var DaySelIdx = DaysObject.selectedIndex;
    var Month = parseInt(MonthObject[MonthObject.selectedIndex].value);
    var Year = parseInt(YearObject[YearObject.selectedIndex].value);
    }
  else if (DaysObject.value)
    { // Opera 6 stores the selectedIndex in property 'value'.
    // alert("The Opera 6 way");
    var DaySelIdx = parseInt(DaysObject.value);
    var Month = parseInt(MonthObject.options[MonthObject.value].value);
    var Year = parseInt(YearObject.options[YearObject.value].value);
    }

  // alert("Day="+(DaySelIdx+1)+" Month="+Month+" Year="+Year);

  var DaysForThisSelection = DaysInMonth(Month, Year);
  var CurrentDaysInSelection = DaysObject.length;
  if (CurrentDaysInSelection > DaysForThisSelection)
  {
    for (i=0; i<(CurrentDaysInSelection-DaysForThisSelection); i++)
    {
      DaysObject.options[DaysObject.options.length - 1] = null
    }
  }
  if (DaysForThisSelection > CurrentDaysInSelection)
  {
    for (i=0; i<DaysForThisSelection; i++)
    {
      DaysObject.options[i] = new Option(eval(i + 1));
    }
  }
  if (DaysObject.selectedIndex < 0) DaysObject.selectedIndex = 0;
  if (DaySelIdx >= DaysForThisSelection)
    DaysObject.selectedIndex = DaysForThisSelection-1;
  else
    DaysObject.selectedIndex = DaySelIdx;
}

  //  End -->
    </script>
  </HEAD>
  <BODY BGCOLOR="#ffffed" TEXT=black LINK="#5B69A6" VLINK="#5B69A6" ALINK=red>
	   <?php if ( $GLOBALS["pview"] != 1 ) { ?>

   <?php # show a warning if this is using a low version of php
       if (substr(phpversion(), 0, 1) == 3)
	       echo get_vocab("not_php3");
       if (!empty($locale_warning))
               echo "[Warning: ".$locale_warning."]";
   ?>

    <table WIDTH="100%">
      <TR>
        <TD BGCOLOR="#5B69A6">
          <table WIDTH="100%" BORDER=0>
            <TR>
              <TD CLASS="banner" BGCOLOR="#C0E0FF">
                <FONT SIZE=4>
                  <B><?php echo $mrbs_company ?></B><br>
                  <a href="index.php"><?php echo get_vocab("mrbs") ?></a>
                </FONT>
              </TD>
              <TD CLASS="banner" BGCOLOR="#C0E0FF">
                <FORM ACTION="day.php" METHOD=GET name="Form1">
                  <FONT SIZE=2>
<?php
   genDateSelector("", $day, $month, $year); // Note: The 1st arg must match the last arg in the call to ChangeOptionDays below.
   if (!empty($area))
        echo "
                    <INPUT TYPE=HIDDEN NAME=area VALUE=$area>\n"
 
?>
	            <script type="text/javascript">
                    <!--
                    // fix number of days for the $month/$year that you start with
                    ChangeOptionDays(document.Form1, ''); // Note: The 2nd arg must match the first in the call to genDateSelector above.
                    // -->
                    </script>
                    <INPUT TYPE=SUBMIT VALUE="<?php echo get_vocab("goto") ?>">
                  </FONT>
                </FORM>
              </TD>
              <TD CLASS="banner">
                <A HREF="help.php?day=<?php echo $day ?>&amp;month=<?php echo $month ?>&amp;year=<?php echo $year ?>"><?php echo get_vocab("help") ?></A>
              </TD>
              <TD CLASS="banner">
                <A HREF="admin.php?day=<?php echo $day ?>&amp;month=<?php echo $month ?>&amp;year=<?php echo $year ?>"><?php echo get_vocab("admin") ?></A>
              </TD>
              <TD CLASS="banner">
                <A HREF="report.php"><?php echo get_vocab("report") ?></A>
              </TD>
              <TD CLASS="banner">
                <FORM METHOD=GET ACTION="search.php">
                  <FONT SIZE=2>
                    <A HREF="search.php?advanced=1"><?php echo get_vocab("search") ?>:</A>
                  </FONT>
                  <INPUT TYPE=TEXT   NAME="search_str" VALUE="<?php echo $search_str ?>" SIZE=10>
                  <INPUT TYPE=HIDDEN NAME=day        VALUE="<?php echo $day        ?>"        >
                  <INPUT TYPE=HIDDEN NAME=month      VALUE="<?php echo $month      ?>"        >
                  <INPUT TYPE=HIDDEN NAME=year       VALUE="<?php echo $year       ?>"        >
<?php
   if (!empty($area))
        echo "
                  <INPUT TYPE=HIDDEN NAME=area VALUE=$area>\n"
?>
                </FORM>
              </TD>
<?php
    # For session protocols that define their own logon box...
    if (function_exists('PrintLogonBox'))
   	{
   	PrintLogonBox();
   	}
?>
            </TR>
          </table>
        </TD>
      </TR>
    </table>
<?php } ?>
<?php
}

function toTimeString(&$dur, &$units)
{
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
						$units = get_vocab("years");
					}
					else
						$units = get_vocab("weeks");
				}
				else
					$units = get_vocab("days");
			}
			else
				$units = get_vocab("hours");
		}
		else
			$units = get_vocab("minutes");
	}
	else
		$units = get_vocab("seconds");
}


function toPeriodString($start_period, &$dur, &$units)
{
	global $enable_periods;
        global $periods;

        $max_periods = count($periods);

	$dur /= 60;

        if( $dur >= $max_periods || $start_period == 0 )
        {
                if( $start_period == 0 && $dur == $max_periods )
                {
                        $units = get_vocab("days");
                        $dur = 1;
                        return;
                }

                $dur /= 60;
                if(($dur >= 24) && is_int($dur))
                {
                	$dur /= 24;
			$units = get_vocab("days");
                        return;
                }
                else
                {
			$dur *= 60;
                        $dur = ($dur % $max_periods) + floor( $dur/(24*60) ) * $max_periods;
                        $units = get_vocab("periods");
                        return;
		}
        }
        else
		$units = get_vocab("periods");
}



function genDateSelector($prefix, $day, $month, $year)
{
	if($day   == 0) $day = date("d");
	if($month == 0) $month = date("m");
	if($year  == 0) $year = date("Y");
	
	echo "
                  <SELECT NAME=\"${prefix}day\">";
	
	for($i = 1; $i <= 31; $i++)
		echo "
                    <OPTION" . ($i == $day ? " SELECTED" : "") . ">$i";

	echo "
                  </SELECT>

                  <SELECT NAME=\"${prefix}month\" onchange=\"ChangeOptionDays(this.form,'$prefix')\">";

	for($i = 1; $i <= 12; $i++)
	{
		$m = utf8_strftime("%b", mktime(0, 0, 0, $i, 1, $year));
		
		print "
                    <OPTION VALUE=\"$i\"" . ($i == $month ? " SELECTED" : "") . ">$m";
	}

	echo "
                  </SELECT>
	          <SELECT NAME=\"${prefix}year\" onchange=\"ChangeOptionDays(this.form,'$prefix')\">";

	$min = min($year, date("Y")) - 5;
	$max = max($year, date("Y")) + 5;

	for($i = $min; $i <= $max; $i++)
		print "
                    <OPTION VALUE=\"$i\"" . ($i == $year ? " SELECTED" : "") . ">$i";

	echo "
                  </SELECT>";
}

# Error handler - this is used to display serious errors such as database
# errors without sending incomplete HTML pages. This is only used for
# errors which "should never happen", not those caused by bad inputs.
# If $need_header!=0 output the top of the page too, else assume the
# caller did that. Alway outputs the bottom of the page and exits.
function fatal_error($need_header, $message)
{
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
	global $tbl_area;
	$area = sql_query1("SELECT id FROM $tbl_area ORDER BY area_name LIMIT 1");
	return ($area < 0 ? 0 : $area);
}

# Return a default room given a valid area; used if no room is already known.
# This returns the first room in alphbetic order in the database.
# This could be changed to implement something like per-user defaults.
function get_default_room($area)
{
	global $tbl_room;
	$room = sql_query1("SELECT id FROM $tbl_room WHERE area_id=$area ORDER BY room_name LIMIT 1");
	return ($room < 0 ? 0 : $room);
}

# Get the local day name based on language. Note 2000-01-02 is a Sunday.
function day_name($daynumber)
{
	return utf8_strftime("%A", mktime(0,0,0,1,2+$daynumber,2000));
}

function hour_min_format()
{
        global $twentyfourhour_format;
        if ($twentyfourhour_format)
	{
  	        return "%H:%M";
	}
	else
	{
		return "%I:%M%p";
	}
}

function period_date_string($t, $mod_time=0)
{
        global $periods;

	$time = getdate($t);
        $p_num = $time["minutes"] + $mod_time;
        if( $p_num < 0 ) $p_num = 0;
        if( $p_num >= count($periods) - 1 ) $p_num = count($periods ) - 1;
	# I have made the separater a ',' as a '-' leads to an ambiguious
	# display in report.php when showing end times.
        return array($p_num, $periods[$p_num] . utf8_strftime(", %A %d %B %Y",$t));
}

function period_time_string($t, $mod_time=0)
{
        global $periods;

	$time = getdate($t);
        $p_num = $time["minutes"] + $mod_time;
        if( $p_num < 0 ) $p_num = 0;
        if( $p_num >= count($periods) - 1 ) $p_num = count($periods ) - 1;
        return $periods[$p_num];
}

function time_date_string($t)
{
        global $twentyfourhour_format;

        if ($twentyfourhour_format)
	{
  	        return utf8_strftime("%H:%M:%S - %A %d %B %Y",$t);
	}
	else
	{
	        return utf8_strftime("%I:%M:%S%p - %A %d %B %Y",$t);
	}
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
	for ($ct = "A"; $ct <= "Z"; $ct++)
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
function round_t_down($t, $resolution, $am7)
{
        return (int)$t - (int)abs(((int)$t-(int)$am7)
				  % $resolution);
}

# Round time up to the nearest resolution
function round_t_up($t, $resolution, $am7)
{
	if (($t-$am7) % $resolution != 0)
	{
		return $t + $resolution - abs(((int)$t-(int)
					       $am7) % $resolution);
	}
	else
	{
		return $t;
	}
}

# generates some html that can be used to select which area should be
# displayed.
function make_area_select_html( $link, $current, $year, $month, $day )
{
	global $tbl_area;
	$out_html = "
<form name=\"areaChangeForm\" method=get action=\"$link\">
  <select name=\"area\" onChange=\"document.areaChangeForm.submit()\">";

	$sql = "select id, area_name from $tbl_area order by area_name";
   	$res = sql_query($sql);
   	if ($res) for ($i = 0; ($row = sql_row($res, $i)); $i++)
   	{
		$selected = ($row[0] == $current) ? "selected" : "";
		$out_html .= "
    <option $selected value=\"".$row[0]."\">" . htmlspecialchars($row[1]);
   	}
	$out_html .= "
  </select>

  <INPUT TYPE=HIDDEN NAME=day        VALUE=\"$day\">
  <INPUT TYPE=HIDDEN NAME=month      VALUE=\"$month\">
  <INPUT TYPE=HIDDEN NAME=year       VALUE=\"$year\">
  <input type=submit value=\"".get_vocab("change")."\">
</form>\n";

	return $out_html;
} # end make_area_select_html

function make_room_select_html( $link, $area, $current, $year, $month, $day )
{
	global $tbl_room;
	$out_html = "
<form name=\"roomChangeForm\" method=get action=\"$link\">
  <select name=\"room\" onChange=\"document.roomChangeForm.submit()\">";

	$sql = "select id, room_name from $tbl_room where area_id=$area order by room_name";
   	$res = sql_query($sql);
   	if ($res) for ($i = 0; ($row = sql_row($res, $i)); $i++)
   	{
		$selected = ($row[0] == $current) ? "selected" : "";
		$out_html .= "
    <option $selected value=\"".$row[0]."\">" . htmlspecialchars($row[1]);
   	}
	$out_html .= "
  </select>
  <INPUT TYPE=HIDDEN NAME=day        VALUE=\"$day\"        >
  <INPUT TYPE=HIDDEN NAME=month      VALUE=\"$month\"        >
  <INPUT TYPE=HIDDEN NAME=year       VALUE=\"$year\"      >
  <INPUT TYPE=HIDDEN NAME=area       VALUE=\"$area\"         >
  <input type=submit value=\"".get_vocab("change")."\">
</form>\n";

	return $out_html;
} # end make_area_select_html


# This will return the appropriate value for isdst for mktime().
# The order of the arguments was chosen to match those of mktime.
# hour is added so that this function can when necessary only be
# run if the time is between midnight and 3am (all DST changes
# occur in this period.
function is_dst ( $month, $day, $year, $hour="-1" )
{

	if( $hour != -1  && $hour > 3)
		return( -1 );
	
	# entering DST
	if( !date( "I", mktime(12, 0, 0, $month, $day-1, $year)) && 
	    date( "I", mktime(12, 0, 0, $month, $day, $year)))
		return( 0 ); 

	# leaving DST
	elseif( date( "I", mktime(12, 0, 0, $month, $day-1, $year)) && 
	    !date( "I", mktime(12, 0, 0, $month, $day, $year)))
		return( 1 );
	else
		return( -1 );
}

# if crossing dst determine if you need to make a modification
# of 3600 seconds (1 hour) in either direction
function cross_dst ( $start, $end )
{
	
	# entering DST
	if( !date( "I", $start) &&  date( "I", $end))
		$modification = -3600;

	# leaving DST
	elseif(  date( "I", $start) && !date( "I", $end))
		$modification = 3600;
	else
		$modification = 0;

	return $modification;
}
?>
