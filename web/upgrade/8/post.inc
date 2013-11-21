<?php

// $Id$

// Populate the room sort_key with the room name (the default value)

global $tbl_room;

if (sql_command("UPDATE $tbl_room SET sort_key=room_name") < 0)
{
  fatal_error(0, "Error updating table. " . sql_error());
}

