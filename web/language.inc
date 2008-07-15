<?php

// $Id$

// A map is needed to convert from the HTTP language specifier to a
// locale specifier for Windows
//
// These locale TLAs found at:
// http://www.microsoft.com/globaldev/reference/winxp/langtla.mspx
$lang_map_windows = array
(
  'ca' => 'cat',
  'ca-es' => 'cat',
  'cs' => 'csy',
  'cs-cz' => 'csy',
  'da' => 'dan',
  'da-dk' => 'dan',
  'de' => 'deu',
  'de-at' => 'dea',
  'de-ch' => 'des',
  'de-de' => 'deu',
  'de-li' => 'dec',
  'de-lu' => 'del',
  'el' => 'ell',
  'el-gr' => 'ell',
  'en' => 'enu',
  'en-au' => 'ena',
  'en-bz' => 'enl',
  'en-ca' => 'enc',
  'en-cb' => 'enb',
  'en-en' => 'eng',
  'en-gb' => 'eng',
  'en-ie' => 'eng',
  'en-jm' => 'enj',
  'en-nz' => 'enz',
  'en-ph' => 'enp',
  'en-tt' => 'ent',
  'en-us' => 'enu',
  'en-za' => 'ens',
  'es' => 'esp',
  'es-es' => 'esp',
  'es-mx' => 'esm',
  'eu' => 'eus',
  'eu-es' => 'eus',
  'fi' => 'fin',
  'fi-fi' => 'fin',
  'fr' => 'fra',
  'fr-be' => 'frb',
  'fr-ca' => 'frc',
  'fr-ch' => 'frs',
  'fr-fr' => 'fra',
  'it' => 'ita',
  'it-ch' => 'its',
  'it-it' => 'ita',
  'ja' => 'jpn',
  'ja-jp' => 'jpn',
  'ko' => 'kor',
  'ko-kr' => 'kor',
  'nb' => 'nor',
  'nb-no' => 'nor',
  'nl' => 'nld',
  'nl-be' => 'nlb',
  'nl-nl' => 'nld',
  'no' => 'nor',
  'no-no' => 'nor',
  'nb' => 'nor',
  'nb-no' => 'nor',
  'nn' => 'non',
  'nn-no' => 'non',
  'pl' => 'plk',
  'pl-pl' => 'plk',
  'pt' => 'ptg',
  'pt-br' => 'ptb',
  'pt-pt' => 'ptg',
  'sl' => 'slv',
  'sl-si' => 'slv',
  'sv' => 'sve',
  'sv-fi' => 'svf',
  'sv-sv' => 'sve',
  'tr' => 'trk',
  'tr-tr' => 'trk',
  'zh' => 'chs',
  'zh-tw' => 'cht',
  'zh-cn' => 'chs',
  'zh-hk' => 'zhh',
  'zh-sg' => 'zhi',
);

// This maps a Windows locale to the charset it uses, which are
// all Windows code pages
//
// The code pages used by these locales found at: 
// http://www.microsoft.com/globaldev/nlsweb/default.mspx
$winlocale_codepage_map = array
(
  'cat' => 'CP1252',
  'chs' => 'CP936',
  'cht' => 'CP950',
  'csy' => 'CP1250',
  'dan' => 'CP1252',
  'dea' => 'CP1252',
  'dec' => 'CP1252',
  'del' => 'CP1252',
  'des' => 'CP1252',
  'deu' => 'CP1252',
  'ell' => 'CP1253',
  'ena' => 'CP1252',
  'enb' => 'CP1252',
  'enc' => 'CP1252',
  'eng' => 'CP1252',
  'enj' => 'CP1252',
  'enl' => 'CP1252',
  'enp' => 'CP1252',
  'ens' => 'CP1252',
  'ent' => 'CP1252',
  'enu' => 'CP1252',
  'enz' => 'CP1252',
  'esm' => 'CP1252',
  'esp' => 'CP1252',
  'eus' => 'CP1252',
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
  'non' => 'CP1252',
  'nor' => 'CP1252',
  'ptb' => 'CP1252',
  'ptg' => 'CP1252',
  'plk' => 'CP1250',
  'slv' => 'CP1250',
  'sve' => 'CP1252',
  'svf' => 'CP1252',
  'trk' => 'CP1254',
  'zhh' => 'CP950',
  'zhi' => 'CP936',
);

