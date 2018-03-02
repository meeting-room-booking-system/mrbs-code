<?php
namespace MRBS;

use MRBS\Form\Form;
use MRBS\Form\ElementInputDate;
use MRBS\Form\ElementInputSearch;
use MRBS\Form\ElementInputSubmit;

  
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
  $buffer_size_prev = 15;  // months of hidden mini-calendars before the visible one
  $buffer_size_next = 15;  // months of hidden mini-calendars after the visible one
  
  $mincal = get_form_var('mincal', 'string');
  
  // Validate the mincal input
  if (isset($mincal))
  {
    $date = DateTime::createFromFormat('Y-m', $mincal);
    if (($date === false) || array_sum($date->getLastErrors()))
    {
      $mincal = null;
    }
  }
  
  $mini_calendar = new MiniCalendar($view, $year, $month, $day, $area, $room, $mincal);
  // Go back to the first mini-calendar in the buffer
  $mini_calendar->sub('P' . $buffer_size_prev . 'M');
  
  // Output all the mini-calendars
  for ($i = -$buffer_size_prev; $i <= $buffer_size_next; $i++)
  {
    $hidden = ($i !=0);
    echo $mini_calendar->toHTML($hidden);
    $mini_calendar->add('P1M');
  }
}


// Print the basic site information.   This function is used for all headers, including
// the simple header, and so mustn't require any database access.
function print_header_site_info()
{
  global $mrbs_company,
         $mrbs_company_url,
         $mrbs_company_logo,
         $mrbs_company_more_info;
  
  // Company logo, with a link to the company
  if (!empty($mrbs_company_logo))
  {
    echo "<div class=\"logo\">\n";
    if (!empty($mrbs_company_url))
    {
      echo '<a href="' . htmlspecialchars($mrbs_company_url) . '">';
    }
    // Suppress error messages in case the logo is a URL and allow_url_fopen
    // is not enabled in php.ini
    $logo_size = @getimagesize($mrbs_company_logo);
    echo '<img src="' . $mrbs_company_logo . '" ' . $logo_size[3] . 
         ' alt="' . htmlspecialchars($mrbs_company) . '">';
    if (!empty($mrbs_company_url))
    {
      echo "</a>\n";
    }
    echo "</div>\n";
  }
  
  // Company name, any extra company info and MRBS
  echo "<div class=\"company\">\n";
  if (!empty($mrbs_company_url))
  {
    echo '<a href="' . htmlspecialchars($mrbs_company_url) . '">';
  }
  echo '<span>' . htmlspecialchars($mrbs_company) . '</span>';
  if (!empty($mrbs_company_url))
  {
    echo "</a>\n";
  }
  if (!empty($mrbs_company_more_info))
  {
    echo '<span id="more_info">' . htmlspecialchars($mrbs_company_more_info) . "</span>\n";
  }
  echo '<a href="index.php">' . get_vocab('mrbs') . "</a>\n";
  echo "</div>\n";
}


function print_goto_date($view, $year, $month, $day, $area, $room)
{
  $form = new Form();
  
  $form_id = 'form_nav';
  
  $form->setAttributes(array('id'     => $form_id,
                             'method' => 'get',
                             'action' => 'index.php'))
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
}


function print_outstanding($query)
{
  $user = getUserName();
  
  // Provide a link to the list of bookings awaiting approval
  // (if there are any enabled areas where we require bookings to be approved)
  $approval_somewhere = some_area('approval_enabled', TRUE);
  if ($approval_somewhere && (authGetUserLevel($user) >= 1))
  {
    $n_outstanding = get_entries_n_outstanding($user);
    
    $class = 'notification';
    
    if ($n_outstanding > 0)
    {
      $class .= ' attention';
    }
    
    echo '<a href="pending.php?' . htmlspecialchars($query) . '"' .
         " class=\"$class\"" .
         ' title="' . get_vocab('outstanding', $n_outstanding) .
         "\">$n_outstanding</a>\n";
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


function print_users($query)
{
  global $auth;
  
  if ($auth['type'] == 'db')
  {
    echo '<a id="user_list_link" href="edit_users.php?' . htmlspecialchars($query) . '">' . 
         get_vocab('user_list') . "</a>\n";
  }
}


function print_search($view, $year, $month, $day, $area, $room, $search_str=null)
{
  if (!isset($search_str))
  {
    $search_str = '';
  }
  
  echo "<div\n";
  
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
  
  echo "</div\n";
}


function print_banner($view, $year, $month, $day, $area, $room, $search_str=null, $simple=false)
{
  echo '<header class="banner' . (($simple) ? ' simple' : '') . "\">\n";
  
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
  
  if (!$simple)
  {
    // print_mini_calendar($view, $year, $month, $day, $area, $room);
  }
  
  print_header_site_info();
  
  
  
  if (!$simple)
  {
    echo "<nav class=\"container\">\n";
    
    echo "<nav>\n";
    
    echo "<nav class=\"menu\">\n";
    print_help($query);
    print_report($query);
    print_rooms($query);
    print_users($query);
    echo "</nav>\n";
    
    // For session protocols that define their own logon box...
    if (function_exists(__NAMESPACE__ . "\\print_logon_box"))
    {
      echo "<nav class=\"logon\">\n";
      print_outstanding($query);
      print_logon_box();
      echo "</nav>\n";
    }
    echo "</nav>\n";
    
    echo "<nav>\n";
    print_goto_date($view, $year, $month, $day, $area, $room);
    print_search($view, $year, $month, $day, $area, $room, $search_str);
    echo "</nav>\n";

    echo "</nav>\n";
  }
  
  echo "</header>\n";
}



// Print a message which will only be displayed (thanks to CSS) if the user is
// using an unsupported browser.
function print_unsupported_message()
{
  echo "<div class=\"unsupported_message\">\n";
  print_banner(null, null, null, null, null, null, null, $simple=true);
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
  
  print_unsupported_message();
  
  print_banner($view, $year, $month, $day, $area, $room, $search_str, $simple);
  
  // This <div> should really be moved out of here so that we can always see
  // the matching closing </div>
  echo "<div class=\"contents\">\n";


} // end of print_theme_header()

