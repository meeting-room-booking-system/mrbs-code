<?php
// $Id$

// This file contains PHP code that specifies language specific strings
// The default strings come from lang.en, and anything in a locale
// specific file will overwrite the default. This is a Czech file.
//
// Translations provided by: "SmEjDiL" <malyl@col.cz>, 
//   "David Krotil" <David.Krotil@mu-sokolov.cz>
//
// This file is PHP code. Treat it as such.

// The charset to use in "Content-type" header
$vocab["charset"]            = "iso-8859-2";

// Used in style.inc
$vocab["mrbs"]               = "MRBS - Rezervaèní systém";

// Used in functions.inc
$vocab["report"]             = "Výpis";
$vocab["admin"]              = "Administrátor";
$vocab["help"]               = "Pomoc";
$vocab["search"]             = "Hledat";
$vocab["not_php3"]             = "<H1>UPOZORNÌNÍ: Toto zøejmì není funkèní s PHP3</H1>";

// Used in day.php
$vocab["bookingsfor"]        = "Objednáno pro";
$vocab["bookingsforpost"]    = ""; // Goes after the date
$vocab["areas"]              = "Oblasti";
$vocab["daybefore"]          = "Den vzad";
$vocab["dayafter"]           = "Den vpøed";
$vocab["gototoday"]          = "Dnes";
$vocab["goto"]               = "Pøejít na";
$vocab["highlight_line"]     = "Oznaète tuto øádku";
$vocab["click_to_reserve"]   = "Klepnìte na buòku, aby jste provedli rezervaci.";

// Used in trailer.inc
$vocab["viewday"]            = "Dny";
$vocab["viewweek"]           = "Týdny";
$vocab["viewmonth"]          = "Mìsíce ";
$vocab["ppreview"]           = "Pro tisk";

// Used in edit_entry.php
$vocab["addentry"]           = "Pøidat záznam";
$vocab["editentry"]          = "Editovat záznam";
$vocab["editseries"]         = "Editovat sérii";
$vocab["namebooker"]         = "Popis instrukce";
$vocab["fulldescription"]    = "Celkový popis:<br>&nbsp;&nbsp;(Poèet cestujících,<br>&nbsp;&nbsp;Obsazeno/Volná místa atd)";
$vocab["date"]               = "Datum";
$vocab["start_date"]         = "Zaèátek";
$vocab["end_date"]           = "Konec";
$vocab["time"]               = "Èas";
$vocab["period"]             = "Perioda";
$vocab["duration"]           = "Doba trvání";
$vocab["seconds"]            = "sekundy";
$vocab["minutes"]            = "minuty";
$vocab["hours"]              = "hodiny";
$vocab["days"]               = "dny";
$vocab["weeks"]              = "víkendy";
$vocab["years"]              = "roky";
$vocab["periods"]            = "period";
$vocab["all_day"]            = "Všechny dny";
$vocab["type"]               = "Typ";
$vocab["internal"]           = "Volná místa";
$vocab["external"]           = "Obsazeno";
$vocab["save"]               = "Uložit";
$vocab["rep_type"]           = "Typ opakování";
$vocab["rep_type_0"]         = "Nikdy";
$vocab["rep_type_1"]         = "Dennì";
$vocab["rep_type_2"]         = "Týdnì";
$vocab["rep_type_3"]         = "Mìsíènì";
$vocab["rep_type_4"]         = "Roènì";
$vocab["rep_type_5"]         = "Mìsíènì, jednou za mìsíc";
$vocab["rep_type_6"]         = "n-týdnù";
$vocab["rep_end_date"]       = "Konec opakování";
$vocab["rep_rep_day"]        = "Opakovat v den";
$vocab["rep_for_weekly"]     = "(pro (n-)týdnù)";
$vocab["rep_freq"]           = "Frekvence";
$vocab["rep_num_weeks"]      = "Èislo týdne";
$vocab["rep_for_nweekly"]    = "(pro n-týdnù)";
$vocab["ctrl_click"]         = "Užít CTRL pro výbìr více místností";
$vocab["entryid"]            = "Vstupní ID ";
$vocab["repeat_id"]          = "ID pro opakování"; 
$vocab["you_have_not_entered"] = "Nevložil jste";
$vocab["you_have_not_selected"] = "Nevybral jste";
$vocab["valid_room"]         = "prostøedek.";
$vocab["valid_time_of_day"]  = "platný èasový úsek dne.";
$vocab["brief_description"]  = "Krátký popis.";
$vocab["useful_n-weekly_value"] = "použitelná x-týdenní hodnota.";

