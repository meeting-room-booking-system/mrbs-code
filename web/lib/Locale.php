<?php

// Emulates the PHP Locale class, for those sites that do not have the Intl extension installed.
// The class will only be found by the autoloader if the global Locale class doesn't exist.
class Locale
{
  
  // Searches the items in the array $langtag for the best match to the language range
  // specified in $locale according to RFC 4647's lookup algorithm.   The langtags and
  // locale can have subtags separated by '-' or '_' and the search is case insensitive.
  // Charsets (eg '.UTF-8') are stripped off $locale
  //
  // Returns the best match, or else an empty string if no match
  public static function lookup($langtag, $locale, $canonicalize = FALSE)
  {
    if (!empty($canonicalize))
    {
      throw new Exception('MRBS: the MRBS version of Locale::lookup() does not yet support $canonicalize = TRUE');
    }
    
    if (func_num_args() > 3)
    {
      throw new Exception('MRBS: optional fourth parameter to Locale::lookup() not yet supported');
    }
    
    // Get the langtags and locale in the same format, ie separated by '-' and
    // all lower case
    $standard_langtags = self::standardise($langtag);
    // Strip off any charset (eg '.UTF-8');
    $locale = preg_replace('/\..*$/', '', $locale);
    $standard_locale = self::standardise($locale);
    
    // Look for a match.   If there isn't one remove the last subtag from the end
    // of the locale and try again.
    while (FALSE === ($index = array_search($standard_locale, $standard_langtags)))
    {
      if (FALSE === ($pos = strrpos($standard_locale, '-')))
      {
        return '';
      }
      $standard_locale = substr($standard_locale, 0, $pos);
    }
    
    return $langtag[$index];  // Return the match in its original format
  }
  
  // Converts $langtag, which can be a string or an array, into a standard form with
  // subtags all in lower case and separated by '-';
  private static function standardise($langtag)
  {
    $glue = ',';
    $result = (is_array($langtag)) ? implode($glue, $langtag) : $langtag;
    $result = MRBS\utf8_strtolower(str_replace('_', '-', $result));
    return (is_array($langtag)) ? explode($glue, $result) : $result;
  }
}