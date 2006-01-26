<?php
# $Id$

# This file contains PHP code that specifies language specific strings
# The default strings come from lang.en, and anything in a locale
# specific file will overwrite the default. This is the Swedish file.
#
# Translated provede by: Bo Kleve (bok@unit.liu.se), MissterX
# Modified on 2006-01-04 by: Björn Wiberg <Bjorn.Wiberg@its.uu.se>
#
#
# This file is PHP code. Treat it as such.

# The charset to use in "Content-type" header
$vocab["charset"]            = "iso-8859-1";

# Used in style.inc
$vocab["mrbs"]               = "MRBS - MötesRums BokningsSystem";

# Used in functions.inc
$vocab["report"]             = "Rapport";
$vocab["admin"]              = "Admin";
$vocab["help"]               = "Hjälp";
$vocab["search"]             = "Sök:";
$vocab["not_php3"]           = "<H1>VARNING: Detta fungerar förmodligen inte med PHP3</H1>";

# Used in day.php
$vocab["bookingsfor"]        = "Bokningar för";
$vocab["bookingsforpost"]    = "";
$vocab["areas"]              = "Områden";
$vocab["daybefore"]          = "Gå till föregående dag";
$vocab["dayafter"]           = "Gå till nästa dag";
$vocab["gototoday"]          = "Gå till idag";
$vocab["goto"]               = "Gå till";
$vocab["highlight_line"]     = "Markera denna rad";
$vocab["click_to_reserve"]   = "Klicka på cellen för att göra en bokning.";

# Used in trailer.inc
$vocab["viewday"]            = "Visa dag";
$vocab["viewweek"]           = "Visa vecka";
$vocab["viewmonth"]          = "Visa månad";
$vocab["ppreview"]           = "Förhandsgranska";

# Used in edit_entry.php
$vocab["addentry"]           = "Ny bokning";
$vocab["editentry"]          = "Ändra bokningen";
$vocab["editseries"]         = "Ändra serie";
$vocab["namebooker"]         = "Kort beskrivning:";
$vocab["fulldescription"]    = "Fullständig beskrivning:";
$vocab["date"]               = "Datum:";
$vocab["start_date"]         = "Starttid:";
$vocab["end_date"]           = "Sluttid:";
$vocab["time"]               = "Tid:";
$vocab["period"]             = "Period:";
$vocab["duration"]           = "Längd:";
$vocab["seconds"]            = "sekunder";
$vocab["minutes"]            = "minuter";
$vocab["hours"]              = "timmar";
$vocab["days"]               = "dagar";
$vocab["weeks"]              = "veckor";
$vocab["years"]              = "år";
$vocab["periods"]            = "perioder";
$vocab["all_day"]            = "hela dagen";
$vocab["type"]               = "Typ:";
$vocab["internal"]           = "Internt";
$vocab["external"]           = "Externt";
$vocab["save"]               = "Spara";
$vocab["rep_type"]           = "Repetitionstyp:";
$vocab["rep_type_0"]         = "ingen";
$vocab["rep_type_1"]         = "dagligen";
$vocab["rep_type_2"]         = "varje vecka";
$vocab["rep_type_3"]         = "månatligen";
$vocab["rep_type_4"]         = "årligen";
$vocab["rep_type_5"]         = "Månadsvis, samma dag";
$vocab["rep_type_6"]         = "Veckovis";
$vocab["rep_end_date"]       = "Repetition slutdatum:";
$vocab["rep_rep_day"]        = "Repetitionsdag:";
$vocab["rep_for_weekly"]     = "(vid varje vecka)";
$vocab["rep_freq"]           = "Intervall:";
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