// These are special cases, generally we can convert from the HTTP
// language specifier to a locale specifier without a map
$lang_map_unix = array
(
  'ca' => 'ca_ES',
  'cs' => 'cs_CZ',
  'da' => 'da_DK',
  'el' => 'el_GR',
  'en' => 'en_GB',
  'eu' => 'eu_ES',
  'ja' => 'ja_JP',
  'ko' => 'ko_KR',
  'sl' => 'sl_SI',
  'sv' => 'sv_SE',
  'zh' => 'zh_CN',
);

// IBM AIX locale to code set table
// See http://publibn.boulder.ibm.com/doc_link/en_US/a_doc_lib/libs/basetrf2/setlocale.htm
$aixlocale_codepage_map = array
(
  'Ar_AA' => 'IBM-1046',
  'ar_AA' => 'ISO8859-6',
  'bg_BG' => 'ISO8856-5',
  'cs_CZ' => 'ISO8859-2',
  'Da_DK' => 'IBM-850',
  'da_DK' => 'ISO8859-1',
  'De_CH' => 'IBM-850',
  'de_CH' => 'ISO8859-1',
  'De_DE' => 'IBM-850',
  'de_DE' => 'ISO8859-1',
  'el_GR' => 'ISO8859-7',
  'En_GB' => 'IBM-850',
  'en_GB' => 'ISO8859-1',
  'En_US' => 'IBM-850',
  'en_US' => 'ISO8859-1',
  'Es_ES' => 'IBM-850',
  'es_ES' => 'ISO8859-1',
  'Fi_FI' => 'IBM-850',
  'fi_FI' => 'ISO8859-1',
  'Fr_BE' => 'IBM-850',
  'fr_BE' => 'ISO8859-1',
  'Fr_CA' => 'IBM-850',
  'fr_CA' => 'ISO8859-1',
  'Fr_FR' => 'IBM-850',
  'fr_FR' => 'ISO8859-1 ',
  'Fr_CH' => 'IBM-850',
  'fr_CH' => 'ISO8859-1',
  'hr_HR' => 'ISO8859-2',
  'hu_HU' => 'ISO8859-2',
  'Is_IS' => 'IBM-850',
  'is_IS' => 'ISO8859-1',
  'It_IT' => 'IBM-850',
  'it_IT' => 'ISO8859-1',
  'Iw_IL' => 'IBM-856',
  'iw_IL' => 'ISO8859-8',
  'Ja_JP' => 'IBM-943',
  'ko_KR' => 'IBM-eucKR',
  'mk_MK' => 'ISO8859-5',
  'Nl_BE' => 'IBM-850',
  'nl_BE' => 'ISO8859-1',
  'Nl_NL' => 'IBM-850',
  'nl_NL' => 'ISO8859-1',
  'No_NO' => 'IBM-850',
  'no_NO' => 'ISO8859-1',
  'pl_PL' => 'ISO8859-2',
  'Pt_PT' => 'IBM-850',
  'pt_PT' => 'ISO8859-1',
  'ro_RO' => 'ISO8859-2',
  'ru_RU' => 'ISO8859-5',
  'sh_SP' => 'ISO8859-2',
  'sl_SI' => 'ISO8859-2',
  'sk_SK' => 'ISO8859-2',
  'sr_SP' => 'ISO8859-5',
  'Zh_CN' => 'GBK',
  'Sv_SE' => 'IBM-850',
  'sv_SE' => 'ISO8859-1',
  'tr_TR' => 'ISO8859-9',
  'zh_TW' => 'IBM-eucTW'
);

