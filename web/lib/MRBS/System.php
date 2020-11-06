<?php

namespace MRBS;

class System
{
  // A set of special cases for mapping a language to a default region
  // (normally the region is the same as the language, eg 'fr' => 'FR')
  private static $default_regions = array(
      'ca' => 'ES',
      'cs' => 'CZ',
      'da' => 'DK',
      'el' => 'GR',
      'en' => 'GB',
      'et' => 'EE',
      'eu' => 'ES',
      'ja' => 'JP',
      'ko' => 'KR',
      'nb' => 'NO',
      'nn' => 'NO',
      'sh' => 'RS',
      'sl' => 'SI',
      'sr' => 'RS',
      'sv' => 'SE',
      'zh' => 'CN',
    );


  // A map is needed to convert from the HTTP language specifier to a
  // locale specifier for Windows
  //
  // The ordering of this array is important as it is also used to map in the
  // reverse direction, ie to convert a Windows style locale into an xx-yy style
  // locale by finding the first occurrence of a value and then using the
  // corresponding key.
  //
  // See https://www.iana.org/assignments/language-subtag-registry/language-subtag-registry
  // for language tags.
  //
  // Windows locales can be found by running the following PHP code on a Windows system
  /*
  foreach (range('a', 'z') as $a)
  {
    foreach (range('a', 'z') as $b)
    {
      foreach (range('a', 'z') as $c)
      {
        $locale = "$a$b$c";
        setlocale(LC_ALL, $locale);
        $result = setlocale(LC_ALL, 0);
        if ($locale != $result)
        {
          echo "$locale: $result\n";
        }
      }
    }
  }
  */
  private static $lang_map_windows = array(
      'aa-et' => 'zzz',           // Afar_Ethiopia.utf8
      'af-za' => 'afk',           // Afrikaans_South Africa.1252
      'am' => 'amh',              // Amharic.utf8
      'am-et' => 'amh',           // Amharic_Ethiopia.utf8
      'ar-ae' => 'aru',           // Arabic_United Arab Emirates.1256
      'ar-bh' => 'arh',           // Arabic_Bahrain.1256
      'ar-dz' => 'arg',           // Arabic_Algeria.1256
      'ar-eg' => 'are',           // Arabic_Egypt.1256
      'ar-iq' => 'ari',           // Arabic_Iraq.1256
      'ar-jo' => 'arj',           // Arabic_Jordan.1256
      'ar-kw' => 'ark',           // Arabic_Kuwait.1256
      'ar-lb' => 'arb',           // Arabic_Lebanon.1256
      'ar-ly' => 'arl',           // Arabic_Libya.1256
      'ar-ma' => 'arm',           // Arabic_Morocco.1256
      'ar-om' => 'aro',           // Arabic_Oman.1256
      'ar-qa' => 'arq',           // Arabic_Qatar.1256
      'ar-sa' => 'ara',           // Arabic_Saudi Arabia.1256
      'ar-sy' => 'ars',           // Arabic_Syria.1256
      'ar-tn' => 'art',           // Arabic_Tunisia.1256
      'ar-ye' => 'ary',           // Arabic_Yemen.1256
      'arn' => 'mpd',             // Mapudungun.1252
      'arn-cl' => 'mpd',          // Mapudungun_Chile.1252
      'as-in' => 'asm',           // Assamese_India.utf8
      'az' => 'aze',              // Azerbaijani.1254
      'az-cyrl' => 'azc',         // Azerbaijani (Cyrillic).1251
      'az-cyrl-az' => 'azc',      // Azerbaijani (Cyrillic)_Azerbaijan.1251
      'az-latn' => 'aze',         // Azerbaijani.1254
      'az-latn-az' => 'aze',      // Azerbaijani_Azerbaijan.1254
      'ba-ru' => 'bas',           // Bashkir_Russia.1251
      'bg' => 'bgr',              // Bulgarian.1251
      'bg-bg' => 'bgr',           // Bulgarian_Bulgaria.1251
      'be' => 'bel',              // Belarusian.1251
      'be-by' => 'bel',           // Belarusian_Belarus.1251
      'bn-bd' => 'bnb',           // Bangla_Bangladesh.utf8
      'bn-in' => 'bng',           // Bengali_India.utf8
      'bo-cn' => 'bob',           // Tibetan_China.utf8
      'br-fr' => 'bre',           // Breton_France.1252
      'bs' => 'bsb',              // Bosnian.1250'
      'bs-cyrl' => 'bsc',         // Bosnian (Cyrillic).1251
      'bs-cyrl-ba' => 'bsc',      // Bosnian (Cyrillic)_Bosnia and Herzegovina.1251
      'bs-latn' => 'bsb',         // Bosnian_Bosnia and Herzegovina.1250'
      'bs-latn-ba' => 'bsb',      // Bosnian_Bosnia and Herzegovina.1250
      'ca' => 'cat',              // Catalan_Spain.1252
      'ca-es' => 'cat',           // Catalan_Spain.1252
      'chr-us' => 'cre',          // Cherokee_United States.utf8
      'ckb-iq' => 'kur',          // Central Kurdish_Iraq.1256
      'co' => 'cos',              // Corsican.1252
      'co-fr' => 'cos',           // Corsican_France.1252
      'cs' => 'csy',              // Czech.1250
      'cs-cz' => 'csy',           // Czech_Czechia.1250
      'cy' => 'cym',              // Welsh.1252'
      'cy-gb' => 'cym',           // Welsh_United Kingdom.1252'
      'da' => 'dan',              // Danish.1252
      'da-dk' => 'dan',           // Danish_Denmark.1252
      'de' => 'deu',              // German.1252
      'de-at' => 'dea',           // German_Austria.1252
      'de-ch' => 'des',           // German_Switzerland.1252
      'de-de' => 'deu',           // German_Germany.1252
      'de-li' => 'dec',           // German_Liechtenstein.1252
      'de-lu' => 'del',           // German_Luxembourg.1252
      'dsb-de' => 'dsb',          // Lower Sorbian_Germany.1252
      'dv-mv' => 'div',           // Divehi_Maldives.utf8'
      'el' => 'ell',              // Greek.1253
      'el-gr' => 'ell',           // Greek_Greece.1253
      'en' => 'enu',              // English.1252
      'en-au' => 'ena',           // English_Australia.1252
      'en-bz' => 'enl',           // English_Belize.1252
      'en-ca' => 'enc',           // English_Canada.1252
      'en-cb' => 'enb',           // English_Caribbean.1252
      'en-gb' => 'eng',           // English_United Kingdom.1252
      'en-hk' => 'enh',           // English_Hong Kong SAR.1252
      'en-ie' => 'eni',           // English_Ireland.1252
      'en-in' => 'enn',           // English_India.1252
      'en-jm' => 'enj',           // English_Jamaica.1252
      'en-my' => 'enm',           // English_Malaysia.1252
      'en-nz' => 'enz',           // English_New Zealand.1252
      'en-ph' => 'enp',           // English_Philippines.1252
      'en-sg' => 'ene',           // English_Singapore.1252
      'en-tt' => 'ent',           // English_Trinidad and Tobago.1252
      'en-us' => 'enu',           // English_United States.1252
      'en-za' => 'ens',           // English_South Africa.1252
      'en-zw' => 'enw',           // English_Zimbabwe.1252
      'es' => 'esp',              // Spanish_Spain.1252
      'es-419' => 'esj',          // Spanish_Latin America.1252
      'es-ar' => 'ess',           // Spanish_Argentina.1252
      'es-bo' => 'esb',           // Spanish_Bolivia.1252
      'es-cl' => 'esl',           // Spanish_Chile.1252
      'es-co' => 'eso',           // Spanish_Colombia.1252
      'es-cr' => 'esc',           // Spanish_Costa Rica.1252
      'es-cu' => 'esk',           // Spanish_Cuba.1252
      'es-do' => 'esd',           // Spanish_Dominican Republic.1252
      'es-ec' => 'esf',           // Spanish_Ecuador.1252
      'es-es' => 'esn',           // Spanish_Spain.1252
      'es-gt' => 'esg',           // Spanish_Guatemala.1252
      'es-hn' => 'esh',           // Spanish_Honduras.1252
      'es-mx' => 'esm',           // Spanish_Mexico.1252
      'es-ni' => 'esi',           // Spanish_Nicaragua.1252
      'es-pa' => 'esa',           // Spanish_Panama.1252
      'es-pe' => 'esr',           // Spanish_Peru.1252
      'es-pr' => 'esu',           // Spanish_Puerto Rico.1252
      'es-py' => 'esz',           // Spanish_Paraguay.1252
      'es-sv' => 'ese',           // Spanish_El Salvador.1252
      'es-us' => 'est',           // Spanish_United States.1252
      'es-uy' => 'esy',           // Spanish_Uruguay.1252
      'es-ve' => 'esv',           // Spanish_Venezuela.1252
      'et' => 'eti',              // Estonian.1257
      'et-ee' => 'eti',           // Estonian_Estonia.1257
      'eu' => 'euq',              // Basque.1252
      'eu-es' => 'euq',           // Basque_Spain.1252
      'fa' => 'far',              // Persian.1256
      'fa-ir' => 'far',           // Persian_Iran.1256
      'ff-latn-sn' => 'ful',      // Fulah (Latin)_Senegal.1252
      'fi' => 'fin',              // Finnish.1252
      'fi-fi' => 'fin',           // Finnish_Finland.1252
      'fil' => 'fpo',             // Filipino.1252
      'fil-ph' => 'fpo',          // Filipino_Philippines.1252
      'fo' => 'fos',              // Faroese.1252
      'fo-fo' => 'fos',           // Faroese_Faroe Islands.1252
      'fr' => 'fra',              // French.1252
      'fr-be' => 'frb',           // French_Belgium.1252
      'fr-ca' => 'frc',           // French_Canada.1252
      'fr-cg' => 'frd',           // French_Congo (DRC).1252
      'fr-ch' => 'frs',           // French_Switzerland.1252
      'fr-ci' => 'fri',           // French_Côte d'Ivoire.1252
      'fr-cm' => 'fre',           // French_Cameroon.1252
      'fr-fr' => 'fra',           // French_France.1252
      'fr-ht' => 'frh',           // French_Haiti.1252
      'fr-lu' => 'frl',           // French_Luxembourg.1252
      'fr-ma' => 'fro',           // French_Morocco.1252
      'fr-mc' => 'frm',           // French_Monaco.1252
      'fr-ml' => 'frf',           // French_Mali.1252
      'fr-re' => 'frr',           // French_Réunion.1252'
      'fr-sn' => 'frn',           // French_Senegal.1252
      'fy' => 'fyn',              // Western Frisian.1252
      'fy-nl' => 'fyn',           // Western Frisian_Netherlands.1252
      'ga' => 'ire',              // Irish.1252
      'ga-ie' => 'ire',           // Irish_Ireland.1252
      'gaz-et' => 'orm',          // Oromo_Ethiopia.utf8
      'gd' => 'gla',              // Scottish Gaelic.1252
      'gd-gb' => 'gla',           // Scottish Gaelic_United Kingdom.1252
      'gl' => 'glc',              // Galician.1252
      'gl-es' => 'glc',           // Galician_Spain.1252
      'gn-py' => 'grn',           // Guarani_Paraguay.1252
      'gsw-fr' => 'gsw',          // Alsatian_France.1252
      'gu' => 'guj',              // Gujarati.utf8
      'gu-in' => 'guj',           // Gujarati_India.utf8
      'ha' => 'hau',              // Hausa.1252
      'ha-latn' => 'hau',         // Hausa.1252'
      'ha-latn-ng' => 'hau',      // Hausa_Nigeria.1252
      'haw-us' => 'haw',          // Hawaiian_United States.1252
      'he' => 'heb',              // Hebrew_Israel.1255
      'he-il' => 'heb',           // Hebrew_Israel.1255
      'hi' => 'hin',              // Hindi.utf8
      'hi-in' => 'hin',           // Hindi_India.utf8
      'hr' => 'hrv',              // Croatian.1250
      'hr-ba' => 'hrb',           // Croatian_Bosnia and Herzegovina.1250
      'hr-hr' => 'hrv',           // Croatian_Croatia.1250
      'hsb' => 'hsb',             // Upper Sorbian.1252
      'hsb-de' => 'hsb',          // Upper Sorbian_Germany.1252
      'hu' => 'hun',              // Hungarian.1250
      'hu-hu' => 'hun',           // Hungarian_Hungary.1250
      'hy' => 'hye',              // Armenian.utf8
      'hy-am' => 'hye',           // Armenian_Armenia.utf8
      'id' => 'ind',              // Indonesian.1252
      'id-id' => 'ind',           // Indonesian_Indonesia.1252
      'ig' => 'ibo',              // Igbo.1252
      'ig-ng' => 'ibo',           // Igbo_Nigeria.1252
      'ii' => 'iii',              // Yi.utf8
      'ii-cn' => 'iii',           // Yi_China.utf8
      'is' => 'isl',              // Icelandic.1252
      'is-is' => 'isl',           // Icelandic_Iceland.1252
      'it' => 'ita',              // Italian.1252
      'it-ch' => 'its',           // Italian_Switzerland.1252
      'it-it' => 'ita',           // Italian_Italy.1252
      'iu' => 'iuk',              // Inuktitut.1252
      'iu-cans-ca' => 'ius',      // Inuktitut (Syllabics)_Canada.utf8
      'iu-latn' => 'iuk',         // Inuktitut.1252
      'iu-latn-ca' => 'iuk',      // Inuktitut_Canada.1252
      'ja' => 'jpn',              // Japanese.932
      'ja-jp' => 'jpn',           // Japanese_Japan.932
      'jv-id' => 'jav',           // Javanese_Indonesia.1252
      'ka' => 'kat',              // Georgian.utf8
      'ka-ge' => 'kat',           // Georgian_Georgia.utf8
      'kk' => 'kkz',              // Kazakh.utf8
      'kk-kz' => 'kkz',           // Kazakh_Kazakhstan.utf8
      'kl-gl' => 'kal',           // Greenlandic_Greenland.1252
      'km' => 'khm',              // Khmer.utf8
      'km-kh' => 'khm',           // Khmer_Cambodia.utf8
      'kn' => 'kdi',              // Kannada.utf8
      'kn-in' => 'kdi',           // Kannada_India.utf8
      'ko' => 'kor',              // Korean.949
      'ko-kr' => 'kor',           // Korean_Korea.949
      'kok' => 'knk',             // Konkani.utf8
      'kok-in' => 'knk',          // Konkani_India.utf8
      'ky-kg' => 'kyr',           // Kyrgyz_Kyrgyzstan.1251
      'lb' => 'lbx',              // Luxembourgish.1252
      'lb-lu' => 'lbx',           // Luxembourgish_Luxembourg.1252
      'lo' => 'lao',              // Lao.utf8
      'lo-la' => 'lao',           // Lao_Laos.utf8
      'lt' => 'lth',              // Lithuanian.1257
      'lt-lt' => 'lth',           // Lithuanian_Lithuania.1257
      'lv' => 'lvi',              // Latvian.1257
      'lv-lv' => 'lvi',           // Latvian_Latvia.1257
      'mk' => 'mki',              // Macedonian.1251
      'mk-mk' => 'mki',           // Macedonian_North Macedonia.1251
      'mi' => 'mri',              // Maori.utf8
      'mi-nz' => 'mri',           // Maori_New Zealand.utf8
      'ml' => 'mym',              // Malayalam.utf8
      'ml-in' => 'mym',           // Malayalam_India.utf8
      'mn' => 'mnn',              // Mongolian_Mongolia.1251
      'mn-mn' => 'mnn',           // Mongolian_Mongolia.1251
      'mn-mong-cn' => 'mng',      // Mongolian (Traditional Mongolian)_China.utf8
      'mn-mong-mn' => 'mnm',      // Mongolian (Traditional Mongolian)_Mongolia.utf8
      'moh' => 'mwk',             // Mohawk.1252
      'moh-ca' => 'mwk',          // Mohawk_Canada.1252
      'mr' => 'mar',              // Marathi.utf8
      'mr-in' => 'mar',           // Marathi_India.utf8
      'ms' => 'msl',              // Malay.1252
      'ms-bn' => 'msb',           // Malay_Brunei.1252
      'ms-my' => 'msl',           // Malay_Malaysia.1252
      'mt' => 'mlt',              // Maltese.utf8
      'mt-mt' => 'mlt',           // Maltese_Malta.utf8
      'my' => 'mya',              // Burmese.utf8
      'my-mm' => 'mya',           // Burmese_Myanmar.utf8
      'nb' => 'nor',              // Norwegian Bokmål.1252
      'nb-no' => 'nor',           // Norwegian Bokmål_Norway.1252
      'ne-in' => 'nei',           // Nepali_India.utf8
      'ne-np' => 'nep',           // Nepali_Nepal.utf8
      'ngo-gn' => 'nqo',          // N'ko_Guinea.utf8
      'nl' => 'nld',              // Dutch.1252
      'nl-be' => 'nlb',           // Dutch_Belgium.1252
      'nl-nl' => 'nld',           // Dutch_Netherlands.1252
      'nn' => 'non',              // Norwegian Nynorsk.1252
      'nn-no' => 'non',           // Norwegian Nynorsk_Norway.1252
      'no' => 'nor',              // Norwegian.1252
      'no-no' => 'nor',           // Norwegian_Norway.1252
      'nso-za' => 'nso',          // Sesotho sa Leboa_South Africa.1252
      'oc' => 'oci',              // Occitan.1252
      'oc-fr' => 'oci',           // Occitan_France.1252
      'ory-in' => 'ori',          // Odia_India.utf8
      'pa-in' => 'pan',           // Punjabi_India.utf8
      'pa-pk' => 'pap',           // Punjabi_Pakistan.1256
      'pl' => 'plk',              // Polish.1250
      'pl-pl' => 'plk',           // Polish_Poland.1250
      'plt-mg' => 'mlg',          // Malagasy_Madagascar.utf8
      'prs' => 'prs',             // Dari.1256
      'prs-af' => 'prs',          // Dari_Afghanistan.1256
      'ps-af' => 'pas',           // Pashto_Afghanistan.utf8
      'pt' => 'ptb',              // Portuguese.1252
      'pt-ao' => 'pta',           // Portuguese_Angola.1252
      'pt-br' => 'ptb',           // Portuguese_Brazil.1252
      'pt-pt' => 'ptg',           // Portuguese_Portugal.1252
      'quc-gt' => 'qut',          // K'iche'_Guatemala.1252
      'quz-bo' => 'qub',          // Quechua_Bolivia.1252
      'quz-ec' => 'que',          // Quechua_Ecuador.1252
      'quz-pe' => 'qup',          // Quechua_Peru.1252
      'rm-ch' => 'rmc',           // Romansh_Switzerland.1252
      'ro' => 'rom',              // Romanian.1250
      'ro-md' => 'rod',           // Romanian_Moldova.1250
      'ro-ro' => 'rom',           // Romanian_Romania.1250
      'ru' => 'rus',              // Russian.1251
      'ru-md' => 'rum',           // Russian_Moldova.1251
      'ru-ru' => 'rus',           // Russian_Russia.1251
      'rw' => 'kin',              // Kinyarwanda.1252
      'rw-rw' => 'kin',           // Kinyarwanda_Rwanda.1252
      'sa' => 'san',              // Sanskrit.utf8
      'sa-in' => 'san',           // Sanskrit_India.utf8
      'sah-ru' => 'sah',          // Sakha_Russia.1251
      'sd-pk' => 'sip',           // Sindhi_Pakistan.1256
      'se-fi' => 'smg',           // Sami (Northern)_Finland.1252
      'se-no' => 'sme',           // Northern Sami_Norway.1252
      'se-se' => 'smf',           // Sami (Northern)_Sweden.1252
      'si' => 'sin',              // Sinhala.utf8
      'si-lk' => 'sin',           // Sinhala_Sri Lanka.utf8
      'sk' => 'sky',              // Slovak.1250
      'sk-sk' => 'sky',           // Slovak_Slovakia.1250
      'sl' => 'slv',              // Slovenian.1250
      'sl-si' => 'slv',           // Slovenian_Slovenia.1250
      'sma-no' => 'sma',          // Sami (Southern)_Norway.1252
      'sma-se' => 'smb',          // Sami (Southern)_Sweden.1252
      'smj-no' => 'smj',          // Sami (Lule)_Norway.1252
      'smj-se' => 'smk',          // Sami (Lule)_Sweden.1252
      'smn-fi' => 'smn',          // Sami (Inari)_Finland.1252
      'sms-fi' => 'sms',          // Sami (Skolt)_Finland.1252
      'sn-zw' => 'sna',           // Shona_Zimbabwe.utf8
      'so-so' => 'som',           // Somali_Somalia.utf8
      'sq' => 'sqi',              // Albanian.1250
      'sq-al' => 'sqi',           // Albanian_Albania.1250
      'sr' => 'srl',              // Serbian.1250
      'sr-cyrl-ba' => 'srn',      // Serbian (Cyrillic)_Bosnia and Herzegovina.1251
      'sr-cyrl-me' => 'srq',      // Serbian (Cyrillic)_Montenegro.1251
      'sr-cyrl-rs' => 'sro',      // Serbian (Cyrillic)_Serbia.1251
      'sr-latn-ba' => 'srs',      // Serbian (Latin)_Bosnia and Herzegovina.1250
      'sr-latn-me' => 'srp',      // Serbian (Latin)_Montenegro.1250
      'sr-latn-rs' => 'srm',      // Serbian (Latin)_Serbia.1250
      'sr-hr' => 'srb',           // Serbian_Croatia.1250
      'sr-rs' => 'srb',           // Serbian_Serbia.1250
      'st-za' => 'sot',           // Sesotho_South Africa.utf8
      'sv' => 'sve',              // Swedish.1252
      'sv-fi' => 'svf',           // Swedish_Finland.1252
      'sv-se' => 'sve',           // Swedish_Sweden.1252
      'sw' => 'swk',              // Kiswahili.1252
      'sw-ke' => 'swk',           // Kiswahili_Kenya.1252
      'syr' => 'syr',             // Syriac.utf8
      'syr-sy' => 'syr',          // Syriac_Syria.utf8
      'ta-in' => 'tai',           // Tamil_India.utf8
      'ta-lk' => 'tam',           // Tamil_Sri Lanka.utf8
      'te-in' => 'tel',           // Telugu_India.utf8
      'tg-cyrl-tj' => 'taj',      // Tajik_Tajikistan.1251
      'th' => 'tha',              // Thai.874
      'th-th' => 'tha',           // Thai_Thailand.874
      'ti-er' => 'tir',           // Tigrinya_Eritrea.utf8
      'ti-et' => 'tie',           // Tigrinya_Ethiopia.utf8
      'tk' => 'tuk',              // Turkmen_Turkmenistan.1250
      'tk-tm' => 'tuk',           // Turkmen_Turkmenistan.1250
      'tn-bw' => 'tsb',           // Setswana_Botswana.1252
      'tn-za' => 'tsn',           // Setswana_South Africa.1252
      'tr' => 'trk',              // Turkish.1254
      'tr-tr' => 'trk',           // Turkish_Turkey.1254
      'ts-za' => 'tso',           // Tsonga_South Africa.utf8
      'tt' => 'ttt',              // Tatar.1251
      'tt-ru' => 'ttt',           // Tatar_Russia.1251
      'tzm-latn-dz' => 'tza',     // Central Atlas Tamazight_Algeria.1252
      'tzm-latn-ma' => 'tzm',     // Central Atlas Tamazight (Tifinagh)_Morocco.utf8
      'ug' => 'uig',              // Uyghur.1256
      'ug-cn' => 'uig',           // Uyghur_China.1256
      'uk' => 'ukr',              // Ukrainian.1251
      'uk-ua' => 'ukr',           // Ukrainian_Ukraine.1251
      'ur-in' => 'uri',           // Urdu_India.1256
      'ur-pk' => 'urd',           // Urdu_Pakistan.1256
      'uz-cyrl' => 'uzc',         // Uzbek (Cyrillic).1251
      'uz-cyrl-uz' => 'uzc',      // Uzbek (Cyrillic)_Uzbekistan.1251
      'uz-latn' => 'uzb',         // Uzbek.1254
      'uz-latn-uz' => 'uzb',      // Uzbek_Uzbekistan.1254
      'vi' => 'vit',              // Vietnamese.1258
      'vi-vn' => 'vit',           // Vietnamese_Vietnam.1258
      'wo' => 'wol',              // Wolof.1252
      'wo-sn' => 'wol',           // Wolof_Senegal.1252
      'xh' => 'xho',              // isiXhosa.1252
      'xh-za' => 'xho',           // isiXhosa_South Africa.1252
      'yo' => 'yor',              // Yoruba.1252
      'yo-ng' => 'yor',           // Yoruba_Nigeria.1252
      'zgh-ma' => 'zhg',          // Standard Moroccan Tamazight_Morocco.utf8
      'zh' => 'chs',              // Chinese.936
      'zh-cn' => 'chs',           // Chinese_China.936
      'zh-hans-sg' => 'zhi',      // Chinese (Simplified)_Singapore.936
      'zh-hant-hk' => 'zhh',      // Chinese (Traditional)_Hong Kong SAR.950
      'zh-hant-mo' => 'zhm',      // Chinese (Traditional)_Macao SAR.950
      'zh-hk' => 'zhh',           // Chinese_Hong Kong SAR.950
      'zh-mo' => 'zhm',           // Chinese_Macao SAR.950
      'zh-sg' => 'zhi',           // Chinese_Singapore.936
      'zh-tw' => 'cht',           // Chinese_Taiwan.950
      'zu' => 'zul',              // isiZulu.1252
      'zu-za' => 'zul'            // isiZulu_South Africa.1252
    );


