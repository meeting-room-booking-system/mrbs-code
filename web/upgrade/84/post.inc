<?php
namespace MRBS;

// Populate the new area column with the default setting

global $periods_booking_opens;

$sql = "UPDATE " . _tbl('area') . "
           SET periods_booking_opens=:periods_booking_opens";

$upgrade_handle->command($sql, array(':periods_booking_opens' => $periods_booking_opens));
