<?php
declare(strict_types=1);
namespace MRBS\Intl;

use MRBS\System;

class LocaleSwitcher
{
  private $category;
  private $locale;
  private $old_locale;


  public function __construct(int $category, string $locale)
  {
    $this->category = $category;
    $this->locale = $locale;
  }


  public function switch()
  {
    // It's not worth testing whether the new locale is the same as the old one, thereby saving us setting
    // the locale again.  That's because setting the locale on Unix systems seems to be about 100 times
    // faster than on Windows.  So on Unix systems, it's not worth worrying about.  And on Windows, we have
    // to set the locale again anyway in case another script running in the same process has changed the
    // locale since we first set it.  See the warning on the PHP manual page for setlocale():
    //
    // "The locale information is maintained per process, not per thread. If you are running PHP on a
    // multithreaded server API like IIS or Apache on Windows, you may experience sudden changes in locale
    // settings while a script is running, though the script itself never called setlocale(). This happens
    // due to other scripts running in different threads of the same process at the same time, changing the
    // process-wide locale using setlocale()."
    $this->old_locale = setlocale($this->category, '0');
    if (false === setlocale($this->category, System::getOSlocale($this->locale)))
    {
      $message = "Could not set locale to '" . $this->locale . "'; continuing to use '" . $this->old_locale . "'.";
      trigger_error($message, E_USER_WARNING);
    }
  }


  public function restore()
  {
    if (!isset($this->old_locale))
    {
      throw new \RuntimeException("switch() must be called before restore().");
    }

    if (false === setlocale($this->category, $this->old_locale))
    {
      // Shouldn't happen as the old locale was what the system told us it was.
      throw new \RuntimeException("Could not restore locale to '" . $this->old_locale . "'");
    }
  }

}
