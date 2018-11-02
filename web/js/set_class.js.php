<?php
namespace MRBS;

require_once "../functions.inc";

http_headers(array("Content-type: application/x-javascript"),
             60*30);  // 30 minute expiry
             
             
// Add a class of "js" so that we know if we're using JavaScript or not
// and remove the non_js class (it's sometimes useful to know that we're
// not running JavaScript)
?>

$('body').addClass('js').removeClass('non_js');
