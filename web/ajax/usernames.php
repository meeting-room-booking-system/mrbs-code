<?php
namespace MRBS;

// Returns an object containing all the usernames available for use by the Select2
// tool on the edit_entry page.

// There are improvements that could be made:
//   1.  We could arrange for the usernames to be returned in a Select2 friendly
//       format so that we don't have to iterate through them here.
//   2.  We could take advantage of the Select2 'term' parameter to limit the number
//       of results that have to be returned.
//   3.  We could take advantage of pagination.
// See https://select2.org/data-sources/ajax fot more details

use MRBS\Form\Form;

require '../defaultincludes.inc';

// Check the CSRF token
Form::checkToken();

// Check the user is authorised for this page
checkAuthorised(this_page());

$result = (object) array();
$result->results = array();

if (function_exists(__NAMESPACE__ . "\\authGetUsernames"))
{
  $names = authGetUsernames();

  foreach ($names as $name)
  {
    $element = (object)array();
    $element->id = $name['username'];
    $element->text = $name['display_name'];
    $result->results[] = $element;
  }
}

http_headers(array("Content-Type: application/json"));

echo json_encode($result);
