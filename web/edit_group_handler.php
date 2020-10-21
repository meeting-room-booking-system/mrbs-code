<?php
namespace MRBS;

use MRBS\Form\Form;

require "defaultincludes.inc";

// Check the CSRF token.
Form::checkToken();

// Check the user is authorised for this page
checkAuthorised(this_page());

$returl = 'edit_group.php';

location_header($returl);
