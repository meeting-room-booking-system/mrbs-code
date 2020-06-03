<?php
namespace MRBS;

require '../defaultincludes.inc';

http_headers(array("Content-type: application/x-javascript"),
             60*30);  // 30 minute expiry

if ($use_strict)
{
  echo "'use strict';\n";
}


// Only show the bottom nav bar if no part of the top one is visible.
?>
var checkNav = function() {
    if ($('nav.main_calendar').eq(0).visible(true))
    {
      $('nav.main_calendar').eq(1).hide();
    }
    else
    {
      $('nav.main_calendar').eq(1).show();
    }
  };


<?php
// Replace the body elememt with the body in response, for the page href.
?>
var replaceBody = function(response, href) {
    <?php
    // We get the entire page HTML returned, but we are only interested in the <body> element.
    // That's because if we replace the whole HTML the browser will re-load the JavaScript and
    // CSS files which is unnecessary and will also cause problems if the CSS is not loaded in
    // time.
    //
    // Unfortunately, we can't use jQuery.replaceWith() on the body object as that doesn't work
    // properly.  So we have to replace the body HTML and then update the attributes for the body
    // tag afterwards.
    ?>
    var matches = response.match(/(<body[^>]*>)([^<]*(?:(?!<\/?body)<[^<]*)*)<\/body\s*>/i);
    var body = $('body');
    body.html(matches[2]);
    $('<div' + matches[1].substring(5) + '</div>').each(function() {
        $.each(this.attributes, function() {
            <?php
            // this.attributes is not a plain object, but an array
            // of attribute nodes, which contain both the name and value
            ?>
            if(this.specified)
            {
              if (this.name.substring(0, 5).toLowerCase() == 'data-')
              {
                <?php
                // Data attributes have to be updated differently from other attributes because
                // they are cached by jQuery.  If the attribute looks like a JSON array, then turn
                // it back into an array.
                ?>
                var value = this.value;
                if (value.charAt(0) === '[')
                {
                  try {
                    value = JSON.parse(value);
                  }
                  catch (e) {
                    value = this.value;
                  }
                }
                body.data(this.name.substring(5), value);
              }
              else
              {
                body.attr(this.name, this.value);
              }
            }
          });
      });

    <?php
    // Trigger a page_ready event, because the normal document ready event
    // won't be triggered when we are just replacing the html.
    ?>
    $(document).trigger('page_ready');

    <?php // change the URL in the address bar ?>
    history.pushState(null, '', href);
  };


<?php
// Update the <body> element either via an Ajax call or using a pre-fetched response,
// in order to avoid flickering of the screen as we move between pages in the calendar view.
//
// 'event' can either be an event object if the function is called from an 'on'
// handler, or else it as an href string (eg when called from flatpickr).
?>
var updateBody = function(event) {
    var href;

    if (typeof event === 'object')
    {
      href = $(this).attr('href');
      event.preventDefault();
    }
    else
    {
      href = event;
    }

    <?php // Add a "Loading ..." message ?>
    $('h2.date').text('<?php echo get_vocab('loading')?>')
                .addClass('loading');

    if (updateBody.prefetched && updateBody.prefetched[href])
    {
      replaceBody(updateBody.prefetched[href], href);
    }
    else
    {
      $.get(href, 'html', function(response){
          replaceBody(response, href);
        });
    }
  };


<?php
// Pre-fetch the prev and next pages to improve performance.  They are probably
// the two most likely pages to be required.
?>
var prefetch = function() {

  <?php
  // Don't pre-fetch if it's been disabled in the config
  if (empty($prefetch_refresh_rate))
  {
    ?>
    return;
    <?php
  }

  // Don't pre-fetch and waste bandwidth if we're on a metered connection ?>
  if (isMeteredConnection())
  {
    return;
  }

  var delay = <?php echo $prefetch_refresh_rate?> * 1000;
  var hrefs = [$('a.prev').attr('href'),
               $('a.next').attr('href')];

  <?php // Clear any existing pre-fetched data and any timeout ?>
  updateBody.prefetched = {};
  clearTimeout(prefetch.timeoutId);

  <?php
  // Don't pre-fetch if the page is hidden.  Just set another timeout
  ?>
  if (isHidden())
  {
    prefetch.timeoutId = setTimeout(prefetch, delay);
    return;
  }

  hrefs.forEach(function(href) {
    $.get({
        url: href,
        dataType: 'html',
        success: function(response) {
            updateBody.prefetched[href] = response;
            <?php // Once we've got all the responses back set off another timeout ?>
            if (Object.keys(updateBody.prefetched).length === hrefs.length)
            {
              prefetch.timeoutId = setTimeout(prefetch, delay);
            }
          }
      });
  });

};


$(document).on('page_ready', function() {

  <?php
  // Turn the room and area selects into fancy select boxes and then
  // show the location menu (it's hidden to avoid screen jiggling).
  ?>
  $('.room_area_select').mrbsSelect();
  $('nav.location').removeClass('js_hidden');

  <?php
  // The bottom navigation was hidden while the Select2 boxes were formed
  // so that the correct widths could be established.  It is then shown if
  // the top navigation is not visible.
  ?>
  $('nav.main_calendar').removeClass('js_hidden');
  checkNav();
  $(window).on('scroll', checkNav);
  $(window).on('resize', checkNav);

  <?php
  // Only reveal the color key once the bottom navigation has been determined,
  // in order to avoid jiggling.
  ?>
  $('.color_key').removeClass('js_hidden');

  <?php
  // Replace the navigation links with Ajax calls in order to eliminate flickering
  // as we move between pages.
  ?>
  $('nav.arrow a, nav.view a').on('click', updateBody);

  <?php
  // Pre-fetch some pages to improve performance
  ?>
  prefetch();
});