// Used in view_entry.php
$vocab["description"]        = "Popis";
$vocab["room"]               = "Místnost";
$vocab["createdby"]          = "Vytvoøil uživatel";
$vocab["lastupdate"]         = "Poslední zmìna";
$vocab["deleteentry"]        = "Smazat záznam";
$vocab["deleteseries"]       = "Smazat sérii";
$vocab["confirmdel"]         = "Jste si jistý\\nsmazáním tohoto záznamu?\\n\\n";
$vocab["returnprev"]         = "Návrat na pøedchozí stránku";
$vocab["invalid_entry_id"]   = "Špatné ID záznamu.";
$vocab["invalid_series_id"]  = "Špatné ID skupiny.";

// Used in edit_entry_handler.php
$vocab["error"]              = "Chyba";
$vocab["sched_conflict"]     = "Konflikt pøi plánování";
$vocab["conflict"]           = "Nová rezervace je v konfliktu s jiným záznamem";
$vocab["too_may_entrys"]     = "Vybraná volba byla vytvoøena pro jiné záznamy.<br>Prosím vyberte jinou volbu!";
$vocab["returncal"]          = "Návrat do kalendáøe";
$vocab["failed_to_acquire"]  = "Chyba výhradního pøístupu do databáze"; 
$vocab["mail_subject_entry"] = $mail["subject"];
$vocab["mail_body_new_entry"] = $mail["new_entry"];
$vocab["mail_body_del_entry"] = $mail["deleted_entry"];
$vocab["mail_body_changed_entry"] = $mail["changed_entry"];
$vocab["mail_subject_delete"] = $mail["subject_delete"];

// Authentication stuff
$vocab["accessdenied"]       = "Pøístup zamítnut";
$vocab["norights"]           = "Nemáte pøístupové právo pro zmìnu této položky.";
$vocab["please_login"]       = "Prosím, pøihlašte se";
$vocab["user_name"]          = "Jméno";
$vocab["user_password"]      = "Heslo";
$vocab["unknown_user"]       = "Neznámý uživatel";
$vocab["you_are"]            = "Jste";
$vocab["login"]              = "Pøihlásit se";
$vocab["logoff"]             = "Odhlásit se";

// Authentication database
$vocab["user_list"]          = "Seznam uživatelù";
$vocab["edit_user"]          = "Editovat uživatele";
$vocab["delete_user"]        = "Smazat tohoto uživatele";
//$vocab["user_name"]         = Use the same as above, for consistency.
//$vocab["user_password"]     = Use the same as above, for consistency.
$vocab["user_email"]         = "Emailová adresa";
$vocab["password_twice"]     = "Pokud chcete zmìnit heslo, prosím napište ho dvakrát";
$vocab["passwords_not_eq"]   = "Chyba: Vložená hesla se neshodují.";
$vocab["add_new_user"]       = "Pøidat nového uživatele";
$vocab["rights"]             = "Práva";
$vocab["action"]             = "Akce";
$vocab["user"]               = "Uživatel";
$vocab["administrator"]      = "Administrátor";
$vocab["unknown"]            = "Neznámý";
$vocab["ok"]                 = "Ano";
$vocab["show_my_entries"]    = "Klepnout pro zobrazání všech nadcházejících záznamù";

// Used in search.php
$vocab["invalid_search"]     = "Prázdný nebo neplatný hledaný øetìzec.";
$vocab["search_results"]     = "Výsledek hledání pro";
$vocab["nothing_found"]      = "Nic nenalezeno";
$vocab["records"]            = "Záznam";
$vocab["through"]            = " skrze ";
$vocab["of"]                 = " o ";
$vocab["previous"]           = "Pøedchozi";
$vocab["next"]               = "Další";
$vocab["entry"]              = "Záznam";
$vocab["view"]               = "Náhled";
$vocab["advanced_search"]    = "Rozšíøené hledání";
$vocab["search_button"]      = "Hledat";
$vocab["search_for"]         = "Hledat co";
$vocab["from"]               = "Od";

