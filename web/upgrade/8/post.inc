<?php
namespace MRBS;

// $Id$

// Populate the room sort_key with the room name (the default value)

global $tbl_room;

sql_command("UPDATE $tbl_room SET sort_key=room_name");
