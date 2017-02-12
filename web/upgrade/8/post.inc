<?php
namespace MRBS;

// Populate the room sort_key with the room name (the default value)

global $tbl_room, $admin_handle;

$admin_handle->command("UPDATE $tbl_room SET sort_key=room_name");
