<?php

# $Id$

# A map is needed to convert from the HTTP language specifier to a
# locale specifier for Windows
$lang_map_windows = array
(
  'cz' => 'csy',
  'cz-cz' => 'csy',
  'da' => 'dan',
  'da-da' => 'dan',
  'de' => 'deu',
  'de-at' => 'dea',
  'de-ch' => 'des',
  'de-de' => 'deu',
  'el' => 'ell',
  'el-el' => 'ell',
  'el-gr' => 'ell',
  'en' => 'eng',
  'en-au' => 'ena',
  'en-ca' => 'enc',
  'en-en' => 'eng',
  'en-nz' => 'enz',
  'en-gb' => 'eng',
  'en-us' => 'usa',
  'es' => 'esp',
  'es-es' => 'esp',
  'es-mx' => 'esm',
  'fi' => 'fin',
  'fi-fi' => 'fin',
  'fr' => 'fra',
  'fr-be' => 'frb',
  'fr-ca' => 'frc',
  'fr-ch' => 'frs',
  'fr-fr' => 'fra',
  'it' => 'ita',
  'it-ch' => 'its',
  'it-it' => 'its',
  'ja' => 'jpn',
  'ja-ja' => 'jpn',
  'ja-jp' => 'jpn',
  'ko' => 'kor',
  'ko-ko' => 'kor',
  'ko-kr' => 'kor',
  'nl' => 'nld',
  'nl-be' => 'nlb',
  'nl-nl' => 'nld',
  'no' => 'norwegian',
  'no-no' => 'norwegian',
  'nb' => 'nor',
  'nb-nb' => 'nor',
  'nn' => 'non',
  'nn-nn' => 'non',
  'pl' => 'plk',
  'pl-pl' => 'plk',
  'pt' => 'ptg',
  'pt-br' => 'ptb',
  'pt-pt' => 'ptg',
  'sv' => 'sve',
  'sv-sv' => 'sve',
  'zh' => 'chinese',
  'zh-tw' => 'cht',
  'zh-cn' => 'chs',
  'zh-hk' => 'cht',
  'zh-sg' => 'cht',
  'zh-zh' => 'chinese',
);

# This maps a Windows locale to the charset it uses, which are
# all Windows code pages
$winlocale_codepage_map = array
(
  'chs' => 'CP936',
  'cht' => 'CP950',
  'csy' => 'CP1250',
  'dan' => 'CP1252',
  'dea' => 'CP1252',
  'des' => 'CP1252',
  'deu' => 'CP1252',
  'ell' => 'CP1253',
  'ena' => 'CP1252',
  'enc' => 'CP1252',
  'eng' => 'CP1252',
  'enz' => 'CP1252',
  'esm' => 'CP1252',
  'esp' => 'CP1252',
  'fin' => 'CP1252',
  'fra' => 'CP1252',
  'frb' => 'CP1252',
  'frc' => 'CP1252',
  'frs' => 'CP1252',
  'ita' => 'CP1252',
  'its' => 'CP1252',
  'jpn' => 'CP932',
  'kor' => 'CP949',
  'nlb' => 'CP1252',
  'nld' => 'CP1252',
  'norwegian' => 'CP1252',
  'ptb' => 'CP1252',
  'ptg' => 'CP1252',
  'plk' => 'CP1250',
  'sve' => 'CP1252',
  'usa' => 'CP1252'
);

# These are special cases, generally we can convert from the HTTP
# language specifier to a locale specifier without a map
$lang_map_unix = array
(
  'da_DA' => 'da_DK',
  'el_EL' => 'el_GR',
  'ja_JA' => 'ja_JP',
  'ko_KO' => 'ko_KR',
  'sv_SV' => 'sv_SE',
  'zh_ZH' => 'zh_CN',
);


##############################################################################
# Language token handling

# Get a default set of language tokens, you can change this if you like
include "lang." . $default_language_tokens;

# Define the default locale here. For a list of supported
# locales on your system do "locale -a"
setlocale(LC_ALL,'C');

# We attempt to make up a sensible locale from the HTTP_ACCEPT_LANGUAGE
# environment variable. If this doesn't work for you, comment it out
# and assign locale directly.
# If HTTP_ACCEPT_LANGUAGE is a comma-separated list, take the first one.
$locale = ereg_replace(",.*", "", getenv('HTTP_ACCEPT_LANGUAGE'));


# The following attempts to import a language based on what the client
# is using.

if (!$disable_automatic_language_changing)
{
  $lang_file = "lang." . $locale;
  if (file_exists($lang_file))
  {
    include $lang_file;
  }
  else
  {
    $lang_file = "lang." . preg_replace("/(\w+)_(\w+)/", "\\1-\\2", $locale);
    if (file_exists($lang_file))
    {
      include $lang_file;
    }
    else
    {
      $lang_file = "lang.". strtolower(substr($locale,0,2));
      if (file_exists($lang_file))
      {
        include $lang_file;
      }
    }
  }
}

##############################################################################
# Locale handling

$windows_locale = "eng";

if ($override_locale != "")
{
  setlocale(LC_ALL,$locale_override);
  $windows_locale = $locale_override;
}
else
{
  $server_os = get_server_os();

  if ($server_os == "windows")
  {
    if ($lang_map_windows[strtolower($locale)])
    {
      setlocale(LC_ALL, $lang_map_windows[strtolower($locale)]);
      $windows_locale = $lang_map_windows[strtolower($locale)];
    }
  }
  else if ($server_os == "unix")
  {
    if (strlen($locale) == 2)
    {
      # Convert locale=xx to xx_XX; this is not correct for some locales???
      $locale = strtolower($locale)."_".strtoupper($locale);
    }
    else
    {
      # Convert locale=xx-xX or xx_Xx or xx_XxXx (etc.) to xx_XX[XX]; this is highly
      # dependent on the machine's installed locales
      $locale = strtolower(substr($locale,0,2))."_".strtoupper(substr($locale,3));
    }
    if ($lang_map_unix[$locale])
    {
      $locale = $lang_map_unix[$locale];
    }
    if ($unicode_encoding)
    {
      $locale .= ".utf-8";
    }
    setlocale(LC_ALL, $locale);
  }
}

function get_server_os()
{
  if (stristr(PHP_OS, "WIN"))
  {
    return "windows";
  }
  else if (stristr(PHP_OS, "ux") || stristr(PHP_OS, "BSD"))
  {
    return "unix";
  }
  else if (stristr(PHP_OS, "Mac"))
  {
    return "mac";
  }
}

# Get a vocab item, in UTF-8 or a local encoding, depending on
# the setting of $unicode_encoding
function get_vocab($tag)
{
  global $vocab, $unicode_encoding;
  
  if ($unicode_encoding)
  {
    return iconv($vocab["charset"],"utf-8",$vocab[$tag]);
  }
  else
  {
    return $vocab[$tag];
  }
}

function utf8_convert($string)
{
  global $windows_locale, $unicode_encoding, $winlocale_codepage_map;
  
  if ($unicode_encoding && (get_server_os() == "windows"))
  {
    if ($winlocale_codepage_map[$windows_locale])
    {
      $string = iconv($winlocale_codepage_map[$windows_locale],"utf-8",$string);
    }
  }
  return $string;
}
  
function utf8_strftime($format, $time)
{
  $result = strftime($format,$time);
  return utf8_convert($result);
}

function utf8_date($format, $time)
{
  $result = date($format,$time);
  return utf8_convert($result);
}

?>
