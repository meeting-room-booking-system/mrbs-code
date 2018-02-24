<?php
namespace MRBS;

use MRBS\Form\Form;
use MRBS\Form\ElementInputDate;
use MRBS\Form\ElementInputSearch;
use MRBS\Form\ElementInputSubmit;


// Gets a month calendar for the given $year, $month, $day.   (We need to specify
// $day as the year-month-day may not be a valid day, eg 2020-12-32 which will be
// interpreted as 2021-01-01).  Returns a six week calendar which is just an array
// of dates, starting on $weekstarts.  It's always six weeks so that tables produced
// from it are always the same height.
function get_calendar($year, $month, $day)
{
  global $weekstarts;
  
  $calendar = array();
  
  $date = new DateTime();
  $date->setDate($year, $month, $day);
  $d = $date->format('t');  // the last day of the month
  // Go to the last day of the month
  $date->setDate($year, $month, $d);
  $day_of_week = $date->format('w');
  
  $interval = new \DateInterval('P1D');  // one day
  
  // Work backwards through the month from the last day until the first
  while ($d > 0)
  {
    array_unshift($calendar, $d);
    $d--;
    $day_of_week = ($day_of_week + 6) % 7;  // in other words -1 mod 7
    $date->sub($interval);
  }
  
  // Then fill in any days of the week before the start of the month
  // We use a do while loop rather than a while loop because we want to
  // have at least some of the previous month (in order to ensure we have
  // a six week calendar).
  do
  {
    array_unshift($calendar, $date->format('j'));
    $day_of_week = ($day_of_week + 6) % 7;  // in other words -1 mod 7
    $date->sub($interval);
  }
  while ($weekstarts != ($day_of_week+1)%7);
  
  // Now fill in at the end of the calendar to make it up to six weeks
  for ($i=count($calendar), $d=1; $i<42; $i++, $d++)
  {
    array_push($calendar, $d);
  }
  
  return $calendar;
}

  
function print_head($simple=false)
{
  echo "<head>\n";
  
  echo "<meta charset=\"" . get_charset() . "\">\n";
  // Set IE=edge so that IE10 will display MRBS properly, even if compatibility mode is used
  // on the browser.  If we don't do this then MRBS will treat IE10 as an unsupported browser
  // when compatibility mode is turned on, potentially confusing users who may have forgotten
  // that they are using compatibility mode.   Unfortunately we can't set IE=edge in the header,
  // which is where we would normally do it, because then we won't be able to detect IE9 using
  // conditional comments.  So we have to do it in a <meta> tag, after the conditional comments
  // around the <html> tags.
  echo "<meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\">\n";
  
  if (!$simple)
  {
    // Add the CSRF token so that JavaScript can use it
    echo "<meta name=\"csrf_token\" content=\"" . htmlspecialchars(Form::getToken()) . "\">\n";
  }
  
  echo "<title>" . get_vocab("mrbs") . "</title>\n";
  
  require_once "style.inc";
  
  if (!$simple)
  {
    require_once "js.inc";
  }
  
  echo "</head>\n";
}


function print_mini_calendar($view, $year, $month, $day, $area, $room)
{
  global $weekstarts, $strftime_format;
  
  $calendar = get_calendar($year, $month, $day);
  
  $html = '';
  
  $html .= "<table>\n";
  
  // Produce the table head
  $html .= "<thead>\n";
  $html .= "<tr>";
  
  for ($i=$weekstarts; $i<$weekstarts+7; $i++)
  {
    // Sunday is Day 0
    $day_name = utf8_strftime($strftime_format['dayname_cal'],
                              strtotime("next sunday + $i days"));
    $html .= '<th>' . htmlspecialchars($day_name) . '</th>';
  }
  
  $html .= "</tr>\n";       
  $html .= "</thead>\n";
  
  // Now the table body
  $html .= "<tbody>\n";
  for ($i=0; $i<count($calendar); $i++)
  {
    if ($i == 0)
    {
      $html .= "<tr>";
    }
    elseif ($i%7 == 0)
    {
      $html .= "</tr><tr>\n";
    }
    $html .= "<td>" . $calendar[$i] . "</td>";
  }
  $html .= "</tr>\n";
  
  $html .= "</tbody>\n";
  $html .= "</table>\n";
  
  echo $html;
}


