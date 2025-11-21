<?php
declare(strict_types=1);
namespace MRBS\Intl;

/**
 * A wrapper for the Locale class, that uses the PHP method if available, otherwise falling
 * back to the emulator.
 */
class Locale
{
  public const LANG_TAG = 'language';
  public const EXTLANG_TAG = 'extlang';
  public const SCRIPT_TAG = 'script';
  public const REGION_TAG = 'region';
  public const VARIANT_TAG = 'variant';
  public const GRANDFATHERED_LANG_TAG = 'grandfathered';
  public const PRIVATE_TAG = 'private';
  public const ACTUAL_LOCALE = 0;
  public const VALID_LOCALE = 1;


  public static function __callStatic(string $name, array $arguments)
  {
    if (method_exists('\Locale', $name))
    {
      // Check that the method we're calling also exists in the emulator class, in case the 'intl' extension is not enabled.
      assert(method_exists(__NAMESPACE__ . '\LocaleEmulator', $name), "Call to \Locale::$name which hasn't been emulated.");
      return \Locale::$name(...$arguments);
    }

    return LocaleEmulator::$name(...$arguments);
  }

}