  // This maps a Windows locale to the charset it uses, which are
  // all Windows code pages
  //
  // The code pages used by these locales found at:
  // http://msdn.microsoft.com/en-us/goglobal/bb896001.aspx
  private static $winlocale_codepage_map = array(
      'afk' => 'CP1252',
      'amh' => 'utf-8',
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
      'aru' => 'CP1256',
      'ary' => 'CP1256',
      'asm' => 'utf-8',
      'azc' => 'CP1251',
      'aze' => 'CP1254',
      'bas' => 'CP1251',
      'bel' => 'CP1251',
      'bgr' => 'CP1251',
      'bnb' => 'utf-8',
      'bng' => 'utf-8',
      'bob' => 'utf-8',
      'bre' => 'CP1252',
      'bsb' => 'CP1250',
      'bsc' => 'CP1251',
      'cat' => 'CP1252',
      'chh' => 'CP950',
      'chi' => 'CP936',
      'chs' => 'CP936',
      'cht' => 'CP950',
      'cos' => 'CP1252',
      'cre' => 'utf-8',
      'csy' => 'CP1250',
      'cym' => 'CP1252',
      'dan' => 'CP1252',
      'dea' => 'CP1252',
      'dec' => 'CP1252',
      'del' => 'CP1252',
      'des' => 'CP1252',
      'deu' => 'CP1252',
      'div' => 'utf-8',
      'dsb' => 'CP1252',
      'ell' => 'CP1253',
      'ena' => 'CP1252',
      'enb' => 'CP1252',
      'enc' => 'CP1252',
      'ene' => 'CP1252',
      'eng' => 'CP1252',
      'enh' => 'CP1252',
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
      'esj' => 'CP1252',
      'esk' => 'CP1252',
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
      'frd' => 'CP1252',
      'fre' => 'CP1252',
      'frf' => 'CP1252',
      'frh' => 'CP1252',
      'fri' => 'CP1252',
      'frl' => 'CP1252',
      'frm' => 'CP1252',
      'frn' => 'CP1252',
      'fro' => 'CP1252',
      'frr' => 'CP1252',
      'frs' => 'CP1252',
      'ful' => 'CP1252',
      'fyn' => 'CP1252',
      'gla' => 'CP1252',
      'glc' => 'CP1252',
      'grn' => 'CP1252',
      'gsw' => 'CP1252',
      'guj' => 'utf-8',
      'hau' => 'CP1252',
      'haw' => 'CP1252',
      'heb' => 'CP1255',
      'hin' => 'utf-8',
      'hrb' => 'CP1250',
      'hrv' => 'CP1250',
      'hsb' => 'CP1252',
      'hun' => 'CP1250',
      'hye' => 'utf-8',
      'ibo' => 'CP1252',
      'iii' => 'utf-8',
      'ind' => 'CP1252',
      'ire' => 'CP1252',
      'isl' => 'CP1252',
      'ita' => 'CP1252',
      'its' => 'CP1252',
      'ius' => 'utf-8',
      'iuk' => 'CP1252',
      'ivl' => 'CP1252',        // Invariant Language_Invariant Country.1252
      'jav' => 'CP1252',
      'jpn' => 'CP932',
      'kal' => 'CP1252',
      'kat' => 'utf-8',
      'kdi' => 'utf-8',
      'khm' => 'utf-8',
      'kin' => 'CP1252',
      'kkz' => 'utf-8',
      'knk' => 'utf-8',
      'kor' => 'CP949',
      'kur' => 'CP1256',
      'kyr' => 'CP1251',
      'lao' => 'utf-8',
      'lbx' => 'CP1252',
      'lth' => 'CP1257',
      'lvi' => 'CP1257',
      'mar' => 'utf-8',
      'mki' => 'CP1251',
      'mlg' => 'utf-8',
      'mlt' => 'utf-8',
      'mng' => 'utf-8',
      'mnm' => 'utf-8',
      'mnn' => 'CP1251',
      'mon' => 'CP1251',
      'mpd' => 'CP1252',
      'mri' => 'utf-8',
      'msb' => 'CP1252',
      'msl' => 'CP1252',
      'mwk' => 'CP1252',
      'mya' => 'utf-8',
      'mym' => 'utf-8',
      'nei' => 'utf-8',
      'nep' => 'utf-8',
      'ngo' => 'utf-8',
      'nlb' => 'CP1252',
      'nld' => 'CP1252',
      'non' => 'CP1252',
      'nor' => 'CP1252',
      'nso' => 'CP1252',
      'oci' => 'CP1252',
      'ori' => 'utf-8',
      'orm' => 'utf-8',
      'pan' => 'utf-8',
      'pap' => 'CP1256',
      'pas' => 'utf-8',
      'plk' => 'CP1250',
      'prs' => 'CP1256',
      'pta' => 'CP1252',
      'ptb' => 'CP1252',
      'ptg' => 'CP1252',
      'qub' => 'CP1252',
      'que' => 'CP1252',
      'qup' => 'CP1252',
      'qut' => 'CP1252',
      'rmc' => 'CP1252',
      'rod' => 'CP1250',
      'rom' => 'CP1250',
      'rum' => 'CP1251',
      'rus' => 'CP1251',
      'sah' => 'CP1251',
      'san' => 'utf-8',
      'sin' => 'utf-8',
      'sip' => 'CP1256',
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
      'sna' => 'utf-8',
      'som' => 'utf-8',
      'sot' => 'utf-8',
      'sqi' => 'CP1250',
      'srb' => 'CP1250',
      'srl' => 'CP1250',
      'srm' => 'CP1250',
      'srn' => 'CP1251',
      'sro' => 'CP1251',
      'srp' => 'CP1250',
      'srq' => 'CP1251',
      'srs' => 'CP1250',
      'sve' => 'CP1252',
      'svf' => 'CP1252',
      'swk' => 'CP1252',
      'syr' => 'utf-8',
      'tai' => 'utf-8',
      'tam' => 'utf-8',
      'taj' => 'CP1251',
      'tel' => 'utf-8',
      'tha' => 'CP874',
      'tie' => 'utf-8',
      'tir' => 'utf-8',
      'trk' => 'CP1254',
      'tsb' => 'CP1252',
      'tsn' => 'CP1252',
      'tso' => 'utf-8',
      'ttt' => 'CP1251',
      'tuk' => 'CP1250',
      'tza' => 'CP1252',
      'tzm' => 'utf-8',
      'uig' => 'CP1256',
      'ukr' => 'CP1251',
      'urb' => 'CP1256',
      'urd' => 'CP1256',
      'uri' => 'CP1256',
      'usa' => 'CP1252',
      'uzb' => 'CP1254',
      'uzc' => 'CP1251',
      'val' => 'CP1252',
      'vit' => 'CP1258',
      'wol' => 'CP1252',
      'xho' => 'CP1252',
      'yor' => 'CP1252',
      'zhg' => 'utf-8',
      'zhh' => 'CP950',
      'zhi' => 'CP936',
      'zhm' => 'CP950',
      'zul' => 'CP1252',
      'zzz' => 'utf-8'
    );

