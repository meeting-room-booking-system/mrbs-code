<?php

// $Id$


// Print the page header
function print_theme_header($day, $month, $year, $area, $room)
{
  global $mrbs_company, $mrbs_company_logo, $mrbs_company_url, $mrbs_company_more_info,
         $search_str, $locale_warning;
  global $tbl_entry, $tbl_room, $tbl_area;
  global $PHP_SELF, $HTTP_HOST, $QUERY_STRING;
  global $view_week_number, $weekstarts, $times_along_top, $periods, $enable_periods;
  global $auth, $max_level;
  global $default_language_tokens, $disable_automatic_language_changing, $override_locale;
  global $lang_map_windows, $langs, $server_os;
  global $select_options;
  global $ajax_refresh_rate;
  global $main_table_cell_border_width, $main_cell_height;
  global $timetohighlight;
  
  $page = basename($PHP_SELF, ".php");
  $user = getUserName();
  $is_admin = (authGetUserLevel($user) >= $max_level);
  
  // Need to set the timezone before we can use date()
  get_area_settings($area);

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
  if (!isset($search_str))
  {
    $search_str = "";
  }
  
  header("Content-Type: text/html; charset=" . get_charset());
  // We want to prevent IE8 offering "Compatability View" as that really
  // messes up MRBS, especially on the Report page
  header("X-UA-Compatible: IE=Edge");
  header("Pragma: no-cache");                          // HTTP 1.0
  header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
  echo DOCTYPE;
?>

<html>
  <head>
  
  <title><?php echo get_vocab("mrbs") ?></title>
  <meta charset="<?php echo get_charset(); ?>">
  
  <?php
  require_once "style.inc";
  require_once "js.inc";
  ?>
  
  </head>
  
  <?php
  // Put the filename in as a class to aid styling.   
  // (Use a class rather than id to avoid specificity problems)
  echo "<body class=\"non_js ".htmlspecialchars($page)."\">\n";
   
    // Add a class of "js" so that we know if we're using JavaScript or not
    // and remove the non_js class (it's sometimes useful to know that we're
    // not running JavaScript)
  ?>
    <script type="text/javascript">
      //<![CDATA[
      $('body').addClass('js').removeClass('non_js');
      //]]>
    </script> 

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
            // Suppress error messages in case the logo is a URL and allow_url_fopen
            // is not enabled in php.ini
            $logo_size = @getimagesize($mrbs_company_logo);
            echo "<img src=\"$mrbs_company_logo\" " . $logo_size[3] . " alt=\"$mrbs_company\">\n";
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
            // Give the form id as the optional fifth parameter because we want
            // the form to be automatically submitted when the datepicker is closed
            genDateSelector("", $day, $month, $year, "Form1");
            if (!empty($area))
            {
              echo "<input type=\"hidden\" name=\"area\" value=\"$area\">\n";
            }
            if (!empty($room))
            {
              echo "<input type=\"hidden\" name=\"room\" value=\"$room\">\n";
            }
            // Although the datepicker will automatically go to the new date when
            // the date is changed, we still need a submit button because there
            // are occasions when you want to go to the date without changing it -
            // for example when you've been on a Search or Report page
            echo "<input type=\"submit\" value=\"" . get_vocab("goto") . "\">\n";
            ?>
           </div>
        </form>
        <?php
        // Provide a link to the list of bookings awaiting approval
        // (if there are any enabled areas where we require bookings to be approved)

        $approval_somewhere = some_area('approval_enabled', TRUE);
        if ($approval_somewhere && (authGetUserLevel($user) >= 1))
        {
          $sql_approval_enabled = some_area_predicate('approval_enabled');
          // Find out how many bookings are awaiting approval
          // (but only for areas where approval is required)
          $sql = "SELECT COUNT(*)
                    FROM $tbl_entry E, $tbl_room R, $tbl_area A
                   WHERE (status&" . STATUS_AWAITING_APPROVAL . " != 0)
                     AND E.room_id = R.id
                     AND R.area_id = A.id
                     AND R.disabled = 0
                     AND A.disabled = 0
                     AND $sql_approval_enabled";
          if (!$is_admin)
          {
            // Ordinary users can only see their own
            $sql .= " AND create_by='" . sql_escape($user) . "'";
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
            <input type="search" name="search_str" value="<?php echo htmlspecialchars($search_str) ?>" required>
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

<div id="contents">
<?php

} // end of print_theme_header()
?>
