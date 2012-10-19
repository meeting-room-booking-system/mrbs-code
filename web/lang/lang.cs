<?php // -*-mode: PHP; coding:utf-8;-*-
// $Id$

// This file contains PHP code that specifies language specific strings
// The default strings come from lang.en, and anything in a locale
// specific file will overwrite the default. This is a US/UK English file.
//
//
//
//
// This file is PHP code. Treat it as such.

// Used in style.inc
$vocab["mrbs"]               = "Rezervační systém";

// Used in functions.inc
$vocab["report"]             = "Výpis";
$vocab["admin"]              = "Administrátor";
$vocab["help"]               = "Nápověda";
$vocab["search"]             = "Hledat";
$vocab["not_php3"]           = "UPOZORNĚNÍ: Toto zřejmě není funkční s PHP3";
$vocab["outstanding"]        = "čekající na schválení";

// Used in day.php
$vocab["bookingsfor"]        = "Objednáno pro";
$vocab["bookingsforpost"]    = ""; // Goes after the date
$vocab["areas"]              = "Oblasti";
$vocab["daybefore"]          = "Den vzad";
$vocab["dayafter"]           = "Den vpřed";
$vocab["gototoday"]          = "Dnes";
$vocab["goto"]               = "Přejít na";
$vocab["highlight_line"]     = "Označte tuto řádku";
$vocab["click_to_reserve"]   = "Klepněte na buňku, aby jste provedli rezervaci.";
$vocab["timezone"]           = "Časová zóna";

// Used in trailer.inc
$vocab["viewday"]            = "Dny";
$vocab["viewweek"]           = "Týdny";
$vocab["viewmonth"]          = "Měsíce ";
$vocab["ppreview"]           = "Pro tisk";

// Used in edit_entry.php
$vocab["addentry"]           = "Přidat záznam";
$vocab["editentry"]          = "Editovat záznam";
$vocab["copyentry"]          = "Kopírovat záznam";
$vocab["editseries"]         = "Editovat sérii";
$vocab["copyseries"]         = "Kopírovat sérii";
$vocab["namebooker"]         = "Krátký popis";
$vocab["fulldescription"]    = "Celkový popis:<br>&nbsp;&nbsp;(Počet cestujících,<br>&nbsp;&nbsp;Obsazeno/Volná místa atd)";
$vocab["date"]               = "Datum";
$vocab["start"]              = "Začátek";
$vocab["end"]                = "Konec";
$vocab["start_date"]         = "Čas začátku";
$vocab["end_date"]           = "Čas konce";
$vocab["time"]               = "Čas";
$vocab["period"]             = "Perioda";
$vocab["duration"]           = "Doba trvání";
$vocab["second_lc"]          = "sekunda";
$vocab["seconds"]            = "sekundy";
$vocab["minute_lc"]          = "minuta";
$vocab["minutes"]            = "minuty";
$vocab["hour_lc"]            = "hodina";
$vocab["hours"]              = "hodiny";
$vocab["day_lc"]             = "den";
$vocab["days"]               = "dny";
$vocab["week_lc"]            = "týden";
$vocab["weeks"]              = "týdny";
$vocab["year_lc"]            = "rok";
$vocab["years"]              = "roky";
$vocab["period_lc"]          = "perioda";
$vocab["periods"]            = "periody";
$vocab["all_day"]            = "Celý den";
$vocab["area"]               = "Oblast";
$vocab["type"]               = "Typ";
$vocab["save"]               = "Uložit";
$vocab["rep_type"]           = "Typ opakování";
$vocab["rep_type_0"]         = "Žádný";
$vocab["rep_type_1"]         = "Denně";
$vocab["rep_type_2"]         = "Týdně";
$vocab["rep_type_3"]         = "Měsíčně";
$vocab["rep_type_4"]         = "Ročně";
$vocab["rep_end_date"]       = "Datum konce opakování";
$vocab["rep_rep_day"]        = "Den opakování";
$vocab["rep_freq"]           = "Frekvence";
$vocab["rep_num_weeks"]      = "Počet týdnů";
$vocab["ctrl_click"]         = "Použij Ctrl + kliknutí pro výběr více místností";
$vocab["entryid"]            = "ID záznamu";
$vocab["repeat_id"]          = "ID opakování"; 
$vocab["you_have_not_entered"] = "Nevložil jsi";
$vocab["brief_description"]  = "Krátký popis.";
$vocab["useful_n-weekly_value"] = "užitečná n-týdenní hodnota.";
$vocab["status"]             = "Stav";
$vocab["public"]             = "Veřejný";
$vocab["private"]            = "Soukromý";
$vocab["unavailable"]        = "Soukromý";
$vocab["is_mandatory_field"] = "je povinné pole, prosím doplň hodnotu";
$vocab["missing_mandatory_field"] = "Nevyplnil jsi povinné pole";
$vocab["confirmed"]          = "Potvrzeno";
$vocab["start_after_end"]    = "Start day after end day";
$vocab["start_after_end_long"] = "Error: the start day is after the end day";

