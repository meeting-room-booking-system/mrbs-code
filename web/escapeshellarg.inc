<?php

/* $Id$
 *
 * Included if your PHP version is less than 4.0.3 and therefore this
 * function doesn't exist.
 *
 */

function escapeshellarg($x)
{
    return "'".ereg_replace("'", "'\\''", $x)."'";
} 

?>
