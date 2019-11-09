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
  
  // Improve scaling on mobile devices
  echo "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n";
  
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
  
  if (!isset($user))
  {
    return;
  }
  
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


function print_menu_items($query)
{
  global $auth;
  
  $menu_items = array('help'   => 'help.php',
                      'report' => 'report.php',
                      'import' => 'import.php',
                      'rooms'  => 'admin.php');
                      
  if ($auth['type'] == 'db')
  {               
    $menu_items['user_list'] = 'edit_users.php';
  }
  
  foreach ($menu_items as $token => $page)
  {
    // Only print menu items for which the user is allowed to access the page
    if (checkAuthorised($page, true))
    {
      echo "<a href=\"$page?" . htmlspecialchars($query) . '">' . get_vocab($token) . "</a>\n";
    }
  }
}



function print_search($view, $year, $month, $day, $area, $room)
{
  if (!checkAuthorised('search.php', true))
  {
    // Don't show the search box if the user isn't allowed to search
    return;
  }
  
  echo "<div>\n";
    
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
  $search_vocab =  get_vocab('search');

  $input->setAttributes(array('name'        => 'search_str',
                              'placeholder' => $search_vocab,
                              'aria-label'  => $search_vocab,
                              'required'    => true));
                              
  $form->addElement($input);
  
  $submit = new ElementInputSubmit();
  $submit->setAttributes(array('value' => get_vocab('search_button'),
                               'class' => 'js_none'));
  $form->addElement($submit);
  
  $form->render();
  
  echo "</div>\n";
}


function print_banner($view, $year, $month, $day, $area, $room, $simple=false)
{
  echo '<header class="banner' . (($simple) ? ' simple' : '') . "\">\n";
  
  $vars = array('view'  => $view,
                'page_date' => format_iso_date($year, $month, $day));
                
  if (!empty($area))
  {
    $vars['area'] = $area;
  }
  if (!empty($room))
  {
    $vars['room'] = $room;
  }
  
  $query = http_build_query($vars, '', '&');
  
  print_header_site_info();
  
  
  
  if (!$simple)
  {
    echo "<nav class=\"container\">\n";
    
    echo "<nav>\n";
    
    echo "<nav class=\"menu\">\n";
    print_menu_items($query);
    echo "</nav>\n";
    
    echo "<nav class=\"logon\">\n";
    print_outstanding($query);
    // For session protocols that define their own logon box...
    // (Note: This really needs to be sorted out so that the HTML
    // is taken out of the session code)
    if (function_exists(__NAMESPACE__ . "\\print_logon_box"))
    {
      print_logon_box();
    }
    else
    {
      $user = getUserName();
      if (isset($user))
      {
        require_once MRBS_ROOT . '/session/functions_session.inc';
        print_report_link($user);
      }
    }
    echo "</nav>\n";
    
    echo "</nav>\n";
    
    echo "<nav>\n";
    print_goto_date($view, $year, $month, $day, $area, $room);
    print_search($view, $year, $month, $day, $area, $room);
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
  print_banner(null, null, null, null, null, null, $simple=true);
  echo "<div class=\"contents\">\n";
  echo "<p>" . get_vocab('browser_not_supported', get_vocab('mrbs_abbr')) . "</p>\n";
  echo "</div>\n";
  echo "</div>\n";
}


// Print the page header
// If $simple is true, then just print a simple header that doesn't require any database
// access or JavaScript (useful for fatal errors and database upgrades).
function print_theme_header($view, $year, $month, $day, $area, $room, $simple=false)
{
  // Get the form token now, before any headers are sent, in case we are using the 'cookie'
  // session scheme.  Otherwise we won't be able to store the Form token.
  Form::getToken();
  
  $headers = array("Content-Type: text/html; charset=" . get_charset());
  http_headers($headers);
  
  echo DOCTYPE . "\n";;

  // We produce two <html> tags: one for versions of IE that we don't support and one for all
  // other browsers.  This enables us to use CSS to hide and show the appropriate text.
  echo "<!--[if lte IE 9]>\n";
  echo "<html lang=\"" . htmlspecialchars(get_mrbs_lang()) . "\" class=\"unsupported_browser\">\n";
  echo "<![endif]-->\n";
  echo "<!--[if (!IE)|(gt IE 9)]><!-->\n";
  echo "<html lang=\"" . htmlspecialchars(get_mrbs_lang()) . "\">\n";
  echo "<!--<![endif]-->\n";
  
  print_head($simple);

  $page = this_page(false, '.php');

  // Put some data attributes in the body element for the benefit of JavaScript.  Note that we
  // cannot use these PHP variables directly in the JavaScript files as those files are cached.
  $data = array(
      'view'      => $view,
      'area'      => $area,
      'room'      => $room,
      'page'      => $page,
      'page-date' => format_iso_date($year, $month, $day),
      'is-admin'  => (is_admin()) ? 'true' : 'false'
    );
    
  // We need $timetohighlight for the day and week views
  $timetohighlight = get_form_var('timetohighlight', 'int');
  if (isset($timetohighlight))
  {
    $data['timetohighlight'] = $timetohighlight;
  }

  // Put the filename in as a class to aid styling.
  $classes = array($page);
  // And if the user is logged in, add another class to aid styling
  $user = getUserName();
  if (isset($user))
  {
    $classes[] = 'logged_in';
  }

  echo '<body class="' . htmlspecialchars(implode(' ', $classes)) . '"';
  foreach ($data as $key => $value)
  {
    if (isset($value))
    {
      echo " data-$key=\"" . htmlspecialchars($value) . '"';
    }
  }
  echo ">\n";
   

  
  print_unsupported_message();
  
  print_banner($view, $year, $month, $day, $area, $room, $simple);
  
  // This <div> should really be moved out of here so that we can always see
  // the matching closing </div>
  echo "<div class=\"contents\">\n";


} // end of print_theme_header()

