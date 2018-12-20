<?php
namespace MRBS;

$message = "If you have just upgraded to MRBS 1.5.1 or later please " .
           "note that the config setting '" . '$db_nopersist' . "' has been replaced " .
           "by '" . '$db_persist' . "'.  The default setting for MRBS is now *not* to " .
           "use persistent connections.";
?>

<script>
//<![CDATA[
  alert("<?php echo $message?>");
//]];
</script>

<?php
