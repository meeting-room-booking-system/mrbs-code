<?php
// +---------------------------------------------------------------------------+
// | Meeting Room Booking System.
// +---------------------------------------------------------------------------+
// | MRBS table destruction script.
// |---------------------------------------------------------------------------+
// | This exists because I can never remember the sequence name magic.
// | Usage: put this file in you mrbs directory and run it in you browser.
// | Note: this script will use the default mrbs login defined in
// | config.inc.php. This user must be allowed to DROP tables.
// +---------------------------------------------------------------------------+
// | @author    thierry_bo.
// | @version   $Revision$.
// +---------------------------------------------------------------------------+
//
// $Id$

require "config.inc.php";
require_once("database.inc.php");

$mdb->dropTable("mrbs_area");
$mdb->dropSequence("mrbs_area_id");
$mdb->dropTable("mrbs_room");
$mdb->dropSequence("mrbs_room_id");
$mdb->dropTable("mrbs_entry");
$mdb->dropSequence("mrbs_entry_id");
$mdb->dropTable("mrbs_repeat");
$mdb->dropSequence("mrbs_repeat_id");
?>