# Used in view_entry.php
$vocab["description"]        = "Beskrivning:";
$vocab["room"]               = "Rum";
$vocab["createdby"]          = "Skapad av:";
$vocab["lastupdate"]         = "Senast uppdaterad:";
$vocab["deleteentry"]        = "Radera bokningen";
$vocab["deleteseries"]       = "Radera serie";
$vocab["confirmdel"]         = "Är du säker att\\ndu vill radera\\nden här bokningen?\\n\\n";
$vocab["returnprev"]         = "Åter till föregående sida";
$vocab["invalid_entry_id"]   = "Ogiltigt boknings-ID!";

# Used in edit_entry_handler.php
$vocab["error"]              = "Fel";
$vocab["sched_conflict"]     = "Bokningskonflikt";
$vocab["conflict"]           = "Den nya bokningen krockar med följande bokning(ar):";
$vocab["too_may_entrys"]     = "De valda inställningarna skapar för många bokningar.<BR>V.g. använd andra inställningar!";
$vocab["returncal"]          = "Återgå till kalendervy";
$vocab["failed_to_acquire"]  = "Kunde ej få exklusiv databasåtkomst"; 
$vocab["mail_subject_entry"] = $mail["subject"];
$vocab["mail_body_new_entry"] = $mail["new_entry"];
$vocab["mail_body_del_entry"] = $mail["deleted_entry"];
$vocab["mail_body_changed_entry"] = $mail["changed_entry"];
$vocab["mail_subject_delete"] = $mail["subject_delete"];

# Authentication stuff
$vocab["accessdenied"]       = "Åtkomst nekad";
$vocab["norights"]           = "Du har inte rättighet att ändra bokningen.";
$vocab["please_login"]       = "Vänligen logga in";
$vocab["user_name"]          = "Användarnamn";
$vocab["user_password"]      = "Lösenord";
$vocab["unknown_user"]       = "Okänd användare";
$vocab["you_are"]            = "Du är";
$vocab["login"]              = "Logga in";
$vocab["logoff"]             = "Logga ut";

# Authentication database
$vocab["user_list"]          = "Användarlista";
$vocab["edit_user"]          = "Editera användare";
$vocab["delete_user"]        = "Radera denna användare";
#$vocab["user_name"]         = Use the same as above, for consistency.
#$vocab["user_password"]     = Use the same as above, for consistency.
$vocab["user_email"]         = "E-postadress";
$vocab["password_twice"]     = "Om du vill ändra ditt lösenord, vänligen mata in detta två gånger";
$vocab["passwords_not_eq"]   = "Fel: Lösenorden stämmer inte överens.";
$vocab["add_new_user"]       = "Lägg till användare";
$vocab["rights"]             = "Rättigheter";
$vocab["action"]             = "Aktion";
$vocab["user"]               = "Användare";
$vocab["administrator"]      = "Administratör";
$vocab["unknown"]            = "Okänd";
$vocab["ok"]                 = "OK";
$vocab["show_my_entries"]    = "Klicka för att visa alla dina aktuella bokningar";

# Used in search.php
$vocab["invalid_search"]     = "Tom eller ogiltig söksträng.";
$vocab["search_results"]     = "Sökresultat för:";
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

# Used in report.php
$vocab["report_on"]          = "Rapport över möten:";
$vocab["report_start"]       = "Startdatum för rapport:";
$vocab["report_end"]         = "Slutdatum för rapport:";
$vocab["match_area"]         = "Sök på plats:";
$vocab["match_room"]         = "Sök på rum:";
$vocab["match_type"]         = "Sök på bokningstyp:";
$vocab["ctrl_click_type"]    = "Håll ner tangenten <I>Ctrl</I> och klicka för att välja fler än en typ";
$vocab["match_entry"]        = "Sök på kort beskrivning:";
$vocab["match_descr"]        = "Sök på fullständig beskrivning:";
$vocab["include"]            = "Inkludera:";
$vocab["report_only"]        = "Endast rapport";
$vocab["summary_only"]       = "Endast sammanställning";
$vocab["report_and_summary"] = "Rapport och sammanställning";
$vocab["summarize_by"]       = "Sammanställ på:";
$vocab["sum_by_descrip"]     = "Kort beskrivning";
$vocab["sum_by_creator"]     = "Skapare";
$vocab["entry_found"]        = "bokning hittad";
$vocab["entries_found"]      = "bokningar hittade";
$vocab["summary_header"]     = "Sammanställning över (bokningar) timmar";
$vocab["summary_header_per"] = "Sammanställning över (bokningar) perioder";
$vocab["total"]              = "Totalt";
$vocab["submitquery"]        = "Skapa rapport";
$vocab["sort_rep"]           = "Sortera rapport efter:";
$vocab["sort_rep_time"]      = "Startdatum/starttid";
$vocab["rep_dsp"]            = "Visa i rapport:";
$vocab["rep_dsp_dur"]        = "Längd";
$vocab["rep_dsp_end"]        = "Sluttid";

