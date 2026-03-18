<?php
declare(strict_types=1);
namespace MRBS\SessionHandler;


class SessionHandlerDbException extends \Exception
{
  const TABLE_NOT_EXISTS  = 1;
  const NO_MULTIPLE_LOCKS = 2;
}
