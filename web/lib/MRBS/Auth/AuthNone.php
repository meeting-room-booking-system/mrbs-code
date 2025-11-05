<?php
namespace MRBS\Auth;


class AuthNone extends Auth
{
  /**
   * Checks if the specified username/password pair are valid.
   *
   * This authentication scheme always validates positively.
   *
   * @return string|null
   */
  public function validateUser(
    #[\SensitiveParameter]
    ?string $user,
    #[\SensitiveParameter]
    ?string $pass)
  {
    return $user;
  }
}
