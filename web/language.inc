<?php

// $Id$

require_once "functions.inc";


// A map of browser locale aliases, maps to "our" preferred representation.
// We downcase the key before using this map, so the keys need to be all
// lower-case.
$lang_aliases = array
(
  'sh' => 'sr-rs-latin',
  'sr' => 'sr-rs-latin',
  'sr-latn' => 'sr-rs-latin',
  'sr-latn-rs' => 'sr-rs-latin',
);

// A map is needed to convert from the HTTP language specifier to a
// locale specifier for Windows
//
// The ordering of this array is important as it is also used to map in the
// reverse direction, ie to convert a Windows style locale into an xx-yy style
// locale by finding the first occurrence of a value and then using the
// corresponding key.
//
// These locale TLAs found at:
// http://msdn.microsoft.com/en-us/goglobal/bb896001.aspx
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
  'en-gb' => 'eng',
  'en-ie' => 'eni',
  'en-in' => 'enn',
  'en-jm' => 'enj',
  'en-my' => 'enm',
  'en-nz' => 'enz',
  'en-ph' => 'enp',
  'en-tt' => 'ent',
  'en-us' => 'enu',
  'en-za' => 'ens',
  'en-zw' => 'enw',
  'es' => 'esp',
  'es-ar' => 'ess',
  'es-bo' => 'esb',
  'es-cl' => 'esl',
  'es-co' => 'eso',
  'es-cr' => 'esc',
  'es-do' => 'esd',
  'es-ec' => 'esf',
  'es-es' => 'esn',
  'es-gt' => 'esg',
  'es-hn' => 'esh',
  'es-mx' => 'esm',
  'es-ni' => 'esi',
  'es-pa' => 'esa',
  'es-pe' => 'esr',
  'es-py' => 'esz',
  'es-sv' => 'ese',
  'es-us' => 'est',
  'es-uy' => 'esy',
  'es-ve' => 'esv',
  'et' => 'eti',
  'et-ee' => 'eti',
  'eu' => 'euq',
  'eu-es' => 'euq',
  'fi' => 'fin',
  'fi-fi' => 'fin',
  'fr' => 'fra',
  'fr-be' => 'frb',
  'fr-ca' => 'frc',
  'fr-ch' => 'frs',
  'fr-fr' => 'fra',
  'fr-lu' => 'frl',
  'fr-mc' => 'frm',
  'he' => 'heb',
  'he-il' => 'heb',
  'hr' => 'hrv',
  'hr-hr' => 'hrv',
  'hu' => 'hun',
  'hu-hu' => 'hun',
  'id' => 'ind',
  'id-id' => 'ind',
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
  'nn' => 'non',
  'nn-no' => 'non',
  'no' => 'nor',
  'no-no' => 'nor',
  'pl' => 'plk',
  'pl-pl' => 'plk',
  'pt' => 'ptb',
  'pt-br' => 'ptb',
  'pt-pt' => 'ptg',
  'ru' => 'rus',
  'ru-ru' => 'rus',
  'sk' => 'sky',
  'sk-sk' => 'sky',
  'sl' => 'slv',
  'sl-si' => 'slv',
  'sr' => 'srl',
  'sr-rs-latin' => 'srl',
  'sr-hr' => 'srb',
  'sv' => 'sve',
  'sv-fi' => 'svf',
  'sv-se' => 'sve',
  'th' => 'tha',
  'th-th' => 'tha',
  'tr' => 'trk',
  'tr-tr' => 'trk',
  'zh' => 'chs',
  'zh-cn' => 'chs',
  'zh-hk' => 'zhh',
  'zh-mo' => 'zhm',
  'zh-sg' => 'zhi',
  'zh-tw' => 'cht'
);

