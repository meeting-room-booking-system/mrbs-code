<?php // -*-mode: PHP; coding:iso-8859-1;-*-

// $Id$

// This file contains PHP code that specifies language specific strings
// The default strings come from lang.en, and anything in a locale
// specific file will overwrite the default. This is the Swedish file.
//
// Translated provede by: Bo Kleve (bok@unit.liu.se), MissterX
// Modified on 2006-01-04 by: Björn Wiberg <Bjorn.Wiberg@its.uu.se>
// Modified on 2010-06-20 by: Kerstin Ståhlbrand <kerstin.stahlbrand@svenskakyrkan.se>
//
// This file is PHP code. Treat it as such.

// The charset to use in "Content-type" header
$vocab["charset"]            = "iso-8859-1";

// Used in style.inc
$vocab["mrbs"]               = "MRBS - MötesRumsBokningsSystem";

// Used in functions.inc
$vocab["report"]             = "Rapport";
$vocab["admin"]              = "Admin";
$vocab["help"]               = "Hjälp";
$vocab["search"]             = "Sök";
$vocab["not_php3"]           = "VARNING: Detta fungerar förmodligen inte med PHP3";
$vocab["outstanding"]        = "väntande bokningar";

// Used in day.php
$vocab["bookingsfor"]        = "Bokningar för";
$vocab["bookingsforpost"]    = "";
$vocab["areas"]              = "Områden";
$vocab["daybefore"]          = "Gå till föregående dag";
$vocab["dayafter"]           = "Gå till nästa dag";
$vocab["gototoday"]          = "Gå till idag";
$vocab["goto"]               = "Gå till";
$vocab["highlight_line"]     = "Markera denna rad";
$vocab["click_to_reserve"]   = "Klicka på cellen för att göra en bokning.";

// Used in trailer.inc
$vocab["viewday"]            = "Visa dag";
$vocab["viewweek"]           = "Visa vecka";
$vocab["viewmonth"]          = "Visa månad";
$vocab["ppreview"]           = "Förhandsgranska";

// Used in edit_entry.php
$vocab["addentry"]           = "Ny bokning";
$vocab["editentry"]          = "Ändra bokning";
$vocab["copyentry"]          = "Kopiera bokning";
$vocab["editseries"]         = "Ändra serie";
$vocab["copyseries"]         = "Kopiera serie";
$vocab["namebooker"]         = "Kort beskrivning";
$vocab["fulldescription"]    = "Fullständig beskrivning:";
$vocab["date"]               = "Datum";
$vocab["start_date"]         = "Starttid";
$vocab["end_date"]           = "Sluttid";
$vocab["time"]               = "Tid";
$vocab["period"]             = "Period";
$vocab["duration"]           = "Längd";
$vocab["seconds"]            = "sekunder";
$vocab["minutes"]            = "minuter";
$vocab["hours"]              = "timmar";
$vocab["days"]               = "dagar";
$vocab["weeks"]              = "veckor";
$vocab["years"]              = "år";
$vocab["periods"]            = "perioder";
$vocab["all_day"]            = "hela dagen";
$vocab["area"]               = "Område";
$vocab["type"]               = "Typ";
$vocab["internal"]           = "Internt";
$vocab["external"]           = "Externt";
$vocab["save"]               = "Spara";
$vocab["rep_type"]           = "Repetitionstyp";
$vocab["rep_type_0"]         = "ingen";
$vocab["rep_type_1"]         = "dagligen";
$vocab["rep_type_2"]         = "varje vecka";
$vocab["rep_type_3"]         = "månatligen";
$vocab["rep_type_4"]         = "årligen";
$vocab["rep_type_5"]         = "Månadsvis, samma dag";
$vocab["rep_type_6"]         = "Veckovis";
$vocab["rep_end_date"]       = "Repetition slutdatum";
$vocab["rep_rep_day"]        = "Repetitionsdag";
$vocab["rep_for_weekly"]     = "(vid varje vecka)";
$vocab["rep_freq"]           = "Intervall";
$vocab["rep_num_weeks"]      = "Antal veckor";
$vocab["rep_for_nweekly"]    = "(För x-veckor)";
$vocab["ctrl_click"]         = "Håll ner tangenten <I>Ctrl</I> och klicka för att välja mer än ett rum";
$vocab["entryid"]            = "Boknings-ID ";
$vocab["repeat_id"]          = "Repetions-ID "; 
$vocab["you_have_not_entered"] = "Du har inte angivit";
$vocab["you_have_not_selected"] = "Du har inte valt";
$vocab["valid_room"]         = "ett giltigt rum.";
$vocab["valid_time_of_day"]  = "en giltig tidpunkt på dagen.";
$vocab["brief_description"]  = "en kort beskrivning.";
$vocab["useful_n-weekly_value"] = "ett användbart n-veckovist värde.";
$vocab["private"]            = "Privat";
$vocab["unavailable"]        = "Privat";

