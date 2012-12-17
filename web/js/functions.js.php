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