// Used in view_entry.php
$vocab["description"]         = "Popis";
$vocab["room"]                = "Místnost/Zdroj";
$vocab["createdby"]           = "Vytvořeno";
$vocab["lastupdate"]          = "Naposled změneno";
$vocab["deleteentry"]         = "Smaž záznam";
$vocab["deleteseries"]        = "Smaž série";
$vocab["exportentry"]         = "Exportuj záznam";
$vocab["exportseries"]        = "Exportuj série";
$vocab["confirmdel"]          = "Jsi si opravdu jistý\\nsmazáním tohoto záznamu?\\n\\n";
$vocab["returnprev"]          = "Návrat na předchozí stránku";
$vocab["invalid_entry_id"]    = "Špatné ID záznamu.";
$vocab["invalid_series_id"]   = "Špatné ID série.";
$vocab["confirmation_status"] = "Stav potvrzení";
$vocab["tentative"]           = "Nezávazný";
$vocab["approval_status"]     = "Stav schválení";
$vocab["approved"]            = "Schváleno";
$vocab["awaiting_approval"]   = "Očekává schválení";
$vocab["approve"]             = "Schválit";
$vocab["reject"]              = "Odmítnout";
$vocab["more_info"]           = "Další info";
$vocab["remind_admin"]        = "Připomeň se administrátorovi";
$vocab["series"]              = "Série";
$vocab["request_more_info"]   = "Prosim uveď další informace";
$vocab["reject_reason"]       = "Prosím vlož důvod odmítnutí tohoto požadavku na rezervaci";
$vocab["send"]                = "Odeslat";
$vocab["approve_failed"]      = "Nebylo možno schválit rezervaci.";
$vocab["no_request_yet"]      = "Žádné požadavky ještě nebyly odeslány"; // Used for the title tooltip on More Info button
$vocab["last_request"]        = "Poslední požadavek odeslán";         // Used for the title tooltip on More Info button
$vocab["by"]                  = "kým";                           // Used for the title tooltip on More Info button
$vocab["sent_at"]             = "Odesláno ";
$vocab["yes"]                 = "Ano";
$vocab["no"]                  = "Ne";

