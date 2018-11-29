<?php
namespace MRBS;

// Check whether there the $year_range config settings are being 
// used.   They have now been discontinued.

global $year_range;

if (isset($year_range))
{
  ?>

  <script>
  //<![CDATA[
    alert('You appear to be using the $year_range config variable.  Please note that this ' +
          'setting has now been discontinued and so you can remove it from your config file ' +
          'as it is now redundant.');
  //]];
  </script>

  <?php
}

