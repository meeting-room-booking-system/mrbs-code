<?php

// $Id$

require "../defaultincludes.inc";

header("Content-type: application/x-javascript");
expires_header(60*30); // 30 minute expiry

if ($use_strict)
{
  echo "'use strict';\n";
}

// =================================================================================

// Extend the init() function 
?>

var oldInitGeneral = init;
init = function(args) {
  oldInitGeneral.apply(this, [args]);

  // if there's a logon box, set the username input field in focus
  var logonForm = document.getElementById('logon');
  if (logonForm && logonForm.NewUserName)
  {
    logonForm.NewUserName.focus();
  }
  
  <?php
  // Add in a hidden input to the header search form so that we can tell if we are using DataTables
  // (which will be if JavaScript is enabled and we're not running IE6 or below).   We
  // need to know this because when we're using an Ajax data source we don't want to send
  // the HTML version of the table data.
  //
  // Also add 'datatable=1' to the link for the user list for the same reason
  ?>
  if (!lteIE6)
  {
    $('<input>').attr({
        type: 'hidden',
        name: 'datatable',
        value: '1'
      }).appendTo('#header_search');
      
    $('#user_list_link').each(function() {
        var href = $(this).attr('href');
        href += (href.indexOf('?') < 0) ? '?' : '&';
        href += 'datatable=1';
        $(this).attr('href', href);
      });
  }
  
  <?php
  // There are some forms that have multiple submit buttons, eg a "Back" and "Save"
  // buttons.   In these cases we want hitting the Enter key in a text input field
  // to result in a "Save" rather than "Back".    So in these cases we have assigned
  // a class of 'default_action' to the one that we want to be executed when we hit
  // Enter.   (Note that it is a class rather than an id just in case we have two or
  // more such forms on a page.   However we should ensure that there is only one
  // button with this class per form.)
  ?>
  $('form input.default_action').each(function() {
      var defaultSubmitButton = $(this);
      $(this).parents('form').find('input').keypress(function(event) {
          if (event.which == 13)  // the Enter key
          {
            defaultSubmitButton.click();
            return false;
          }
          else
          {
            return true;
          }
        });
    });
};