// Used in edit_entry_handler.php
$vocab["error"]              = "Chyba";
$vocab["sched_conflict"]     = "Konflikt při plánování";
$vocab["conflict"]           = "Nová rezervace bude kolidovat s těmito záznamy";
$vocab["rules_broken"]       = "Nová bude kolidovat s těmito zásadami";
$vocab["too_may_entrys"]     = "Vybraná volba byla vytvořena pro jiné záznamy.<br>Prosím vyberte jinou volbu!";
$vocab["returncal"]          = "Návrat do kalendáře";
$vocab["failed_to_acquire"]  = "Chyba při zajišťování výhradního přístupu k databázi";
$vocab["invalid_booking"]    = "Neplatná rezervace";
$vocab["must_set_description"] = "Musíš uvést krátký popis rezervace. Prosím jdi zpět a vlože jej.";
$vocab["mail_subject_approved"]  = "Záznam schválen pro $mrbs_company MRBS";
$evocab["mail_subject_rejected"]  = "Záznam odmítnut pro $mrbs_company MRBS";
$vocab["mail_subject_more_info"] = "$mrbs_company MRBS: je vyžadováno více informací";
$vocab["mail_subject_reminder"]  = "Připomenutí pro $mrbs_company MRBS";
$vocab["mail_body_approved"]     = "Záznam byl schválen administrátorem; zde jsou detaily:";
$vocab["mail_body_rej_entry"]    = "Záznam byl odmítnut administrátorem, zde jsou detaily:";
$vocab["mail_body_more_info"]    = "Administrátor požaduje více informací o rezervaci; zde jsou detaily:";
$vocab["mail_body_reminder"]     = "Připomenutí - záznam čeká na schválení; zde jsou detaily:";
$vocab["mail_subject_new_entry"]     = "Záznam přidán pro $mrbs_company MRBS";
$vocab["mail_subject_changed_entry"] = "Záznam změněn pro $mrbs_company MRBS";
$vocab["mail_subject_delete"]        = "Záznam smazán pro $mrbs_company MRBS";
$vocab["mail_body_new_entry"]     = "Byl vytvořen nový záznam, zde jsou detaily:";
$vocab["mail_body_changed_entry"] = "Záznam byl změněn, zde jsou detaily:";
$vocab["mail_body_del_entry"]     = "Záznam byl smazán, zde jsou detaily";
$vocab["new_value"]           = "Nový";
$vocab["old_value"]           = "Starý";
$vocab["deleted_by"]          = "Smazáno";
$vocab["reason"]              = "Důvod";
$vocab["info_requested"]      = "Požadavek na uvedení více informací";
$vocab["min_time_before"]     = "Nejmenší interval mezi aktuálním časem a začátkem rezervace je";
$vocab["max_time_before"]     = "Největší interval mezi aktuálním časem a začátkem rezervace je";

// Used in pending.php
$vocab["pending"]            = "Rezervace čekající na schválení";
$vocab["none_outstanding"]   = "Žádné tvé rezervace nečekají na schválení.";

// Authentication stuff
$vocab["accessdenied"]       = "Přístup zamítnut";
$vocab["norights"]           = "Nemáte přístupové právo pro změnu této položky.";
$vocab["please_login"]       = "Prosím, přihlašte se";
$vocab["users.name"]          = "Jméno";
$vocab["users.password"]      = "Heslo";
$vocab["users.level"]         = "Práva";
$vocab["unknown_user"]       = "Neznámý uživatel";
$vocab["you_are"]            = "Jste";
$vocab["login"]              = "Přihlásit se";
$vocab["logoff"]             = "Odhlásit se";

// Database upgrade code
$vocab["database_login"]           = "Přihlašovací jméno pro databázi";
$vocab["upgrade_required"]         = "Je třeba provést upgrade databáze. Prosím zazálohuj databázi.";
$vocab["supply_userpass"]          = "Prosím vyplň jméno a heslo k databázi(musí mít administrátorská práva).";
$vocab["contact_admin"]            = "Jestliže nejsi administrátor rezervačního systému, prosím kontaktuj $mrbs_admin.";
$vocab["upgrade_to_version"]       = "Upgrade databáze na verzi";
$vocab["upgrade_to_local_version"] = "Upgrade databáze na lokánlní verzi";
$vocab["upgrade_completed"]        = "Upgrade databáze dokončena.";

// User access levels
$vocab["level_0"]            = "none";
$vocab["level_1"]            = "uživatel";
$vocab["level_2"]            = "administátor";
$vocab["level_3"]            = "administrátor uživatelů";