// Print the basic site information.   This function is used for all headers, including
// the simple header, and so mustn't require any database access.
function print_header_site_info()
{
  global $mrbs_company,
         $mrbs_company_url,
         $mrbs_company_logo,
         $mrbs_company_more_info;
  
  echo "<div class=\"company\">\n";
  
  echo "<div class=\"logo\">\n";
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

  echo "<div class=\"mrbs\">\n";
  echo "<a href=\"index.php\">" . get_vocab("mrbs") . "</a>\n";
  echo "</div>\n";
  
  echo "</div>\n";
}


function print_goto_date($view, $year, $month, $day, $area, $room)
{
  $user = getUserName();
  
  $form = new Form();
  
  $form_id = 'form_nav';
  
  $form->setAttributes(array('id'     => $form_id,
                             'method' => 'get',
                             'action' => 'calendar.php'))
       ->addHiddenInput('view', $view);
                    
  if (isset($area))
  {
    $form->addHiddenInput('area', $area);
  }
  
  if (isset($room))
  {
    $form->addHiddenInput('room', $room);
  }
  
  $input = new ElementInputDate();
  $input->setAttributes(array('name'        => 'page_date',
                              'value'       => format_iso_date($year, $month, $day),
                              'required'    => true,
                              'data-submit' => $form_id));
  
  $form->addElement($input);
  
  $submit = new ElementInputSubmit();
  $submit->setAttribute('value', get_vocab('goto'));
  $form->addElement($submit);
  
  $form->render();
  
  
  // Provide a link to the list of bookings awaiting approval
  // (if there are any enabled areas where we require bookings to be approved)
  $approval_somewhere = some_area('approval_enabled', TRUE);
  if ($approval_somewhere && (authGetUserLevel($user) >= 1))
  {
    $n_outstanding = get_entries_n_outstanding($user);
    echo "<div id=\"n_outstanding\"" .
         (($n_outstanding > 0) ? " class=\"outstanding\"" : '') .
         ">\n";
    
    $vars = array('view'  => $view,
                  'year'  => $year,
                  'month' => $month,
                  'day'   => $day,
                  'area'  => $area);
                  
    if (isset($room))
    {
      $vars['room'] = $room;
    }
    
    $query = http_build_query($vars, '', '&');
    
    echo '<a href="pending.php?' . htmlspecialchars($query) .
         "\">$n_outstanding " . get_vocab('outstanding') . "</a>\n";
    echo "</div>\n";
  }
}


function print_help($query)
{
  echo '<a href="help.php?' . htmlspecialchars($query) . '">' . get_vocab('help') . "</a>\n";
}


function print_rooms($query)
{
  echo '<a href="admin.php?' . htmlspecialchars($query) . '">' . get_vocab('rooms') . "</a>\n";
}


function print_report($query)
{
  echo '<a href="report.php?' . htmlspecialchars($query) . '">' . get_vocab('report') . "</a>\n";
}


function print_search($view, $year, $month, $day, $area, $room, $search_str=null)
{
  if (!isset($search_str))
  {
    $search_str = '';
  }
  
  echo "<label><a href=\"search.php?advanced=1\">" . get_vocab('search') . "</a></label>\n";
  
  $form = new Form();
  
  $form->setAttributes(array('id'     => 'header_search',
                             'method' => 'post',
                             'action' => 'search.php'))
       ->addHiddenInputs(array('view'  => $view,
                               'year'  => $year,
                               'month' => $month,
                               'day'   => $day));
  if (!empty($area))
  {
    $form->addHiddenInput('area', $area);
  }
  if (!empty($room))
  {
    $form->addHiddenInput('room', $room);
  }
  
  $input = new ElementInputSearch();
  
  $input->setAttributes(array('name'     => 'search_str',
                              'value'    => $search_str,
                              'required' => true));
                              
  $form->addElement($input);
  
  $form->render();
}


