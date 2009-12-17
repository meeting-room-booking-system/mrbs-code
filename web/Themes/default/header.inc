<?php

// $Id$

// Print the page header
function print_theme_header($day, $month, $year, $area, $room)
{
  global $mrbs_company, $mrbs_company_logo, $mrbs_company_url, $mrbs_company_more_info,
         $search_str, $locale_warning, $provisional_enabled;
  global $tbl_entry;
  global $PHP_SELF;
  
  $page = basename($PHP_SELF, ".php" );

  // If we dont know the right date then make it up 
  if (!$day)
  {
    $day   = date("d");
  }
  if (!$month)
  {
    $month = date("m");
  }
  if (!$year)
  {
    $year  = date("Y");
  }
  if (empty($search_str))
  {
    $search_str = "";
  }
  
  header("Content-Type: text/html; charset=" . get_charset());
  header("Pragma: no-cache");                          // HTTP 1.0
  header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
  echo DOCTYPE;
?>

<html>
  <head>
<?php
  require_once "style.inc";
?>
    <title><?php echo get_vocab("mrbs") ?></title>
    <script type="text/javascript">

//<![CDATA[

/*   Script inspired by "True Date Selector"
     Created by: Lee Hinder, lee.hinder@ntlworld.com 
     
     Tested with Windows IE 6.0
     Tested with Linux Opera 7.21, Mozilla 1.3, Konqueror 3.1.0
     
*/

function daysInFebruary (year)
{
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
  {
    DaysInMonth = 30;
  }
  if (WhichMonth == "2")
  {
    DaysInMonth = daysInFebruary( WhichYear );
  }
  return DaysInMonth;
}

//function to change the available days in a months
function ChangeOptionDays(formObj, prefix)
{
  var DaysObject = eval("formObj." + prefix + "day");
  var MonthObject = eval("formObj." + prefix + "month");
  var YearObject = eval("formObj." + prefix + "year");

  if (typeof DaysObject.selectedIndex == 'number' && DaysObject.options)
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
  if (DaysObject.selectedIndex < 0)
  {
    DaysObject.selectedIndex = 0;
  }
  if (DaySelIdx >= DaysForThisSelection)
  {
    DaysObject.selectedIndex = DaysForThisSelection-1;
  }
  else
  {
    DaysObject.selectedIndex = DaySelIdx;
  }
}


<?php
if ($page == 'edit_area_room')
{
?>
  // disable the min and max book ahead values and units if the corresponding
  // checkbox isn't checkd;  and enable it it is.
  function check_book_ahead()
  {
    var form = document.getElementById('edit_area');
    
    if (form)
    {
      if (form.area_min_ba_enabled)
      {
        form.area_min_ba_value.disabled = !form.area_min_ba_enabled.checked;
        form.area_min_ba_units.disabled = !form.area_min_ba_enabled.checked;
      }
      if (form.area_max_ba_enabled)
      {
        form.area_max_ba_value.disabled = !form.area_max_ba_enabled.checked;
        form.area_max_ba_units.disabled = !form.area_max_ba_enabled.checked;
      }
    }
  }
<?php
} // edit_area_room


if ($page == 'pending')
{
?>
  // test whether array contains element
  function contains(array, element)
  {
    for (var i=0; i<array.length; i++)
    {
      if (array[i] == element)
      {
        return true;
      }
    }
    return false;
  }
  
  // Remove duplicates from an array
  function unique(array)
  {
    temp = new Array();
    for (var i=0; i<array.length; i++)
    {
      if (!contains(temp, array[i]))
      {
        temp.push(array[i]);
      }
    }
    return temp;
  }
  
  // Toggle a sub-table in the pending_list table to make it maximised/minimised
  function toggle_table(id)
  { 
    var table = document.getElementById(id);
    if (table.className == 'minimised')
    {
      table.className = 'maximised';
      table.firstChild.firstChild.firstChild.innerHTML = '-';
    }
    else
    {
      table.className = 'minimised';
      table.firstChild.firstChild.firstChild.innerHTML = '+';
    }
  }

  
  // Put a + control in the first cell of all the series headers on the pending.php page
  // and minimise the tables (except for those tables listed in the cookie)
  function activate_sub_tables()
  {
    // Go through each sub-table and minimise it
    var tables = document.getElementsByTagName('table');
    for (var i = 0; (element = tables[i]) != null; i++)
    {
      if (tables[i].className.indexOf('maximised') >= 0)
      {
        tables[i].className = 'minimised';
        tables[i].firstChild.firstChild.firstChild.innerHTML = '+'; 
      } 
    }
     
    // now make the table visible (if it's there at all - which it won't be when you 
    // log off from pending.php
    if (document.getElementById('pending_list'))
    {
      document.getElementById('pending_list').style.visibility = 'visible';
    }
  }
  
  
  
<?php
} // pending
?>

// actions to be taken on page load
function init()
{
  <?php // ALL PAGES ?>
  // if there's a logon box, set the username input field in focus
  var logonForm = document.getElementById('logon');
  if (logonForm && logonForm.NewUserName)
  {
    logonForm.NewUserName.focus();
  }
  
  <?php
  // EDIT_AREA_ROOM.PHP
  if ($page == 'edit_area_room')
  {
  ?>
    check_book_ahead();
  <?php
  }
  
  // EDIT_ENTRY.PHP
  // put the booking name field in focus (but only for new bookings,
  // ie when the field is empty:  if it's a new booking you have to
  // complete that field, but if it's an existing booking you might
  // want to edit any field)
  if ($page == 'edit_entry')
  {
  ?>
  var form = document.getElementById('main');
  if (form && form.name && (form.name.value.length == 0))
  {
    form.name.focus();
  }
  <?php
  }
  
  // PENDING.PHP
  if ($page == 'pending')
  {
  ?>
    activate_sub_tables();
  <?php
  }

  // SEARCH.PHP
  // put the search string field in focus
  if ($page == 'search')
  {
  ?>
  var form = document.getElementById('search_form');
  if (form && form.search_str)
  {
    form.search_str.focus();
  }
  <?php
  }
  ?>
}


//]]>
    </script>
  </head>
  <?php
  // Put the filename in as a class to aid styling.   
  // (Use a class rather than id to avoid specificity problems)
  echo "<body class=\"$page\" onLoad =\"init()\">\n";
  ?>
    <div class="screenonly">

<?php // show a warning if this is using a low version of php
  if (substr(phpversion(), 0, 1) == 3)
  {
    echo "<h1>" . get_vocab("not_php3") . "</h1>\n";
  }
  if (!empty($locale_warning))
  {
    echo "[Warning: ".$locale_warning."]";
  }
?>

  <table id="banner">
    <tr>
      <td id="company">
        <div>
          <?php
          echo "<div id=\"logo\">\n";
          if (!empty($mrbs_company_url))
          {
            echo "<a href=\"$mrbs_company_url\">\n";
          }
          if (empty($mrbs_company_logo))
          {
            echo "<span>$mrbs_company</span>\n";
          }
          else
          {
            $logo_size = getimagesize($mrbs_company_logo);
            echo "<img src=\"$mrbs_company_logo\" " . $logo_size[3] . " alt=\"logo\">\n";
          }
          if (!empty($mrbs_company_url))
          {
            echo "</a>\n";
          }
          echo "</div>\n";
          if (!empty($mrbs_company_more_info))
          {
            echo "<div id=\"more_info\">$mrbs_company_more_info</div>\n";
          }
          ?>
          <div id="mrbs">
            <a href="index.php"><?php echo get_vocab("mrbs") ?></a>
          </div>
        </div>
      </td>
      <td>
        <form action="day.php" method="get" id="Form1">
          <div>
            <?php
            genDateSelector("", $day, $month, $year); // Note: The 1st arg must match the last arg in the call to ChangeOptionDays below.
            if (!empty($area))
            {
              echo "<input type=\"hidden\" name=\"area\" value=\"$area\">\n";
            }
            if (!empty($room))
            {
              echo "<input type=\"hidden\" name=\"room\" value=\"$room\">\n";
            }
  
            ?>
            <script type="text/javascript">
              //<![CDATA[
              // fix number of days for the $month/$year that you start with
              ChangeOptionDays(document.getElementById('Form1'), ''); // Note: The 2nd arg must match the first in the call to genDateSelector above.
              //]]>
            </script>
            <input type="submit" value="<?php echo get_vocab("goto") ?>">
          </div>
        </form>
        <?php
        // Provide a link to the list of outstanding provisional bookings
        $user = getUserName();
        if ($provisional_enabled && (authGetUserLevel($user) >= 1))
        {
          $is_admin = (authGetUserLevel($user) >= 2);
          // Find out how many provisional bookings there are
          $sql = "SELECT COUNT(*) FROM $tbl_entry WHERE status=" . STATUS_PROVISIONAL;
          if (!$is_admin)
          {
            // Ordinary users can only see their own
            $sql .= " AND create_by='" . addslashes($user) . "'";
          }
          $n_outstanding = sql_query1($sql);
          echo "<div id=\"n_outstanding\"" .
               (($n_outstanding > 0) ? " class=\"outstanding\"" : '') .
               ">\n";
          echo "<a href=\"pending.php?day=$day&amp;month=$month&amp;year=$year&amp;area=$area" . 
               ((!empty($room)) ? "&amp;room=$room" : "") . 
               "\">$n_outstanding " . get_vocab("outstanding") . "</a>\n";
          echo "</div>\n";
        }
        ?>
      </td>
      <?php
      $query_str = "day=$day&amp;month=$month&amp;year=$year";
      if (!empty($area))
      {
        $query_str .= "&amp;area=$area";
      }
      if (!empty($room))
      {
        $query_str .= "&amp;room=$room";
      }
      
      echo "<td>\n";
      echo "<a href=\"help.php?$query_str\">" . get_vocab("help") . "</a>\n";
      echo "</td>\n";
      
      echo "<td>\n";
      echo "<a href=\"admin.php?$query_str\">" . get_vocab("admin") . "</a>\n";
      echo "</td>\n";
      
      echo "<td>\n";
      echo "<a href=\"report.php?$query_str\">" . get_vocab("report") . "</a>\n";
      echo "</td>\n";
      ?>
      
      <td>
        <form id="header_search" method="get" action="search.php">
          <div>
            <a href="search.php?advanced=1"><?php echo get_vocab("search") ?>:</a>
            <input type="text"   name="search_str" value="<?php echo $search_str ?>">
            <input type="hidden" name="day"        value="<?php echo $day        ?>">
            <input type="hidden" name="month"      value="<?php echo $month      ?>">
            <input type="hidden" name="year"       value="<?php echo $year       ?>">
            <?php
            if (!empty($area))
            {
              echo "<input type=\"hidden\" name=\"area\" value=\"$area\">\n";
            }
            if (!empty($room))
            {
              echo "<input type=\"hidden\" name=\"room\" value=\"$room\">\n";
            }
            ?>
          </div>
        </form>
      </td>
      <?php
      // For session protocols that define their own logon box...
      if (function_exists('PrintLogonBox'))
      {
        echo "<td>\n";
        echo "<div id=\"logon_box\">\n";
        PrintLogonBox(); 
        echo "</div>\n";
        echo "</td>\n";
      }
      ?>
    </tr>
  </table>
</div>
<?php

} // end of print_theme_header()
?>