// GNU iconv code set to IBM AIX libiconv code set table
// Keys of this table should be in lowercase, and searches should be performed using lowercase!
$gnu_iconv_to_aix_iconv_codepage_map = array
(
  // "iso-8859-[1-9]" --> "ISO8859-[1-9]" according to http://publibn.boulder.ibm.com/doc_link/en_US/a_doc_lib/libs/basetrf2/setlocale.htm
  'iso-8859-1' => 'ISO8859-1',
  'iso-8859-2' => 'ISO8859-2',
  'iso-8859-3' => 'ISO8859-3',
  'iso-8859-4' => 'ISO8859-4',
  'iso-8859-5' => 'ISO8859-5',
  'iso-8859-6' => 'ISO8859-6',
  'iso-8859-7' => 'ISO8859-7',
  'iso-8859-8' => 'ISO8859-8',
  'iso-8859-9' => 'ISO8859-9',

  // "big5" --> "IBM-eucTW" according to http://kadesh.cepba.upc.es/mancpp/classref/ref/ITranscoder_DSC.htm
  'big5' => 'IBM-eucTW',

  // "big-5" --> "IBM-eucTW" (see above)
  'big-5' => 'IBM-eucTW'
);

// IBM AIX libiconv UTF-8 converters
// See http://publibn.boulder.ibm.com/doc_link/en_US/a_doc_lib/aixprggd/genprogc/convert_prg.htm#HDRDNNRI49HOWA
$aix_utf8_converters = array
(
  'ISO8859-1',
  'ISO8859-2',
  'ISO8859-3',
  'ISO8859-4',
  'ISO8859-5',
  'ISO8859-6',
  'ISO8859-7',
  'ISO8859-8',
  'ISO8859-9',
  'JISX0201.1976-0',
  'JISX0208.1983-0',
  'CNS11643.1986-1',
  'CNS11643.1986-2',
  'KSC5601.1987-0',
  'IBM-eucCN',
  'IBM-eucJP',
  'IBM-eucKR',
  'IBM-eucTW',
  'IBM-udcJP',
  'IBM-udcTW',
  'IBM-sbdTW',
  'UCS-2',
  'IBM-437',
  'IBM-850',
  'IBM-852',
  'IBM-857',
  'IBM-860',
  'IBM-861',
  'IBM-863',
  'IBM-865',
  'IBM-869',
  'IBM-921',
  'IBM-922',
  'IBM-932',
  'IBM-943',
  'IBM-934',
  'IBM-935',
  'IBM-936',
  'IBM-938',
  'IBM-942',
  'IBM-944',
  'IBM-946',
  'IBM-948',
  'IBM-1124',
  'IBM-1129',
  'TIS-620',
  'IBM-037',
  'IBM-273',
  'IBM-277',
  'IBM-278',
  'IBM-280',
  'IBM-284',
  'IBM-285',
  'IBM-297',
  'IBM-500',
  'IBM-875',
  'IBM-930',
  'IBM-933',
  'IBM-937',
  'IBM-939',
  'IBM-1026',
  'IBM-1112',
  'IBM-1122',
  'IBM-1124',
  'IBM-1129',
  'IBM-1381',
  'GBK',
  'TIS-620'
);


////////////////////////////////////////////////////////////////////////
// Language token handling

// Get first default set of language tokens for emails.
if (MAIL_ADMIN_ON_BOOKINGS or MAIL_AREA_ADMIN_ON_BOOKINGS or
    MAIL_ROOM_ADMIN_ON_BOOKINGS or MAIL_BOOKER)
{
  include "lang." . $default_language_tokens;
  include "lang." . MAIL_ADMIN_LANG;
  $mail_vocab = $vocab;
  unset ($vocab);
}

// Get a default set of language tokens, you can change this if you like
include "lang." . $default_language_tokens;