// Used in view_entry.php
$vocab["description"]        = "Beskrivning";
$vocab["room"]               = "Rum";
$vocab["createdby"]          = "Skapad av";
$vocab["lastupdate"]         = "Senast uppdaterad";
$vocab["deleteentry"]        = "Radera bokningen";
$vocab["deleteseries"]       = "Radera serie";
$vocab["confirmdel"]         = "Är du säker att\\ndu vill radera\\nden här bokningen?\\n\\n";
$vocab["returnprev"]         = "Åter till föregående sida";
$vocab["invalid_entry_id"]   = "Ogiltigt boknings-ID!";
$vocab["invalid_series_id"]  = "Ogiltigt serie-ID!";
$vocab["status"]             = "Status";
$vocab["confirmed"]          = "Bekräftad bokning";
$vocab["provisional"]        = "Provisorisk bokning";
$vocab["accept"]             = "Acceptera";
$vocab["reject"]             = "Neka";
$vocab["more_info"]          = "Mer information";
$vocab["remind_admin"]       = "Påminn administratör";
$vocab["series"]             = "Serier";
$vocab["request_more_info"]  = "Tala om vilken övrig information du behöver.";
$vocab["reject_reason"]      = "Ge en anledning till varför du nekar den här bokningsförfrågan.";
$vocab["send"]               = "Skicka";
$vocab["accept_failed"]      = "Bokningen kunde inte godkännas.";


// Used in edit_entry_handler.php
$vocab["error"]              = "Fel";
$vocab["sched_conflict"]     = "Bokningskonflikt";
$vocab["conflict"]           = "Den nya bokningen krockar med följande bokning(ar)";
$vocab["rules_broken"]       = "The new booking will conflict with the following policies";
$vocab["too_may_entrys"]     = "De valda inställningarna skapar för många bokningar.<br>V.g. använd andra inställningar!";
$vocab["returncal"]          = "Återgå till kalendervy";
$vocab["failed_to_acquire"]  = "Kunde ej få exklusiv databasåtkomst"; 
$vocab["invalid_booking"]    = "Ogiltig bokning";
$vocab["must_set_description"] = "Du måste ange en kort beskrivning för bokningen. Vänligen gå tillbaka och korrigera detta.";
$vocab["mail_subject_accepted"]  = "Bokningen godkänd i $mrbs_company MRBS";
$vocab["mail_subject_rejected"]  = "Bokningen avvisad i $mrbs_company MRBS";
$vocab["mail_subject_more_info"] = "$mrbs_company MRBS: Mer information önskas";
$vocab["mail_subject_reminder"]  = "Påminnelse från $mrbs_company MRBS";
$vocab["mail_body_accepted"]     = "En bokning har blivit godkänd av administratören; här är detaljerna:";
$vocab["mail_body_rej_entry"]    = "En bokning har blivit avvisad av administratören; här är detaljerna:";
$vocab["mail_body_more_info"]    = "Administratören behöver mer information om bokningen; här är detaljerna:";
$vocab["mail_body_reminder"]     = "Påminnelse - en bokning väntar på godkännande; här är detaljerna:";
$vocab["mail_subject_entry"] = "Bokning gjort/ändrad i $mrbs_company MRBS";
$vocab["mail_body_new_entry"] = "En ny bokning är gjord; här är detaljerna:";
$vocab["mail_body_del_entry"] = "En bokning har raderats; här är detaljerna:";
$vocab["mail_body_changed_entry"] = "En bokning har ändrats; här är detaljerna:";
$vocab["mail_subject_delete"] = "Bokning raderad i $mrbs_company MRBS";
$vocab["deleted_by"]          = "Raderad av";
$vocab["reason"]              = "Anledning";
$vocab["info_requested"]      = "Information behövs";
$vocab["min_time_before"]     = "Minsta intervall mellan nu och starten för en bokning är";
$vocab["max_time_before"]     = "Största intervall mellan nu och starten för en bokning är";

