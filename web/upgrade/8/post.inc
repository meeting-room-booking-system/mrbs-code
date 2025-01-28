<?php
namespace MRBS;

// Populate the room sort_key with the room name (the default value)

$upgrade_handle->command("UPDATE " . _tbl('room') . " SET sort_key=room_name");