// This maps a Windows locale to the charset it uses, which are
// all Windows code pages
//
// The code pages used by these locales found at: 
// http://msdn.microsoft.com/en-us/goglobal/bb896001.aspx
$winlocale_codepage_map = array
(
  'afk' => 'CP1252',
  'ara' => 'CP1256',
  'arb' => 'CP1256',
  'are' => 'CP1256',
  'arg' => 'CP1256',
  'arh' => 'CP1256',
  'ari' => 'CP1256',
  'arj' => 'CP1256',
  'ark' => 'CP1256',
  'arl' => 'CP1256',
  'arm' => 'CP1256',
  'aro' => 'CP1256',
  'arq' => 'CP1256',
  'ars' => 'CP1256',
  'art' => 'CP1256',
  'ary' => 'CP1256',
  'aze' => 'CP1254',
  'bas' => 'CP1251',
  'bel' => 'CP1251',
  'bgr' => 'CP1251',
  'bre' => 'CP1252',
  'bsb' => 'CP1250',
  'bsc' => 'CP1251',
  'cat' => 'CP1252',
  'chs' => 'CP936',
  'cht' => 'CP950',
  'cos' => 'CP1252',
  'csy' => 'CP1250',
  'cym' => 'CP1252',
  'dan' => 'CP1252',
  'dea' => 'CP1252',
  'dec' => 'CP1252',
  'del' => 'CP1252',
  'des' => 'CP1252',
  'deu' => 'CP1252',
  'dsb' => 'CP1252',
  'ell' => 'CP1253',
  'ena' => 'CP1252',
  'enb' => 'CP1252',
  'enc' => 'CP1252',
  'ene' => 'CP1252',
  'eng' => 'CP1252',
  'eni' => 'CP1252',
  'enj' => 'CP1252',
  'enl' => 'CP1252',
  'enm' => 'CP1252',
  'enn' => 'CP1252',
  'enp' => 'CP1252',
  'ens' => 'CP1252',
  'ent' => 'CP1252',
  'enu' => 'CP1252',
  'enw' => 'CP1252',
  'enz' => 'CP1252',
  'esa' => 'CP1252',
  'esb' => 'CP1252',
  'esc' => 'CP1252',
  'esd' => 'CP1252',
  'ese' => 'CP1252',
  'esf' => 'CP1252',
  'esg' => 'CP1252',
  'esh' => 'CP1252',
  'esi' => 'CP1252',
  'esl' => 'CP1252',
  'esm' => 'CP1252',
  'esn' => 'CP1252',
  'eso' => 'CP1252',
  'esp' => 'CP1252',
  'esr' => 'CP1252',
  'ess' => 'CP1252',
  'est' => 'CP1252',
  'esu' => 'CP1252',
  'esv' => 'CP1252',
  'esy' => 'CP1252',
  'esz' => 'CP1252',
  'euq' => 'CP1252',
  'eti' => 'CP1257',
  'far' => 'CP1256',
  'fin' => 'CP1252',
  'fos' => 'CP1252',
  'fpo' => 'CP1252',
  'fra' => 'CP1252',
  'frb' => 'CP1252',
  'frc' => 'CP1252',
  'frl' => 'CP1252',
  'frm' => 'CP1252',
  'frs' => 'CP1252',
  'fyn' => 'CP1252',
  'glc' => 'CP1252',
  'gsw' => 'CP1252',
  'hau' => 'CP1252',
  'heb' => 'CP1255',
  'hrb' => 'CP1250',
  'hrv' => 'CP1250',
  'hsb' => 'CP1252',
  'hun' => 'CP1250',
  'ibo' => 'CP1252',
  'ind' => 'CP1252',
  'ire' => 'CP1252',
  'isl' => 'CP1252',
  'ita' => 'CP1252',
  'its' => 'CP1252',
  'iuk' => 'CP1252',
  'jpn' => 'CP932',
  'kal' => 'CP1252',
  'kin' => 'CP1252',
  'kkz' => 'CP1251',
  'kor' => 'CP949',
  'kyr' => 'CP1251',
  'lbx' => 'CP1252',
  'lth' => 'CP1257',
  'lvi' => 'CP1257',
  'mki' => 'CP1251',
  'mon' => 'CP1251',
  'mpd' => 'CP1252',
  'msb' => 'CP1252',
  'msl' => 'CP1252',
  'mwk' => 'CP1252',
  'nlb' => 'CP1252',
  'nld' => 'CP1252',
  'non' => 'CP1252',
  'nor' => 'CP1252',
  'nso' => 'CP1252',
  'oci' => 'CP1252',
  'plk' => 'CP1250',
  'prs' => 'CP1256',
  'ptb' => 'CP1252',
  'ptg' => 'CP1252',
  'qub' => 'CP1252',
  'que' => 'CP1252',
  'qup' => 'CP1252',
  'qut' => 'CP1252',
  'rmc' => 'CP1252',
  'rom' => 'CP1250',
  'rus' => 'CP1251',
  'sah' => 'CP1252',
  'sky' => 'CP1250',
  'slv' => 'CP1250',
  'sma' => 'CP1252',
  'smb' => 'CP1252',
  'sme' => 'CP1252',
  'smf' => 'CP1252',
  'smg' => 'CP1252',
  'smj' => 'CP1252',
  'smk' => 'CP1252',
  'smn' => 'CP1252',
  'sms' => 'CP1252',
  'sqi' => 'CP1250',
  'srb' => 'CP1251',
  'srl' => 'CP1250',
  'srn' => 'CP1251',
  'srs' => 'CP1250',
  'sve' => 'CP1252',
  'svf' => 'CP1252',
  'swk' => 'CP1252',
  'taj' => 'CP1251',
  'tha' => 'CP874',
  'trk' => 'CP1254',
  'tsn' => 'CP1252',
  'ttt' => 'CP1251',
  'tuk' => 'CP1250',
  'tzm' => 'CP1252',
  'uig' => 'CP1256',
  'ukr' => 'CP1251',
  'urb' => 'CP1256',
  'uzb' => 'CP1254',
  'vit' => 'CP1258',
  'wol' => 'CP1252',
  'xho' => 'CP1252',
  'yor' => 'CP1252',
  'zhh' => 'CP950',
  'zhi' => 'CP936',
  'zhm' => 'CP950',
  'zul' => 'CP1252',
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
  'et' => 'et_EE',
  'eu' => 'eu_ES',
  'ja' => 'ja_JP',
  'ko' => 'ko_KR',
  'nb' => 'nb_NO',
  'nn' => 'nn_NO',
  'sh' => 'sr_RS',
  'sl' => 'sl_SI',
  'sr-rs-latin' => 'sr_RS',
  'sv' => 'sv_SE',
  'zh' => 'zh_CN',
);

