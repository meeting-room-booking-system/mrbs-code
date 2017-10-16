<?php

namespace MRBS;

class JUser extends \JUser {
 
  // NOTE:  In some versions of Joomla!, JUser::getAuthorisedGroups() seems to reset the
  // timezone to the user's Joomla timezone, which may be different from the MRBS timezone, so
  // we have to get the timezone before calling it and restore it afterwards.
  public function getAuthorisedGroups()
  {
    $tz = date_default_timezone_get();
    $result = parent::getAuthorisedGroups();
    date_default_timezone_set($tz);
    return $result;
  }
  
  // Not sure whether getAuthorisedViewLevels() has the same problem, but just in case ...
  public function getAuthorisedViewLevels()
  {
    $tz = date_default_timezone_get();
    $result = parent::getAuthorisedViewLevels();
    date_default_timezone_set($tz);
    return $result;
  }
}
