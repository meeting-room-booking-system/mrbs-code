<?php
namespace MRBS;


function print_head($simple=false)
{
  echo "<head>\n";
  echo "<meta charset=\"" . get_charset() . "\">\n";
  echo "<title>" . get_vocab("mrbs") . "</title>\n";
  
  require_once "style.inc";
  
  if (!$simple)
  {
    require_once "js.inc";
  }
  
  echo "</head>\n";
}


// Print the basic site information.   This function is used for all headers, including
// the simple header, and so mustn't require any database access.
function print_header_site_info()
{
  global $mrbs_company,
         $mrbs_company_url,
         $mrbs_company_logo,
         $mrbs_company_more_info;
  
  echo "<div id=\"company\">\n";
  
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

  echo "<div id=\"mrbs\">\n";
  echo "<a href=\"index.php\">" . get_vocab("mrbs") . "</a>\n";
  echo "</div>\n";
  
  echo "</div>\n";
}


function print_goto_date($day, $month, $year, $area, $room)
{
  $user = getUserName();
  
  echo "<form action=\"day.php\" method=\"get\" id=\"Form1\">\n";

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

  echo "</form>\n";
  
  // Provide a link to the list of bookings awaiting approval
  // (if there are any enabled areas where we require bookings to be approved)
  $approval_somewhere = some_area('approval_enabled', TRUE);
  if ($approval_somewhere && (authGetUserLevel($user) >= 1))
  {
    $n_outstanding = get_entries_n_outstanding($user);
    echo "<div id=\"n_outstanding\"" .
         (($n_outstanding > 0) ? " class=\"outstanding\"" : '') .
         ">\n";
    echo "<a href=\"pending.php?day=$day&amp;month=$month&amp;year=$year&amp;area=$area" . 
         ((!empty($room)) ? "&amp;room=$room" : "") . 
         "\">$n_outstanding " . get_vocab("outstanding") . "</a>\n";
    echo "</div>\n";
  }
}


function print_help($query_string)
{
  echo "<a href=\"help.php?$query_string\">" . get_vocab("help") . "</a>\n";
}


function print_rooms($query_string)
{
  echo "<a href=\"admin.php?$query_string\">" . get_vocab("rooms") . "</a>\n";
}


function print_report($query_string)
{
  echo "<a href=\"report.php?$query_string\">" . get_vocab("report") . "</a>\n";
}


function print_search($day, $month, $year, $area, $room, $search_str = '')
{
  echo "<form id=\"header_search\" method=\"get\" action=\"search.php\">\n";
  echo "<div>\n";
  echo "<a href=\"search.php?advanced=1\">" . get_vocab("search") . ":</a>\n";
  echo "<input type=\"search\" name=\"search_str\" value=\"" . htmlspecialchars($search_str) . "\" required>\n";
  echo "<input type=\"hidden\" name=\"day\" value=\"$day\">\n";
  echo "<input type=\"hidden\" name=\"month\" value=\"$month\">\n";
  echo "<input type=\"hidden\" name=\"year\" value=\"$year\">\n";
  if (!empty($area))
  {
    echo "<input type=\"hidden\" name=\"area\" value=\"$area\">\n";
  }
  if (!empty($room))
  {
    echo "<input type=\"hidden\" name=\"room\" value=\"$room\">\n";
  }
  echo "</div>\n";
  echo "</form>\n";
}


function print_logon()
{
    echo "<div id=\"logon_box\">\n";
    PrintLogonBox(); 
    echo "</div>\n";
}


function print_nav($day, $month, $year, $area, $room, $search_str = '', $simple=false)
{
  $query_string = "day=$day&amp;month=$month&amp;year=$year";
  if (!empty($area))
  {
    $query_string .= "&amp;area=$area";
  }
  if (!empty($room))
  {
    $query_string .= "&amp;room=$room";
  }
  
  echo "<nav>\n";
  echo "<ul>\n";
  
  echo "<li>\n";
  print_header_site_info();
  echo "</li>\n";
  
  if (!$simple)
  {
    echo "<li>\n";
    print_goto_date($day, $month, $year, $area, $room);
    echo "</li>\n";
    
    echo "<li>\n";
    print_help($query_string);
    echo "</li>\n";
    
    echo "<li>\n";
    print_rooms($query_string);
    echo "</li>\n";
    
    echo "<li>\n";
    print_report($query_string);
    echo "</li>\n";
    
    echo "<li>\n";
    print_search($day, $month, $year, $area, $room, $search_str);
    echo "</li>\n";
    
    // For session protocols that define their own logon box...
    if (function_exists(__NAMESPACE__ . "\\PrintLogonBox"))
    {
      echo "<li>\n";
      print_logon();
      echo "</li>\n";
    }
  }
  
  echo "</ul>\n";
  echo "</nav>\n";
}


// Print the page header
// If $simple is true, then just print a simple header that doesn't require any database
// access or JavaScript (useful for fatal errors and database upgrades).
function print_theme_header($day, $month, $year, $area, $room, $simple=false)
{
  global $search_str, $locale_warning;

  // Need to set the timezone before we can use date()
  if ($simple)
  {
    // We don't really care what timezone is being used
    mrbs_default_timezone_set();
  }
  else
  {
    // This will set the correct timezone for the area
    get_area_settings($area);  
  }

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

  http_headers();
  echo DOCTYPE;

  echo "<html>\n";
  
  print_head($simple);

  // Put the filename in as a class to aid styling.   
  // (Use a class rather than id to avoid specificity problems)
  echo "<body class=\"non_js ".htmlspecialchars(this_page('.php'))."\">\n";
   
  if (!$simple)
  {
    // Add a class of "js" so that we know if we're using JavaScript or not
    // and remove the non_js class (it's sometimes useful to know that we're
    // not running JavaScript)
    ?>
    <script type="text/javascript">
      //<![CDATA[
      $('body').addClass('js').removeClass('non_js');
      //]]>
    </script> 
    <?php
  }
  
  if (!empty($locale_warning))
  {
    echo "[Warning: ".$locale_warning."]";
  }
  
  $class = 'banner';
  if ($simple)
  {
    $class .= ' simple';
  }
  
  echo "<header class=\"$class\">\n";
  print_nav($day, $month, $year, $area, $room, $search_str, $simple);
  echo "</header>\n";

  // This <div> should really be moved out of here so that we can always see
  // the matching closing </div>
  echo "<div id=\"contents\">\n";


} // end of print_theme_header()

