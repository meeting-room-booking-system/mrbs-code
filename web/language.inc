<?php

// $Id$

require_once "functions.inc";

// A map is needed to convert from the HTTP language specifier to a
// locale specifier for Windows
//
// The ordering of this array is important as it is also used to map in the
// reverse direction, ie to convert a Windows style locale into an xx-yy style
// locale by finding the first occurence of a value and then using the
// corresponding key.
//
// These locale TLAs found at:
// http://www.microsoft.com/globaldev/reference/winxp/langtla.mspx
$lang_map_windows = array
(
  'bg' => 'bgr',
  'bg-bg' => 'bgr',
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
  'he' => 'heb',
  'he-il' => 'heb',
  'hr' => 'hrv',
  'hr-hr' => 'hrv',
  'hu' => 'hun',
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
  'ru' => 'rus',
  'ru-ru' => 'rus',
  'sl' => 'slv',
  'sl-si' => 'slv',
  'sv' => 'sve',
  'sv-fi' => 'svf',
  'sv-sv' => 'sve',
  'th' => 'tha',
  'th-th' => 'tha',
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
  'bgr' => 'CP1251',
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
  'heb' => 'CP1255',
  'hrv' => 'CP1250',
  'hun' => 'CP1250',
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
  'rus' => 'CP1251',
  'slv' => 'CP1250',
  'sve' => 'CP1252',
  'svf' => 'CP1252',
  'tha' => 'CP874',
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

// A list of languages that use Right to Left text
$rtl_languages = array('he');

////////////////////////////////////////////////////////////////////////
// Language token handling

// Get a default set of language tokens, you can change this if you like.
// Always include English as the fallback language, in case the selected language
// is missing some translated tokens
include "lang.en";
// include_once just to avoid reloading the 'en' file
include_once "lang." . $default_language_tokens;


// Get first default set of language tokens for emails.
$need_to_send_mail = ($mail_settings['admin_on_bookings'] or
                      $mail_settings['area_admin_on_bookings'] or
                      $mail_settings['room_admin_on_bookings'] or
                      $mail_settings['booker'] or
                      $mail_settings['book_admin_on_approval']);
                      
if ($need_to_send_mail)
{
  $web_vocab = $vocab;   // Save $vocab before it gets overwritten
  include "lang." . $mail_settings['admin_lang'];
  $mail_vocab = $vocab;
  $vocab = $web_vocab;  // Restore $vocab
}



// Define the default locale here. For a list of supported
// locales on your system do "locale -a"
setlocale(LC_ALL,'C');

// We attempt to make up a sensible locale from the HTTP_ACCEPT_LANGUAGE
// environment variable.
$cli_mode = is_cli();

// If we're running from the CLI, use the config setting
if ($cli_mode && !empty($cli_language))
{
  $langs[$cli_language] = 1.0;
}
// Otherwise we enumerate the user's language preferences...
elseif (isset($HTTP_ACCEPT_LANGUAGE)) // Attempt to use $HTTP_ACCEPT_LANGUAGE only when defined.
{
  $lang_specifiers = explode(',',$HTTP_ACCEPT_LANGUAGE);
  foreach ($lang_specifiers as $specifier)
  {
    if (preg_match('/([a-zA-Z\-]+);q=([0-9\.]+)/', $specifier, $matches))
    {
      $langs[$matches[1]] = (float) $matches[2];
    }
    else if (preg_match("/([a-zA-Z\-]+)/", $specifier, $matches))
    {
      $langs[$matches[1]] = 1.0;
    }
  }
  langs_arsort($langs);
}
else // Else use the value from config.inc.php.
{
  $langs[$default_language_tokens] = 1.0;
}

// The following attempts to import a language based on what the client
// is using.

if (!$disable_automatic_language_changing || $cli_mode)
{
  $doneit = 0;

  // First try for an exact match, so if the user specified en-gb, look
  // for lang.en-gb

  foreach ($langs as $lang => $qual)
  {
    $lang_file = "lang." . strtolower($lang);

    // Set the locale even if MRBS hasn't got a translation, at least
    // we'll get time formats right
    if (!isset($locale))
    {
      $locale = $lang;
    }
    // When in CLI mode, we need to add the full path name as
    // file_exists() ignores the include path
    if ($cli_mode)
    {
      $lang_file = dirname($PHP_SELF) . "/" . $lang_file;
    }
    if (file_exists($lang_file))
    {
      include $lang_file;
      $locale = $lang;
      // Work out if we're using a RTL language.   This variable is used
      // globally so that we can include the correct stylesheet
      $using_rtl = in_array(strtolower($lang), $rtl_languages);
      $doneit = 1;
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
      // When in CLI mode, we need to add the full path name as
      // file_exists() ignores the include path
      if ($cli_mode)
      {
        $lang_file = dirname($PHP_SELF) . "/" . $lang_file;
      }
      if (file_exists($lang_file))
      {
        include $lang_file;
        // If we have found a translation, update the locale to match
        $locale = $lang;
        break;
      }
    }
  }
}

//////////////////////////////////////////////////////////////////////
// Locale handling

$server_os = get_server_os();  // used globally

// 2003/11/09 JF Larvoire: Help new admins understand what to do in case the iconv error occurs...
if (!function_exists('iconv') && 
    (($server_os == 'windows') || ($server_os == 'aix') || (get_charset() != get_csv_charset())) )
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
<p>
  To fix this error, you need to install and enable the iconv module.
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
</p>
</body>
</html>
');
}