// Authentication database
$vocab["user_list"]          = "Seznam uživatelů";
$vocab["edit_user"]          = "Editovat uživatele";
$vocab["delete_user"]        = "Smazat tohoto uživatele";
//$vocab["users.name"]         = Use the same as above, for consistency.
//$vocab["users.password"]     = Use the same as above, for consistency.
$vocab["users.email"]         = "Emailová adresa";
$vocab["password_twice"]     = "Pokud chcete změnit heslo, prosím napište ho dvakrát";
$vocab["passwords_not_eq"]   = "Chyba: Vložená hesla se neshodují.";
$vocab["password_invalid"]   = "Heslo nesplňuje bezpečnostní požadavky. Musí obsahovat alespoň:";
$vocab["policy_length"]      = "znaků(s)";
$vocab["policy_alpha"]       = "písmen";
$vocab["policy_lower"]       = "malých písmen";
$vocab["policy_upper"]       = "velkých písmen";
$vocab["policy_numeric"]     = "číselných znaků";
$vocab["policy_special"]     = "speciálních znaků";
$vocab["add_new_user"]       = "Přidat nového uživatele";
$vocab["action"]             = "Akce";
$vocab["user"]               = "Uživatel";
$vocab["administrator"]      = "Administrátor";
$vocab["unknown"]            = "Neznámý";
$vocab["ok"]                 = "OK";
$vocab["show_my_entries"]    = "Klikni zde pro zobrazení všech nadcházejících záznamů";
$vocab["no_users_initial"]   = "V databázi nejsou žádní uživatelé, povol vytvoření prvního uživatele";
$vocab["no_users_create_first_admin"] = "Vytvoř uživatele pro administrátora, pak se můžeš přihlásit a vytvářet nové uživatele.";
$vocab["warning_last_admin"] = "Varování! Tohle je poslední administrátorstký účet takže jej nemůžeš smazat ani mu odebrat administrátorská práva, v opačném případě ti bude zablokován přístup do systému.";

// Used in search.php
$vocab["invalid_search"]     = "Prázdný nebo neplatný hledaný řetězec.";
$vocab["search_results"]     = "Výsledek hledání pro";
$vocab["nothing_found"]      = "Nic nenalezeno";
$vocab["records"]            = "Záznam";
$vocab["through"]            = " skrze ";
$vocab["of"]                 = " o ";
$vocab["previous"]           = "Předchozi";
$vocab["next"]               = "Další";
$vocab["entry"]              = "Záznam";
$vocab["advanced_search"]    = "Rozšířené hledání";
$vocab["search_button"]      = "Hledat";
$vocab["search_for"]         = "Hledat co";
$vocab["from"]               = "Od";

// Used in report.php
$vocab["report_on"]             = "Výpis schůzek";
$vocab["report_start"]          = "Výpis začátků";
$vocab["report_end"]            = "Výpis konců";
$vocab["match_area"]            = "Hledaná oblast";
$vocab["match_room"]            = "Hledaná místnost/zdroj";
$vocab["match_type"]            = "Hledaný typ";
$vocab["ctrl_click_type"]       = "Použij ctrl + kliknutí pro vybrání více typů";
$vocab["match_entry"]           = "Hledat v krátkém popisu";
$vocab["match_descr"]           = "Hledat v celém popisu";
$vocab["include"]               = "Zahrnovat";
$vocab["report_only"]           = "Jen výpis";
$vocab["summary_only"]          = "Jen přehled";
$vocab["report_and_summary"]    = "Výpis a přehled";
$vocab["report_as_csv"]         = "Výpis v CSV";
$vocab["summary_as_csv"]        = "Přehled v CSV";
$vocab["report_as_ical"]        = "Výpis jako iCalendar (soubor .ics) - neobsahuje periody";
$vocab["summarize_by"]          = "Přehled od";
$vocab["sum_by_descrip"]        = "Krátký popis";
$vocab["sum_by_creator"]        = "Tvůrce";
$vocab["entry_found"]           = "nalezeno";
$vocab["entries_found"]         = "nalezenond";
$vocab["summary_header"]        = "Přehled (záznamy) hodin";
$vocab["summary_header_per"]    = "Přehled (záznamy) period";
$vocab["summary_header_both"]   = "Přehled (záznamy) hodin/period";
$vocab["entries"]               = "záznamy";
$vocab["total"]                 = "Celkem";
$vocab["submitquery"]           = "Spusť výpis";
$vocab["sort_rep"]              = "Seřadit výpis dle";
$vocab["sort_rep_time"]         = "Počáteční datum/čas";
$vocab["fulldescription_short"] = "Celkový popis";
$vocab["both"]                  = "Vše";
$vocab["privacy_status"]        = "Soukromý status";
$vocab["search_criteria"]       = "Kritéria pro hledání";
$vocab["presentation_options"]  = "Možnosti výstupu";

