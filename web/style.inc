<?php

// $Id$

?>
  <LINK REL="stylesheet" href="mrbs.css" type="text/css">
  <META HTTP-EQUIV="Content-Type" content="text/html; charset=<?php echo $vocab["charset"]?>">
<?php

global $refresh_rate;

if ($refresh_rate != 0)
{
    echo "  <META HTTP-EQUIV=\"Refresh\" CONTENT=\"$refresh_rate\">\n";
}
?>