// Define the default locale here. For a list of supported
// locales on your system do "locale -a"
setlocale(LC_ALL,'C');

// We attempt to make up a sensible locale from the HTTP_ACCEPT_LANGUAGE
// environment variable.

// First we enumerate the user's language preferences...
if (isset($HTTP_ACCEPT_LANGUAGE)) // Attempt to use $HTTP_ACCEPT_LANGUAGE only when defined.
{
  $lang_specifiers = explode(',',$HTTP_ACCEPT_LANGUAGE);
  foreach ($lang_specifiers as $specifier)
  {
    if (preg_match('/([a-zA-Z\-]+);q=([0-9\.]+)/', $specifier, $matches))
    {
      $langs[$matches[1]] = $matches[2];
    }
    else if (preg_match("/([a-zA-Z\-]+)/", $specifier, $matches))
    {
      $langs[$matches[1]] = 1.0;
    }
  }
  arsort($langs,SORT_NUMERIC);
}
else // Else use the value from config.inc.php.
{
  $langs[$default_language_tokens] = 1.0;
}

// The following attempts to import a language based on what the client
// is using.

if (!$disable_automatic_language_changing)
{
  $doneit = 0;

  // First try for an exact match, so if the user specified en-gb, look
  // for lang.en-gb

  foreach ($langs as $lang => $qual)
  {
    $lang_file = "lang." . strtolower($lang);

    if (file_exists($lang_file))
    {
      include $lang_file;
      $doneit = 1;
      $locale = $lang;
      break;
    }
  }
  if ($doneit == 0)
  {
    // None of the user's preferred languages was available, so try to
    // find a lang file for one of the base languages, e.g. look for
    // lang.en if "en-ca" was specified.

    foreach ($langs as $lang => $qual)
    {
      $lang_file = "lang." . strtolower(substr($lang,0,2));

      if (file_exists($lang_file))
      {
        include $lang_file;
        $locale = $lang;
        break;
      }
    }
  }
}

//////////////////////////////////////////////////////////////////////
// Locale handling

$windows_locale = "eng";