// Used in week.php
$vocab["weekbefore"]         = "Týden dozadu";
$vocab["weekafter"]          = "Týden dopředu";
$vocab["gotothisweek"]       = "Tento týden";

// Used in month.php
$vocab["monthbefore"]        = "Měsíc dozadu";
$vocab["monthafter"]         = "Měsic dopředu";
$vocab["gotothismonth"]      = "Tento měsíc";

// Used in {day week month}.php
$vocab["no_rooms_for_area"]  = "Pro tuto místnost/zdroj není definována žadná oblast!";

// Used in admin.php
$vocab["edit"]               = "Upravit";
$vocab["delete"]             = "Smazat";
$vocab["rooms"]              = "Místnosti/Zdroje";
$vocab["in"]                 = "v";
$vocab["noareas"]            = "Žádné oblasti nebyly definovány.";
$vocab["noareas_enabled"]    = "Žádné oblasti nebyly povoleny.";
$vocab["addarea"]            = "Přidat oblast";
$vocab["name"]               = "Jméno";
$vocab["noarea"]             = "Není vybrána žádná oblast";
$vocab["browserlang"]        = "Tvůj webový prohlížeč má nastaveno následující pořadí preferování jazyků";
$vocab["addroom"]            = "Přidat místnost/zdroj";
$vocab["capacity"]           = "Kapacita";
$vocab["norooms"]            = "Žádné místnosti/zdroje nebyly definovány.";
$vocab["norooms_enabled"]    = "Žádné místnosti/zdroje nebyly povoleny.";
$vocab["administration"]     = "Detaily místnosti/zdroje";
$vocab["invalid_area_name"]  = "Toto jméno oblasti je již použito!";
$vocab["empty_name"]         = "Nevložil jsi jméno!";

