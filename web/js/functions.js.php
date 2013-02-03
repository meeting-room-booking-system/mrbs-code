<?php

// $Id$

require "../defaultincludes.inc";

header("Content-type: application/x-javascript");
expires_header(60*30); // 30 minute expiry

if ($use_strict)
{
  echo "'use strict';\n";
}

?>

$.fn.reverse = [].reverse;

function getErrorList(errors)
{
  var result = {html: '', text: ''};
  var patternSpan = /<span[\s\S]*span>/gi;
  var patternTags = /<\S[^><]*>/g;
  result.html += "<ul>";
  for (var i=0; i<errors.length; i++)
  {
    result.html += "<li>" + errors[i] + "<\/li>";
    result.text += '(' + (i+1).toString() + ') ';
    <?php // strip out the <span> and its contents and then all other tags ?>
    result.text += errors[i].replace(patternSpan, '').replace(patternTags, '') + "  \n";
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
      visibilityPrefix.prefix === null;
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

