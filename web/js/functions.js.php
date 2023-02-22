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

// Decodes a base64 encoded string.  Returns false if it can't be decoded.
// See https://stackoverflow.com/questions/30106476/using-javascripts-atob-to-decode-base64-doesnt-properly-decode-utf-8-strings
function base64Decode(string)
{
  if (typeof TextDecoder === "undefined")
  {
    return false;
  }
  <?php
  // We can use const and let here because it's only IE and Opera Mini that
  // don't support them and neither of them support TextDecoder.
  ?>
  const text = atob(string);
  const length = text.length;
  const bytes = new Uint8Array(length);
  for (let i = 0; i < length; i++) {
    bytes[i] = text.charCodeAt(i);
  }
  const decoder = new TextDecoder(); // default is utf-8
  return decoder.decode(bytes);
}

// Fix for iOS 13 where the User Agent string has been changed.
// See https://github.com/flatpickr/flatpickr/issues/1992
function isIos()
{
  return (window.navigator.userAgent.match(/iPad/i) ||
          window.navigator.userAgent.match(/iPhone/i) ||
          /iPad|iPhone|iPod/.test(navigator.platform) ||
          (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1));
}

function isMobile()
{
  return (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) || isIos());
}

$.fn.reverse = [].reverse;


jQuery.fn.extend({

  <?php
  // Turn a select element into a fancy Select2 field control.  This wrapper function also
  // (a) only does anything if we are using tags or are not on a mobile device, unless useOnMobile
  //     is true, because the native select elements on mobile devices tend to be better.
  //     [Could maybe turn the element from a <select> into a <datalist> if we are using tags?]
  // (b) wraps the select element in a <div> because in some places, eg in forms,  MRBS uses
  //     a table structure and because Select2 adds a sibling element the table structure is
  //     ruined.
  // (c) adjusts the width of the select2 container because Select2 doesn't always get it right
  //     resulting in a '...'
  //
  // Get the best available language
  $select2_lang = basename(get_select2_lang_path(), '.js');
  ?>
  mrbsSelect: function(tags, useOnMobile) {
    if (tags || useOnMobile || !isMobile())
    {
      tags = Boolean(tags);
      $(this).wrap('<div></div>')
        .select2({
          dropdownAutoWidth: true,
          tags: tags,
          <?php
          if (isset($select2_lang) && ($select2_lang !== ''))
          {
            echo "language: '$select2_lang',";
          }
          ?>
        })
        .next('.select2-container').each(function() {
            var container = $(this);
            container.width(container.width() + 5);
          });
    }
    return($(this));
  }

});


function getMaxWidth (selection) {
  return Math.max.apply(null, selection.map(function() {
    return $(this).width();
  }).get());
}


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
        now = +new Date(),
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


<?php
// Get a query string parameter from a url
// See https://stackoverflow.com/questions/901115/how-can-i-get-query-string-values-in-javascript
?>
function getParameterByName(name, url)
{
  if (!url)
  {
    url = window.location.href;
  }
  name = name.replace(/[\[\]]/g, "\\$&");
  var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
      results = regex.exec(url);
  if (!results) return null;
  if (!results[2])
  {
    return '';
  }
  return decodeURIComponent(results[2].replace(/\+/g, " "));
}


