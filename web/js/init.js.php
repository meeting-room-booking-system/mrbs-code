<?php
declare(strict_types=1);
namespace MRBS;

require_once "../functions.inc";

http_headers(array("Content-type: application/x-javascript"),
             60*30);  // 30 minute expiry
?>

'use strict';

<?php
// Add a class of "js" so that we know if we're using JavaScript or not.
// jQuery hasn't been loaded yet, so use JavaScript ?>
var html = document.getElementsByTagName('html')[0];
html.classList.add('js');
