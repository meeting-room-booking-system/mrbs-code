<?php
namespace MRBS;


abstract class Location extends Table
{
  protected $is_visible;
  protected $is_writable;
  protected $is_book_admin;

  public static function getById($id)
  {
    return self::getByColumn('id', $id);
  }


  public static function getByName($name)
  {
    // This method should really be declared as an abstract public static function,
    // but in PHP 5 that throws a strict standards warning.  It's OK in PHP 7 onwards,
    // but while we are still supporting PHP 5 we need to do something else.
    // (An alternative solution that might make sense is to rename the room_name and
    // area_name columns to just 'name', which would have the added benefit of
    // simplifying the tables).
    throw new \Exception("getByName() needs to be implemented in the child class.");
  }

  abstract public function isDisabled();

  abstract public function getPermissions(array $role_ids);


  // Determines whether the location is visible to the currently logged in user
  public function isVisible()
  {
    if (!isset($this->is_visible))
    {
      $this->is_visible = $this->isAble(LocationRule::READ,
                                        session()->getCurrentUser());
    }

    return $this->is_visible;
  }


  // Determines whether $user, which is either a \MRBS\User object or null, can perform
  // $operation in this location
  public function isAble($operation, $user)
  {
    // We can get rid of the assert when the minimum PHP version is 7.1 or greater and
    // we can use a nullable type
    assert(is_null($user) || ($user instanceof User),
           '$user must be null or of class ' . __NAMESPACE__ . '\User');

    if (isset($user))
    {
      // Admins can do anything
      if ($user->isAdmin())
      {
        return true;
      }
      $rules = $user->getRules($this);
    }
    else
    {
      // If there's no logged in user, use the default rules
      $rules = array($this->getDefaultPermission($user));
    }

    return LocationRule::can($rules, $operation);
  }


  // Gets the default permission for a user, which is either a \MRBS\User object or null
  // TODO: make this is configurable?  Either in the config file or through the browser
  public function getDefaultPermission($user)
  {
    // We can get rid of the assert when the minimum PHP version is 7.1 or greater and
    // we can use a nullable type
    assert(is_null($user) || ($user instanceof User),
           '$user must be null or of class ' . __NAMESPACE__ . '\User');

    if ($this instanceof Room)
    {
      $result = new RoomRule();
    }
    elseif ($this instanceof Area)
    {
      $result = new AreaRule();
    }
    else
    {
      throw new \Exception("Unknown child class");
    }

    $result->state = $result::GRANTED;

    if (!isset($user))
    {
      $result->permission = $result::READ;
    }
    elseif ($user->isAdmin())
    {
      $result->permission = $result::ALL;
    }
    else
    {
      $result->permission = $result::WRITE;
    }

    return $result;
  }
}
