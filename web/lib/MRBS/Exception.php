<?php
namespace MRBS;

// At the moment MRBS\Exception is identical to \Exception and only exists to catch
// code throwing a new Exception within the MRBS namespace.   However it does allow
// us to do MRBS specific exception handling in the future.

class Exception extends \Exception
{
}