// A map to add extra locale specifiers onto the end of a locale specifier
// *after* the UTF-8 specifier is added. Keys must match with $lang_map_unix
// above
$lang_map_unix_postfix = array
(
  'sr-rs-latin' => '@latin',
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

// The DataTables language files are for the moment given descriptive names.   I have
// asked the DataTables team to use standard tags instead, but until that happens we have
// to map the descriptions onto tags.  (The other method would be to rename all the files,
// but then it would be less easy to take new releases of DataTables).
$datatable_lang_map = array(
  'Afrikaans'           => 'af',
  'Albanian'            => 'sq',
  'Arabic'              => 'ar',
  'Armenian'            => 'hy',
  'Azerbaijan'          => 'az',
  'Bangla'              => 'bn',
  'Basque'              => 'eu',
  'Belarusian'          => 'be',
  'Bulgarian'           => 'bg',
  'Catalan'             => 'ca',
  'Chinese-traditional' => 'zh-tw',
  'Chinese'             => 'zh',
  'Croatian'            => 'hr',
  'Czech'               => 'cs',
  'Danish'              => 'da',
  'Dutch'               => 'nl',
  'English'             => 'en',
  'Estonian'            => 'et',
  'Filipino'            => 'fil',
  'Finnish'             => 'fi',
  'French'              => 'fr',
  'Galician'            => 'gl',
  'Georgian'            => 'ka',
  'German'              => 'de',
  'Greek'               => 'el',
  'Gujarati'            => 'gu',
  'Hebrew'              => 'he',
  'Hindi'               => 'hi',
  'Hungarian'           => 'hu',
  'Icelandic'           => 'is',
  'Indonesian'          => 'id',
  'Irish'               => 'ga',
  'Italian'             => 'it',
  'Japanese'            => 'ja',
  'Korean'              => 'ko',
  'Kyrgyz'              => 'ky',
  'Latvian'             => 'lv',
  'Lithuanian'          => 'lt',
  'Macedonian'          => 'mk',
  'Malay'               => 'ms',
  'Mongolian'           => 'mn',
  'Nepali'              => 'ne',
  'Norwegian'           => 'no',
  'Persian'             => 'fa',
  'Polish'              => 'pl',
  'Portuguese-Brasil'   => 'pt-br',
  'Portuguese'          => 'pt',
  'Romanian'            => 'ro',
  'Russian'             => 'ru',
  'Serbian'             => 'sr',
  'Sinhala'             => 'si',
  'Slovak'              => 'sk',
  'Slovenian'           => 'sl',
  'Spanish'             => 'es',
  'Swahili'             => 'sw',
  'Swedish'             => 'sv',
  'Tamil'               => 'ta',
  'Thai'                => 'th',
  'Turkish'             => 'tr',
  'Ukranian'            => 'uk',
  'Urdu'                => 'ur',
  'Uzbek'               => 'uz',
  'Vietnamese'          => 'vi',
  'Welsh'               => 'cy'
);


// A list of languages that use Right to Left text
$rtl_languages = array('he');


// Set up mb_string internal encoding
if (function_exists('mb_internal_encoding'))
{
  mb_internal_encoding('UTF-8');
}


////////////////////////////////////////////////////////////////////////
// Language token handling

// Get a default set of language tokens, you can change this if you like.
// Always include English as the fallback language, in case the selected language
// is missing some translated tokens
set_vocab('en');
if ($default_language_tokens != 'en')
{
  set_vocab($default_language_tokens);
}

// Get first default set of language tokens for emails.
$need_to_send_mail = ($mail_settings['admin_on_bookings'] or
                      $mail_settings['area_admin_on_bookings'] or
                      $mail_settings['room_admin_on_bookings'] or
                      $mail_settings['booker'] or
                      $mail_settings['book_admin_on_approval']);
                      
if ($need_to_send_mail)
{
  $web_vocab = $vocab;   // Save $vocab before it gets overwritten
  set_vocab($mail_settings['admin_lang']);
  $mail_vocab = $vocab;
  $vocab = $web_vocab;  // Restore $vocab
}



// Define the default locale here. For a list of supported
// locales on your system do "locale -a"
setlocale(LC_ALL,'C');

// We attempt to make up a sensible locale from the HTTP_ACCEPT_LANGUAGE
// environment variable.
$cli_mode = is_cli();

// Get the acceptable languages, convert to aliases where applicable and sort
// them in reverse order so that the most acceptable one is at the start
$langs = get_language_qualifiers();
$langs = alias_qualifiers($langs);
arsort($langs, SORT_NUMERIC);

// The following attempts to import a language based on what the client
// is using.
if (!$disable_automatic_language_changing || $cli_mode)
{
  $doneit = 0;

  // First try for an exact match, so if the user specified en-gb, look
  // for lang.en-gb

  foreach ($langs as $lang => $qual)
  {
    // Set the locale even if MRBS hasn't got a translation, at least
    // we'll get time formats right
    if (!isset($locale))
    {
      $locale = $lang;
    }

    if (set_vocab($lang))
    {
      $locale = $lang;
      // Work out if we're using a RTL language.   This variable is used
      // globally so that we can include the correct stylesheet
      $using_rtl = in_array(utf8_strtolower($lang), $rtl_languages);
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
      if (set_vocab(substr($lang,0,2)))
      {
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



// Returns a locale in the correct format for the server OS given a language
// Returns FALSE if no locale can be determined
function get_os_locale($lang)
{
  global $lang_map_unix, $lang_map_unix_postfix, $lang_map_windows;
  global $locale_warning, $server_os;
  
  $locale = FALSE;
  
  if ($server_os == "windows")
  {
    if (array_key_exists(utf8_strtolower($lang), $lang_map_windows))
    {
      $locale = $lang_map_windows[utf8_strtolower($lang)];
    }
    else
    {
      $locale_warning = "Server failed to map browser language '$lang' to a Windows locale specifier";
      trigger_error($locale_warning, E_USER_NOTICE);
    }
  }
  /* All of these Unix OSes work in mostly the same way... */
  else if (in_array($server_os, array('linux', 'sunos', 'bsd', 'aix', 'macosx')))
  {
    if (strlen($lang) == 2)
    {
      if (isset($lang_map_unix[$lang]) && ($lang_map_unix[$lang]))
      {
        $locale = $lang_map_unix[$lang];
      }
      else
      {
        // Convert locale=xx to xx_XX
        $locale = utf8_strtolower($lang) . "_" . utf8_strtoupper($lang);
      }
    }
    else
    {
      // First, if we have a map, use it
      if (isset($lang_map_unix[utf8_strtolower($lang)]))
      {
        $locale = $lang_map_unix[utf8_strtolower($lang)];
      }
      else
      {
        // Convert locale=xx-xX or xx_Xx or xx_XxXx (etc.) to xx_XX[XX]; this is highly
        // dependent on the machine's installed locales
        $locale = locale_format($lang, '_');
      }
    }
    switch ($server_os)
    {
      case "sunos":
      case "linux":
      case "bsd":
        $locale .= ".UTF-8";
        if (isset($lang_map_unix_postfix[$locale]))
        { 
          $locale .= $lang_map_unix_postfix[$locale];
        }
        break;

      case "macosx":
        $locale .= ".utf-8";
        break;
      default:
        break;
    }
  }
  return $locale;
}

// Works out what locale should be used by MRBS depending on the config settings
// and the user's browser preferences.
// Returns the locale, or FALSE if no locale can be determined
function determine_mrbs_locale()
{
  global $override_locale;
  global $locale, $windows_locale;

  if (isset($override_locale) && ($override_locale !== ''))
  {
    $mrbs_locale = $override_locale;
  }
  else
  {
    $mrbs_locale = get_os_locale($locale);
  }
  $windows_locale = $mrbs_locale;
  
  return $mrbs_locale;
}


// Sets the locale according to the MRBS config settings and the user's
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


// Returns an array of language qualifiers with the language key replaced
// by its alias, if one exists
function alias_qualifiers($qualifiers)
{
  global $lang_aliases;
  
  $result = array();
  foreach ($qualifiers as $lang => $qualifier)
  {
    if (isset($lang_aliases[utf8_strtolower($lang)]))
    {
      $result[$lang_aliases[utf8_strtolower($lang)]] = $qualifier;
    }
    else
    {
      $result[$lang] = $qualifier;
    }
  }
  return $result;
}


// Returns an unsorted associative array of acceptable language qualifiers, indexed
// by language.   The language will have been replaced its alias if one exists.
function get_language_qualifiers()
{
  global $cli_mode, $cli_language, $default_language_tokens, $HTTP_ACCEPT_LANGUAGE;
  
  static $qualifiers = array();
  
  if (count($qualifiers) == 0)
  {
    // If we're running from the CLI, use the config setting
    if ($cli_mode && !empty($cli_language))
    {
      $qualifiers[$cli_language] = 1.0;
    }
    // Otherwise we enumerate the user's language preferences...
    elseif (isset($HTTP_ACCEPT_LANGUAGE)) // Attempt to use $HTTP_ACCEPT_LANGUAGE only when defined.
    {
      $lang_specifiers = explode(',',$HTTP_ACCEPT_LANGUAGE);
      foreach ($lang_specifiers as $specifier)
      {
        if (preg_match('/([a-zA-Z\-]+);q=([0-9\.]+)/', $specifier, $matches))
        {
          $qualifiers[$matches[1]] = (float) $matches[2];
        }
        else if (preg_match("/([a-zA-Z\-]+)/", $specifier, $matches))
        {
          $qualifiers[$matches[1]] = 1.0;
        }
      }
    }
    else // Else use the value from config.inc.php.
    {
      $qualifiers[$default_language_tokens] = 1.0;
    }
  }
  
  return $qualifiers;
}


// Get the best fit language file from $dir given the set of language preferences
//   $lang_preferences (an associative array language => quality_value).
//   $default_langtags is an array of languages that are already built into the system
//     and do not need an explicit regional setting.
//   $lang_map is an associative array that maps a filename (less prefix and suffix)
//     onto a language tag, for example array('English' => 'en').  Useful when the localisation
//     files do not follow a language tag naming scheme.
//
// The file names are assumed to be of the form $prefix . $lang . $suffix, eg 
// 'datepicker-en-GB.js'
function get_best_lang_file($lang_preferences, $dir, $prefix, $suffix, $default_langtags=NULL, $lang_map=NULL)
{
  global $default_language_tokens, $disable_automatic_language_changing;
  
  if (!file_exists($dir))
  {
    trigger_error("MRBS: directory $dir does not exist", E_USER_NOTICE);
    return NULL;
  }
  
  // Get the available languages
  $files = scandir($dir);
  if (!isset($default_langtags))
  {
    $default_langtags = array(); 
  } 
  $available_langtags = $default_langtags;
  
  foreach ($files as $file)
  {
    // . and .. will be included in the output of scandir(), so 
    // we need to exclude them
    if (!is_dir($dir . '/' . $file))
    {
      // Then strip out the language tag from the file name
      $pattern = sprintf('/%s(.+)%s/i', $prefix, $suffix);
      if (preg_match($pattern, $file, $matches))
      {
        if (isset($matches[1]))
        {
          $langtag = $matches[1];
          $available_langtags[] = (isset($lang_map) && isset($lang_map[$langtag])) ? $lang_map[$langtag] : $langtag;
        }
      }
    }
  }
  
  // If automatic language changing is disabled then add the default language to
  // the top of the list of preferred languages.    Otherwise, unless it's already
  // in the list, add it to the bottom as a backstop.
  if (isset($default_language_tokens) && ($default_language_tokens !== ''))
  {
    if ($disable_automatic_language_changing)
    {
      $lang_preferences[$default_language_tokens] = 1.1; // 1.1 will force it to the top
      arsort($lang_preferences, SORT_NUMERIC);
    }
    elseif (!isset($lang_preferences[$default_language_tokens]))
    {
      $lang_preferences[$default_language_tokens] = 0.0;
    }
  }
  
  // Find the best fit.
  foreach ($lang_preferences as $lang => $qual)
  {
    // For some reason the third parameter to Locale::lookup() is mandatory in 5.3.0.
    // Can't see anything about it in the manual - must be a bug that was fixed in
    // later versions.
    $locale = Locale::lookup($available_langtags, $lang, $canonicalize = FALSE);
    if (!empty($locale))
    {
      if (in_array($locale, $default_langtags))
      {
        return NULL;
      }
      $filename_body = isset($lang_map) ? array_search($locale, $lang_map) : $locale;
      return $prefix . $filename_body . $suffix;
    }
  }

  return NULL;  // We failed to find anything acceptable
}


// Returns the pathname of the language file to use for the jQuery UI
// datepicker.    If no suitable language file exists then returns NULL
// $dir is the directory containing the language files
function get_datepicker_lang_file($dir)
{
  global $langs;
  
  // 'en' and 'en-US' are the default settings and are built into the main
  // datepicker.js code, so are always available.
  $file = get_best_lang_file($langs, 'jquery/ui/i18n', 'datepicker-', '.js', array('en', 'en-US'));
  
  if (isset($file))
  {
    return $dir . '/' . $file;
  }
  else
  {
    return NULL;
  }
}
  


// Returns the pathname of the language file to use for the dataTables
// jQuery plugin.    If no suitable language file exists then returns NULL
// $dir is the directory containing the language files
function get_datatable_lang_file($dir)
{
  global $langs, $datatable_lang_map;
  
  $file = get_best_lang_file($langs, $dir, '', '.lang', NULL, $datatable_lang_map);
  
  if (isset($file))
  {
    return $dir . '/' . $file;
  }
  else
  {
    return NULL;
  }
}


// Set $vocab.   Returns TRUE if a lang file is found, otherwise FALSE
function set_vocab($lang)
{
  global $vocab_override, $cli_mode;
  global $PHP_SELF;
  global $vocab, $mrbs_admin, $mrbs_company;  // Used in lang files
  
  $lang = utf8_strtolower($lang);

  if (file_exists('lang'))
  {
    $lang_file = "lang/lang.$lang";
  }
  else
  {
    // The JavaScript files in the js directory end up including language.inc.
    // In this case we need to adjust the path
    $lang_file = "../lang/lang.$lang";
  }
  // When in CLI mode, we need to add the full path name as
  // file_exists() ignores the include path
  if ($cli_mode)
  {
    $lang_file = dirname($PHP_SELF) . "/" . $lang_file;
  }
  if (file_exists($lang_file))
  {
    // Get the standard language tokens
    include "$lang_file";
    // Apply any site overrides
    if (isset($vocab_override[$lang]))
    {
      foreach ($vocab_override[$lang] as $tag => $str)
      {
        $vocab[$tag] = $str;
      }
    }
    return TRUE;
  }
  return FALSE;
}


function get_charset()
{
  return 'utf-8';
}


function get_mail_charset()
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
  switch(utf8_strtolower($charset))
  {
    case 'utf-8':
      return "\xEF\xBB\xBF";
      break;
    case 'utf-16':
      return pack('S', 0xFEFF);
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
  $character_encoding = utf8_strtolower($character_encoding);

  // Check that we know of an IBM AIX libiconv character encoding name equivalent for this character encoding name
  if (!array_key_exists($character_encoding, $gnu_iconv_to_aix_iconv_codepage_map))
  {
    return FALSE;
  }

  return $gnu_iconv_to_aix_iconv_codepage_map[$character_encoding];
}


// Get a vocab item, in UTF-8
// Takes additional parameters as for sprintf()
//
// [Maybe in the future we should switch to using the MessageFormatter
//  class as it is more powerful.   However the Intl extension isn't present
//  in all PHP installations and so the class would have to be emulated]
function get_vocab($tag)
{
  global $vocab;
  
  // Return the tag itself if we can't find a vocab string
  if (!isset($vocab[$tag]))
  {
    return $tag;
  }
  
  $args = func_get_args();
  $args[0] = $vocab[$tag];
  return call_user_func_array('sprintf', $args);
}


// Same as get_vocab(), but uses the mailing language
function get_mail_vocab($tag)
{
  global $mail_vocab;

  // Return the tag itself if we can't find a vocab string
  if (!isset($mail_vocab[$tag]))
  {
    return $tag;
  }
  
  $args = func_get_args();
  $args[0] = $mail_vocab[$tag];
  $result = call_user_func_array('sprintf', $args);

  return str_replace('&nbsp;', ' ', $result);
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
  
  $tag = utf8_substr($table, utf8_strlen($db_tbl_prefix));  // strip the prefix off the table name
  $tag .= "." . $name;           // add on the fieldname
  // then if there's a string in the vocab array for $tag use that
  // otherwise just use the fieldname
  return (isset($vocab[$tag])) ? get_vocab($tag) : $name;
}


// AIX version of utf8_convert(); needed as locales won't give us UTF-8
// NOTE: Should ONLY be called with input encoded in the default code set of the current locale!
// NOTE: Uses the LC_TIME category for determining the current locale setting, so should preferrably be used on date/time input only!
function utf8_convert_aix($string, $aix_locale = NULL)
{
  global $aixlocale_codepage_map, $aix_utf8_converters;

  if (!isset($aix_locale))
  {
    // Retrieve current locale setting
    $aix_locale = setlocale(LC_TIME, '0');
  }

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


function utf8_convert_from_locale($string, $locale=NULL)
{
  global $windows_locale, $winlocale_codepage_map, $server_os;

  if ($server_os == "windows")
  {
    if (!isset($locale))
    {
      $locale = $windows_locale;
    }
    if (array_key_exists($locale, $winlocale_codepage_map))
    {
      $string = iconv($winlocale_codepage_map[$locale],"utf-8",
                      $string);
    }
  }
  else if ($server_os == "aix")
  {
    $string = utf8_convert_aix($string, $locale);
  }
  return $string;
}


//  optionally switch to a new locale, switching back at the end
function utf8_strftime($format, $time, $temp_locale=NULL)
{
  global $server_os;

  // Set the temporary locale
  if (!empty($temp_locale))
  {
    $old_locale = setlocale(LC_TIME, '0');
    setlocale(LC_TIME, $temp_locale);
  }
  elseif ($server_os == "windows")
  {
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
  
  if ($server_os == "windows")
  {
    // Some formats not supported on Windows.   Replace with suitable alternatives
    $format = str_replace("%R", "%H:%M", $format);
    $format = str_replace("%P", "%p", $format);
    $format = str_replace("%l", "%I", $format);
    $format = str_replace("%e", "%#d", $format);
  }
  
  // %p doesn't actually work in some locales, we have to patch it up ourselves
  if (preg_match('/%p/', $format))
  {
    $ampm = strftime('%p', $time);  // Don't replace the %p with the $strftime_format variable!!
    if ($ampm == '')
    {
      $ampm = date('a', $time);
    }

    $format = preg_replace('/%p/', $ampm, $format);
  }

  $result = strftime($format, $time);
  $result = utf8_convert_from_locale($result, $temp_locale);
  
  // Restore the original locale
  if (!empty($temp_locale))
  {
    setlocale(LC_TIME, $old_locale);
  }
  
  return $result;
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
function utf8_substr($str, $start, $length=NULL)
{
  if (!isset($length))
  {
    // We cannot set $length to PHP_INT_MAX because there is a bug (#42101)
    // in mb_substr() when using PHP_INT_MAX on 64 bit Linux systems.  See
    // https://bugs.php.net/bug.php?id=42101.    Note also that passing NULL
    // for the length in mb_substr() causes mb_substr to use a length of 0.
    // See the user contributed notes on the PHP mb_substr() manual page.
    $length = utf8_strlen($str);
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
  // We cannot rely on strlen() to return the number of bytes because it might
  // have been overloaded by mb_strlen() which returns characters
  if (function_exists('mb_strlen'))
  {
    return mb_strlen($str, '8bit');
  }
  else
  {
    return strlen($str);
  }
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


// Takes a UTF-8 string and converts it to UTF-16 without using iconv
function utf8_to_utf16($string)
{
  $ucs2 = array();
  $byte_index = 0;
      
  while (!is_null($byte_index))
  {
    $next = utf8_seq($string, $byte_index);

    $c0 = ord($next[0]);

    // Easy case, code is 0xxxxxxx - just use it as is
    if ($c0 < 0x80)
    {
      array_push($ucs2, $c0);
      continue;
    }
    $cn = ord($next[1]) ^ 0x80;
    $ucs = ($c0 << 6) | $cn;

    // Two byte codes: 110xxxxx 10xxxxxx
    if ($c0 < 0xE0)
    {
      $ucs &= ~0x3000;
      array_push($ucs2, $ucs);
      continue;
    }

    $cn = ord($next[2]) ^ 0x80;
    $ucs = ($ucs << 6) | $cn;

    // Three byte codes: 1110xxxx 10xxxxxx 10xxxxxx
    if ($c0 < 0xF0)
    {
      $ucs &= ~0xE0000;
      array_push($ucs2, $ucs);
      continue;
    }
        
    $cn = ord($next[3]) ^ 0x80;
    $ucs = ($ucs << 6) | $cn;
    
    // Four byte codes: 11110xxx 10xxxxxxx 10xxxxxx 10xxxxxx
    if ($c0 < 0xF8)
    {
      $ucs &= ~0x3C00000;
      array_push($ucs2, $ucs);
      continue;
    }
    die("Shouldn't get here!");
  }

  $out = "";
  foreach ($ucs2 as $char)
  {
    $ucs_string = pack("v", $char);
    //error_log(sprintf("UCS %04x -> %02x,%02x",$char,ord($ucs_string[0]),ord($ucs_string[1])));
    $out .= $ucs_string;
  }
  return $out;
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


// Wrapper round mb_strtolower() to make sure it's used if present and mb_string
// function overloading is not enabled.
// The ordinary strtolower() will give unexpected results when the locale is set to
// Turkish and will not convert the letter 'I'.
function utf8_strtolower($str)
{
  if (function_exists('mb_strtolower'))
  {
    return mb_strtolower($str);
  }
  return strtolower($str);
}


// Wrapper round mb_strtoupper() to make sure it's used if present and mb_string
// function overloading is not enabled.
// The ordinary strtoupper() will give unexpected results when the locale is set to
// Turkish and will not convert the letter 'i'.
function utf8_strtoupper($str)
{
  if (function_exists('mb_strtoupper'))
  {
    return mb_strtoupper($str);
  }
  return strtoupper($str);
}


// Format a locale which could be xx-xX or xx_Xx or xx_XxXx (etc.) into a
// standardised format consiting of a lower case language followed, if applicable,
// by an upper case country, separated by $separator.    Typically the separator
// will be '-' or '_'.
function locale_format($locale, $separator)
{
  if (strlen($locale) == 2)
  {
    $locale = utf8_strtolower($locale);
  }
  else
  {
    $locale = utf8_strtolower(substr($locale,0,2)) . $separator . utf8_strtoupper(substr($locale,3));
  }
  return $locale;
}