$locale_warning = '';
$windows_locale = "eng";
set_mrbs_locale();


// Works out what locale should be used by MRBS depending on the config settings
// and the user's browser preferences.
// Returns the locale, or FALSE if no locale can be determined
function determine_mrbs_locale()
{
  global $override_locale;
  global $locale_warning, $locale, $windows_locale, $server_os;
  global $lang_map_unix, $lang_map_windows;
  
  $mrbs_locale = FALSE;
  
  if ($override_locale != "")
  {
    $mrbs_locale = $override_locale;
    $windows_locale = $mrbs_locale;
  }
  else
  {
    if ($server_os == "windows")
    {
      if ($lang_map_windows[strtolower($locale)])
      {
        $mrbs_locale = $lang_map_windows[strtolower($locale)];
        $windows_locale = $mrbs_locale;
      }
      else
      {
        $locale_warning = "Server failed to map browser language \"" .
                          $locale .
                          "\" to a Windows locale specifier";
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
          $mrbs_locale = $lang_map_unix[$locale];
        }
        else
        {
          // Convert locale=xx to xx_XX
          $mrbs_locale = strtolower($locale)."_".strtoupper($locale);
        }
      }
      else
      {
        // Convert locale=xx-xX or xx_Xx or xx_XxXx (etc.) to xx_XX[XX]; this is highly
        // dependent on the machine's installed locales
        $mrbs_locale = locale_format($locale, '_');
      }
      switch ($server_os)
      {
        case "sunos":
        case "linux":
        case "bsd":
          $mrbs_locale .= ".UTF-8";
          break;

        case "macosx":
          $mrbs_locale .= ".utf-8";
          break;
      }
    }
  }
  return $mrbs_locale;
}


// Sets the locale according to the MRBS config seetings and the user's
// browser preferences
function set_mrbs_locale()
{
  global $locale_warning;
  
  static $locale = '';
  static $have_locale = FALSE;
  static $have_valid_locale = FALSE;
  
  // If we've tried this before and have got a good locale, then set it.
  if ($have_locale)
  {
    if ($have_valid_locale)
    {
      setlocale(LC_ALL, $locale);
    }
    return;
  }
  // Otherwise work out what the locale should be and set it.
  $locale = determine_mrbs_locale();
  $have_locale = TRUE;
  if ($locale !== FALSE)
  {
    $have_valid_locale = (setlocale(LC_ALL, $locale) !== FALSE);
    if (!$have_valid_locale)
    {
      $locale_warning = "Server failed to set locale to '$locale'";
    }
  }
}