# Used in week.php
$vocab["weekbefore"]         = "Föregående vecka";
$vocab["weekafter"]          = "Nästa vecka";
$vocab["gotothisweek"]       = "Denna vecka";

# Used in month.php
$vocab["monthbefore"]        = "Föregående månad";
$vocab["monthafter"]         = "Nästa månad";
$vocab["gotothismonth"]      = "Denna månad";

# Used in {day week month}.php
$vocab["no_rooms_for_area"]  = "Rum saknas för denna plats";

# Used in admin.php
$vocab["edit"]               = "Ändra";
$vocab["delete"]             = "Radera";
$vocab["rooms"]              = "Rum";
$vocab["in"]                 = "i";
$vocab["noareas"]            = "Inget område";
$vocab["addarea"]            = "Lägg till område";
$vocab["name"]               = "Namn";
$vocab["noarea"]             = "Inget område valt";
$vocab["browserlang"]        = "Din webbläsare är inställd att använda språk(en)";
$vocab["postbrowserlang"]    = "";
$vocab["addroom"]            = "Lägg till rum";
$vocab["capacity"]           = "Kapacitet";
$vocab["norooms"]            = "Inga rum.";
$vocab["administration"]     = "Administration";

# Used in edit_area_room.php
$vocab["editarea"]           = "Ändra område";
$vocab["change"]             = "Ändra";
$vocab["backadmin"]          = "Tillbaka till Administration";
$vocab["editroomarea"]       = "Ändra område eller rum";
$vocab["editroom"]           = "Ändra rum";
$vocab["update_room_failed"] = "Uppdatering av rum misslyckades: ";
$vocab["error_room"]         = "Fel: rum ";
$vocab["not_found"]          = " hittades ej";
$vocab["update_area_failed"] = "Uppdatering av område misslyckades: ";
$vocab["error_area"]         = "Fel: område";
$vocab["room_admin_email"]   = "E-postadress till rumsansvarig:";
$vocab["area_admin_email"]   = "E-postadress till områdesansvarig";
$vocab["invalid_email"]      = "Ogiltig e-postadress!";

# Used in del.php
$vocab["deletefollowing"]    = "Detta raderar följande bokningar";
$vocab["sure"]               = "Är du säker?";
$vocab["YES"]                = "JA";
$vocab["NO"]                 = "NEJ";
$vocab["delarea"]            = "Du måste ta bort alla rum i detta område innan du kan ta bort området!<p>";
$vocab["backadmin"]          = "Tillbaka till Administration";

# Used in help.php
$vocab["about_mrbs"]         = "Om MRBS";
$vocab["database"]           = "Databas: ";
$vocab["system"]             = "System: ";
$vocab["please_contact"]     = "Var vänlig kontakta ";
$vocab["for_any_questions"]  = "för eventuella frågor som ej besvaras här.";

# Used in mysql.inc AND pgsql.inc
$vocab["failed_connect_db"]  = "Fatalt fel: Kunde ej ansluta till databasen!";

?>