function print_nav($view, $year, $month, $day, $area, $room, $search_str=null, $simple=false)
{
  $vars = array('view'  => $view,
                'year'  => $year,
                'month' => $month,
                'day'   => $day);
                
  if (!empty($area))
  {
    $vars['area'] = $area;
  }
  if (!empty($room))
  {
    $vars['room'] = $room;
  }
  
  $query = http_build_query($vars, '', '&');
  
  echo "<nav>\n";
  echo "<ul>\n";
  
  echo "<li>\n";
  print_mini_calendar($view, $year, $month, $day, $area, $room);
  echo "</li>\n";
  
  echo "<li>\n";
  print_header_site_info();
  echo "</li>\n";
  
  if (!$simple)
  {
    echo "<li>\n";
    print_goto_date($view, $year, $month, $day, $area, $room);
    echo "</li>\n";
    
    echo "<li>\n";
    print_help($query);
    echo "</li>\n";
    
    echo "<li>\n";
    print_rooms($query);
    echo "</li>\n";
    
    echo "<li>\n";
    print_report($query);
    echo "</li>\n";
    
    echo "<li>\n";
    print_search($view, $year, $month, $day, $area, $room, $search_str);
    echo "</li>\n";
    
    // For session protocols that define their own logon box...
    if (function_exists(__NAMESPACE__ . "\\print_logon_box"))
    {
      echo "<li id=\"logon_box\">\n";
      print_logon_box();
      echo "</li>\n";
    }
  }
  
  echo "</ul>\n";
  echo "</nav>\n";
}



// Print a message which will only be displayed (thanks to CSS) if the user is
// using an unsupported browser.
function print_unsupported_message()
{
  echo "<div class=\"unsupported_message\">\n";
  echo "<header class=\"banner simple\">\n";
  print_nav(null, null, null, null, null, null, null, $simple=true);
  echo "</header>\n";
  echo "<div class=\"contents\">\n";
  echo "<p>" . get_vocab('browser_not_supported', get_vocab('mrbs_abbr')) . "</p>\n";
  echo "</div>\n";
  echo "</div>\n";
}


// Print the page header
// If $simple is true, then just print a simple header that doesn't require any database
// access or JavaScript (useful for fatal errors and database upgrades).
function print_theme_header($view, $year, $month, $day, $area, $room, $simple=false, $search_str=null)
{
  $headers = array("Content-Type: text/html; charset=" . get_charset());
  http_headers($headers);
  
  echo DOCTYPE . "\n";;

  // We produce two <html> tags: one for versions of IE that we don't support and one for all
  // other browsers.  This enables us to use CSS to hide and show the appropriate text.
  echo "<!--[if lte IE 9]>\n";
  echo "<html lang=\"" . htmlspecialchars(get_lang()) . "\" class=\"unsupported_browser\">\n";
  echo "<![endif]-->\n";
  echo "<!--[if (!IE)|(gt IE 9)]><!-->\n";
  echo "<html lang=\"" . htmlspecialchars(get_lang()) . "\">\n";
  echo "<!--<![endif]-->\n";
  
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
  
  $class = 'banner';
  if ($simple)
  {
    $class .= ' simple';
  }
  
  print_unsupported_message();
  
  echo "<header class=\"$class\">\n";
  print_nav($view, $year, $month, $day, $area, $room, $search_str, $simple);
  echo "</header>\n";

  // This <div> should really be moved out of here so that we can always see
  // the matching closing </div>
  echo "<div class=\"contents\">\n";


} // end of print_theme_header()