// Returns the pathname of the language file to use for the dataTables
// jQuery plugin.    If no suitable language file exists then returns FALSE
function get_datatable_lang()
{
  global $default_language_tokens, $disable_automatic_language_changing, $override_locale;
  global $langs, $lang_map_windows, $server_os;
  
  $found_lang = FALSE;
  $language_dir = "jquery/datatables/language";  // location of dataTables language files
  
  // If automatic language changing is disabled, then use the $override_locale
  // as the first choice of language, and then second the default language tokens
  if ($disable_automatic_language_changing)
  {
    $preferred_langs = array();
    if (!empty($override_locale))
    {
      if ($server_os == 'windows')
      {
        // If the server is running on Windows we'll have to try and translate the 
        // Windows style locale back into an xx-YY locale
        $locale = array_search($override_locale, $lang_map_windows);
      }
      else
      {
        $locale = $override_locale;
      }
      $preferred_langs[$locale] = 1.0;
    }
    $preferred_langs[$default_language_tokens] = 0.5;
  }
  // Otherwise use the browser preferences
  else
  {
    $preferred_langs = $langs;
  }
  
  // Sort the array in reverse order, ie so that the preferred language
  // is at the start of the array
  arsort($preferred_langs, SORT_NUMERIC);
  // First try for an exact match, so if the user specified en-gb, look
  // for en-gb.txt
  foreach ($preferred_langs as $lang => $qual)
  {
    $lang_file = strtolower(locale_format($lang, '-'));
    $lang_file = "$language_dir/$lang_file.txt";
    if (file_exists($lang_file))
    {
      $found_lang = TRUE;
      break;
    }
  }
  if (!$found_lang)
  {
    // None of the user's preferred languages was available, so try to
    // find a lang file for one of the base languages, e.g. look for
    // lang.en if "en-ca" was specified.
    foreach ($preferred_langs as $lang => $qual)
    {
      $lang_file = strtolower(substr($lang,0,2));
      $lang_file = "$language_dir/$lang_file.txt";
      if (file_exists($lang_file))
      {
        $found_lang = TRUE;
        break;
      }
    }
  }
  
  return ($found_lang) ? $lang_file : FALSE;
}


function get_charset()
{
  return 'utf-8';
}


function get_csv_charset()
{
  global $csv_charset;
  if (empty($csv_charset))
  {
    return get_charset();
  }
  else
  {
    return $csv_charset;
  }
}