// 2003/11/09 JF Larvoire: Help new admins understand what to do in case the iconv error occurs...
if (($unicode_encoding) && (!function_exists('iconv')))
{
  exit('
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <title>MRBS - iconv module not installed</title>
</head>
<body>
<p>
  <strong>Error:</strong> The iconv module, which provides PHP support for Unicode, is not
installed on your system.
</p>
<p>
  Unicode gives MRBS the ability to easily support languages other than
  English. Without Unicode, support for non-English-speaking users will
  be crippled.
</p>
<p>To fix this error, do one of the following:</p>
<ul>
  <li>
    Install and enable the iconv module.
    <ul>
      <li>
        On a Windows server, enable php_iconv.dll in %windir%\php.ini,
        and make sure both %phpdir%\dlls\iconv.dll and
        %phpdir%\extensions\php_iconv.dll are in the path. One way to
        do this is to copy these two files to %windir%.
      </li>
      <li>
        On a Unix server, recompile your PHP module with the appropriate
        option for enabling the iconv extension. Consult your PHP server
        documentation for more information about enabling iconv support.
      </li>
    </ul>
  </li>
  <li>
    Disable Unicode support by modifying config.inc.php and setting the
    variable $unicode_encoding to 0. If your MRBS installation is on a
    shared host, then this may be your only option.
  </li>
</ul>
</body>
</html>
');
}

if ($override_locale != "")
{
  if (setlocale(LC_ALL,$override_locale) == FALSE)
  {
    $locale_warning = "Server failed to set locale to
 \"".$override_locale."\" (Override locale)";
  }
  $windows_locale = $override_locale;
}
else
{
  $server_os = get_server_os();

  if ($server_os == "windows")
  {
    if ($lang_map_windows[strtolower($locale)])
    {
      if (setlocale(LC_ALL, $lang_map_windows[strtolower($locale)]) == FALSE)
      {
        $locale_warning = "Server failed to set locale to
 \"".$lang_map_windows[strtolower($locale)]."\" (Windows)";
      }
      $windows_locale = $lang_map_windows[strtolower($locale)];
    }
    else
    {
      $locale_warning = "Server failed to map browser language
 \"".$locale."\" to a Windows locale specifier";
    }
  }
  /* All of these Unix OSes work in mostly the same way... */
  else if (($server_os == "linux") ||
           ($server_os == "sunos") ||
           ($server_os == "bsd") ||
           ($server_os == "aix") ||
           ($server_os == "macosx"))
  {
    if (strlen($locale) == 2)
    {
      if (isset($lang_map_unix[$locale]) && ($lang_map_unix[$locale]))
      {
        $locale = $lang_map_unix[$locale];
      }
      else
      {
        // Convert locale=xx to xx_XX
        $locale = strtolower($locale)."_".strtoupper($locale);
      }
    }
    else
    {
      // Convert locale=xx-xX or xx_Xx or xx_XxXx (etc.) to xx_XX[XX]; this is highly
      // dependent on the machine's installed locales
      $locale = strtolower(substr($locale,0,2))."_".strtoupper(substr($locale,3));
    }
    if ($unicode_encoding)
    {
      if ($server_os == "sunos")
      {
        $locale .= ".UTF-8";
      }
      else
      {
        // On IBM AIX, do not add ".utf-8" as this yields an invalid
        // locale name
        if ($server_os != "aix")
        {
          $locale .= ".utf-8";
        }
      }
    }
    if (setlocale(LC_ALL, $locale) == FALSE)
    {
      $locale_warning = "Server failed to set locale to \"".$locale."\"
(Unix)";
    }
  }
}

function get_server_os()
{
  if (stristr(PHP_OS,"Darwin"))
  {
    return "macosx";
  }
  else if (stristr(PHP_OS, "WIN"))
  {
    return "windows";
  }
  else if (stristr(PHP_OS, "Linux"))
  {
    return "linux";
  }
  else if (stristr(PHP_OS, "BSD"))
  {
    return "bsd";
  }
  else if (stristr(PHP_OS, "SunOS"))
  {
    return "sunos";
  }
  else if (stristr(PHP_OS, "AIX"))
  {
    return "aix";
  }
  else
  {
    return "unsupported";
  }
}

// Translates a GNU libiconv character encoding name to its corresponding IBM AIX libiconv character
// encoding name. Returns FALSE if character encoding name is unknown.
function get_aix_character_encoding($character_encoding)
{
  global $gnu_iconv_to_aix_iconv_codepage_map;

  // Check arguments
  if ($character_encoding == NULL ||
      !is_string($character_encoding) ||
      empty($character_encoding))
  {
    return FALSE;
  }

  // Convert character encoding name to lowercase
  $character_encoding = strtolower($character_encoding);

  // Check that we know of an IBM AIX libiconv character encoding name equivalent for this character encoding name
  if (!array_key_exists($character_encoding, $gnu_iconv_to_aix_iconv_codepage_map))
  {
    return FALSE;
  }

  return $gnu_iconv_to_aix_iconv_codepage_map[$character_encoding];
}

function get_vocab_utf8_aix($vocab_array, $tag)
{
  global $aix_utf8_converters;

  // Attempt to translate character encoding name
  $aix_character_set = get_aix_character_encoding($vocab_array['charset']);

  // Check if character encoding name translation was successful
  if ($aix_character_set === FALSE)
  {
    // Unsuccessful; just use the original character encoding name
    $aix_character_set = $vocab_array['charset'];

  }
  else
  {
    // Successful; check that a corresponding UTF-8 converter exists
    if (!in_array($aix_character_set, $aix_utf8_converters, TRUE))
    {
      // Corresponding UTF-8 converter does not exist; just use the original character encoding name
      $aix_character_set = $vocab_array['charset'];

    }
    else
    {
      // Success; the translated character encoding name is ready to use
    }
  }

  return iconv($aix_character_set, 'UTF-8', $vocab_array[$tag]);
}


// Get a vocab item, in UTF-8 or a local encoding, depending on
// the setting of $unicode_encoding
function get_vocab($tag)
{
  global $vocab, $unicode_encoding;

  if (func_num_args() > 1)
  {
    $vocab_array = func_get_arg(1);
  }
  else
  {
    $vocab_array = $vocab;
  }

  if ($unicode_encoding && (strcasecmp($vocab_array["charset"], "utf-8") != 0))
  {
    if ((get_server_os() == 'aix') &&
        (strcasecmp(ICONV_IMPL, 'unknown') == 0) &&
        (strcasecmp(ICONV_VERSION, 'unknown') == 0))
    {
      $translated = get_vocab_utf8_aix($vocab_array, $tag);
    }
    else
    {
      $translated = iconv($vocab_array["charset"],"utf-8",$vocab_array[$tag]);
    }
  }
  else
  {
    $translated = $vocab_array[$tag];
  }
  return $translated;
}

// AIX version of utf8_convert(); needed as locales won't give us UTF-8
// NOTE: Should ONLY be called with input encoded in the default code set of the current locale!
// NOTE: Uses the LC_TIME category for determining the current locale setting, so should preferrably be used on date/time input only!
function utf8_convert_aix($vocab_array, $string)
{
  global $aixlocale_codepage_map, $aix_utf8_converters, $unicode_encoding;

  // Retrieve current locale setting
  $aix_locale = setlocale(LC_TIME, '0');

  if ($aix_locale === FALSE)
  {
    // Locale setting could not be retrieved; return string unchanged
    return $string;
  }

  if (!array_key_exists($aix_locale, $aixlocale_codepage_map))
  {
    // Default code page of locale could not be found; return string unchanged
    return $string;
  }

  $aix_codepage = $aixlocale_codepage_map[$aix_locale];

  if (!in_array($aix_codepage, $aix_utf8_converters, TRUE))
  {
    // No suitable UTF-8 converter was found for this code page; return string unchanged
    return $string;
  }

  // Convert string to UTF-8
  $aix_string = iconv($aix_codepage, 'UTF-8', $string);

  // Default to original string if conversion failed
  $string = ($aix_string === FALSE) ? $string : $aix_string;

  return $string;
}

function utf8_convert_from_locale($string)
{
  global $windows_locale, $unicode_encoding, $winlocale_codepage_map;

  if ($unicode_encoding)
  {
    if (get_server_os() == "windows")
    {
      if ($winlocale_codepage_map[$windows_locale])
      {
        $string = iconv($winlocale_codepage_map[$windows_locale],"utf-8",
                        $string);
      }
    }
    else if (get_server_os() == "aix")
    {
      $string = utf8_convert_aix($string);
    }
  }
  return $string;
}
  
function utf8_strftime($format, $time)
{
  // %p doesn't actually work in some locales, we have to patch it up ourselves
  if (preg_match('/%p/', $format))
  {
    $ampm = strftime('%p', $time);
    if ($ampm == '')
    {
      $ampm = date('a',$time);
    }

    $format = preg_replace('/%p/', $ampm, $format);
  }

  $result = strftime($format,$time);
  return utf8_convert_from_locale($result);
}


// UTF-8 compatible substr function obtained from a contribution by
// "frank at jkelloggs dot dk" in the PHP online manual for substr()
function utf8_substr($str,$start)
{
  preg_match_all("/./su", $str, $ar);

  if(func_num_args() >= 3) {
    $end = func_get_arg(2);
    return join("",array_slice($ar[0],$start,$end));
  } else {
    return join("",array_slice($ar[0],$start));
  }
}

?>
