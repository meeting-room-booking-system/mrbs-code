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
  

var updateBody = function(e) {
    var href = $(this).attr('href');
    e.preventDefault();
    $.get({ 
        url: href, 
        dataType: 'html', 
        success: function(response){
            var matches = response.match(/(<body[^>]*>)([^<]*(?:(?!<\/?body)<[^<]*)*)<\/body\s*>/i);
            var body = $('body');
            body.html(matches[2]);
            $('<div' + matches[1].substring(5) + '</div>').each(function() {
                $.each(this.attributes, function() {
                    <?php
                    // this.attributes is not a plain object, but an array
                    // of attribute nodes, which contain both the name and value
                    ?>
                    if(this.specified) {
                      if (this.name.substring(0, 5).toLowerCase() == 'data-')
                      {
                        body.data(this.name.substring(5), this.value);
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
        }
      }); 
  };


$(document).on('page_ready', function() {
  
  <?php
  // Turn the room and area selects into fancy select boxes and then
  // show the location menu (it's hidden to avoid screen jiggling).
  // If we are using a mobile device then keep the native select elements
  // as they tend to be better.
  ?>
  if (!isMobile())
  {
    $('.room_area_select').select2();
    <?php
    // Select2 doesn't always get the width right, so increase it by a
    // few pixels to make sure we don't get a '...'
    ?>
    $('.select2-container').each(function() {
      var container = $(this);
      container.width(container.width() + 5);
    });
  }
  $('nav.location').removeClass('js_hidden');
  
  <?php
  // The bottom navigation was hidden while the Select2 boxes were formed
  // so that the correct widths could be established.  It is then shown if
  // the top navigation is not visible.
  ?>
  $('nav.main_calendar').removeClass('js_hidden');
  checkNav();
  $(window).scroll(checkNav);
  $(window).resize(checkNav);
  
  <?php
  // Only reveal the color key once the bottom navigation has been determined,
  // in order to avoid jiggling.
  ?>
  $('.color_key').removeClass('js_hidden');
  
  $('nav.arrow a, nav.view a').click(updateBody);
  
});