  // These are special cases, generally we can convert from the HTTP
  // language specifier to a locale specifier without a map
  private static $lang_map_unix = array(
      'ca' => 'ca-ES',
      'cs' => 'cs-CZ',
      'da' => 'da-DK',
      'el' => 'el-GR',
      'en' => 'en-GB',
      'et' => 'et-EE',
      'eu' => 'eu-ES',
      'ja' => 'ja-JP',
      'ko' => 'ko-KR',
      'nb' => 'nb-NO',
      'nn' => 'nn-NO',
      'sh' => 'sr-RS',
      'sl' => 'sl-SI',
      'sv' => 'sv-SE',
      'zh' => 'zh-CN',
    );

  // IBM AIX locale to code set table
  // See http://publibn.boulder.ibm.com/doc_link/en_US/a_doc_lib/libs/basetrf2/setlocale.htm
  private static $aixlocale_codepage_map = array(
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

  // IBM AIX libiconv UTF-8 converters
  // See http://publibn.boulder.ibm.com/doc_link/en_US/a_doc_lib/aixprggd/genprogc/convert_prg.htm#HDRDNNRI49HOWA
  private static $aix_utf8_converters = array(
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

  // GNU iconv code set to IBM AIX libiconv code set table
  // Keys of this table should be in lowercase, and searches should be performed using lowercase!
  private static $gnu_iconv_to_aix_iconv_codepage_map = array(
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


  public static function getServerOS()
  {
    static $server_os = null;

    if (!isset($server_os))
    {
      if (stristr(PHP_OS,'Darwin'))
      {
        $server_os = 'macosx';
      }
      elseif (stristr(PHP_OS, 'WIN'))
      {
        $server_os = 'windows';
      }
      elseif (stristr(PHP_OS, 'Linux'))
      {
        $server_os = 'linux';
      }
      elseif (stristr(PHP_OS, 'BSD'))
      {
        $server_os = 'bsd';
      }
      elseif (stristr(PHP_OS, 'SunOS'))
      {
        $server_os = 'sunos';
      }
      elseif (stristr(PHP_OS, 'AIX'))
      {
        $server_os = 'aix';
      }
      else
      {
        $server_os = 'unsupported';
      }
    }

    return $server_os;
  }


  // Checks whether $langtag is advertised as being available on this system
  private static function isAdvertisedLocale($langtag)
  {
    if (!class_exists('\\ResourceBundle'))
    {
      // Could try using locale -a but that requires exec() which is not
      // available on most systems.
      // Could also use IntlCalendar::getAvailableLocales(), but that needs
      // the intl extension, just like ResourceBundle::getLocales().
      return false;
    }

    // Get the available locales
    $locales = \ResourceBundle::getLocales('');
    // Put our locale into PHP's format
    $locale = Locale::composeLocale(Locale::parseLocale($langtag));
    // See whether our locale exists.   Note that if it does we return the original
    // $langtag, which will be in BCP 47 format, rather than the locale in PHP's
    // format.  Although PHP will give you locales with underscores, when you try
    // and set them you need hyphens!
    return in_array($locale, $locales);
  }


  // Checks whether $langtag, which is in BCP 47 format, is available on this system
  public static function isAvailableLocale($langtag)
  {
    // If the OS tells us it's available, then that's enough
    if (self::isAdvertisedLocale($langtag))
    {
      return true;
    }

    // Otherwise try setting the locale
    if (self::testLocale($langtag))
    {
      return true;
    }

    // If that didn't work then we might just be running on a very old OS that does
    // things differently
    $server_os = self::getServerOS();

    // Windows systems
    if ($server_os == "windows")
    {
      $langtag_lower = utf8_strtolower($langtag);
      if (!isset(self::$lang_map_windows[$langtag_lower]))
      {
        return false;
      }
      $locale = self::$lang_map_windows[$langtag_lower];
    }
    // All of these Unix OSes work in mostly the same way...
    elseif (in_array($server_os, array('linux', 'sunos', 'bsd', 'aix', 'macosx')))
    {
      // Construct the locale name
      if (strlen($langtag) == 2)
      {
        if (isset(self::$lang_map_unix[$langtag]) && (self::$lang_map_unix[$langtag]))
        {
          $locale = self::$lang_map_unix[$langtag];
        }
        else
        {
          // Convert locale=xx to xx_XX
          $locale = utf8_strtolower($langtag) . "-" . utf8_strtoupper($langtag);
        }
      }
      else
      {
        $locale = $langtag;
      }
    }
    // Unsupported OS
    else
    {
      return false;
    }

    // Then test it
    return self::testLocale($locale);
  }


  // Add a codeset suffix to $locale
  private static function addCodeset($locale)
  {
    $server_os = self::getServerOS();

    switch ($server_os)
    {
      case 'sunos':
      case 'linux':
      case 'bsd':
        $codeset = '.UTF-8';
        break;

      case 'macosx':
        $codeset = '.utf-8';
        break;

      default:
        $codeset = '';
        break;
    }

    return $locale . $codeset;
  }


  // Returns an array of locales in the correct format for the server OS given
  // a BCP 47 language tag.  There is an array of locales to try because some
  // operating systems and versions accept locales with underscores, some with
  // hyphens and some as special codes.
  public static function getOSlocale($langtag)
  {
    $locales = self::getLocaleAlternatives($langtag);

    // Add on a codeset [is this still necessary??]
    $locales = array_map('self::addCodeset', $locales);

    return $locales;
  }


  // The inverse of getOSlocale.  Turns an OS-specific locale into a BCP 47 style
  // language tag.    This is provided for backwards compatibility with old versions
  // of MRBS where the $override_locale config setting was required to be operating
  // system specific (eg 'en_GB.utf-8' on Unix systems or 'eng' on Windows).  Now
  // $override_locale should be in BCP 47 format, but we accept old-style settings.
  public static function getBCPlocale($locale)
  {
    $result = $locale;

    // Strip anything like '.utf-8' off the end
    if (utf8_strpos($result, '.') !== false)
    {
      $result = strstr($locale, '.', true);
    }

    // Convert an old style Windows locale, eg 'eng' to a BCP 47 one, eg 'en-gb'
    if ((self::getServerOS() == 'windows') && in_array($result, self::$lang_map_windows))
    {
      $result = array_search($result, self::$lang_map_windows);
    }

    // Parse it and then recompose it.  This will get the capitalisation correct, eg
    // "sr-Latn-RS".  Note though that BCP 47 language tags are case insensitive and
    // the capitalisation is just a convention.
    $result = Locale::composeLocale(Locale::parseLocale($result));

    // Convert underscores to hyphens.  Locale::composeLocale() returns underscores.
    $result = str_replace('_', '-', $result);

    return $result;
  }


  public static function utf8ConvertFromLocale($string, $locale=null)
  {
    $server_os = self::getServerOS();

    if ($server_os == 'windows')
    {
      if (!isset($locale))
      {
        $locale = get_mrbs_locale();
      }

      $locale = utf8_strtolower($locale);

      if (isset(self::$lang_map_windows[$locale]) &&
          array_key_exists(self::$lang_map_windows[$locale], self::$winlocale_codepage_map))
      {
        $string = iconv(self::$winlocale_codepage_map[self::$lang_map_windows[$locale]], 'utf-8',
                        $string);
      }
    }
    else if ($server_os == 'aix')
    {
      $string = self::utf8ConvertAix($string, $locale);
    }
    return $string;
  }


  private static function getLocaleAlternatives($langtag)
  {
    $locales = array();

    // Put the $langtag into standard PHP format
    $subtags = Locale::parseLocale($langtag);

    if (!empty($subtags))
    {
      $locale = Locale::composeLocale($subtags);

      // First locale to try is one with hyphens instead of underscores.  These work on newer
      // Windows systems, whereas underscores do not.  Also, on Windows systems, although
      // setlocale will succeed with, for example, both 'en_GB' and 'en-GB', only 'en-GB' (and
      // indeed 'eng') will give the date in the correct format when using strftime('%x').
      $locales[] = str_replace('_', '-', $locale);

      // Next locale to try is a PHP style locale, ie with underscores
      // Make sure we haven't already got it
      if (!in_array($locale, $locales))
      {
        $locales[] = $locale;
      }

      if (self::getServerOS() == 'windows')
      {
        // Add in the three-letter code if any as a last resort
        if (isset(self::$lang_map_windows[utf8_strtolower($langtag)]))
        {
          $locales[] = self::$lang_map_windows[utf8_strtolower($langtag)];
        }
      }

      // If there isn't a region specified then add one, because on some systems
      // setlocale(LC_ALL, 'en'), for example, doesn't work, even though 'en' seems
      // to be an available locale.
      if (!isset($subtags['region']))
      {
        $subtags['region'] = self::getDefaultRegion($subtags['language']);
        if (isset($subtags['region']))  // avoid an infinite recursion
        {
          $locales = array_merge($locales, self::getLocaleAlternatives(Locale::composeLocale($subtags)));
        }
      }
    }

    return $locales;
  }


  // Returns the default region for a language
  private static function getDefaultRegion($language)
  {
    if (isset(self::$default_regions[$language]))
    {
      return self::$default_regions[$language];
    }

    return utf8_strtoupper($language);
  }


  // AIX version of utf8_convert(); needed as locales won't give us UTF-8
  // NOTE: Should ONLY be called with input encoded in the default code set of the current locale!
  // NOTE: Uses the LC_TIME category for determining the current locale setting, so should preferably be used on date/time input only!
  private static function utf8ConvertAix($string, $aix_locale = null)
  {
    if (!isset($aix_locale))
    {
      // Retrieve current locale setting
      $aix_locale = setlocale(LC_TIME, '0');
    }

    if ($aix_locale === false)
    {
      // Locale setting could not be retrieved; return string unchanged
      return $string;
    }

    if (!array_key_exists($aix_locale, self::$aixlocale_codepage_map))
    {
      // Default code page of locale could not be found; return string unchanged
      return $string;
    }

    $aix_codepage = self::$aixlocale_codepage_map[$aix_locale];

    if (!in_array($aix_codepage, self::$aix_utf8_converters, true))
    {
      // No suitable UTF-8 converter was found for this code page; return string unchanged
      return $string;
    }

    // Convert string to UTF-8
    $aix_string = iconv($aix_codepage, 'UTF-8', $string);

    // Default to original string if conversion failed
    $string = ($aix_string === false) ? $string : $aix_string;

    return $string;
  }


  // Translates a GNU libiconv character encoding name to its corresponding IBM AIX libiconv character
  // encoding name. Returns FALSE if character encoding name is unknown.
  private static function getAixCharacterEncoding($character_encoding)
  {
    // Check arguments
    if ($character_encoding == null ||
        !is_string($character_encoding) ||
        empty($character_encoding))
    {
      return false;
    }

    // Convert character encoding name to lowercase
    $character_encoding = utf8_strtolower($character_encoding);

    // Check that we know of an IBM AIX libiconv character encoding name equivalent for this character encoding name
    if (!array_key_exists($character_encoding, self::$gnu_iconv_to_aix_iconv_codepage_map))
    {
      return false;
    }

    return self::$gnu_iconv_to_aix_iconv_codepage_map[$character_encoding];
  }


  // Tests whether $langtag can be set on this system. Preserves the current locale.
  private static function testLocale($langtag)
  {
    // Save the original locales so that we can restore them later.   Note that
    // there could be different locales for different categories
    $original_locales = explode(';', setlocale(LC_ALL, 0));

    $os_locale = self::getOSLocale($langtag);

    // Try the test locale
    $result = setlocale(LC_ALL, $os_locale);

    // Restore the original settings
    foreach ($original_locales as $locale_setting)
    {
      if (utf8_strpos($locale_setting, '=') !== false)
      {
        list($category, $locale) = explode('=', $locale_setting);
        // Need to turn the string back into a constant (sometimes PHP doesn't recognise a LC_ constant,
        // so check that it has been defined first before turning it into a constant).
        $category = (defined($category)) ? constant($category) : null;
      }
      else
      {
        $category = LC_ALL;
        $locale   = $locale_setting;
      }
      if (isset($category))
      {
        setlocale($category, $locale);
      }
    }

    return ($result !== false);
  }

}