// Used in pending.php
$vocab["pending"]            = "Provisorisk bokning väntar på godkännande";
$vocab["none_outstanding"]   = "Du har inga provisoriska bokningar som väntar på att godkännas.";

// Authentication stuff
$vocab["accessdenied"]       = "Åtkomst nekad";
$vocab["norights"]           = "Du har inte rättighet att ändra bokningen.";
$vocab["please_login"]       = "Vänligen logga in";
$vocab["users.name"]          = "Användarnamn";
$vocab["users.password"]      = "Lösenord";
$vocab["users.level"]         = "Rättigheter";
$vocab["unknown_user"]       = "Okänd användare";
$vocab["you_are"]            = "Du är";
$vocab["login"]              = "Logga in";
$vocab["logoff"]             = "Logga ut";

// Database upgrade code
$vocab["database_login"]           = "Databas inloggning";
$vocab["upgrade_required"]         = "Databasen behöver uppdateras.";
$vocab["supply_userpass"]          = "Ange databasens användarnamn och lösenord som har admin rättigheter.";
$vocab["contact_admin"]            = "Om du inte är MRBS administratör vänligen kontakta $mrbs_admin.";
$vocab["upgrade_to_version"]       = "Uppgradera till databas version";
$vocab["upgrade_to_local_version"] = "Uppgradera till databas lokal version";
$vocab["upgrade_completed"]        = "Databasen har uppdateras klart.";

// User access levels
$vocab["level_0"]            = "Ingen rättighet";
$vocab["level_1"]            = "Användare";
$vocab["level_2"]            = "Administratör";
$vocab["level_3"]            = "Superadministratör";

// Authentication database
$vocab["user_list"]          = "Användarlista";
$vocab["edit_user"]          = "Ändra användare";
$vocab["delete_user"]        = "Radera denna användare";
//$vocab["users.name"]         = Use the same as above, for consistency.
//$vocab["users.password"]     = Use the same as above, for consistency.
$vocab["users.email"]         = "E-postadress";
$vocab["password_twice"]     = "Om du vill ändra ditt lösenord, vänligen mata in detta två gånger";
$vocab["passwords_not_eq"]   = "Fel: Lösenorden stämmer inte överens.";
$vocab["password_invalid"]   = "Lösenordet är inte giltigt. Det måste innehålla följande:";
$vocab["policy_length"]      = "tecken";
$vocab["policy_alpha"]       = "bokstav/bokstäver";
$vocab["policy_lower"]       = "liten bokstav (gemener)";
$vocab["policy_upper"]       = "stora bokstav (VERSALER)";
$vocab["policy_numeric"]     = "siffra/siffror";
$vocab["policy_special"]     = "special tecken";
$vocab["add_new_user"]       = "Lägg till användare";
$vocab["action"]             = "Handling";
$vocab["user"]               = "Användare";
$vocab["administrator"]      = "Administratör";
$vocab["unknown"]            = "Okänd";
$vocab["ok"]                 = "OK";
$vocab["show_my_entries"]    = "Klicka för att visa alla dina aktuella bokningar";
$vocab["no_users_initial"]   = "Inga användare finns i databasen. Tillåter initialt skapande av användare.";
$vocab["no_users_create_first_admin"] = "Skapa en administrativ användare först. Därefter kan du logga in och skapa fler användare.";
$vocab["warning_last_admin"] = "Varning!  Detta är den sista administratören och du kan inte ändra rättigheterna eller ta bort denna, gör du det i alla fall har du ingen möjlighet att komma in i systemet igen.";

