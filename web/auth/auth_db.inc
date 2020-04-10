<?php
namespace MRBS;

/*****************************************************************************
*
*   File name       auth_db.inc
*
*   Description     Authenticate users from a table in the MRBS database.
*
*   Notes           To use this authentication scheme, set in config.inc.php:
*         $auth["type"]  = "db";
*
*   History
*    Available in the source control system
*
******************************************************************************/




// Return an array of all users
function authGetUsers()
{
  global $tbl_users;
  
  $res = db()->query("SELECT * FROM $tbl_users ORDER BY name");
  
  return $res->all_rows_keyed();
}

