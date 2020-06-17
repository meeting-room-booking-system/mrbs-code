<?php
namespace MRBS;

// Check whether there is any custom HTML in the room and area tables.   If there is and 
// they haven't set $auth['allow_custom_html'] to true, then warn them that custom HTML is
// now disabled by default for security reasons and they will have to enable it if they
// want to continue using it.

global $auth, $admin_handle;

if (empty($auth['allow_custom_html']))
{
  $sql = "SELECT custom_html
            FROM " . _tbl('area') . " UNION SELECT custom_html FROM " . _tbl('room');
  $custom_html = $admin_handle->query_array($sql);
  
  foreach ($custom_html as $value)
  {
    if (isset($value) && ($value !== ''))
    {
      ?>

      <script>
      //<![CDATA[
        alert('You are using custom HTML. For security reasons this has now been disabled ' +
              'by default. If you want to continue using custom HTML and trust the HTML that ' +
              'is being used, then you need to set "$auth[\'allow_custom_html\'] = true;" in ' +
              'your config.inc.php file.');
      //]];
      </script>

      <?php
      break;
    }
  }

}