function get_bom($charset)
{
  switch(strtolower($charset))
  {
    case 'utf-8':
      return "\xEF\xBB\xBF";
      break;
    case 'utf-16':
      return "\xFE\xFF";
      break;
    default:
      return '';
      break;
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


// Get a vocab item, in UTF-8
function get_vocab($tag)
{
  global $vocab;

  if (func_num_args() > 1)
  {
    $vocab_array = func_get_arg(1);
  }
  else
  {
    $vocab_array = $vocab;
  }

  return $vocab_array[$tag];
}


// Get localised booking type name
function get_type_vocab($type)
{
  return get_vocab("type.$type");
}


// Get localized field name for a user defined table column
// Looks for a tag of the format tablename.columnname (where tablename is
// stripped of the table prefix) and if can't find a string for that tag will
// return the columnname
function get_loc_field_name($table, $name)
{
  global $vocab, $db_tbl_prefix;
  
  $tag = substr($table, strlen($db_tbl_prefix));  // strip the prefix off the table name
  $tag .= "." . $name;           // add on the fieldname
  // then if there's a string in the vocab array for $tag use that
  // otherwise just use the fieldname
  return (isset($vocab[$tag])) ? get_vocab($tag) : $name;
}


// AIX version of utf8_convert(); needed as locales won't give us UTF-8
// NOTE: Should ONLY be called with input encoded in the default code set of the current locale!
// NOTE: Uses the LC_TIME category for determining the current locale setting, so should preferrably be used on date/time input only!
function utf8_convert_aix($string)
{
  global $aixlocale_codepage_map, $aix_utf8_converters;

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
  global $windows_locale, $winlocale_codepage_map, $server_os;

  if ($server_os == "windows")
  {
    if ($winlocale_codepage_map[$windows_locale])
    {
      $string = iconv($winlocale_codepage_map[$windows_locale],"utf-8",
                      $string);
    }
  }
  else if ($server_os == "aix")
  {
    $string = utf8_convert_aix($string);
  }
  return $string;
}


//  
function utf8_strftime($format, $time)
{
  global $server_os;

  if ($server_os == "windows")
  {
    // Some formats not supported on Windows.   Replace with suitable alternatives
    $format = str_replace("%R", "%H:%M", $format);
    $format = str_replace("%P", "%p", $format);
    $format = str_replace("%l", "%I", $format);
    // If we are running Windows we have to set the locale again in case another script
    // running in the same process has changed the locale since we first set it.  See the
    // warning on the PHP manual page for setlocale():
    //
    // "The locale information is maintained per process, not per thread. If you are 
    // running PHP on a multithreaded server API like IIS or Apache on Windows, you may
    // experience sudden changes in locale settings while a script is running, though
    // the script itself never called setlocale(). This happens due to other scripts
    // running in different threads of the same process at the same time, changing the
    // process-wide locale using setlocale()."
    set_mrbs_locale();
  }
  
  // %p doesn't actually work in some locales, we have to patch it up ourselves
  if (preg_match('/%p/', $format))
  {
    $ampm = strftime('%p', $time);  // Don't relace the %p with the $strftime_format variable!!
    if ($ampm == '')
    {
      $ampm = date('a', $time);
    }

    $format = preg_replace('/%p/', $ampm, $format);
  }

  $result = strftime($format, $time);
  return utf8_convert_from_locale($result);
}


// UTF-8 compatible substr function obtained from a contribution by
// "frank at jkelloggs dot dk" in the PHP online manual for substr()
function utf8_substr_old($str,$start)
{
  preg_match_all("/./su", $str, $ar);

  if(func_num_args() >= 3) {
    $length = func_get_arg(2);
    return join("", array_slice($ar[0], $start, $length));
  } else {
    return join("", array_slice($ar[0], $start));
  }
}


// UTF-8 compatible substr function
function utf8_substr($str, $start)
{
  if (func_num_args() >= 3)
  {
    $length = func_get_arg(2);
  }
  else
  {
    $length = PHP_INT_MAX;
  }

  if (function_exists('mb_substr'))
  {
    // If we have mb_substr, use it - it's much quicker than our
    // routines, as it's native code

    $encoding = mb_detect_encoding($str);

    return mb_substr($str, $start, $length, $encoding);
  }
  if (strlen($str) > 1000)
  {
    // If the string is long, the old routine is quicker. :(
    
    return utf8_substr_old($str, $start, $length);
  }

  $i = 0;
  $index = 0;
  while ((ord($str[$index]) != 0) && ($i < $start))
  {
    $index = utf8_next_index($str, $index);
    $i++;
  }

  if (!isset($index))
  {
    return NULL;
  }
  if (func_num_args() >= 3)
  {
    $end_index = $index;

    $j = 0;
    while (isset($end_index) && ($j < $length))
    {
      $end_index = utf8_next_index($str, $end_index);
      $j++;
    }
    $j = 0;
    $ret = "."; // dummy to fool PHP
    for ($i = $index;
         (ord($str[$i]) != 0) && (!isset($end_index) || ($i < $end_index));
         $i++)
    { 
      $ret[$j++] = $str[$i];
    }
    return $ret;
  }
  else
  {
    $j = 0;
    $ret = "."; // dummy to fool PHP
    for ($i = $index; ord($str[$i]) != 0; $i++)
    {
      $ret[$j++] = $str[$i];
    }
    return $ret;
  }
}


// Takes a string (which may be UTF-8) and returns how long it is in
// _bytes_
function utf8_bytecount($str)
{
  return count(str_split($str));
}


// Takes a UTF-8 string and returns the string with one Unicode character
// removed from the front
function utf8_next($str)
{
  $ret = NULL;

  if (isset($str))
  {
    $index = utf8_next_index($str, 0);

    if ($index)
    {
      $i = 0;
      $ret = "."; // dummy to fool PHP
      while (ord($str[$index]) != 0)
      {
        $ret[$i++] = $str[$index++];
      }
    }
  }
  return $ret;
}


// Takes a UTF-8 string and a byte index into that string, and
// returns the byte index of the next UTF-8 sequence. When the end
// of the string is encountered, the function returns NULL
function utf8_next_index($str, $start)
{
  $ret = NULL;

  $i = $start;

  if (isset($str))
  {
    if (ord($str[$i]) < 0xc0)
    {
      $i++;
    }
    else
    {
      $i++;
      while ((ord($str[$i]) & 0xc0) == 0x80)
      {
        $i++;
      }
    }
    if (isset($str[$i]) && (ord($str[$i]) != 0))
    {
      $ret = $i;
    }
  }
  return $ret;
}


// Given a UTF-8 string and a byte index, return the UTF-8 sequence
// at this index as a string, and update the byte index to point to
// the next sequence. When the end of the string is encountered, the
// last sequence is returned, and the byte index set to NULL
function utf8_seq($str, &$byte_index)
{
  $ret = "."; // dummy to fool PHP

  $next = utf8_next_index($str, $byte_index);

  if (isset($next))
  {
    $j = 0;
    for ($i = $byte_index; $i < $next; $i++)
    {
      $ret[$j] = $str[$i];
      $j++;
    }
  }
  else
  {
    $j = 0;
    for ($i = $byte_index; isset($str[$i]) && (ord($str[$i]) != 0); $i++)
    {
      $ret[$j] = $str[$i];
      $j++;
    }
  }
  $byte_index = $next;
  return $ret;
}

  
// Takes a UTF-8 string, and returns the number of _characters_ in the
// string
function utf8_strlen($str)
{
  if (!isset($str) || ($str == ""))
  {
    return 0;
  }
  if (function_exists('mb_strlen'))
  {
    // If we have mb_strlen(), use it - it'll be quicker
    return mb_strlen($str);
  }
  $len = 1;
  $next = 0;
  while ($next = utf8_next_index($str, $next))
  {
    $len++;
  }
  return $len;
}


// Format a locale which could be xx-xX or xx_Xx or xx_XxXx (etc.) into a
// standardised format consiting of a lower case language followed, if applicable,
// by an upper case country, separated by $separator.    Typically the separator
// will be '-' or '_'.
function locale_format($locale, $separator)
{
  if (strlen($locale) == 2)
  {
    $locale = strtolower($locale);
  }
  else
  {
    $locale = strtolower(substr($locale,0,2)) . $separator . strtoupper(substr($locale,3));
  }
  return $locale;
}


// Sort an associative array of the form language => qualifier, where qualifier
// is a float, eg "en" => 0.6 in reverse order, ie in decreasing order of
// preference.   The key feature of this function is that if two languages have the
// same qualifier it preserves the browser's original preference order.
//
// Returns TRUE on success, FALSE on failure
function langs_arsort(&$langs)
{
  // First of all build an array indexed by qualifier.    Each element will
  // contain an array of languages with that qualifier (in the order presented
  // by the browser, ie descending order of preference)
  $qualifiers = array();
  foreach ($langs as $lang => $qual)
  {
    $qual = (string) $qual; // We need a string for the array index
    $qualifiers[$qual][] = $lang;
  }
  // Sort the $qualifiers array so that it is in descending order (but we're
  // not sorting the language arays, so they'll still be in descending order)
  if (krsort($qualifiers, SORT_NUMERIC) === FALSE)
  {
    return FALSE;
  }
  // Then reconstruct $langs
  $langs = array();
  foreach ($qualifiers as $qual => $languages)
  {
    foreach ($languages as $language)
    {
      $langs[$language] = (float) $qual;  // Turn the qualifier back to a float
    }
  }

  return TRUE;
}

?>