// Used in edit_area_room.php
$vocab["editarea"]                = "Upravit oblast";
$vocab["change"]                  = "Změnit";
$vocab["backadmin"]               = "Zpět do místností/zdrojů";
$vocab["editroomarea"]            = "Upravit popis oblasti nebo místnosti/zdrojů";
$vocab["editroom"]                = "Upravit místnost/zdroj";
$vocab["viewroom"]                = "Zobrazit místnost/zdroj";
$vocab["update_room_failed"]      = "Úprava místnosti/zdroje skončila chybou: ";
$vocab["error_room"]              = "Chyba: místnost/zdroj ";
$vocab["not_found"]               = " nenalezeno";
$vocab["update_area_failed"]      = "Úprava oblasti skončila chybou: ";
$vocab["error_area"]              = "Chyba: oblast ";
$vocab["room_admin_email"]        = "E-mail administrátora místnosti/zdroje";
$vocab["area_admin_email"]        = "E-mail administrátora oblasti";
$vocab["area_first_slot_start"]   = "Začátek první pozice";
$vocab["area_last_slot_start"]    = "Začátek poslední pozice";
$vocab["area_res_mins"]           = "Rozlišení (minuty)";
$vocab["area_def_duration_mins"]  = "Výchozí délka trvání (minuty)";
$vocab["invalid_area"]            = "Neplatná oblast!";
$vocab["invalid_room_name"]       = "Toto jméno místnosti/zdroje již bylo použito v aktuální oblasti!";
$vocab["invalid_email"]           = "Neplatná emailová adresa!";
$vocab["invalid_resolution"]      = "Neplatná kombinace první pozice, poslední pozice a rozlišení!";
$vocab["too_many_slots"]          = 'Je třeba zvýšit hodnotu $max_slots v konfiguračním souboru!';
$vocab["general_settings"]        = "Obecné";
$vocab["time_settings"]           = "Časy pozic";
$vocab["confirmation_settings"]   = "Nastavení potvrzování";
$vocab["allow_confirmation"]      = "Povolit nezávazné rezervace";
$vocab["default_settings_conf"]   = "Výchozí nastavení";
$vocab["default_confirmed"]       = "Potvrzeno";
$vocab["default_tentative"]       = "Nezávazné";
$vocab["approval_settings"]       = "Nastavené schvalování";
$vocab["enable_approval"]         = "Je třeba schválit rezervaci";
$vocab["enable_reminders"]        = "Povolit uživatelům připomínání administrátorům";
$vocab["private_settings"]        = "Nastavení soukromí";
$vocab["allow_private"]           = "Povolit soukromé rezervace";
$vocab["force_private"]           = "Vynutit soukromé rezervace";
$vocab["default_settings"]        = "Výchozí/vynucená nastavení";
$vocab["default_private"]         = "Soukromá";
$vocab["default_public"]          = "Veřejná";
$vocab["private_display"]         = "Nastavení soukromí (zobrazení)";
$vocab["private_display_label"]   = "Jak by měly být zobrazeny soukromé rezervace?";
$vocab["private_display_caution"] = "UPOZORNĚNÍ: pořádně se rozmysli předtím než v tomto nastavení něco změníš!";
$vocab["treat_respect"]           = "Respektuj nastavení soukromí této rezervace";
$vocab["treat_private"]           = "Považuj všechny rezervace za soukromé, ignoruj jejich výchozí nastavení soukromí";
$vocab["treat_public"]            = "Považuj všechny rezervace za veřejné, ignoruj jejich výchozí nastavení soukromí";
$vocab["sort_key"]                = "Klíč pro řazení";
$vocab["sort_key_note"]           = "Toto je klíč použitý pro řazení místností/zdrojů";
$vocab["booking_policies"]        = "Zásady rezerování";
$vocab["min_book_ahead"]          = "Rozšířené rezervace - minimum";
$vocab["max_book_ahead"]          = "Rozšířené rezervace - maximum";
$vocab["custom_html"]             = "Libovolné HTML";
$vocab["custom_html_note"]        = "Toto pole může být použito pro zobrazení tvého vlastního HTML, například vložená Google mapa";
$vocab["email_list_note"]         = "Vlož seznam e-mailových adres oddelěných čárkami nebo novými řádky";
$vocab["mode"]                    = "Mód";
$vocab["mode_periods"]            = "Periody";
$vocab["mode_times"]              = "Časy";
$vocab["times_only"]              = "Times mode only";
$vocab["enabled"]                 = "Povoleno";
$vocab["disabled"]                = "Zakázáno";
$vocab["disabled_area_note"]      = "Pokud je tato oblast zakázána, nezobrazí se v pohledu na kalendář " .
                                    "a nebude možno zarezervovat místnosti v ní. Existující rezervace " .
                                    "budou zachovány a zůstanou viditelné ve výsledích hledání a ve výpisech.";
$vocab["disabled_room_note"]      = "Tato místnost je zakázána, nezobrazí se v pohledu na kalendář " .
                                    "a nebude možno ji zarezervovat. Existující rezervace " .
                                    "budou zachovány a zůstanou viditelné ve výsledích hledání a ve výpisech.";
$vocab["book_ahead_note_periods"] = "When using periods, book ahead times are rounded down to the nearest whole day.";

// Used in edit_users.php
$vocab["name_empty"]         = "Musíš vložit jméno.";
$vocab["name_not_unique"]    = "jiže existuje. Prosím vyber jiné jméno.";

// Used in del.php
$vocab["deletefollowing"]    = "Budou smazány následující rezervace";
$vocab["sure"]               = "Jsi si jistý?";
$vocab["YES"]                = "ANO";
$vocab["NO"]                 = "NE";
$vocab["delarea"]            = "Musíš smazat všechny místnosti v této oblasti před smazáním<p>";

// Used in help.php
$vocab["about_mrbs"]         = "O rezervačním systému";
$vocab["database"]           = "Databáze";
$vocab["system"]             = "Systém";
$vocab["servertime"]         = "Čas serveru";
$vocab["please_contact"]     = "Prosím kontaktuj ";
$vocab["for_any_questions"]  = "pro všechny otázky, které zde nejsou zodpovězeny.";

// Used in mysql.inc AND pgsql.inc
$vocab["failed_connect_db"]  = "Závažná chyba: Nemůžu se připojit k databázi";

// General
$vocab["fatal_db_error"]     = "Závažná chyba: databáze buhužel není momentálně dostupná.";

?>
