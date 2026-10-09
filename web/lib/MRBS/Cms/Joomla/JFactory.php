<?php

namespace MRBS\Cms\Joomla;

class JFactory extends \JFactory {

  // NOTE:  JFactory::getUser() seems to reset the timezone to the user's
  // Joomla timezone, which may be different from the MRBS timezone, so we
  // have to get the timezone before calling it and restore it afterwards.
  // (This doesn't seem to happen in Joomla 5. It may also be OK in some earlier versions.)
  public static function getUser($username = null)
  {
    $tz = date_default_timezone_get();

    // As JFactory::getUser can take either a username (string) or a
    // user_id (integer), this creates problems when the username is
    // something like "1234", which is a valid username but is treated
    // as an id.   So convert everything to an id first.
    if (is_string($username))
    {
      $user_id = \JUserHelper::getUserId($username);
      if (is_null($user_id))
      {
        // The user doesn't exist.  Set the user_id to zero, which guarantees that parent::getUser() will return
        // a user that doesn't exist (the id will be 0 and the username NULL).  (Note: this assumes the default
        // installation of Joomla, ie AUTOINCREMENT hasn't been set to a value other than 1.)
        $user_id = 0;
      }
    }
    else
    {
      $user_id = $username;
    }

    // need to cast the object to MRBS\JUser to avoid more
    // Joomla timezone problems
    $result = self::cast('MRBS\Cms\Joomla\JUser', parent::getUser($user_id));
    date_default_timezone_set($tz);
    return $result;
  }

  /**
   * Class casting
   *
   * @param string|object $destination
   * @param object $sourceObject
   * @return object
   */
  private static function cast($destination, $sourceObject)
  {
    if (is_string($destination))
    {
        $destination = new $destination();
    }

    $sourceReflection = new \ReflectionObject($sourceObject);
    $destinationReflection = new \ReflectionObject($destination);
    $sourceProperties = $sourceReflection->getProperties();

    foreach ($sourceProperties as $sourceProperty)
    {
      $sourceProperty->setAccessible(true);
      $name = $sourceProperty->getName();
      $value = $sourceProperty->getValue($sourceObject);
      if ($destinationReflection->hasProperty($name))
      {
        $propDest = $destinationReflection->getProperty($name);
        $propDest->setAccessible(true);
        $propDest->setValue($destination,$value);
      }
      else
      {
        $destination->$name = $value;
      }
    }

    return $destination;
  }
}