// Used in search.php
$vocab["invalid_search"]     = "Tom eller ogiltig söksträng.";
$vocab["search_results"]     = "Sökresultat för";
$vocab["nothing_found"]      = "Inga sökträffar hittades.";
$vocab["records"]            = "Bokning ";
$vocab["through"]            = " t.o.m. ";
$vocab["of"]                 = " av ";
$vocab["previous"]           = "Föregående";
$vocab["next"]               = "Nästa";
$vocab["entry"]              = "Bokning";
$vocab["view"]               = "Visa";
$vocab["advanced_search"]    = "Avancerad sökning";
$vocab["search_button"]      = "Sök";
$vocab["search_for"]         = "Sök för";
$vocab["from"]               = "Från";

// Used in report.php
$vocab["report_on"]          = "Rapport över möten";
$vocab["report_start"]       = "Startdatum för rapport";
$vocab["report_end"]         = "Slutdatum för rapport";
$vocab["match_area"]         = "Sök på plats";
$vocab["match_room"]         = "Sök på rum";
$vocab["match_type"]         = "Sök på bokningstyp";
$vocab["ctrl_click_type"]    = "Håll ner tangenten <I>Ctrl</I> och klicka för att välja fler än en typ";
$vocab["match_entry"]        = "Sök på kort beskrivning";
$vocab["match_descr"]        = "Sök på fullständig beskrivning";
$vocab["include"]            = "Inkludera";
$vocab["report_only"]        = "Endast rapport";
$vocab["summary_only"]       = "Endast sammanställning";
$vocab["report_and_summary"] = "Rapport och sammanställning";
$vocab["report_as_csv"]         = "Rapport som CSV";
$vocab["summary_as_csv"]        = "Sammanställning som CSV";
$vocab["summarize_by"]       = "Sammanställ på";
$vocab["sum_by_descrip"]     = "Kort beskrivning";
$vocab["sum_by_creator"]     = "Skapare";
$vocab["entry_found"]        = "bokning hittad";
$vocab["entries_found"]      = "bokningar hittade";
$vocab["summary_header"]     = "Sammanställning över (bokningar) timmar";
$vocab["summary_header_per"] = "Sammanställning över (bokningar) perioder";
$vocab["entries"]               = "bokningar";
$vocab["total"]              = "Totalt";
$vocab["submitquery"]        = "Skapa rapport";
$vocab["sort_rep"]           = "Sortera rapport efter";
$vocab["sort_rep_time"]      = "Startdatum/starttid";
$vocab["rep_dsp"]            = "Visa i rapport";
$vocab["rep_dsp_dur"]        = "Längd";
$vocab["rep_dsp_end"]        = "Sluttid";
$vocab["fulldescription_short"] = "Fullständig beskrivning";

// Used in week.php
$vocab["weekbefore"]         = "Föregående vecka";
$vocab["weekafter"]          = "Nästa vecka";
$vocab["gotothisweek"]       = "Denna vecka";

// Used in month.php
$vocab["monthbefore"]        = "Föregående månad";
$vocab["monthafter"]         = "Nästa månad";
$vocab["gotothismonth"]      = "Denna månad";

// Used in {day week month}.php
$vocab["no_rooms_for_area"]  = "Rum saknas för denna plats";

// Used in admin.php
$vocab["edit"]               = "Ändra";
$vocab["delete"]             = "Radera";
$vocab["rooms"]              = "Rum";
$vocab["in"]                 = "i";
$vocab["noareas"]            = "Inget område";
$vocab["addarea"]            = "Lägg till område";
$vocab["name"]               = "Namn";
$vocab["noarea"]             = "Inget område valt";
$vocab["browserlang"]        = "Din webbläsare är inställd att använda språk(en)";
$vocab["addroom"]            = "Lägg till rum";
$vocab["capacity"]           = "Kapacitet";
$vocab["norooms"]            = "Inga rum.";
$vocab["administration"]     = "Administration";
$vocab["invalid_area_name"]  = "Detta områdes namn finns redan.";

