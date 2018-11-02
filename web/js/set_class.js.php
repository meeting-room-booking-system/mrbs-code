<?php
namespace MRBS;

require_once "../functions.inc";

http_headers(array("Content-type: application/x-javascript"),
             60*30);  // 30 minute expiry
             
             
// Add a class of "js" so that we know if we're using JavaScript or not
?>

$('body').addClass('js');
