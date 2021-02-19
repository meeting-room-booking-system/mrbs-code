<?php
namespace MRBS;

// Populate the new area column with the default setting

global $admin_handle;
global $periods_booking_opens;

$sql = "UPDATE " . _tbl('area') . "
           SET periods_booking_opens=:periods_booking_opens";

$admin_handle->command($sql, array(':periods_booking_opens' => $periods_booking_opens));
