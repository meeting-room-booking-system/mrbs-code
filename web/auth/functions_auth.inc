<?php
namespace MRBS;


// Sorts an array of users indexed by 'username' and 'display_name', eg the
// output of authGetUsernames().   Sorts by display_name then username.
function sort_users(array &$users)
{
  // Obtain a list of columns
  if (function_exists('array_column'))  // PHP >= 5.5.0
  {
    $username     = array_column($users, 'username');
    $display_name = array_column($users, 'display_name');
  }
  else
  {
    $username = array();
    $display_name = array();
    
    foreach ($users as $key => $user)
    {
      $username[$key]     = $user['username'];
      $display_name[$key] = $user['display_name'];
    }
  }
  
  // Sort the data with volume descending, edition ascending
  // Add $data as the last parameter, to sort by the common key
  array_multisort($display_name, SORT_ASC, SORT_LOCALE_STRING | SORT_FLAG_CASE,
                  $username, SORT_ASC, SORT_LOCALE_STRING | SORT_FLAG_CASE,
                  $users);
}