(function($){

    /**
     * Copyright 2012, Digital Fusion
     * Licensed under the MIT license.
     * http://teamdf.com/jquery-plugins/license/
     *
     * @author Sam Sehnert
     * @desc A small plugin that checks whether elements are within
     *       the user visible viewport of a web browser.
     *       only accounts for vertical position, not horizontal.
     */
    var $w=$(window);
    $.fn.visible = function(partial,hidden,direction,container){

        if (this.length < 1)
            return;

	// Set direction default to 'both'.
	direction = direction || 'both';

        var $t          = this.length > 1 ? this.eq(0) : this,
						isContained = typeof container !== 'undefined' && container !== null,
						$c				  = isContained ? $(container) : $w,
						wPosition        = isContained ? $c.position() : 0,
            t           = $t.get(0),
            vpWidth     = $c.outerWidth(),
            vpHeight    = $c.outerHeight(),
            clientSize  = hidden === true ? t.offsetWidth * t.offsetHeight : true;

        if (typeof t.getBoundingClientRect === 'function'){

            // Use this native browser method, if available.
            var rec = t.getBoundingClientRect(),
                tViz = isContained ?
												rec.top - wPosition.top >= 0 && rec.top < vpHeight + wPosition.top :
												rec.top >= 0 && rec.top < vpHeight,
                bViz = isContained ?
												rec.bottom - wPosition.top > 0 && rec.bottom <= vpHeight + wPosition.top :
												rec.bottom > 0 && rec.bottom <= vpHeight,
                lViz = isContained ?
												rec.left - wPosition.left >= 0 && rec.left < vpWidth + wPosition.left :
												rec.left >= 0 && rec.left <  vpWidth,
                rViz = isContained ?
												rec.right - wPosition.left > 0  && rec.right < vpWidth + wPosition.left  :
												rec.right > 0 && rec.right <= vpWidth,
                vVisible   = partial ? tViz || bViz : tViz && bViz,
                hVisible   = partial ? lViz || rViz : lViz && rViz,
		vVisible = (rec.top < 0 && rec.bottom > vpHeight) ? true : vVisible,
                hVisible = (rec.left < 0 && rec.right > vpWidth) ? true : hVisible;

            if(direction === 'both')
                return clientSize && vVisible && hVisible;
            else if(direction === 'vertical')
                return clientSize && vVisible;
            else if(direction === 'horizontal')
                return clientSize && hVisible;
        } else {

            var viewTop 				= isContained ? 0 : wPosition,
                viewBottom      = viewTop + vpHeight,
                viewLeft        = $c.scrollLeft(),
                viewRight       = viewLeft + vpWidth,
                position          = $t.position(),
                _top            = position.top,
                _bottom         = _top + $t.height(),
                _left           = position.left,
                _right          = _left + $t.width(),
                compareTop      = partial === true ? _bottom : _top,
                compareBottom   = partial === true ? _top : _bottom,
                compareLeft     = partial === true ? _right : _left,
                compareRight    = partial === true ? _left : _right;

            if(direction === 'both')
                return !!clientSize && ((compareBottom <= viewBottom) && (compareTop >= viewTop)) && ((compareRight <= viewRight) && (compareLeft >= viewLeft));
            else if(direction === 'vertical')
                return !!clientSize && ((compareBottom <= viewBottom) && (compareTop >= viewTop));
            else if(direction === 'horizontal')
                return !!clientSize && ((compareRight <= viewRight) && (compareLeft >= viewLeft));
        }
    };

})(jQuery);

