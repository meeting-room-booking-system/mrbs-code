<?php

// $Id$

// Print the page header
function print_theme_header($day, $month, $year, $area, $room)
{
  global $mrbs_company, $mrbs_company_logo, $mrbs_company_url, $mrbs_company_more_info,
         $search_str, $locale_warning, $area_defaults;
  global $tbl_entry, $tbl_room, $tbl_area;
  global $PHP_SELF, $view_week_number, $weekstarts;
  global $enable_periods;
  global $default_language_tokens, $disable_automatic_language_changing, $override_locale;
  global $lang_map_windows, $langs;
  
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
    
    <?php
    // We are using the jQuery UI library, which has been custom built to include the following components:
    //   - Core
    //   - Widget
    //   - Mouse
    //   - Draggable
    //   - Datepicker
    // If you need any other components you will need to rebuild the custom UI library
    // See http://jqueryui.com/
    ?>
    
    <script type="text/javascript" src="jquery/jquery-1.4.2.min.js"></script>
    <script type="text/javascript" src="jquery/ui/jquery-ui-1.8.4.custom.min.js"></script>
    <script type="text/javascript" src="jquery/ui/jquery-ui-i18n.js"></script>
    <?php
    // The Portuguese (-pt) file below is a non-standard addition to the regional
    // variants.   The standard package only has a pt-BR variant at the moment.  The
    // file has been kept separate to avoid problems with merging new releases of UI
    ?>
    <script type="text/javascript" src="jquery/ui/jquery-ui-datepicker-pt.js"></script>
    
    <script type="text/javascript">

//<![CDATA[
<?php
// Set the default values for datepicker, including the default regional setting
?>
$(function() {
  <?php
  // We set the regional setting by setting locales in reverse order of priority.
  // If you try and set a datepicker locale that doesn't exist, then nothing is
  // changed and the regional setting stays as it was before.   The reverse order
  // of priority is:
  // - the MRBS default language
  // - locales taken from the browser in increasing order of browser preference
  // - then, if automatic language changing is disabled, 
  //      - the MRBS default language setting again
  //      - the language part of the override_locale
  //      - the full override_locale
  // This algorithm is designed to ensure that datepicker is set to the closest
  // available locale to that specified in the config file.   If automatic language
  // changing is disabled, we fall back to a browser specified locale if the locale
  // in the config file is not available in datepicker.
  
  // Some special cases
  $default_lang = strtolower($default_language_tokens);
  switch ($default_lang)
  {
    case 'en':
      // There is no 'en' localisation!  It is the default.
      $default_lang = '';
      break;
    default:
      break;
  }
  
  echo "$.datepicker.setDefaults($.datepicker.regional['$default_lang']);\n";
  $datepicker_langs = $langs;
  asort($datepicker_langs, SORT_NUMERIC);
  foreach ($datepicker_langs as $lang => $qual)
  {
    // Get the locale in the format that datepicker likes: language lower case
    // and country upper case (xx-XX)
    $datepicker_locale = locale_format($lang, '-');
    echo "$.datepicker.setDefaults($.datepicker.regional['$datepicker_locale']);\n";
  }
  if ($disable_automatic_language_changing)
  {
    // They don't want us to use the browser language, so we'll set the datepicker
    // locale setting back to the default language (as a fall-back) and then we'll
    // try and set it to the override_locale
    echo "$.datepicker.setDefaults($.datepicker.regional['$default_lang']);\n";
    if (!empty($override_locale))
    {
      $server_os = get_server_os();
      if ($server_os == 'windows')
      {
        // If the server is running on Windows we'll have to try and translate the 
        // Windows style locale back into an xx-YY locale
        $datepicker_locale = array_search($override_locale, $lang_map_windows);
      }
      else
      {
        $datepicker_locale = $override_locale;
      }
      if (!empty($datepicker_locale))  // in case the array_search() returned FALSE
      {
        $datepicker_locale = locale_format($datepicker_locale, '-');
        $datepicker_lang = substr($datepicker_locale, 0, 2);
        // First we'll try and get the correct language and then we'll try and
        // overwrite that with the correct country variant
        echo "$.datepicker.setDefaults($.datepicker.regional['$datepicker_lang']);\n";
        echo "$.datepicker.setDefaults($.datepicker.regional['$datepicker_locale']);\n";
      }
    }
  }
  ?>
  $.datepicker.setDefaults({
    showOtherMonths: true,
    selectOtherMonths: true,
    changeMonth: true,
    changeYear: true,
    duration: 'fast',
    showWeek: <?php echo ($view_week_number) ? 'true' : 'false' ?>,
    firstDay: <?php echo $weekstarts ?>,
    altFormat: 'yy-mm-dd',
    onClose: function(dateText, inst) {datepicker_close(dateText, inst);}
  });
});


<?php
// Writes out the day, month and year values to the three hidden inputs
// created by the PHP function genDateSelector().    It gets the date values
// from the _alt input, which is the alternate field populated by datepicker
// and is populated by datepicker with a date in yy-mm-dd format.
//
// (datepicker can only have one alternate field, which is why we need to write
// to the three fields ourselves).
//
// Blur the datepicker input field on close, so that the datepicker will reappear
// if you select it.    (Not quite sure why you need this.  It only seems
// to be necessary when you are using Firefox and the datepicker is draggable).
?>
function datepicker_close(dateText, inst)
{
  var alt_id = inst.id + '_alt';
  var date = document.getElementById(alt_id).value.split('-');
  document.getElementById(alt_id + '_year').value  = date[0];
  document.getElementById(alt_id + '_month').value = date[1];
  document.getElementById(alt_id + '_day').value   = date[2];
  document.getElementById(inst.id).blur();
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


if ($page == 'edit_entry')
{
  // Declare some variables to hold details of the slot selectors.
  // We are going to store the contents of the selectors on page load
  // (when they will be fully populated with options) so that we can
  // rebuild the arrays later
  // Also declare a variable to hold text strings with the current
  // locale translations for periods,minutes, hours, etc.
  ?>
  var nStartOptions;
  var startOptions = new Array();
  var nEndOptions;
  var endOptions = new Array();
  var vocab = new Array();

  function durFormat(r)
  {

    r = r.toFixed(2);
    r = parseFloat(r);
    r = r.toLocaleString();

    while (r.substr(r.length -1) == '0')
    {
      r = r.substr(0, r.length - 1);
    }

    if ((r.substr(r.length -1) == '.') || (r.substr(r.length -1) == ','))
    {
      r = r.substr(0, r.length - 1);
    }
    
    return r;
  }
  
  <?php
  // Returns a string giving the duration having chosen sensible units,
  // translated into the user's language, and formatted the number, taking
  // into account the user's locale.    Note that when using periods one
  // is added to the duration because the model is slightly different
  ?>
  function getDuration(from, to)
  {
    var duration, durUnits, text;
    
    durUnits = '<?php echo ($enable_periods) ? "periods" : "minutes" ?>';
    duration = Math.floor((to - from) / 60);
    <?php
    if ($enable_periods)
    {
      ?>
      duration++;
      <?php
    }
    else
    {
      ?>
      if (duration >= 60)
      {
        durUnits = "hours";
        duration = durFormat(duration/60);
      }
      <?php
    }
    ?>
    text = duration + ' ' + vocab[durUnits];
    return text;
  }
  
  function adjustSlotSelectors(form)
  {
    <?php
    // the end slot options cannot start before the currently
    // selected start option.   When we are using periods the booking
    // model is different and you are allowed to have the start and 
    // end period the same
    ?>
    var startIndex = form.start_seconds.selectedIndex;
    var startValue = parseInt(form.start_seconds.options[startIndex].value);
    var endIndex = form.end_seconds.selectedIndex;
    var endValue = parseInt(form.end_seconds.options[endIndex].value);
    var text, durationText, isSelected, i, j;
    var nbsp = '\u00A0';
 
    while (form.start_seconds.options.length > 0)
    {
      form.start_seconds.remove(0);
    }

    i = 0;
    while ((i < nStartOptions) && (startOptions[i]['value'] <?php echo ($enable_periods) ? "<=" : "<"?> endValue))
    {
      isSelected = (startOptions[i]['value'] == startValue);
      durationText = getDuration(startOptions[i]['value'], endValue);
      text = startOptions[i]['text'];
      if (!isSelected)
      {
        text = text + nbsp + nbsp + '(' + durationText + ')';
      }
      form.start_seconds.options[i] = new Option(text, startOptions[i]['value'], false, isSelected);
      i++;
    }
    
    while (form.end_seconds.options.length > 0)
    {
      form.end_seconds.remove(0);
    }

    j = 0;
    for (i = 0; i < nEndOptions; i++)
    {
      if (endOptions[i]['value'] <?php echo ($enable_periods) ? ">=" : ">"?> startValue)
      {
        isSelected = (endOptions[i]['value'] == endValue);
        durationText = getDuration(startValue, endOptions[i]['value']);
        text = endOptions[i]['text'] + nbsp + nbsp + '(' + durationText + ')';
        form.end_seconds.options[j] = new Option(text, endOptions[i]['value'], false, isSelected);
        j++;
      }
    }
    
  }
<?php
}


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
  // (1) put the booking name field in focus (but only for new bookings,
  // ie when the field is empty:  if it's a new booking you have to
  // complete that field, but if it's an existing booking you might
  // want to edit any field)
  // (2) Adjust the slot selectors
  if ($page == 'edit_entry')
  {
  ?>
  var form = document.getElementById('main');
  if (form && form.name && (form.name.value.length == 0))
  {
    form.name.focus();
  }
  <?php
  // Get the current vocab (in the appropriate language) for periods,
  // minutes, hours
  ?>
  vocab['periods'] = '<?php echo get_vocab("periods") ?>';
  vocab['minutes'] = '<?php echo get_vocab("minutes") ?>';
  vocab['hours'] = '<?php echo get_vocab("hours") ?>';
  vocab['days'] = '<?php echo get_vocab("days") ?>';
  <?php
  // Get the details of the start and end slot selectors now since
  // they are fully populated with options.  We can then use the details
  // to rebuild the selectors later on
  ?>
  nStartOptions = form.start_seconds.options.length;
  for (var i=0; i < nStartOptions; i++)
  {
    startOptions[i] = new Array();
    startOptions[i]['text'] = form.start_seconds.options[i].text;
    startOptions[i]['value'] = parseInt(form.start_seconds.options[i].value);
  }
  
  nEndOptions = form.end_seconds.options.length;
  for (var i=0; i < nEndOptions; i++)
  {
    endOptions[i] = new Array();
    endOptions[i]['text'] = form.end_seconds.options[i].text;
    endOptions[i]['value'] = parseInt(form.end_seconds.options[i].value);
  }
  
  adjustSlotSelectors(form);
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
            genDateSelector("", $day, $month, $year);
            if (!empty($area))
            {
              echo "<input type=\"hidden\" name=\"area\" value=\"$area\">\n";
            }
            if (!empty($room))
            {
              echo "<input type=\"hidden\" name=\"room\" value=\"$room\">\n";
            }
  
            ?>
            <input type="submit" value="<?php echo get_vocab("goto") ?>">
          </div>
        </form>
        <?php
        // Provide a link to the list of outstanding provisional bookings
        // (if there are any areas where we are using provisional bookings)
        $user = getUserName();
        // Build the SQL condition for evaluating whether provisional booking is
        // enabled for an area.   It is enabled if the field is set, or if it's 
        // not set but the default area setting is for it to be enabled.
        $sql_provisional_enabled = "(provisional_enabled IS NOT NULL AND provisional_enabled > 0)";
        if ($area_defaults['provisional_enabled'])
        {
          $sql_provisional_enabled = "(" . $sql_provisional_enabled . " OR (provisional_enabled IS NULL))";
        }
        
        $sql = "SELECT COUNT(*)
                        FROM $tbl_area
                       WHERE $sql_provisional_enabled
                       LIMIT 1";
        $provisional_somewhere = (sql_query1($sql) > 0);
        if ($provisional_somewhere && (authGetUserLevel($user) >= 1))
        {
          $is_admin = (authGetUserLevel($user) >= 2);
          // Find out how many provisional bookings there are
          // (but only for areas where provisional bookings are enabled)
          $sql = "SELECT COUNT(*)
                    FROM $tbl_entry E, $tbl_room R, $tbl_area A
                   WHERE status=" . STATUS_PROVISIONAL . "
                     AND E.room_id = R.id
                     AND R.area_id = A.id
                     AND $sql_provisional_enabled";
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
      echo "<a href=\"admin.php?$query_str\">" . get_vocab("rooms") . "</a>\n";
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
