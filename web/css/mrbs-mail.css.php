<?php
namespace MRBS;

require_once "systemdefaults.inc.php";
require_once "config.inc.php";
require_once "theme.inc";

global $body_background_color, $standard_font_color, $standard_font_family;
global $banner_back_color, $banner_font_color;
?>

/* CSS to be used for email messages  */

body#mrbs {
  background-color: <?php echo $body_background_color ?>;
  color: <?php echo $standard_font_color ?>;
  font-family: <?php echo $standard_font_family ?>;
  margin: 0;
  padding: 0;
}

div#header {
  width: 100%;
  background-color: <?php echo $banner_back_color ?>;
  color: <?php echo $banner_font_color ?>;
  margin: 0;
  padding: 10px;
}

div#contents {
  width: 100%;
  padding: 10px;
  margin-bottom: 1em;
}

#mrbs a:link {
  color: #0B263B;
  text-decoration: none;
  font-weight: bold;
}

#mrbs a:visited {
  color: #0B263B;
  text-decoration: none;
  font-weight: bold;
}

#mrbs a:hover {
  color: #ff0066;
  text-decoration: underline;
  font-weight: bold;
}

#mrbs tr {
  padding: 0;
  margin: 0;
}

#mrbs th, #mrbs td {
  text-align: left;
  padding: 1px 1em;
  margin: 0;
}