// https://github.com/mgalante/jquery.redirect
/*
jQuery Redirect v1.1.4

Copyright (c) 2013-2022 Miguel Galante
Copyright (c) 2011-2013 Nemanja Avramovic, www.avramovic.info

Licensed under CC BY-SA 4.0 License: http://creativecommons.org/licenses/by-sa/4.0/

This means everyone is allowed to:

Share - copy and redistribute the material in any medium or format
Adapt - remix, transform, and build upon the material for any purpose, even commercially.
Under following conditions:

Attribution - You must give appropriate credit, provide a link to the license, and indicate if changes were made. You may do so in any reasonable manner, but not in any way that suggests the licensor endorses you or your use.
ShareAlike - If you remix, transform, or build upon the material, you must distribute your contributions under the same license as the original.
*/
; (function ($) {
  'use strict';

  // Defaults configuration
  var defaults = {
    url: null,
    values: null,
    method: 'POST',
    target: null,
    traditional: false,
    redirectTop: false
  };

  /**
   * jQuery Redirect
   * @param {string} url - Url of the redirection
   * @param {Object} values - (optional) An object with the data to send. If not present will look for values as QueryString in the target url.
   * @param {string} method - (optional) The HTTP verb can be GET or POST (defaults to POST)
   * @param {string} target - (optional) The target of the form. "_blank" will open the url in a new window.
   * @param {boolean} traditional - (optional) This provides the same function as jquery's ajax function. The brackets are omitted on the field name if its an array.  This allows arrays to work with MVC.net among others.
   * @param {boolean} redirectTop - (optional) If its called from a iframe, force to navigate the top window.
   *//**
   * jQuery Redirect
   * @param {string} opts - Options object
   * @param {string} opts.url - Url of the redirection
   * @param {Object} opts.values - (optional) An object with the data to send. If not present will look for values as QueryString in the target url.
   * @param {string} opts.method - (optional) The HTTP verb can be GET or POST (defaults to POST)
   * @param {string} opts.target - (optional) The target of the form. "_blank" will open the url in a new window.
   * @param {boolean} opts.traditional - (optional) This provides the same function as jquery's ajax function. The brackets are omitted on the field name if its an array.  This allows arrays to work with MVC.net among others.
   * @param {boolean} opts.redirectTop - (optional) If its called from a iframe, force to navigate the top window.
   */

  $.redirect = function (url, values, method, target, traditional, redirectTop) {
    var opts = url;
    if (typeof url !== 'object') {
      opts = {
        url: url,
        values: values,
        method: method,
        target: target,
        traditional: traditional,
        redirectTop: redirectTop
      };
    }

    var config = $.extend({}, defaults, opts);
    var generatedForm = $.redirect.getForm(config.url, config.values, config.method, config.target, config.traditional);
    $('body', config.redirectTop ? window.top.document : undefined).append(generatedForm.form);
    generatedForm.submit();
    generatedForm.form.remove();
  };

  $.redirect.getForm = function (url, values, method, target, traditional) {
    method = (method && ['GET', 'POST', 'PUT', 'DELETE'].indexOf(method.toUpperCase()) !== -1) ? method.toUpperCase() : 'POST';

    url = url.split('#');
    var hash = url[1] ? ('#' + url[1]) : '';
    url = url[0];

    if (!values) {
      var obj = $.parseUrl(url);
      url = obj.url;
      values = obj.params;
    }

    values = removeNulls(values);

    var form = $('<form>')
      .attr('method', method)
      .attr('action', url + hash);

    if (target) {
      form.attr('target', target);
    }

    var submit = form[0].submit;
    iterateValues(values, [], form, null, traditional);

    return { form: form, submit: function () { submit.call(form[0]); } };
  };

  // Utility Functions
  /**
   * Url and QueryString Parser.
   * @param {string} url - a Url to parse.
   * @returns {object} an object with the parsed url with the following structure {url: URL, params:{ KEY: VALUE }}
   */
  $.parseUrl = function (url) {
    if (url.indexOf('?') === -1) {
      return {
        url: url,
        params: {}
      };
    }
    var parts = url.split('?');
    var queryString = parts[1];
    var elems = queryString.split('&');
    url = parts[0];

    var i;
    var pair;
    var obj = {};
    for (i = 0; i < elems.length; i += 1) {
      pair = elems[i].split('=');
      obj[pair[0]] = pair[1];
    }

    return {
      url: url,
      params: obj
    };
  };

  // Private Functions
  var getInput = function (name, value, parent, array, traditional) {
    var parentString;
    if (parent.length > 0) {
      parentString = parent[0];
      var i;
      for (i = 1; i < parent.length; i += 1) {
        parentString += '[' + parent[i] + ']';
      }

      if (array) {
        if (traditional) {
          name = parentString;
        } else {
          name = parentString + '[' + name + ']';
        }
      } else {
        name = parentString + '[' + name + ']';
      }
    }

    return $('<input>').attr('type', 'hidden')
      .attr('name', name)
      .attr('value', value);
  };

  var iterateValues = function (values, parent, form, isArray, traditional) {
    var iterateParent = [];
    Object.keys(values).forEach(function (i) {
      if (typeof values[i] === 'object') {
        iterateParent = parent.slice();
        iterateParent.push(i);
        iterateValues(values[i], iterateParent, form, Array.isArray(values[i]), traditional);
      } else {
        form.append(getInput(i, values[i], parent, isArray, traditional));
      }
    });
  };

  var removeNulls = function (values) {
    var propNames = Object.getOwnPropertyNames(values);
    for (var i = 0; i < propNames.length; i++) {
      var propName = propNames[i];
      if (values[propName] === null || values[propName] === undefined) {
        delete values[propName];
      } else if (typeof values[propName] === 'object') {
        values[propName] = removeNulls(values[propName]);
      } else if (values[propName].length < 1) {
        delete values[propName];
      }
    }
    return values;
  };
}(window.jQuery || window.Zepto || window.jqlite));