// Used in report.php
$vocab["report_on"]          = "Výpis setkání";
$vocab["report_start"]       = "Výpis zaèátkù";
$vocab["report_end"]         = "Výpis koncù";
$vocab["match_area"]         = "Hledaná oblast";
$vocab["match_room"]         = "Hledaná místnost";
$vocab["match_type"]         = "Hledaný typ";
$vocab["ctrl_click_type"]    = "Užít CTRL pro výbìr více typù";
$vocab["match_entry"]        = "Hledat v popisu";
$vocab["match_descr"]        = "Hledat v celém popisu";
$vocab["include"]            = "Zahrnovat";
$vocab["report_only"]        = "Jen výpis";
$vocab["summary_only"]       = "Jen pøehled";
$vocab["report_and_summary"] = "Výpis a pøehled";
$vocab["summarize_by"]       = "Pøehled od";
$vocab["sum_by_descrip"]     = "Popis instrukce";
$vocab["sum_by_creator"]     = "Tvùrce";
$vocab["entry_found"]        = "nalezeno";
$vocab["entries_found"]      = "nalezeno";
$vocab["summary_header"]     = "Pøehled  (záznamu) hodiny";
$vocab["summary_header_per"] = "Pøehled  (záznamu) periody";
$vocab["total"]              = "Celkem";
$vocab["submitquery"]        = "Vytvoøit sestavu";
$vocab["sort_rep"]           = "Seøadit výpis podle";
$vocab["sort_rep_time"]      = "Výchozí den/èas";
$vocab["rep_dsp"]            = "Zobrazit ve výpisu";
$vocab["rep_dsp_dur"]        = "Trvání";
$vocab["rep_dsp_end"]        = "Èas ukonèení";

// Used in week.php
$vocab["weekbefore"]         = "Týden dozadu";
$vocab["weekafter"]          = "Týden dopøedu";
$vocab["gotothisweek"]       = "Tento týden";

// Used in month.php
$vocab["monthbefore"]        = "Mìsíc dozadu";
$vocab["monthafter"]         = "Mìsic dopøedu";
$vocab["gotothismonth"]      = "Tento mìsíc";

// Used in {day week month}.php
$vocab["no_rooms_for_area"]  = "Pro tuto místnost není definována žadná oblast!";

// Used in admin.php
$vocab["edit"]               = "Editovat";
$vocab["delete"]             = "Smazat";
$vocab["rooms"]              = "Místnosti";
$vocab["in"]                 = "v";
$vocab["noareas"]            = "Žádné oblasti";
$vocab["addarea"]            = "Pøidat oblast";
$vocab["name"]               = "Jméno";
$vocab["noarea"]             = "Není vybrána žádná oblast";
$vocab["browserlang"]        = "Prohlížec je nastaven k použití";
$vocab["addroom"]            = "Pøidat místnost";
$vocab["capacity"]           = "Kapacita";
$vocab["norooms"]            = "Žádná místnost.";
$vocab["administration"]     = "Administrace";

// Used in edit_area_room.php
$vocab["editarea"]           = "Editovat oblast";
$vocab["change"]             = "Zmìna";
$vocab["backadmin"]          = "Návrat do administrace";
$vocab["editroomarea"]       = "Editovat popis oblasti nebo místnosti";
$vocab["editroom"]           = "Editovat místnosti";
$vocab["update_room_failed"] = "Chyba editace místnosti: ";
$vocab["error_room"]         = "Chyba: místnost ";
$vocab["not_found"]          = " nenalezen";
$vocab["update_area_failed"] = "Chyba editace oblasti: ";
$vocab["error_area"]         = "Chyba: oblast ";
$vocab["room_admin_email"]   = "Email administrátora místnosti";
$vocab["area_admin_email"]   = "Email administrátora oblasti";
$vocab["invalid_email"]      = "Špatný email!";

// Used in del.php
$vocab["deletefollowing"]    = "Bylo smazáno rezervování";
$vocab["sure"]               = "Jste si jistý?";
$vocab["YES"]                = "ANO";
$vocab["NO"]                 = "NE";
$vocab["delarea"]            = "Musíte smazat všechny místnosti v této oblasti pøedtím než ji mùžete smazat<p>";

// Used in help.php
$vocab["about_mrbs"]         = "O MRBS";
$vocab["database"]           = "Databáze";
$vocab["system"]             = "Systém";
$vocab["please_contact"]     = "Prosím kontaktujte ";
$vocab["for_any_questions"]  = "pokud máte nìjaké další otázky.";

// Used in mysql.inc AND pgsql.inc
$vocab["failed_connect_db"]  = "Fatalní chyba: Nepodaøilo se pøipojit do databáze";

?>