// Used in edit_area_room.php
$vocab["editarea"]                = "Ändra område";
$vocab["change"]                  = "Ändra";
$vocab["backadmin"]               = "Tillbaka till Administration";
$vocab["editroomarea"]            = "Ändra område eller rum";
$vocab["editroom"]                = "Ändra rum";
$vocab["viewroom"]                = "Visa rum";
$vocab["update_room_failed"]      = "Uppdatering av rum misslyckades: ";
$vocab["error_room"]              = "Fel: rum ";
$vocab["not_found"]               = " hittades ej";
$vocab["update_area_failed"]      = "Uppdatering av område misslyckades: ";
$vocab["error_area"]              = "Fel: område";
$vocab["room_admin_email"]        = "E-postadress till rumsansvarig";
$vocab["area_admin_email"]        = "E-postadress till områdesansvarig";
$vocab["area_first_slot_start"]   = "Start för första tid";
$vocab["area_last_slot_start"]    = "Start för sista tid";
$vocab["area_res_mins"]           = "Tidsintervall (i minuter)";
$vocab["area_def_duration_mins"]  = "Gällande intervall (i minuter)";
$vocab["invalid_area"]            = "Ogiltigt område!";
$vocab["invalid_room_name"]       = "Det här rums namnet används redan i det här området!";
$vocab["invalid_email"]           = "Ogiltig e-postadress!";
$vocab["invalid_resolution"]      = "Ogiltig kombination av första tid, sista tid och tidsintervall!";
$vocab["too_many_slots"]          = 'Du behöver öka värdet för $max_slots i config filen!';
$vocab["general_settings"]        = "Allmän";
$vocab["time_settings"]           = "Tidsintervaller";
$vocab["provisional_settings"]    = "Provisoriska bokningar";
$vocab["enable_provisional"]      = "Tillåt provisoriska bokningar";
$vocab["enable_reminders"]        = "Tillåt användare att påminna administratören.";
$vocab["private_settings"]        = "Privata bokningar";
$vocab["allow_private"]           = "Tillåt privata bokningar";
$vocab["force_private"]           = "Bestäm privata bokningar";
$vocab["default_settings"]        = "Gällande/bestämda inställningar";
$vocab["default_private"]         = "Privat";
$vocab["default_public"]          = "Öppen";
$vocab["private_display"]         = "Privat bokning (visa)";
$vocab["private_display_label"]   = "Hur ska privata bokningar visas?";
$vocab["private_display_caution"] = "Varning: tänk efter vilka konsekvenser detta kan få innan du ändrar dessa inställningar!";
$vocab["treat_respect"]           = "Respektera att detta är en privat bokning";
$vocab["treat_private"]           = "Behandla alla bokningar som privata, ignorera den individuella inställningen.";
$vocab["treat_public"]            = "Behandla alla bokningar som öppna, ignorera den individuella inställningen.";
$vocab["sort_key"]                = "Sorterings nyckel";
$vocab["sort_key_note"]           = "Det här är nyckeln för att sortera rum i bokstavsordning.";
$vocab["booking_policies"]        = "Boknings policy";
$vocab["min_book_ahead"]          = "Bokning i förväg - minimum";
$vocab["max_book_ahead"]          = "Bokning i förväg - maximum";
$vocab["custom_html"]             = "Vanlig visning (HTML)";
$vocab["custom_html_note"]        = "Det här fältet kan användas för att visa din egen HTML, till exempel en inbäddad Google karta";
 
// Used in edit_users.php
$vocab["name_empty"]         = "Du måste ange ett namn.";
$vocab["name_not_unique"]    = "finns redan.   Ange ett annat namn.";

// Used in del.php
$vocab["deletefollowing"]    = "Detta raderar följande bokningar";
$vocab["sure"]               = "Är du säker?";
$vocab["YES"]                = "JA";
$vocab["NO"]                 = "NEJ";
$vocab["delarea"]            = "Du måste ta bort alla rum i detta område innan du kan ta bort området!<p>";

// Used in help.php
$vocab["about_mrbs"]         = "Om MRBS";
$vocab["database"]           = "Databas";
$vocab["system"]             = "System";
$vocab["servertime"]         = "Server tid";
$vocab["please_contact"]     = "Var vänlig kontakta ";
$vocab["for_any_questions"]  = "för eventuella frågor som ej besvaras här.";

// Used in mysql.inc AND pgsql.inc
$vocab["failed_connect_db"]  = "Fatalt fel: Kunde ej ansluta till databasen!";

?>
