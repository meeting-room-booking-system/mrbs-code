<?php
namespace MRBS;

require "../defaultincludes.inc";

http_headers(array("Content-type: application/x-javascript"),
             60*30);  // 30 minute expiry

if ($use_strict)
{
  echo "'use strict';\n";
}

?>

$.fn.reverse = [].reverse;

function getErrorList(errors)
{
  var result = {html: '', text: ''},
      patternSpan = /<span[\s\S]*span>/gi,
      patternTags = /<\S[^><]*>/g,
      str;
      
  result.html += "<ul>";
  
  for (var i=0; i<errors.length; i++)
  {
    result.html += "<li>" + errors[i] + "<\/li>";
    result.text += '(' + (i+1).toString() + ') ';
    <?php // strip out the <span> and its contents and then all other tags ?>
    str = errors[i].replace(patternSpan, '').replace(patternTags, '');
    <?php // undo the htmlspecialchars() ?>
    result.text += $('<div>').html(str).text();
    result.text += "  \n";
  }
  
  result.html += "<\/ul>";
  
  return result;
}


<?php
// Gets the correct prefix to use (if any) with the page visibility API.
// Returns null if not supported.
?>
var visibilityPrefix = function visibilityPrefix() {
    var prefixes = ['', 'webkit', 'moz', 'ms', 'o'];
    var testProperty;
    
    if (typeof visibilityPrefix.prefix === 'undefined')
    {
      visibilityPrefix.prefix = null;
      for (var i=0; i<prefixes.length; i++)
      {
        testProperty = prefixes[i];
        testProperty += (prefixes[i] === '') ? 'hidden' : 'Hidden';
        if (testProperty in document)
        {
          visibilityPrefix.prefix = prefixes[i];
          break;
        }
      }
    }

    return visibilityPrefix.prefix;
  };

<?php
// Determine if the page is hidden from the user (eg if it has been minimised
// or the tab is not visible).    Returns true, false or null (if not known).
?>
var isHidden = function isHidden() {
    var prefix;
    prefix = visibilityPrefix();
    switch (prefix)
    {
      case null:
        return null;
        break;
      case '':
        return document.hidden;
        break;
      default:
        return document[prefix + 'Hidden'];
        break;
    }
  };


<?php
// Thanks to Remy Sharp https://remysharp.com/2010/07/21/throttling-function-calls
?>
function throttle(fn, threshold, scope) {

  var last,
      deferTimer;
      
  threshold || (threshold = 250);
  
  return function () {
    var context = scope || this,
        now = +new Date,
        args = arguments;
        
    if (last && now < last + threshold)
    {
      // hold on to it
      clearTimeout(deferTimer);
      deferTimer = setTimeout(function () {
          last = now;
          fn.apply(context, args);
        }, threshold);
    }
    else 
    {
      last = now;
      fn.apply(context, args);
    }
  };
}

<?php
// Tries to determine if the network connection is metered and subject to
// charges or throttling
?>
function isMeteredConnection()
{
  var connection = navigator.connection || 
                   navigator.mozConnection || 
                   navigator.webkitConnection ||
                   navigator.msConnection ||
                   null;
  
  if (connection === null)
  {
    return false;
  }
  
  if ('type' in connection)
  {
    <?php 
    // Although not all cellular networks will be metered, they
    // may be subject to throttling once a data threshold has passed.
    // It is probably sensible to assume that most users connected via
    // a cellular network will want to minimise data traffic.
    ?>
    return (connection.type === 'cellular');
  }
  
  <?php // The older version of the interface ?>
  if ('metered' in connection)
  {
    return connection.metered;
  }
  
  return false;
}


function getCSRFToken()
{
  return $('meta[name="csrf_token"]').attr('content');
}